<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
$basePath   = '../';
$currentProgram = (int)($_GET['p'] ?? 1);
$db         = getDB();

$professors = $db->query("SELECT * FROM professors ORDER BY active DESC, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Profesores</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .prof-admin-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 14px;
      margin-bottom: 30px;
    }
    .prof-admin-card {
      background: var(--white);
      border: 1px solid var(--blue-border);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: box-shadow 0.15s;
    }
    .prof-admin-card:hover { box-shadow: var(--shadow-lg); }
    .prof-admin-card .card-top {
      padding: 20px 16px 14px;
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .prof-photo {
      width: 64px; height: 64px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--white);
      box-shadow: 0 2px 8px rgba(0,0,0,0.18);
      flex-shrink: 0;
    }
    .prof-photo-placeholder {
      width: 64px; height: 64px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      font-weight: 800;
      color: #fff;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .prof-admin-card .card-actions {
      border-top: 1px solid var(--blue-border);
      padding: 10px 14px;
      display: flex;
      gap: 8px;
      background: var(--blue-light);
    }
    .inactive-overlay { opacity: 0.5; }
    .color-preview {
      width: 32px; height: 32px;
      border-radius: 50%;
      border: 2px solid var(--blue-border);
      cursor: pointer;
      flex-shrink: 0;
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="page">
  <div class="page-title">🎾 Gestión de Profesores</div>
  <div class="page-sub">Agrega, edita y gestiona los profesores de la academia.</div>

  <!-- Professor cards -->
  <div class="prof-admin-grid" id="prof-grid">
    <?php foreach ($professors as $p):
          $initials = implode('', array_map(fn($w) => $w[0], explode(' ', $p['name'])));
          $initials = substr($initials, 0, 2);
    ?>
    <div class="prof-admin-card <?= !$p['active'] ? 'inactive-overlay' : '' ?>" id="pcard-<?= $p['id'] ?>">
      <div class="card-top" style="background: linear-gradient(135deg, <?= htmlspecialchars($p['color_hex']) ?>22, <?= htmlspecialchars($p['color_hex']) ?>44)">
        <?php if ($p['photo'] && file_exists(__DIR__ . '/../' . $p['photo'])): ?>
          <img class="prof-photo" src="../<?= htmlspecialchars($p['photo']) ?>?v=<?= filemtime(__DIR__ . '/../' . $p['photo']) ?>" alt="">
        <?php else: ?>
          <div class="prof-photo-placeholder" style="background:<?= htmlspecialchars($p['color_hex']) ?>">
            <?= htmlspecialchars($initials) ?>
          </div>
        <?php endif; ?>
        <div style="flex:1">
          <div style="font-weight:800;font-size:1rem;margin-bottom:3px"><?= htmlspecialchars($p['name']) ?></div>
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
            <span class="color-dot" style="background:<?= htmlspecialchars($p['color_hex']) ?>;width:14px;height:14px"></span>
            <span style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($p['color_hex']) ?></span>
          </div>
          <?php if ($p['availability']): ?>
            <div style="font-size:0.72rem;color:var(--text-muted);line-height:1.3">
              📅 <?= htmlspecialchars($p['availability']) ?>
            </div>
          <?php endif; ?>
          <div style="margin-top:5px">
            <?php if ($p['active']): ?>
              <span class="badge green">Activo</span>
            <?php else: ?>
              <span class="badge red">Inactivo</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Photo upload -->
      <div style="padding:8px 14px;border-bottom:1px solid var(--blue-border);background:var(--blue-light)">
        <label style="font-size:0.72rem;font-weight:700;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;gap:6px">
          📷 <span>Cambiar foto</span>
          <input type="file" accept="image/*" class="photo-upload" data-id="<?= $p['id'] ?>" style="display:none">
        </label>
      </div>

      <div class="card-actions">
        <button class="btn-secondary btn-edit-prof"
                data-id="<?= $p['id'] ?>"
                data-name="<?= htmlspecialchars($p['name']) ?>"
                data-color="<?= htmlspecialchars($p['color_hex']) ?>"
                data-avail="<?= htmlspecialchars($p['availability'] ?? '') ?>"
                data-active="<?= $p['active'] ?>"
                style="flex:1">
          ✏️ Editar
        </button>
        <button class="btn-danger btn-del-prof" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>">
          ×
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Add professor form -->
  <div class="form-card" style="max-width:480px">
    <h3>Agregar nuevo profesor</h3>
    <div class="form-grid">
      <div class="form-group full">
        <label class="form-label">Nombre *</label>
        <input type="text" id="new-prof-name" class="input-field" placeholder="Ej: CARLOS RUIZ">
      </div>
      <div class="form-group">
        <label class="form-label">Color</label>
        <div style="display:flex;align-items:center;gap:8px">
          <input type="color" id="new-prof-color" value="#4a7fc1" style="width:42px;height:38px;border:1px solid var(--blue-border);border-radius:6px;padding:2px;cursor:pointer">
          <span id="new-color-hex" style="font-size:0.8rem;font-family:monospace;color:var(--text-muted)">#4a7fc1</span>
        </div>
      </div>
      <div class="form-group full">
        <label class="form-label">Disponibilidad / Notas</label>
        <input type="text" id="new-prof-avail" class="input-field" placeholder="Ej: Lunes a Viernes 6AM-12PM">
      </div>
    </div>
    <button class="btn-primary" id="btn-add-prof" style="margin-top:12px;width:100%">Agregar profesor</button>
  </div>
</div>

<!-- Edit modal -->
<div id="edit-modal" class="modal-overlay hidden">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <h2>Editar Profesor</h2>
      <button class="modal-close" id="edit-close">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-prof-id">
      <div class="form-grid">
        <div class="form-group full">
          <label class="form-label">Nombre *</label>
          <input type="text" id="edit-prof-name" class="input-field">
        </div>
        <div class="form-group">
          <label class="form-label">Color</label>
          <div style="display:flex;align-items:center;gap:8px">
            <input type="color" id="edit-prof-color" style="width:42px;height:38px;border:1px solid var(--blue-border);border-radius:6px;padding:2px;cursor:pointer">
            <span id="edit-color-hex" style="font-size:0.8rem;font-family:monospace;color:var(--text-muted)"></span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Estado</label>
          <select id="edit-prof-active" class="select-field">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <div class="form-group full">
          <label class="form-label">Disponibilidad / Notas</label>
          <input type="text" id="edit-prof-avail" class="input-field">
        </div>
      </div>
      <button class="btn-primary" id="btn-save-edit" style="margin-top:14px;width:100%">Guardar cambios</button>
    </div>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
const CURRENT_PROGRAM = <?= $currentProgram ?>;

// Color preview sync
document.getElementById('new-prof-color').addEventListener('input', function() {
  document.getElementById('new-color-hex').textContent = this.value;
});
document.getElementById('edit-prof-color').addEventListener('input', function() {
  document.getElementById('edit-color-hex').textContent = this.value;
});

// Add professor
document.getElementById('btn-add-prof').addEventListener('click', async () => {
  const name = document.getElementById('new-prof-name').value.trim();
  if (!name) { toast('El nombre es requerido', 'error'); return; }
  try {
    await api('../api/professors.php', 'POST', {
      name,
      color_hex:    document.getElementById('new-prof-color').value,
      availability: document.getElementById('new-prof-avail').value.trim(),
    });
    toast('Profesor agregado', 'success');
    setTimeout(() => location.reload(), 600);
  } catch (e) { toast(e.message, 'error'); }
});

// Edit professor
document.querySelectorAll('.btn-edit-prof').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('edit-prof-id').value        = btn.dataset.id;
    document.getElementById('edit-prof-name').value      = btn.dataset.name;
    document.getElementById('edit-prof-color').value     = btn.dataset.color;
    document.getElementById('edit-color-hex').textContent= btn.dataset.color;
    document.getElementById('edit-prof-avail').value     = btn.dataset.avail;
    document.getElementById('edit-prof-active').value    = btn.dataset.active;
    document.getElementById('edit-modal').classList.remove('hidden');
  });
});

