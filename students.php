<?php
require_once __DIR__ . '/includes/functions.php';
$basePath       = '';
$currentProgram = (int)($_GET['p'] ?? 1);
$programs       = getPrograms();
$students       = getStudents($currentProgram);

$currentProg = null;
foreach ($programs as $pg) {
    if ($pg['id'] == $currentProgram) { $currentProg = $pg; break; }
}

$genderLabel = ['M' => 'Hombre', 'F' => 'Mujer', '' => ''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Estudiantes</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page">
  <div class="page-title">
    <?= $currentProg ? $currentProg['icon'] . ' ' : '' ?>Estudiantes
  </div>
  <div class="page-sub">
    <?= count($students) ?> estudiante<?= count($students) != 1 ? 's' : '' ?> en
    <?= $currentProg ? htmlspecialchars($currentProg['name']) : 'todos los programas' ?>
  </div>

  <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">

    <!-- Add student form -->
    <div class="form-card" style="max-width:400px;flex:0 0 400px">
      <h3>Agregar estudiante</h3>
      <div class="form-grid">
        <div class="form-group full">
          <label class="form-label">Nombre completo *</label>
          <input type="text" id="new-name" class="input-field" placeholder="Ej: JUAN PÉREZ">
        </div>
        <div class="form-group">
          <label class="form-label">Sexo</label>
          <select id="new-gender" class="select-field">
            <option value="">— —</option>
            <option value="M">Hombre</option>
            <option value="F">Mujer</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Categoría</label>
          <input type="text" id="new-category" class="input-field" placeholder="Ej: CAT 4TA">
        </div>
        <div class="form-group">
          <label class="form-label">Teléfono</label>
          <input type="text" id="new-phone" class="input-field" placeholder="Ej: 8888-0000">
        </div>
        <div class="form-group">
          <label class="form-label">Membresía</label>
          <select id="new-membership-status" class="select-field">
            <option value="active">Activa</option>
            <option value="courtesy">Cortesía</option>
            <option value="expired">Vencida</option>
          </select>
        </div>
        <div class="form-group full">
          <label class="form-label">Fecha vencimiento membresía</label>
          <input type="date" id="new-membership-expires" class="input-field">
        </div>
      </div>
      <button class="btn-primary" id="btn-add-student" style="margin-top:12px;width:100%">
        Agregar estudiante
      </button>
    </div>

    <!-- Right column: collapsible student list -->
    <div style="flex:1;min-width:280px">
      <details class="students-collapse" open>
        <summary>
          <span class="collapse-label">
            <span class="collapse-icon">👥</span>
            <span id="collapse-count"><?= count($students) ?> estudiante<?= count($students) != 1 ? 's' : '' ?> registrado<?= count($students) != 1 ? 's' : '' ?></span>
          </span>
          <span class="collapse-arrow">›</span>
        </summary>
        <div class="collapse-body">
          <div style="margin-bottom:14px">
            <input type="text" id="search-students" class="input-field" placeholder="🔍 Buscar estudiante…">
          </div>
          <div id="students-grid" class="students-grid">
            <?php foreach ($students as $s):
                  $g = $s['gender'] ?? ''; ?>
            <div class="student-card" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>">
              <div style="flex:1">
                <div class="s-name"><?= htmlspecialchars($s['name']) ?></div>
                <div class="s-meta">
                  <?php if ($g): ?>
                    <span class="badge gender-<?= $g ?>"><?= $genderLabel[$g] ?></span>
                  <?php endif; ?>
                  <?php if ($s['category']): ?>
                    <span class="badge"><?= htmlspecialchars($s['category']) ?></span>
                  <?php endif; ?>
                  <?php if ($s['phone']): ?>
                    <span class="badge" style="color:var(--text-muted)">📞 <?= htmlspecialchars($s['phone']) ?></span>
                  <?php endif; ?>
                  <?php
                    $ms = $s['membership_status'] ?? 'active';
                    $me = $s['membership_expires'] ?? null;
                    $meExpired = $me && $me < date('Y-m-d');
                    if ($ms === 'expired' || $meExpired):
                  ?><span class="badge red">Membresía vencida</span><?php
                    elseif ($ms === 'courtesy'):
                  ?><span class="badge">Cortesía</span><?php
                    elseif ($me):
                  ?><span class="badge green" style="font-size:0.67rem">✓ <?= date('d/m/Y', strtotime($me)) ?></span><?php
                    endif;
                  ?>
                  <?php if (!($s['active'] ?? 1)): ?>
                    <span class="badge red">Inactivo</span>
                  <?php endif; ?>
                </div>
              </div>
              <button class="btn-danger btn-del-student" title="Eliminar de base de datos">×</button>
            </div>
            <?php endforeach; ?>
            <?php if (empty($students)): ?>
              <div class="empty-state" style="grid-column:1/-1">
                No hay estudiantes registrados aún.<br>
                <small>Agrégalos con el formulario de la izquierda.</small>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </details>
    </div>

  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const CURRENT_PROGRAM = <?= $currentProgram ?>;

document.getElementById('btn-add-student').addEventListener('click', async () => {
  const name = document.getElementById('new-name').value.trim();
  if (!name) { toast('El nombre es requerido', 'error'); return; }
  try {
    const res = await api('api/students.php', 'POST', {
      name,
      gender:              document.getElementById('new-gender').value,
      category:            document.getElementById('new-category').value.trim(),
      phone:               document.getElementById('new-phone').value.trim(),
      membership_status:   document.getElementById('new-membership-status').value,
      membership_expires:  document.getElementById('new-membership-expires').value || null,
    });
    toast('Estudiante agregado', 'success');
    const card = buildStudentCard(res.student_id, name.toUpperCase(),
      document.getElementById('new-gender').value,
      document.getElementById('new-category').value.trim(),
      document.getElementById('new-phone').value.trim()
    );
    document.getElementById('students-grid').prepend(card);
    ['new-name','new-gender','new-category','new-phone'].forEach(id => {
      const el = document.getElementById(id);
      if (el.tagName === 'SELECT') el.selectedIndex = 0;
      else el.value = '';
    });
  } catch (e) { toast(e.message, 'error'); }
});

function buildStudentCard(id, name, gender, category, phone) {
  const card = document.createElement('div');
  card.className = 'student-card';
  card.dataset.id   = id;
  card.dataset.name = name;

  const genderMap = { M: 'Hombre', F: 'Mujer', '': '' };
  let badges = '';
  if (gender)   badges += `<span class="badge gender-${gender}">${genderMap[gender] || ''}</span>`;
  if (category) badges += `<span class="badge">${category}</span>`;
  if (phone)    badges += `<span class="badge" style="color:var(--text-muted)">📞 ${phone}</span>`;

  card.innerHTML = `
    <div style="flex:1">
      <div class="s-name">${name}</div>
      <div class="s-meta">${badges}</div>
    </div>
    <button class="btn-danger btn-del-student" title="Dar de baja">×</button>
  `;
  attachDelete(card.querySelector('.btn-del-student'), card);
  return card;
}

function attachDelete(btn, card) {
  btn.addEventListener('click', async () => {
    if (!confirm(`¿Dar de baja a ${card.dataset.name}?`)) return;
    try {
      await api('api/students.php', 'DELETE', { id: parseInt(card.dataset.id) });
      card.remove();
      toast('Estudiante dado de baja');
    } catch (e) { toast(e.message, 'error'); }
  });
}
document.querySelectorAll('.btn-del-student').forEach(btn => {
  attachDelete(btn, btn.closest('.student-card'));
});

document.getElementById('search-students').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.student-card').forEach(c => {
    c.style.display = c.dataset.name.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>
