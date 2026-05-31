<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

// POST — login
if ($method === 'POST') {
    $username = trim($input['username'] ?? '');
    $password = $input['password']        ?? '';

    if (!$username || !$password) {
        jsonResponse(['ok' => false, 'error' => 'Credenciales requeridas'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['ok' => false, 'error' => 'Usuario o contraseña incorrectos'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['display_name'] = $user['display_name'];
    $_SESSION['user_role']    = $user['role'];

    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    logAudit('login', 'user', (int)$user['id'], "Inicio de sesión: {$user['username']}");

    jsonResponse(['ok' => true, 'user' => [
        'id'           => (int)$user['id'],
        'username'     => $user['username'],
        'display_name' => $user['display_name'],
        'role'         => $user['role'],
    ]]);
}

// DELETE — logout
if ($method === 'DELETE') {
    logAudit('logout', 'user', (int)($_SESSION['user_id'] ?? 0), 'Cierre de sesión');
    session_destroy();
    jsonResponse(['ok' => true]);
}

// PUT — change own password
if ($method === 'PUT') {
    requireAuth();
    $current = $input['current_password'] ?? '';
    $new     = $input['new_password']     ?? '';

    if (!$current || !$new || strlen($new) < 6) {
        jsonResponse(['ok' => false, 'error' => 'Contraseña nueva debe tener al menos 6 caracteres'], 400);
    }

    $user = $db->prepare("SELECT * FROM users WHERE id = ?")->execute([$_SESSION['user_id']]);
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        jsonResponse(['ok' => false, 'error' => 'Contraseña actual incorrecta'], 401);
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);
    logAudit('change_password', 'user', (int)$user['id'], 'Cambio de contraseña propio');
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
