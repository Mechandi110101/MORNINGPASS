<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → redirect
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']        ?? '';

    if (!$username || !$password) {
        $error = 'Completa usuario y contraseña.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['user_role']    = $user['role'];

            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            $redirect = $_GET['redirect'] ?? 'index.php';
            // Sanitize redirect to prevent open redirect
            $redirect = preg_replace('/[^a-zA-Z0-9_.\/\-?=&]/', '', $redirect);
            header("Location: $redirect");
            exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Iniciar sesión</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
<script>
(function(){
  var t = localStorage.getItem('morning-pass-theme');
  if (t) document.documentElement.setAttribute('data-theme', t);
})();
</script>

<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      <span class="login-icon">🎾</span>
      <h1 class="login-title">Morning Pass</h1>
      <p class="login-sub">Sistema de gestión de horarios</p>
    </div>

    <?php if ($error): ?>
      <div class="login-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="login-form" autocomplete="off">
      <div class="form-group">
        <label class="form-label" for="login-user">Usuario</label>
        <input
          type="text"
          id="login-user"
          name="username"
          class="input-field"
          placeholder="Nombre de usuario"
          value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
          required
          autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="login-pass">Contraseña</label>
        <input
          type="password"
          id="login-pass"
          name="password"
          class="input-field"
          placeholder="Contraseña"
          required>
      </div>
      <button type="submit" class="btn-primary login-btn">Iniciar sesión</button>
    </form>
  </div>
</div>
</body>
</html>
