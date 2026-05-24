<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

if ($method === 'GET') {
    $search = trim($_GET['q'] ?? '');
    if ($search) {
        $stmt = $db->prepare("SELECT * FROM students WHERE active = 1 AND name LIKE ? ORDER BY name LIMIT 30");
        $stmt->execute(['%' . $search . '%']);
    } else {
        $stmt = $db->query("SELECT * FROM students WHERE active = 1 ORDER BY name");
    }
    jsonResponse(['ok' => true, 'students' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $name = trim($input['name'] ?? '');
    if (!$name) jsonResponse(['ok' => false, 'error' => 'name is required'], 400);
    $stmt = $db->prepare("INSERT INTO students (name) VALUES (?)");
    $stmt->execute([strtoupper($name)]);
    jsonResponse(['ok' => true, 'student_id' => (int)$db->lastInsertId()]);
}

if ($method === 'PUT') {
    $id   = (int)($input['id']   ?? 0);
    $name = trim($input['name'] ?? '');
    if (!$id || !$name) jsonResponse(['ok' => false, 'error' => 'id and name required'], 400);
    $db->prepare("UPDATE students SET name = ? WHERE id = ?")->execute([strtoupper($name), $id]);
    jsonResponse(['ok' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(['ok' => false, 'error' => 'id required'], 400);
    $db->prepare("UPDATE students SET active = 0 WHERE id = ?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
