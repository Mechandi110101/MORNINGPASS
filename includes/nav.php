<?php
// Shared navigation — include at the top of every page
// Requires $basePath to be set before including:
//   root pages  → $basePath = '';
//   admin pages → $basePath = '../';

$currentProgram = (int)($_GET['p'] ?? 1);
$currentPage    = basename($_SERVER['PHP_SELF']);

$programs = [
    1 => ['name' => 'Morning Pass',    'icon' => '🌅', 'color' => '#B8232a'],
    2 => ['name' => 'Academia',        'icon' => '🏫', 'color' => '#27393f'],
    3 => ['name' => 'Team Competition','icon' => '🏆', 'color' => '#1a6a8a'],
];

$navPages = [
    'dashboard.php'        => '📊 Dashboard',
    'index.php'            => 'Horario',
    'professor.php'        => 'Por Profesor',
    'students.php'         => 'Estudiantes',
    'admin/slots.php'      => 'Admin Horarios',
    'admin/professors.php' => 'Profesores',
];

// Current user (if auth is loaded)
$me     = function_exists('currentUser') ? currentUser() : ['display_name' => '', 'role' => 'staff'];
$isAdmin = ($me['role'] === 'admin');
?>
<script>
(function(){
  var t = localStorage.getItem('morning-pass-theme');
  if (t) document.documentElement.setAttribute('data-theme', t);
})();
</script>
<header class="site-header">
  <div class="header-brand">
    <span class="brand-icon">🎾</span>
    <span class="brand-name">Morning Pass</span>
  </div>

  <div class="program-tabs">
    <?php foreach ($programs as $pid => $prog): ?>
      <a href="<?= $basePath ?>index.php?p=<?= $pid ?>"
         class="prog-tab <?= $pid === $currentProgram ? 'active' : '' ?>"
         style="--prog-color:<?= $prog['color'] ?>">
        <?= $prog['icon'] ?> <?= $prog['name'] ?>
      </a>
    <?php endforeach; ?>
  </div>

  <nav class="site-nav">
    <?php foreach ($navPages as $file => $label):
        $href     = $basePath . $file . '?p=' . $currentProgram;
        $isActive = ($currentPage === basename($file))
             || ($currentPage === 'slots.php'      && $file === 'admin/slots.php')
             || ($currentPage === 'professors.php'  && $file === 'admin/professors.php');
    ?>
      <a href="<?= $href ?>" class="<?= $isActive ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>

    <?php if ($isAdmin): ?>
      <a href="<?= $basePath ?>admin/audit.php"
         class="nav-admin-link <?= $currentPage === 'audit.php' ? 'active' : '' ?>">
        🔍 Auditoría
      </a>
      <a href="<?= $basePath ?>admin/users.php"
         class="nav-admin-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
        👥 Usuarios
      </a>
    <?php endif; ?>

    <!-- User menu -->
    <?php if (!empty($me['display_name'])): ?>
    <div class="user-menu" id="user-menu">
      <button class="user-menu-btn" id="user-menu-btn" title="Mi cuenta">
        <span class="user-avatar"><?= htmlspecialchars(mb_substr($me['display_name'], 0, 1)) ?></span>
        <span class="user-name"><?= htmlspecialchars($me['display_name']) ?></span>
        <span class="user-caret">▾</span>
      </button>
      <div class="user-dropdown" id="user-dropdown">
        <div class="user-dropdown-info">
          <strong><?= htmlspecialchars($me['display_name']) ?></strong>
          <span><?= $isAdmin ? 'Administrador' : 'Staff' ?></span>
        </div>
        <hr class="user-dropdown-sep">
        <button class="user-dropdown-item" id="btn-change-pass">🔑 Cambiar contraseña</button>
        <button class="user-dropdown-item logout-item" id="btn-logout">↩ Cerrar sesión</button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Dark mode toggle -->
    <button id="dark-toggle" class="dark-toggle" title="Cambiar tema">🌙</button>
  </nav>
</header>

<!-- Change password modal -->
<div id="change-pass-modal" class="modal-overlay hidden">
  <div class="modal" style="max-width:380px">
    <div class="modal-header">
      <h2>Cambiar contraseña</h2>
      <button class="modal-close" id="change-pass-close">×</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Contraseña actual</label>
        <input type="password" id="cp-current" class="input-field" placeholder="Contraseña actual">
      </div>
      <div class="form-group">
        <label class="form-label">Nueva contraseña</label>
        <input type="password" id="cp-new" class="input-field" placeholder="Mínimo 6 caracteres">
      </div>
      <div class="form-group">
        <label class="form-label">Confirmar nueva contraseña</label>
        <input type="password" id="cp-confirm" class="input-field" placeholder="Repite la contraseña">
      </div>
      <button class="btn-primary" id="btn-cp-save" style="width:100%;margin-top:8px">Guardar contraseña</button>
    </div>
  </div>
</div>
