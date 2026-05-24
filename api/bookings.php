<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$method    = $_SERVER['REQUEST_METHOD'];
$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$db        = getDB();
$programId = (int)($_GET['p'] ?? $input['program_id'] ?? 1);

// GET  ?week=YYYY-MM-DD&p=N
if ($method === 'GET') {
    $week     = $_GET['week'] ?? getWeekStart();
    $schedule = getScheduleForWeek($week, $programId);
    jsonResponse(['ok' => true, 'schedule' => $schedule, 'week_start' => $week]);
}

// POST { slot_id, student_id, program_id?, is_trial?, trial_date? }
if ($method === 'POST') {
    $slotId    = (int)($input['slot_id']    ?? 0);
    $studentId = (int)($input['student_id'] ?? 0);
    $isTrial   = !empty($input['is_trial']) ? 1 : 0;
    $trialDate = $isTrial ? ($input['trial_date'] ?? null) : null;

    if (!$slotId || !$studentId) {
        jsonResponse(['ok' => false, 'error' => 'slot_id y student_id son requeridos'], 400);
    }
    if ($isTrial && !$trialDate) {
        jsonResponse(['ok' => false, 'error' => 'Para clase de prueba debes indicar la fecha'], 400);
    }

    // Check slot status — only active groups accept enrollments
    $slotRow = $db->prepare("SELECT slot_status, max_students FROM time_slots WHERE id = ? AND active = 1");
    $slotRow->execute([$slotId]);
    $slot = $slotRow->fetch();
    if (!$slot) jsonResponse(['ok' => false, 'error' => 'Horario no encontrado'], 404);
    if ($slot['slot_status'] !== 'active') {
        jsonResponse(['ok' => false, 'error' => 'Este grupo está cerrado o pendiente de activar'], 409);
    }

    // Capacity check (permanent enrollments + trials for same date)
    $capStmt = $db->prepare("
        SELECT COUNT(*) FROM enrollments
        WHERE time_slot_id = ? AND status = 'active'
          AND (is_trial = 0 OR (is_trial = 1 AND trial_date = ?))
    ");
    $capStmt->execute([$slotId, $trialDate ?? '0000-00-00']);
    if ((int)$capStmt->fetchColumn() >= $slot['max_students']) {
        jsonResponse(['ok' => false, 'error' => 'Este grupo ya está lleno (' . $slot['max_students'] . '/' . $slot['max_students'] . ' cupos)'], 409);
    }

    // Duplicate check (same student, same slot, non-trial)
    if (!$isTrial) {
        $dup = $db->prepare("SELECT id FROM enrollments WHERE time_slot_id=? AND student_id=? AND status='active' AND is_trial=0");
        $dup->execute([$slotId, $studentId]);
        if ($dup->fetch()) {
            jsonResponse(['ok' => false, 'error' => 'Este estudiante ya está inscrito en este grupo'], 409);
        }
    }

    $ins = $db->prepare("
        INSERT INTO enrollments (time_slot_id, student_id, enrolled_date, is_trial, trial_date, notes)
        VALUES (?, ?, CURRENT_DATE, ?, ?, ?)
    ");
    $ins->execute([$slotId, $studentId, $isTrial, $trialDate, $input['notes'] ?? '']);
    jsonResponse(['ok' => true, 'booking_id' => (int)$db->lastInsertId()]);
}

// DELETE { booking_id }
if ($method === 'DELETE') {
    $enrollId = (int)($input['booking_id'] ?? 0);
    if (!$enrollId) jsonResponse(['ok' => false, 'error' => 'booking_id requerido'], 400);
    $db->prepare("UPDATE enrollments SET status = 'inactive' WHERE id = ?")->execute([$enrollId]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
