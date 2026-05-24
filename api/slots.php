<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

// GET  /api/slots.php?professor_id=1
// POST /api/slots.php  { professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students }
// PUT  /api/slots.php  { slot_id, ... fields to update }
// DELETE /api/slots.php { slot_id }

if ($method === 'GET') {
    $profId = isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : null;
    if ($profId) {
        $stmt = $db->prepare("SELECT ts.*, p.name AS professor_name, p.color_hex FROM time_slots ts JOIN professors p ON p.id = ts.professor_id WHERE ts.professor_id = ? AND ts.active = 1 ORDER BY ts.day_of_week, ts.start_time");
        $stmt->execute([$profId]);
    } else {
        $stmt = $db->query("SELECT ts.*, p.name AS professor_name, p.color_hex FROM time_slots ts JOIN professors p ON p.id = ts.professor_id WHERE ts.active = 1 ORDER BY ts.day_of_week, ts.start_time, p.name");
    }
    jsonResponse(['ok' => true, 'slots' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $required = ['professor_id', 'day_of_week', 'start_time', 'end_time'];
    foreach ($required as $f) {
        if (empty($input[$f])) jsonResponse(['ok' => false, 'error' => "$f is required"], 400);
    }
    $stmt = $db->prepare("
        INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        (int)$input['professor_id'],
        (int)$input['day_of_week'],
        $input['start_time'],
        $input['end_time'],
        $input['class_name']   ?? '',
        $input['class_type']   ?? '',
        (int)($input['max_students'] ?? 4),
        $input['notes']        ?? '',
    ]);
    jsonResponse(['ok' => true, 'slot_id' => (int)$db->lastInsertId()]);
}

if ($method === 'PUT') {
    $slotId = (int)($input['slot_id'] ?? 0);
    if (!$slotId) jsonResponse(['ok' => false, 'error' => 'slot_id required'], 400);
    $stmt = $db->prepare("
        UPDATE time_slots SET
            professor_id = ?, day_of_week = ?, start_time = ?, end_time = ?,
            class_name = ?, class_type = ?, max_students = ?, notes = ?
        WHERE id = ?
    ");
    $stmt->execute([
        (int)$input['professor_id'],
        (int)$input['day_of_week'],
        $input['start_time'],
        $input['end_time'],
        $input['class_name']   ?? '',
        $input['class_type']   ?? '',
        (int)($input['max_students'] ?? 4),
        $input['notes']        ?? '',
        $slotId,
    ]);
    jsonResponse(['ok' => true]);
}

if ($method === 'DELETE') {
    $slotId = (int)($input['slot_id'] ?? 0);
    if (!$slotId) jsonResponse(['ok' => false, 'error' => 'slot_id required'], 400);
    $db->prepare("UPDATE time_slots SET active = 0 WHERE id = ?")->execute([$slotId]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
