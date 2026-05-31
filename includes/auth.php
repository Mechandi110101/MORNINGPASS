<?php
/**
 * Auth helpers — include after functions.php / db.php
 *
 * requireAuth()   — redirige a login si no hay sesión (o devuelve 401 JSON en APIs)
 * requireAdmin()  — igual pero exige role='admin'
 * currentUser()   — array con datos del usuario logueado
 * logAudit()      — inserta registro en audit_log
 */

function _ensureSession(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function requireAuth(): void {
    _ensureSession();
    if (!empty($_SESSION['user_id'])) return;

    // API requests get JSON 401
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($uri, '/api/')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Sesión expirada. Recarga la página.']);
        exit;
    }

    // Web pages: redirect to login preserving path depth
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    $appRoot   = realpath(__DIR__ . '/..');
    $rel = '';
    if ($scriptDir && $appRoot && $scriptDir !== $appRoot) {
        $depth = substr_count(str_replace($appRoot, '', $scriptDir), DIRECTORY_SEPARATOR);
        $rel   = str_repeat('../', max(0, $depth));
    }
    header("Location: {$rel}login.php");
    exit;
}

function requireAdmin(): void {
    requireAuth();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, '/api/')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Acceso restringido a administradores.']);
            exit;
        }
        $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
        $appRoot   = realpath(__DIR__ . '/..');
        $rel = '';
        if ($scriptDir && $appRoot && $scriptDir !== $appRoot) {
            $depth = substr_count(str_replace($appRoot, '', $scriptDir), DIRECTORY_SEPARATOR);
            $rel   = str_repeat('../', max(0, $depth));
        }
        header("Location: {$rel}index.php");
        exit;
    }
}

function currentUser(): array {
    _ensureSession();
    return [
        'id'           => (int)($_SESSION['user_id']      ?? 0),
        'username'     => $_SESSION['username']            ?? '',
        'display_name' => $_SESSION['display_name']        ?? '',
        'role'         => $_SESSION['user_role']           ?? 'staff',
    ];
}

function logAudit(string $action, string $entityType = '', int $entityId = 0, string $description = ''): void {
    _ensureSession();
    try {
        $db = getDB();
        $db->prepare("
            INSERT INTO audit_log (user_id, username, action, entity_type, entity_id, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $_SESSION['user_id']  ?? null,
            $_SESSION['username'] ?? 'system',
            $action,
            $entityType ?: null,
            $entityId   ?: null,
            $description ?: null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (\Throwable $e) {
        // Never let audit failures break the main operation
    }
}
