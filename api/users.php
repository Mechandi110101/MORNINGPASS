<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

if ($method === 'GET') {
    $stmt = $db->query("SELECT id, username, display_name, role, active, last_login, created_at FROM users ORDER BY role DESC, username");
    jsonResponse(['ok' => true, 'users' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $username = strtolower(trim($input['username'] ?? ''));
    $display  = trim($input['display_name'] ?? '');
    $pass     = $input['password'] ?? '';
    $role     = in_array($input['role'] ?? '', ['admin','staff']) ? $input['role'] : 'staff';

    if (!$username || !$display) jsonResponse(['ok' => false, 'error' => 'Usuario y nombre requeridos'], 400);
    if (!$pass || strlen($pass) < 6) jsonResponse(['ok' => false, 'error' => 'Contraseña mínimo 6 caracteres'], 400);
    if (!preg_match('/^[a-z0-9_]+$/', $username)) jsonResponse(['ok' => false, 'error' => 'Usuario solo puede tener letras, números y _'], 400);

    try {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?,?,?,?)")
           ->execute([$username, $hash, $display, $role]);
        $userId = (int)$db->lastInsertId();
        logAudit('create_user', 'user', $userId, "Usuario creado: {$username} ({$role})");
        jsonResponse(['ok' => true, 'user_id' => $userId]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            jsonResponse(['ok' => false, 'error' => "El usuario '{$username}' ya existe"], 409);
        }
        throw $e;
    }
}

if ($method === 'PUT') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(['ok' => false, 'error' => 'id requerido'], 400);

    // Reset password (admin action)
    if (!empty($input['reset_password'])) {
        $pass = $input['reset_password'];
        if (strlen($pass) < 6) jsonResponse(['ok' => false, 'error' => 'Contraseña mínimo 6 caracteres'], 400);
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);
        logAudit('edit_user', 'user', $id, "Contraseña restablecida por admin");
        jsonResponse(['ok' => true]);
    }

    // Toggle active
    if (isset($input['active'])) {
        $active = $input['active'] ? 1 : 0;
        $db->prepare("UPDATE users SET active = ? WHERE id = ?")->execute([$active, $id]);
        logAudit('edit_user', 'user', $id, $active ? 'Usuario activado' : 'Usuario desactivado');
        jsonResponse(['ok' => true]);
    }

    // Full edit
    $username = strtolower(trim($input['username'] ?? ''));
    $display  = trim($input['display_name'] ?? '');
    $role     = in_array($input['role'] ?? '', ['admin','staff']) ? $input['role'] : 'staff';

    if (!$username || !$display) jsonResponse(['ok' => false, 'error' => 'Usuario y nombre requeridos'], 400);
    if (!preg_match('/^[a-z0-9_]+$/', $username)) jsonResponse(['ok' => false, 'error' => 'Usuario solo puede tener letras, números y _'], 400);

    try {
        $db->prepare("UPDATE users SET username=?, display_name=?, role=? WHERE id=?")
           ->execute([$username, $display, $role, $id]);
        logAudit('edit_user', 'user', $id, "Usuario editado: {$username} ({$role})");
        jsonResponse(['ok' => true]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            jsonResponse(['ok' => false, 'error' => "El usuario '{$username}' ya existe"], 409);
        }
        throw $e;
    }
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(['ok' => false, 'error' => 'id requerido'], 400);
    $me = currentUser();
    if ($id === $me['id']) jsonResponse(['ok' => false, 'error' => 'No puedes eliminarte a ti mismo'], 400);

    $s = $db->prepare("SELECT username FROM users WHERE id = ?");
    $s->execute([$id]);
    $info = $s->fetch();

    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    logAudit('delete_user', 'user', $id, "Usuario eliminado: " . ($info['username'] ?? $id));
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
