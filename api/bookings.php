<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

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

// POST — inscribir estudiante (regular, prueba o premio)
// Acepta student_id (estudiante registrado) O guest_name (invitado en prueba/premio)
if ($method === 'POST') {
    $slotId    = (int)($input['slot_id']    ?? 0);
    $studentId = !empty($input['student_id']) ? (int)$input['student_id'] : null;
    $guestName = trim($input['guest_name']  ?? '');
    $isTrial   = !empty($input['is_trial']) ? 1 : 0;
    $isAward   = !empty($input['is_award']) ? 1 : 0;
    $trialDate = $isTrial ? ($input['trial_date'] ?? null) : null;
    $awardDate = $isAward ? ($input['award_date'] ?? null) : null;

    if (!$slotId) {
        jsonResponse(['ok' => false, 'error' => 'slot_id es requerido'], 400);
    }

    // For regular enrollments a real student is mandatory
    if (!$isTrial && !$isAward && !$studentId) {
        jsonResponse(['ok' => false, 'error' => 'student_id es requerido para inscripciones regulares'], 400);
    }

    // For trial/award: need either a registered student OR a guest name
    if (($isTrial || $isAward) && !$studentId && !$guestName) {
        jsonResponse(['ok' => false, 'error' => 'Ingresa el nombre del invitado o selecciona un estudiante'], 400);
    }

    if ($isTrial && !$trialDate) {
        jsonResponse(['ok' => false, 'error' => 'Para clase de prueba debes indicar la fecha'], 400);
    }
    if ($isAward && !$awardDate) {
        jsonResponse(['ok' => false, 'error' => 'Para clase premio debes indicar la fecha'], 400);
    }

    // Check slot status — only active groups accept enrollments
    $slotRow = $db->prepare("SELECT slot_status, max_students, class_name FROM time_slots WHERE id = ? AND active = 1");
    $slotRow->execute([$slotId]);
    $slot = $slotRow->fetch();
    if (!$slot) jsonResponse(['ok' => false, 'error' => 'Horario no encontrado'], 404);
    if ($slot['slot_status'] !== 'active') {
        jsonResponse(['ok' => false, 'error' => 'Este grupo está cerrado o pendiente de activar'], 409);
    }

    // Capacity check
    $dateForCap = $trialDate ?? $awardDate ?? '0000-00-00';
    $capStmt = $db->prepare("
        SELECT COUNT(*) FROM enrollments
        WHERE time_slot_id = ? AND status = 'active'
          AND (
            (is_trial = 0 AND is_award = 0)
            OR (is_trial = 1 AND trial_date = ?)
            OR (is_award = 1 AND award_date = ?)
          )
    ");
    $capStmt->execute([$slotId, $dateForCap, $dateForCap]);
    if ((int)$capStmt->fetchColumn() >= $slot['max_students']) {
        jsonResponse(['ok' => false, 'error' => 'Este grupo ya está lleno (' . $slot['max_students'] . '/' . $slot['max_students'] . ' cupos)'], 409);
    }

    // Duplicate check for regular enrollments only
    if (!$isTrial && !$isAward && $studentId) {
        $dup = $db->prepare("SELECT id FROM enrollments WHERE time_slot_id=? AND student_id=? AND status='active' AND is_trial=0 AND is_award=0");
        $dup->execute([$slotId, $studentId]);
        if ($dup->fetch()) {
            jsonResponse(['ok' => false, 'error' => 'Este estudiante ya está inscrito en este grupo'], 409);
        }
    }

    // For guest trial/award: no student_id, store guest_name only
    $finalStudentId = ($isTrial || $isAward) && $guestName && !$studentId ? null : $studentId;
    $finalGuestName = ($isTrial || $isAward) && $guestName ? $guestName : null;

    $ins = $db->prepare("
        INSERT INTO enrollments (time_slot_id, student_id, guest_name, enrolled_date, is_trial, trial_date, is_award, award_date, notes)
        VALUES (?, ?, ?, CURRENT_DATE, ?, ?, ?, ?, ?)
    ");
    $ins->execute([$slotId, $finalStudentId, $finalGuestName, $isTrial, $trialDate, $isAward, $awardDate, $input['notes'] ?? '']);

    $bookingId  = (int)$db->lastInsertId();
    $displayName = $finalGuestName ?? ($studentId ? (function() use ($db, $studentId) {
        $s = $db->prepare("SELECT name FROM students WHERE id = ?");
        $s->execute([$studentId]);
        return $s->fetchColumn() ?: 'Estudiante';
    })() : 'Invitado');

    $typeLabel = $isTrial ? 'prueba' : ($isAward ? 'premio' : 'regular');
    logAudit('enroll', 'enrollment', $bookingId,
        "Inscripción {$typeLabel}: {$displayName} → grupo {$slotId} ({$slot['class_name']})");

    jsonResponse(['ok' => true, 'booking_id' => $bookingId]);
}

// DELETE — quitar estudiante del grupo (no elimina de la BD)
if ($method === 'DELETE') {
    $enrollId = (int)($input['booking_id'] ?? 0);
    if (!$enrollId) jsonResponse(['ok' => false, 'error' => 'booking_id requerido'], 400);

    // Fetch for audit log
    $row = $db->prepare("
        SELECT e.*, s.name AS student_name
        FROM enrollments e
        LEFT JOIN students s ON s.id = e.student_id
        WHERE e.id = ?
    ");
    $row->execute([$enrollId]);
    $enroll = $row->fetch();

    $db->prepare("UPDATE enrollments SET status = 'inactive' WHERE id = ?")->execute([$enrollId]);

    if ($enroll) {
        $name = $enroll['guest_name'] ?: ($enroll['student_name'] ?? 'Estudiante');
        logAudit('unenroll', 'enrollment', $enrollId,
            "Retiro del grupo: {$name} → slot {$enroll['time_slot_id']}");
    }

    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
