<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$basePath       = '../';
$currentProgram = (int)($_GET['p'] ?? 1);
$db  = getDB();
$me  = currentUser();

$users = $db->query("SELECT * FROM users ORDER BY role DESC, username")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Usuarios</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<script>(function(){var t=localStorage.getItem('morning-pass-theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>
<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="page">
  <div class="page-title">👥 Gestión de Usuarios</div>
  <div class="page-sub">Crea, edita y gestiona los accesos al sistema.</div>

  <div style="margin-bottom:20px">
    <button class="btn-primary" id="btn-new-user">+ Nuevo usuario</button>
  </div>

  <div class="scroll-x">
    <table class="data-table">
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Nombre</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Último acceso</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
          <td><?= htmlspecialchars($u['display_name']) ?></td>
          <td>
            <span class="role-badge role-<?= $u['role'] ?>">
              <?= $u['role'] === 'admin' ? '⭐ Admin' : '👤 Staff' ?>
            </span>
          </td>
          <td>
            <span class="status-dot <?= $u['active'] ? 'active' : 'inactive' ?>"></span>
            <?= $u['active'] ? 'Activo' : 'Inactivo' ?>
          </td>
          <td class="audit-date">
            <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '—' ?>
          </td>
          <td>
            <button class="btn-secondary btn-sm btn-edit-user"
              data-id="<?= $u['id'] ?>"
              data-username="<?= htmlspecialchars($u['username']) ?>"
              data-display="<?= htmlspecialchars($u['display_name']) ?>"
              data-role="<?= $u['role'] ?>"
              data-active="<?= $u['active'] ?>">
              ✏️ Editar
            </button>
            <?php if ($u['id'] !== $me['id']): ?>
            <button class="btn-secondary btn-sm btn-reset-pass"
              data-id="<?= $u['id'] ?>"
              data-username="<?= htmlspecialchars($u['username']) ?>">
              🔑 Contraseña
            </button>
            <?php if ($u['active']): ?>
            <button class="btn-danger btn-sm btn-toggle-user"
              data-id="<?= $u['id'] ?>"
              data-username="<?= htmlspecialchars($u['username']) ?>"
              data-active="1">
              🚫 Desactivar
            </button>
            <?php else: ?>
            <button class="btn-secondary btn-sm btn-toggle-user"
              data-id="<?= $u['id'] ?>"
              data-username="<?= htmlspecialchars($u['username']) ?>"
              data-active="0">
              ✅ Activar
            </button>
            <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- New/Edit user modal -->
<div id="user-modal" class="modal-overlay hidden">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2 id="user-modal-title">Nuevo usuario</h2>
      <button class="modal-close" id="user-modal-close">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="um-id">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Usuario *</label>
          <input type="text" id="um-username" class="input-field" placeholder="sin espacios, minúsculas">
        </div>
        <div class="form-group">
          <label class="form-label">Nombre a mostrar *</label>
          <input type="text" id="um-display" class="input-field" placeholder="Ej: Cindy">
        </div>
        <div class="form-group">
          <label class="form-label">Rol</label>
          <select id="um-role" class="select-field">
            <option value="staff">👤 Staff</option>
            <option value="admin">⭐ Administrador</option>
          </select>
        </div>
        <div class="form-group" id="um-pass-wrap">
          <label class="form-label">Contraseña *</label>
          <input type="password" id="um-pass" class="input-field" placeholder="Mínimo 6 caracteres">
        </div>
      </div>
      <button class="btn-primary" id="btn-user-save" style="width:100%;margin-top:14px">Guardar</button>
    </div>
  </div>
</div>

<!-- Reset password modal -->
<div id="reset-pass-modal" class="modal-overlay hidden">
  <div class="modal" style="max-width:360px">
    <div class="modal-header">
      <h2>Restablecer contraseña</h2>
      <button class="modal-close" id="reset-pass-close">×</button>
    </div>
    <div class="modal-body">
      <p class="form-label" id="reset-pass-label" style="margin-bottom:12px"></p>
      <input type="hidden" id="rp-id">
      <div class="form-group">
        <label class="form-label">Nueva contraseña *</label>
        <input type="password" id="rp-pass" class="input-field" placeholder="Mínimo 6 caracteres">
      </div>
      <button class="btn-primary" id="btn-rp-save" style="width:100%;margin-top:8px">Guardar contraseña</button>
    </div>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
// ── Users admin page ──────────────────────────────────
(function() {
  // New user
  document.getElementById('btn-new-user').onclick = () => {
    document.getElementById('um-id').value       = '';
    document.getElementById('um-username').value  = '';
    document.getElementById('um-display').value   = '';
    document.getElementById('um-role').value      = 'staff';
    document.getElementById('um-pass').value      = '';
    document.getElementById('um-pass-wrap').style.display = '';
    document.getElementById('user-modal-title').textContent = 'Nuevo usuario';
    document.getElementById('user-modal').classList.remove('hidden');
    document.getElementById('um-username').focus();
  };

  // Edit user
  document.querySelectorAll('.btn-edit-user').forEach(btn => {
    btn.onclick = () => {
      document.getElementById('um-id').value       = btn.dataset.id;
      document.getElementById('um-username').value  = btn.dataset.username;
      document.getElementById('um-display').value   = btn.dataset.display;
      document.getElementById('um-role').value      = btn.dataset.role;
      document.getElementById('um-pass').value      = '';
      document.getElementById('um-pass-wrap').style.display = 'none';
      document.getElementById('user-modal-title').textContent = 'Editar usuario';
      document.getElementById('user-modal').classList.remove('hidden');
    };
  });

  document.getElementById('user-modal-close').onclick =
    () => document.getElementById('user-modal').classList.add('hidden');

  document.getElementById('btn-user-save').onclick = async () => {
    const id       = document.getElementById('um-id').value;
    const username = document.getElementById('um-username').value.trim().toLowerCase();
    const display  = document.getElementById('um-display').value.trim();
    const role     = document.getElementById('um-role').value;
    const pass     = document.getElementById('um-pass').value;

    if (!username || !display) { toast('Completa usuario y nombre', 'error'); return; }
    if (!id && !pass) { toast('La contraseña es requerida para nuevo usuario', 'error'); return; }

    try {
      const body = id ? { id: parseInt(id), username, display_name: display, role }
                      : { username, display_name: display, role, password: pass };
      const method = id ? 'PUT' : 'POST';
      await api('../api/users.php', method, body);
      toast(id ? 'Usuario actualizado' : 'Usuario creado', 'success');
      document.getElementById('user-modal').classList.add('hidden');
      setTimeout(() => location.reload(), 600);
    } catch (e) { toast(e.message, 'error'); }
  };

  // Reset password
  document.querySelectorAll('.btn-reset-pass').forEach(btn => {
    btn.onclick = () => {
      document.getElementById('rp-id').value = btn.dataset.id;
      document.getElementById('reset-pass-label').textContent =
        `Nueva contraseña para "${btn.dataset.username}"`;
      document.getElementById('rp-pass').value = '';
      document.getElementById('reset-pass-modal').classList.remove('hidden');
    };
  });

  document.getElementById('reset-pass-close').onclick =
    () => document.getElementById('reset-pass-modal').classList.add('hidden');

  document.getElementById('btn-rp-save').onclick = async () => {
    const id   = document.getElementById('rp-id').value;
    const pass = document.getElementById('rp-pass').value;
    if (!pass || pass.length < 6) { toast('Mínimo 6 caracteres', 'error'); return; }
    try {
      await api('../api/users.php', 'PUT', { id: parseInt(id), reset_password: pass });
      toast('Contraseña restablecida', 'success');
      document.getElementById('reset-pass-modal').classList.add('hidden');
    } catch (e) { toast(e.message, 'error'); }
  };

  // Toggle active
  document.querySelectorAll('.btn-toggle-user').forEach(btn => {
    btn.onclick = async () => {
      const active = btn.dataset.active === '1' ? 0 : 1;
      const label  = active ? 'activar' : 'desactivar';
      if (!confirm(`¿${label.charAt(0).toUpperCase() + label.slice(1)} a "${btn.dataset.username}"?`)) return;
      try {
        await api('../api/users.php', 'PUT', { id: parseInt(btn.dataset.id), active });
        toast(`Usuario ${active ? 'activado' : 'desactivado'}`, 'success');
        setTimeout(() => location.reload(), 500);
      } catch (e) { toast(e.message, 'error'); }
    };
  });
})();
</script>
</body>
</html>
