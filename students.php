<?php
require_once __DIR__ . '/includes/functions.php';
$students = getStudents();
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

<header class="site-header">
  <h1>🎓 Morning Pass</h1>
  <nav>
    <a href="index.php">Horario Semanal</a>
    <a href="professor.php">Por Profesor</a>
    <a href="students.php" class="active">Estudiantes</a>
    <a href="admin/slots.php">Admin Horarios</a>
  </nav>
</header>

<div class="page">
  <div class="page-title">Estudiantes</div>
  <div class="page-sub"><?= count($students) ?> estudiantes registrados</div>

  <!-- Add student form -->
  <div style="background:var(--white);border:1px solid var(--beige-border);border-radius:10px;padding:16px;max-width:400px;margin-bottom:20px;box-shadow:var(--shadow)">
    <h3 style="font-size:0.9rem;color:var(--brown);margin-bottom:10px">Agregar nuevo estudiante</h3>
    <div style="display:flex;gap:8px">
      <input type="text" id="new-student-name" class="input-search" placeholder="Nombre completo" style="flex:1">
      <button class="btn-primary" id="btn-add-student">Agregar</button>
    </div>
  </div>

  <!-- Search -->
  <div style="max-width:340px;margin-bottom:16px">
    <input type="text" id="search-students" class="input-search" placeholder="Buscar estudiante…">
  </div>

  <!-- Grid -->
  <div id="students-grid" class="students-grid">
    <?php foreach ($students as $s): ?>
    <div class="student-card" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>">
      <span><?= htmlspecialchars($s['name']) ?></span>
      <button class="del-booking btn-del-student" title="Eliminar" style="color:var(--danger);background:none;border:none;cursor:pointer;font-size:1rem">×</button>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
document.getElementById('btn-add-student').addEventListener('click', async () => {
  const name = document.getElementById('new-student-name').value.trim();
  if (!name) return;
  try {
    const res = await api('api/students.php', 'POST', { name });
    toast('Estudiante agregado', 'success');
    const grid = document.getElementById('students-grid');
    const card = document.createElement('div');
    card.className = 'student-card';
    card.dataset.id = res.student_id;
    card.dataset.name = name.toUpperCase();
    card.innerHTML = `<span>${name.toUpperCase()}</span><button class="btn-del-student" style="color:var(--danger);background:none;border:none;cursor:pointer;font-size:1rem">×</button>`;
    grid.prepend(card);
    document.getElementById('new-student-name').value = '';
    attachDelete(card.querySelector('.btn-del-student'), card);
  } catch (e) { toast(e.message, 'error'); }
});

function attachDelete(btn, card) {
  btn.addEventListener('click', async () => {
    if (!confirm(`¿Eliminar a ${card.dataset.name}?`)) return;
    try {
      await api('api/students.php', 'DELETE', { id: parseInt(card.dataset.id) });
      card.remove();
      toast('Estudiante eliminado');
    } catch (e) { toast(e.message, 'error'); }
  });
}

document.querySelectorAll('.btn-del-student').forEach(btn => {
  attachDelete(btn, btn.closest('.student-card'));
});

document.getElementById('search-students').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.student-card').forEach(card => {
    card.style.display = card.dataset.name.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>