document.getElementById('edit-close').addEventListener('click', () => {
  document.getElementById('edit-modal').classList.add('hidden');
});

document.getElementById('btn-save-edit').addEventListener('click', async () => {
  const id = parseInt(document.getElementById('edit-prof-id').value);
  try {
    await api('../api/professors.php', 'PUT', {
      id,
      name:         document.getElementById('edit-prof-name').value.trim(),
      color_hex:    document.getElementById('edit-prof-color').value,
      availability: document.getElementById('edit-prof-avail').value.trim(),
      active:       parseInt(document.getElementById('edit-prof-active').value),
    });
    toast('Guardado', 'success');
    setTimeout(() => location.reload(), 600);
  } catch (e) { toast(e.message, 'error'); }
});

// Delete professor
document.querySelectorAll('.btn-del-prof').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm(`¿Dar de baja a ${btn.dataset.name}? No se puede deshacer si tiene horarios activos.`)) return;
    try {
      await api('../api/professors.php', 'DELETE', { id: parseInt(btn.dataset.id) });
      document.getElementById('pcard-' + btn.dataset.id)?.remove();
      toast('Profesor dado de baja');
    } catch (e) { toast(e.message, 'error'); }
  });
});

// Photo upload
document.querySelectorAll('.photo-upload').forEach(input => {
  input.addEventListener('change', async function() {
    if (!this.files.length) return;
    const formData = new FormData();
    formData.append('photo', this.files[0]);
    formData.append('professor_id', this.dataset.id);
    try {
      const res  = await fetch('../api/professors.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error);
      toast('Foto actualizada', 'success');
      setTimeout(() => location.reload(), 600);
    } catch (e) { toast(e.message, 'error'); }
  });
});
</script>
</body>
</html>
