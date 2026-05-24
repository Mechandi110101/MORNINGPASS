<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// GET  /api/bookings.php?week=2025-05-19
// POST /api/bookings.php  { slot_id, student_id, week_start, notes }
// DELETE /api/bookings.php { booking_id }

$db = getDB();

if ($method === 'GET') {
    $week = $_GET['week'] ?? getWeekStart();
    $schedule = getScheduleForWeek($week);
    jsonResponse(['ok' => true, 'schedule' => $schedule, 'week_start' => $week]);
}

if ($method === 'POST') {
    $slotId    = (int)($input['slot_id']    ?? 0);
    $studentId = (int)($input['student_id'] ?? 0);
    $weekStart = $input['week_start'] ?? getWeekStart();
    $notes     = trim($input['notes'] ?? '');

    if (!$slotId || !$studentId) {
        jsonResponse(['ok' => false, 'error' => 'slot_id and student_id are required'], 400);
    }

    // Check capacity
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM bookings
        WHERE time_slot_id = ? AND week_start = ? AND status != 'cancelled'
    ");
    $stmt->execute([$slotId, $weekStart]);
    $count = (int)$stmt->fetchColumn();

    $maxStmt = $db->prepare("SELECT max_students FROM time_slots WHERE id = ?");
    $maxStmt->execute([$slotId]);
    $max = (int)$maxStmt->fetchColumn();

    if ($count >= $max) {
        jsonResponse(['ok' => false, 'error' => 'Este horario ya está lleno (' . $max . '/' . $max . ' estudiantes)'], 409);
    }

    // Check duplicate
    $dupStmt = $db->prepare("
        SELECT id FROM bookings
        WHERE time_slot_id = ? AND student_id = ? AND week_start = ? AND status != 'cancelled'
    ");
    $dupStmt->execute([$slotId, $studentId, $weekStart]);
    if ($dupStmt->fetch()) {
        jsonResponse(['ok' => false, 'error' => 'Este estudiante ya está reservado en este horario'], 409);
    }

    $ins = $db->prepare("
        INSERT INTO bookings (time_slot_id, student_id, week_start, notes)
        VALUES (?, ?, ?, ?)
    ");
    $ins->execute([$slotId, $studentId, $weekStart, $notes]);
    jsonResponse(['ok' => true, 'booking_id' => (int)$db->lastInsertId()]);
}

if ($method === 'DELETE') {
    $bookingId = (int)($input['booking_id'] ?? 0);
    if (!$bookingId) {
        jsonResponse(['ok' => false, 'error' => 'booking_id is required'], 400);
    }
    $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$bookingId]);
    jsonResponse(['ok' => true]);
}

if ($method === 'PATCH') {
    // Update notes on a booking
    $bookingId = (int)($input['booking_id'] ?? 0);
    $notes     = trim($input['notes'] ?? '');
    if (!$bookingId) {
        jsonResponse(['ok' => false, 'error' => 'booking_id is required'], 400);
    }
    $stmt = $db->prepare("UPDATE bookings SET notes = ? WHERE id = ?");
    $stmt->execute([$notes, $bookingId]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
