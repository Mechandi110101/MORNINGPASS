<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

if ($method === 'GET') {
    $profId    = isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : null;
    $programId = isset($_GET['p'])            ? (int)$_GET['p']            : null;

    $where  = ['ts.active = 1'];
    $params = [];
    if ($profId)    { $where[] = 'ts.professor_id = ?'; $params[] = $profId;    }
    if ($programId) { $where[] = 'ts.program_id   = ?'; $params[] = $programId; }

    $sql  = "SELECT ts.*, p.name AS professor_name, p.color_hex,
                    prog.name AS program_name, prog.color_hex AS program_color
             FROM time_slots ts
             JOIN professors p    ON p.id    = ts.professor_id
             JOIN programs   prog ON prog.id = ts.program_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ts.program_id, ts.day_of_week, ts.start_time, p.name";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['ok' => true, 'slots' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    foreach (['professor_id', 'day_of_week', 'start_time', 'end_time'] as $f) {
        if (empty($input[$f])) jsonResponse(['ok' => false, 'error' => "$f es requerido"], 400);
    }
    $stmt = $db->prepare("
        INSERT INTO time_slots
            (program_id, professor_id, day_of_week, start_time, end_time,
             class_name, class_type, max_students, notes, start_date, end_date, slot_status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        (int)($input['program_id']    ?? 1),
        (int)$input['professor_id'],
        (int)$input['day_of_week'],
        $input['start_time'],
        $input['end_time'],
        $input['class_name']    ?? '',
        $input['class_type']    ?? '',
        (int)($input['max_students']  ?? 4),
        $input['notes']         ?? '',
        $input['start_date']    ?: null,
        $input['end_date']      ?: null,
        $input['slot_status']   ?? 'active',
    ]);
    $slotId = (int)$db->lastInsertId();
    logAudit('create_slot', 'time_slot', $slotId,
        "Grupo creado: {$input['class_name']} día {$input['day_of_week']} {$input['start_time']}");
    jsonResponse(['ok' => true, 'slot_id' => $slotId]);
}

if ($method === 'PUT') {
    $slotId = (int)($input['slot_id'] ?? 0);
    if (!$slotId) jsonResponse(['ok' => false, 'error' => 'slot_id requerido'], 400);

    // Special action: activate / close group
    if (isset($input['action'])) {
        $newStatus = $input['action'] === 'activate' ? 'active' : 'closed';
        $db->prepare("UPDATE time_slots SET slot_status = ? WHERE id = ?")->execute([$newStatus, $slotId]);
        logAudit('update_slot_status', 'time_slot', $slotId, "Estado grupo: {$newStatus}");
        jsonResponse(['ok' => true, 'slot_status' => $newStatus]);
    }

    // Full edit
    $stmt = $db->prepare("
        UPDATE time_slots SET
            program_id   = ?, professor_id = ?, day_of_week  = ?,
            start_time   = ?, end_time     = ?, class_name   = ?,
            class_type   = ?, max_students = ?, notes        = ?,
            start_date   = ?, end_date     = ?, slot_status  = ?
        WHERE id = ?
    ");
    $stmt->execute([
        (int)($input['program_id']    ?? 1),
        (int)$input['professor_id'],
        (int)$input['day_of_week'],
        $input['start_time'],
        $input['end_time'],
        $input['class_name']    ?? '',
        $input['class_type']    ?? '',
        (int)($input['max_students']  ?? 4),
        $input['notes']         ?? '',
        $input['start_date']    ?: null,
        $input['end_date']      ?: null,
        $input['slot_status']   ?? 'active',
        $slotId,
    ]);
    logAudit('edit_slot', 'time_slot', $slotId,
        "Grupo editado: {$input['class_name']} día {$input['day_of_week']} {$input['start_time']}");
    jsonResponse(['ok' => true]);
}

if ($method === 'DELETE') {
    $slotId = (int)($input['slot_id'] ?? 0);
    if (!$slotId) jsonResponse(['ok' => false, 'error' => 'slot_id requerido'], 400);

    $slotInfo = $db->prepare("SELECT class_name FROM time_slots WHERE id = ?")->execute([$slotId]);
    $s = $db->prepare("SELECT class_name FROM time_slots WHERE id = ?");
    $s->execute([$slotId]);
    $info = $s->fetch();

    $db->prepare("UPDATE time_slots SET active = 0 WHERE id = ?")->execute([$slotId]);
    logAudit('delete_slot', 'time_slot', $slotId,
        "Grupo eliminado: " . ($info['class_name'] ?? $slotId));
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
