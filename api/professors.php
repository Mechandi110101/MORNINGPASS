<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

// ── GET: list professors ─────────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM professors ORDER BY name");
    jsonResponse(['ok' => true, 'professors' => $stmt->fetchAll()]);
}

// ── POST: create professor OR upload photo ────────────────────────────────────
if ($method === 'POST') {
    // Photo upload (multipart form)
    if (isset($_FILES['photo'])) {
        $profId = (int)($_POST['professor_id'] ?? 0);
        if (!$profId) jsonResponse(['ok' => false, 'error' => 'professor_id requerido'], 400);

        $file    = $_FILES['photo'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($file['type'], $allowed)) {
            jsonResponse(['ok' => false, 'error' => 'Solo se permiten JPG, PNG, WebP o GIF'], 400);
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            jsonResponse(['ok' => false, 'error' => 'La foto no puede superar 2 MB'], 400);
        }

        $dir = __DIR__ . '/../assets/uploads/professors/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $profId . '.' . strtolower($ext);
        $target   = $dir . $filename;

        // Remove previous photo files for this professor
        foreach (glob($dir . $profId . '.*') as $old) unlink($old);

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            jsonResponse(['ok' => false, 'error' => 'Error al guardar la imagen'], 500);
        }

        $photoPath = 'assets/uploads/professors/' . $filename;
        $db->prepare("UPDATE professors SET photo = ? WHERE id = ?")->execute([$photoPath, $profId]);
        jsonResponse(['ok' => true, 'photo' => $photoPath]);
    }

    // Create new professor
    $name = strtoupper(trim($input['name'] ?? ''));
    if (!$name) jsonResponse(['ok' => false, 'error' => 'name es requerido'], 400);

    $stmt = $db->prepare("
        INSERT INTO professors (name, color_hex, availability)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $input['color_hex']    ?? '#4a7fc1',
        $input['availability'] ?? '',
    ]);
    jsonResponse(['ok' => true, 'professor_id' => (int)$db->lastInsertId()]);
}

// ── PUT: update professor ────────────────────────────────────────────────────
if ($method === 'PUT') {
    $id   = (int)($input['id']   ?? 0);
    $name = strtoupper(trim($input['name'] ?? ''));
    if (!$id || !$name) jsonResponse(['ok' => false, 'error' => 'id y name requeridos'], 400);

    $stmt = $db->prepare("
        UPDATE professors SET name=?, color_hex=?, availability=?, active=? WHERE id=?
    ");
    $stmt->execute([
        $name,
        $input['color_hex']    ?? '#4a7fc1',
        $input['availability'] ?? '',
        isset($input['active']) ? (int)$input['active'] : 1,
        $id,
    ]);
    jsonResponse(['ok' => true]);
}

// ── DELETE: deactivate professor ─────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(['ok' => false, 'error' => 'id requerido'], 400);

    // Check if professor has active slots
    $check = $db->prepare("SELECT COUNT(*) FROM time_slots WHERE professor_id=? AND active=1");
    $check->execute([$id]);
    if ((int)$check->fetchColumn() > 0) {
        jsonResponse(['ok' => false, 'error' => 'Este profesor tiene horarios activos. Elimínalos primero.'], 409);
    }

    $db->prepare("UPDATE professors SET active = 0 WHERE id = ?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
