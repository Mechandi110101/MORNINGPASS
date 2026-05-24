<?php
// Enrollment-based API (permanent recurring assignments)
// Keeps the same response shape so the JS doesn't need to change.
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$method    = $_SERVER['REQUEST_METHOD'];
$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$db        = getDB();
$programId = (int)($_GET['p'] ?? $input['program_id'] ?? 1);

// GET  ?week=YYYY-MM-DD&p=N  → schedule with enrolled students
if ($method === 'GET') {
    $week     = $_GET['week'] ?? getWeekStart();
    $schedule = getScheduleForWeek($week, $programId);
    jsonResponse(['ok' => true, 'schedule' => $schedule, 'week_start' => $week]);
}

// POST { slot_id, student_id, program_id? }  → create permanent enrollment
if ($method === 'POST') {
    $slotId    = (int)($input['slot_id']    ?? 0);
    $studentId = (int)($input['student_id'] ?? 0);

    if (!$slotId || !$studentId) {
        jsonResponse(['ok' => false, 'error' => 'slot_id y student_id son requeridos'], 400);
    }

    // Capacity check
    $capStmt = $db->prepare("
        SELECT ts.max_students,
               COUNT(e.id) AS enrolled
        FROM time_slots ts
        LEFT JOIN enrollments e ON e.time_slot_id = ts.id AND e.status = 'active'
        WHERE ts.id = ?
        GROUP BY ts.id
    ");
    $capStmt->execute([$slotId]);
    $row = $capStmt->fetch();
    if (!$row) jsonResponse(['ok' => false, 'error' => 'Horario no encontrado'], 404);

    if ($row['enrolled'] >= $row['max_students']) {
        jsonResponse([
            'ok'    => false,
            'error' => 'Este grupo ya está lleno (' . $row['max_students'] . '/' . $row['max_students'] . ' cupos)'
        ], 409);
    }

    // Duplicate check
    $dup = $db->prepare("SELECT id FROM enrollments WHERE time_slot_id=? AND student_id=? AND status='active'");
    $dup->execute([$slotId, $studentId]);
    if ($dup->fetch()) {
        jsonResponse(['ok' => false, 'error' => 'Este estudiante ya está inscrito en este grupo'], 409);
    }

    $ins = $db->prepare("
        INSERT INTO enrollments (time_slot_id, student_id, enrolled_date, notes)
        VALUES (?, ?, CURRENT_DATE, ?)
        ON DUPLICATE KEY UPDATE status = 'active', notes = VALUES(notes)
    ");
    $ins->execute([$slotId, $studentId, $input['notes'] ?? '']);
    jsonResponse(['ok' => true, 'booking_id' => (int)$db->lastInsertId()]);
}

// DELETE { booking_id }  → remove enrollment
if ($method === 'DELETE') {
    $enrollId = (int)($input['booking_id'] ?? 0);
    if (!$enrollId) jsonResponse(['ok' => false, 'error' => 'booking_id requerido'], 400);
    $db->prepare("UPDATE enrollments SET status = 'inactive' WHERE id = ?")->execute([$enrollId]);
    jsonResponse(['ok' => true]);
}

// PATCH { booking_id, notes }  → update notes
if ($method === 'PATCH') {
    $enrollId = (int)($input['booking_id'] ?? 0);
    $notes    = trim($input['notes'] ?? '');
    if (!$enrollId) jsonResponse(['ok' => false, 'error' => 'booking_id requerido'], 400);
    $db->prepare("UPDATE enrollments SET notes = ? WHERE id = ?")->execute([$notes, $enrollId]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
