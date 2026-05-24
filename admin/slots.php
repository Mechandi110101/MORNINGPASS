<?php
require_once __DIR__ . '/../includes/functions.php';
$professors = getProfessors();
$db = getDB();

$dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];
$classTypes = ['', 'MIXTO', 'FEM', 'MASC', 'CAT', 'STA'];

$slots = $db->query("
    SELECT ts.*, p.name AS professor_name, p.color_hex
    FROM time_slots ts
    JOIN professors p ON p.id = ts.professor_id
    WHERE ts.active = 1
    ORDER BY ts.day_of_week, ts.start_time, p.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Admin Horarios</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header">
  <h1>🎓 Morning Pass</h1>
  <nav>
    <a href="../index.php">Horario Semanal</a>
    <a href="../professor.php">Por Profesor</a>
    <a href="../students.php">Estudiantes</a>
    <a href="slots.php" class="active">Admin Horarios</a>
  </nav>
</header>

<div class="page">
  <div class="page-title">Administrar Horarios</div>
  <div class="page-sub">Agregar, editar o eliminar bloques de horario de cada profesor.</div>

  <!-- Add slot form -->
  <div style="background:var(--white);border:1px solid var(--beige-border);border-radius:10px;padding:20px;max-width:640px;margin-bottom:24px;box-shadow:var(--shadow)">
    <h3 style="font-size:0.95rem;color:var(--brown);margin-bottom:14px">Agregar nuevo horario</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div>
        <label style="font-size:0.78rem;font-weight:700;color:var(--brown);display:block;margin-bottom:3px">Profesor</label>
        <select id="new-prof" class="select-student">
          <?php foreach ($professors as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:0.78rem;font-weight:700;color:var(--brown);display:block;margin-bottom:3px">Día</label>
        <select id="new-day" class="select-student">
          <?php foreach ($dayNames as $num => $name): ?>
            <option value="<?= $num ?>"><?= $name ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:0.78rem;font-weight:700;color:var(--brown);display:block;margin-bottom:3px">Hora inicio</label>
        <input type="time" id="new-start" class="input-search" value="07:00">
      </div>
      <div>
        <label style="font-size:0.78rem;font-weight:700;color:var(--brown);display:block;margin-bottom:3px">Hora fin</label>
        <input type="time" id="new-end" class="input-search" value="08:00">
      </div>
      <div>
        <label style="font-size:0.78rem;font-weight:700;color:var(--brown);display:block;margin-bottom:3px">Nombre de clase</label>
        <input type="text" id="new-classname" class="input-search" placeholder="Ej: CAT 5TA JEAN">
      </div>
      <div>
        <label style="font-size:0.78rem;font-weight:700;color:var(--brown);display:block;margin-bottom:3px">Tipo</label>
        <select id="new-classtype" class="select-student">
          <?php foreach ($classTypes as $ct): ?>
            <option value="<?= $ct ?>"><?= $ct ?: '— ninguno —' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:0.78rem;font-weight:700;color:var(--brown);display:block;margin-bottom:3px">Máx. estudiantes</label>
        <input type="number" id="new-max" class="input-search" value="4" min="1" max="20">
      </div>
    </div>
    <button class="btn-primary" id="btn-add-slot" style="margin-top:14px">Agregar horario</button>
  </div>

  <!-- Slots table -->
  <div class="scroll-x">
    <table class="slots-table" id="slots-table">
      <thead>
        <tr>
          <th>Día</th>
          <th>Profesor</th>
          <th>Hora inicio</th>
          <th>Hora fin</th>
          <th>Nombre clase</th>
          <th>Tipo</th>
          <th>Máx.</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($slots as $slot): ?>
        <tr id="slot-row-<?= $slot['id'] ?>">
          <td><?= $dayNames[$slot['day_of_week']] ?></td>
          <td>
            <span style="display:inline-flex;align-items:center;gap:5px">
              <span style="width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($slot['color_hex']) ?>;display:inline-block"></span>
              <?= htmlspecialchars($slot['professor_name']) ?>
            </span>
          </td>
          <td><?= formatTime($slot['start_time']) ?></td>
          <td><?= formatTime($slot['end_time']) ?></td>
          <td><?= htmlspecialchars($slot['class_name']) ?></td>
          <td><?= htmlspecialchars($slot['class_type']) ?></td>
          <td><?= $slot['max_students'] ?></td>
          <td>
            <button class="del-booking btn-del-slot" data-id="<?= $slot['id'] ?>">Eliminar</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
document.getElementById('btn-add-slot').addEventListener('click', async () => {
  const body = {
    professor_id: parseInt(document.getElementById('new-prof').value),
    day_of_week:  parseInt(document.getElementById('new-day').value),
    start_time:   document.getElementById('new-start').value,
    end_time:     document.getElementById('new-end').value,
    class_name:   document.getElementById('new-classname').value.trim(),
    class_type:   document.getElementById('new-classtype').value,
    max_students: parseInt(document.getElementById('new-max').value) || 4,
  };
  try {
    await api('../api/slots.php', 'POST', body);
    toast('Horario agregado', 'success');
    setTimeout(() => location.reload(), 800);
  } catch (e) { toast(e.message, 'error'); }
});

document.querySelectorAll('.btn-del-slot').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('¿Eliminar este horario? También se eliminarán sus reservas.')) return;
    try {
      await api('../api/slots.php', 'DELETE', { slot_id: parseInt(btn.dataset.id) });
      document.getElementById('slot-row-' + btn.dataset.id)?.remove();
      toast('Horario eliminado');
    } catch (e) { toast(e.message, 'error'); }
  });
});
</script>
</body>
</html>
