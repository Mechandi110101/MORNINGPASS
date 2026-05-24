<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

if ($method === 'GET') {
    $search    = trim($_GET['q']  ?? '');
    $programId = (int)($_GET['p'] ?? 0);

    $where  = ['s.active = 1'];
    $params = [];

    if ($search)    { $where[] = 's.name LIKE ?'; $params[] = '%' . $search . '%'; }
    if ($programId) {
        $where[] = 'EXISTS (
            SELECT 1 FROM enrollments e
            JOIN time_slots ts ON ts.id = e.time_slot_id
            WHERE e.student_id = s.id AND e.status = \'active\' AND ts.program_id = ?
        )';
        $params[] = $programId;
    }

    $sql  = "SELECT * FROM students s WHERE " . implode(' AND ', $where) . " ORDER BY s.name" . ($search ? " LIMIT 40" : "");
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['ok' => true, 'students' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $name = strtoupper(trim($input['name'] ?? ''));
    if (!$name) jsonResponse(['ok' => false, 'error' => 'name es requerido'], 400);

    $stmt = $db->prepare("INSERT INTO students (name, gender, category, phone) VALUES (?,?,?,?)");
    $stmt->execute([
        $name,
        $input['gender']   ?? '',
        $input['category'] ?? '',
        $input['phone']    ?? '',
    ]);
    jsonResponse(['ok' => true, 'student_id' => (int)$db->lastInsertId()]);
}

if ($method === 'PUT') {
    $id   = (int)($input['id']   ?? 0);
    $name = strtoupper(trim($input['name'] ?? ''));
    if (!$id || !$name) jsonResponse(['ok' => false, 'error' => 'id y name requeridos'], 400);

    $stmt = $db->prepare("UPDATE students SET name=?, gender=?, category=?, phone=? WHERE id=?");
    $stmt->execute([
        $name,
        $input['gender']   ?? '',
        $input['category'] ?? '',
        $input['phone']    ?? '',
        $id,
    ]);
    jsonResponse(['ok' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(['ok' => false, 'error' => 'id requerido'], 400);
    $db->prepare("UPDATE students SET active = 0 WHERE id = ?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
