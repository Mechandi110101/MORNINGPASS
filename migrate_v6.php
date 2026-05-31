<?php
/**
 * Morning Pass – Migration v6
 * Ejecutar una sola vez desde CLI o navegador (con protección):
 *   php migrate_v6.php
 *
 * Agrega:
 *  - Tabla users (autenticación)
 *  - Tabla audit_log (bitácora de actividad)
 *  - Columna guest_name en enrollments (clases de prueba/premio sin alumno en BD)
 *  - Hace nullable enrollments.student_id para guests
 */

// Bloquear acceso web si no viene de CLI
if (php_sapi_name() !== 'cli') {
    $secret = $_GET['secret'] ?? '';
    if ($secret !== 'mp_migrate_v6_run') {
        http_response_code(403);
        die('Acceso denegado. Ejecutar por CLI: php migrate_v6.php');
    }
}

require_once __DIR__ . '/includes/db.php';
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$steps = [];

// ── 1. Tabla users ─────────────────────────────────────────────────────────
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            username     VARCHAR(50)  UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(100) NOT NULL DEFAULT '',
            role         ENUM('admin','staff') NOT NULL DEFAULT 'staff',
            active       TINYINT(1) NOT NULL DEFAULT 1,
            last_login   TIMESTAMP NULL,
            created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $steps[] = '✅ Tabla users creada (o ya existía)';
} catch (PDOException $e) {
    $steps[] = '❌ Error creando tabla users: ' . $e->getMessage();
}

// ── 2. Tabla audit_log ─────────────────────────────────────────────────────
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT NULL,
            username    VARCHAR(50) NOT NULL DEFAULT 'system',
            action      VARCHAR(60) NOT NULL,
            entity_type VARCHAR(50) NULL,
            entity_id   INT NULL,
            description TEXT NULL,
            ip_address  VARCHAR(45) NULL,
            created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_user_id    (user_id)
        )
    ");
    $steps[] = '✅ Tabla audit_log creada (o ya existía)';
} catch (PDOException $e) {
    $steps[] = '❌ Error creando tabla audit_log: ' . $e->getMessage();
}

// ── 3. Columna guest_name en enrollments ───────────────────────────────────
try {
    $cols = $db->query("SHOW COLUMNS FROM enrollments LIKE 'guest_name'")->fetchAll();
    if (!$cols) {
        $db->exec("ALTER TABLE enrollments ADD COLUMN guest_name VARCHAR(150) DEFAULT NULL");
        $steps[] = '✅ Columna enrollments.guest_name agregada';
    } else {
        $steps[] = '⏭  enrollments.guest_name ya existe';
    }
} catch (PDOException $e) {
    $steps[] = '❌ Error agregando guest_name: ' . $e->getMessage();
}

// ── 4. Hacer nullable enrollments.student_id ──────────────────────────────
try {
    $col = $db->query("SHOW COLUMNS FROM enrollments LIKE 'student_id'")->fetch();
    if ($col && strpos(strtolower($col['Null']), 'no') !== false) {
        // Drop FK si existe
        $fks = $db->query("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'enrollments'
              AND COLUMN_NAME  = 'student_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll();
        foreach ($fks as $fk) {
            $db->exec("ALTER TABLE enrollments DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
        }
        $db->exec("ALTER TABLE enrollments MODIFY COLUMN student_id INT NULL");
        $steps[] = '✅ enrollments.student_id ahora es nullable';
    } else {
        $steps[] = '⏭  enrollments.student_id ya es nullable';
    }
} catch (PDOException $e) {
    $steps[] = '❌ Error modificando student_id: ' . $e->getMessage();
}

// ── 5. Insertar usuarios ───────────────────────────────────────────────────
$users = [
    ['admin',  'MPPadelZone', 'Administrador', 'admin'],
    ['cindy',  'Estefano10',  'Cindy',          'staff'],
    ['allen',  'Matias25',    'Allen',          'staff'],
    ['dani',   'Sebas12',     'Dani',           'staff'],
    ['cami',   'Cami1234',    'Cami',           'staff'],
];

$ins = $db->prepare("
    INSERT INTO users (username, password_hash, display_name, role)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        password_hash = VALUES(password_hash),
        display_name  = VALUES(display_name),
        role          = VALUES(role)
");

foreach ($users as [$username, $password, $displayName, $role]) {
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins->execute([$username, $hash, $displayName, $role]);
        $steps[] = "✅ Usuario '{$username}' creado/actualizado";
    } catch (PDOException $e) {
        $steps[] = "❌ Error con usuario '{$username}': " . $e->getMessage();
    }
}

// ── Resultado ──────────────────────────────────────────────────────────────
$isCli = php_sapi_name() === 'cli';
$nl    = $isCli ? "\n" : "<br>";

echo $isCli ? '' : '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migration v6</title></head><body><pre>';
echo "Morning Pass – Migration v6{$nl}";
echo str_repeat('─', 40) . $nl;
foreach ($steps as $s) echo $s . $nl;
echo str_repeat('─', 40) . $nl;
echo "Migración completada.{$nl}";
echo $isCli ? '' : '</pre></body></html>';
