<?php
require_once __DIR__ . '/../includes/functions.php';
$basePath       = '../';
$currentProgram = (int)($_GET['p'] ?? 1);
$programs       = getPrograms();
$professors     = getProfessors();
$db             = getDB();

$dayNames   = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];
$currentProg = null;
foreach ($programs as $pg) {
    if ($pg['id'] == $currentProgram) { $currentProg = $pg; break; }
}

$slots = $db->prepare("
    SELECT ts.*, p.name AS professor_name, p.color_hex,
           prog.name AS program_name, prog.color_hex AS program_color, prog.icon AS program_icon,
           (SELECT COUNT(*) FROM enrollments e WHERE e.time_slot_id=ts.id AND e.status='active') AS enrolled
    FROM time_slots ts
    JOIN professors p    ON p.id    = ts.professor_id
    JOIN programs   prog ON prog.id = ts.program_id
    WHERE ts.active = 1 AND ts.program_id = ?
    ORDER BY ts.day_of_week, ts.start_time, p.name
");
$slots->execute([$currentProgram]);
$slots = $slots->fetchAll();
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

<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="page">
  <div class="page-title">
    <?= $currentProg ? $currentProg['icon'] . ' ' : '' ?>Admin Horarios
    <?php if ($currentProg): ?>
      <span style="font-size:0.75rem;font-weight:400;color:var(--text-muted)">
        — <?= htmlspecialchars($currentProg['name']) ?>
      </span>
    <?php endif; ?>
  </div>
  <div class="page-sub">Crear, modificar o eliminar bloques de horario. Los cambios se reflejan automáticamente en todas las semanas.</div>

  <!-- Add slot form -->
  <div class="form-card" style="max-width:700px">
    <h3>Agregar nuevo horario</h3>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Programa *</label>
        <select id="new-program" class="select-field">
          <?php foreach ($programs as $pg): ?>
            <option value="<?= $pg['id'] ?>" <?= $pg['id'] == $currentProgram ? 'selected' : '' ?>>
              <?= $pg['icon'] ?> <?= htmlspecialchars($pg['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Profesor *</label>
        <select id="new-prof" class="select-field">
          <?php foreach ($professors as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Día *</label>
        <select id="new-day" class="select-field">
          <?php foreach ($dayNames as $num => $name): ?>
            <option value="<?= $num ?>"><?= $name ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Hora inicio *</label>
        <input type="time" id="new-start" class="input-field" value="07:00">
      </div>
      <div class="form-group">
        <label class="form-label">Hora fin *</label>
        <input type="time" id="new-end" class="input-field" value="08:00">
      </div>
      <div class="form-group">
        <label class="form-label">Fecha inicio grupo</label>
        <input type="date" id="new-startdate" class="input-field">
      </div>
      <div class="form-group">
        <label class="form-label">Fecha fin grupo (opcional)</label>
        <input type="date" id="new-enddate" class="input-field">
      </div>
      <div class="form-group full">
        <label class="form-label">Nombre de la clase</label>
        <input type="text" id="new-classname" class="input-field" placeholder="Ej: CAT 5TA JEAN">
      </div>
      <div class="form-group">
        <label class="form-label">Máx. estudiantes</label>
        <input type="number" id="new-max" class="input-field" value="4" min="1" max="20">
      </div>
      <div class="form-group">
        <label class="form-label">Estado inicial del grupo</label>
        <select id="new-slot-status" class="select-field">
          <option value="active">✅ Activo — acepta inscripciones</option>
          <option value="pending">⏳ Pendiente — grupo en planificación</option>
          <option value="closed">🔒 Cerrado — no acepta inscripciones</option>
        </select>
      </div>
    </div>
    <button class="btn-primary" id="btn-add-slot" style="margin-top:14px">Agregar horario</button>
  </div>

  <!-- Slots table -->
  <div class="scroll-x">
    <table class="data-table" id="slots-table">
      <thead>
        <tr>
          <th>Día</th>
          <th>Hora</th>
          <th>Profesor</th>
          <th>Clase</th>
          <th>Inscritos</th>
          <th>Inicio grupo</th>
          <th>Fin grupo</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($slots as $slot):
          $slotJson = htmlspecialchars(json_encode([
            'id'           => $slot['id'],
            'program_id'   => $slot['program_id'],
            'professor_id' => $slot['professor_id'],
            'day_of_week'  => $slot['day_of_week'],
            'start_time'   => $slot['start_time'],
            'end_time'     => $slot['end_time'],
            'class_name'   => $slot['class_name'],
            'class_type'   => $slot['class_type'],
            'max_students' => $slot['max_students'],
            'notes'        => $slot['notes'] ?? '',
            'start_date'   => $slot['start_date'],
            'end_date'     => $slot['end_date'],
            'slot_status'  => $slot['slot_status'] ?? 'active',
            'enrolled'     => $slot['enrolled'],
          ]), ENT_QUOTES);
        ?>
        <tr id="slot-row-<?= $slot['id'] ?>" data-slot='<?= $slotJson ?>'>
          <td><?= $dayNames[$slot['day_of_week']] ?></td>
          <td class="td-hora" style="white-space:nowrap">
            <?= formatTime($slot['start_time']) ?> – <?= formatTime($slot['end_time']) ?>
          </td>
          <td>
            <span style="display:inline-flex;align-items:center;gap:5px">
              <span class="color-dot" style="background:<?= htmlspecialchars($slot['color_hex']) ?>"></span>
              <?= htmlspecialchars($slot['professor_name']) ?>
            </span>
          </td>
          <td class="td-class"><?= htmlspecialchars($slot['class_name']) ?></td>
          <td class="td-max">
            <span style="font-weight:700;color:<?= $slot['enrolled'] >= $slot['max_students'] ? 'var(--red)' : 'var(--success)' ?>">
              <?= $slot['enrolled'] ?>/<?= $slot['max_students'] ?>
            </span>
          </td>
          <td style="font-size:0.78rem"><?= $slot['start_date'] ? date('d/m/Y', strtotime($slot['start_date'])) : '—' ?></td>
          <td style="font-size:0.78rem"><?= $slot['end_date']   ? date('d/m/Y', strtotime($slot['end_date']))   : '∞' ?></td>
          <td class="td-actions">
            <?php $st = $slot['slot_status'] ?? 'active'; ?>
            <button class="btn-secondary btn-edit-slot" data-id="<?= $slot['id'] ?>"
                    style="font-size:0.72rem;padding:5px 8px;margin-right:2px" title="Editar">✏️</button>
            <?php if ($st !== 'active'): ?>
              <button class="btn-primary btn-activate-slot" data-id="<?= $slot['id'] ?>"
                      style="font-size:0.72rem;padding:5px 10px;margin-right:4px">
                ▶ Activar
              </button>
            <?php else: ?>
              <button class="btn-secondary btn-close-slot" data-id="<?= $slot['id'] ?>"
                      style="font-size:0.72rem;padding:5px 10px;margin-right:4px">
                🔒 Cerrar
              </button>
            <?php endif; ?>
            <button class="btn-danger btn-del-slot" data-id="<?= $slot['id'] ?>">×</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($slots)): ?>
        <tr><td colspan="8" class="empty-state">No hay horarios en este programa. Agrega el primero arriba.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
const CURRENT_PROGRAM = <?= $currentProgram ?>;

document.getElementById('btn-add-slot').addEventListener('click', async () => {
  const body = {
    program_id:   parseInt(document.getElementById('new-program').value),
    professor_id: parseInt(document.getElementById('new-prof').value),
    day_of_week:  parseInt(document.getElementById('new-day').value),
    start_time:   document.getElementById('new-start').value,
    end_time:     document.getElementById('new-end').value,
    class_name:   document.getElementById('new-classname').value.trim(),
    max_students: parseInt(document.getElementById('new-max').value) || 4,
    start_date:   document.getElementById('new-startdate').value || null,
    end_date:     document.getElementById('new-enddate').value   || null,
    slot_status:  document.getElementById('new-slot-status').value,
  };
  try {
    await api('../api/slots.php', 'POST', body);
    toast('Horario agregado', 'success');
    setTimeout(() => location.reload(), 700);
  } catch (e) { toast(e.message, 'error'); }
});

document.querySelectorAll('.btn-del-slot').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('¿Eliminar este horario? Se eliminarán todas las inscripciones asociadas.')) return;
    try {
      await api('../api/slots.php', 'DELETE', { slot_id: parseInt(btn.dataset.id) });
      document.getElementById('slot-row-' + btn.dataset.id)?.remove();
      toast('Horario eliminado');
    } catch (e) { toast(e.message, 'error'); }
  });
});

// Activate group
document.querySelectorAll('.btn-activate-slot').forEach(btn => {
  btn.addEventListener('click', async () => {
    try {
      await api('../api/slots.php', 'PUT', { slot_id: parseInt(btn.dataset.id), action: 'activate' });
      toast('Grupo activado ✅', 'success');
      setTimeout(() => location.reload(), 500);
    } catch (e) { toast(e.message, 'error'); }
  });
});

// Close group
document.querySelectorAll('.btn-close-slot').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('¿Cerrar este grupo? Los estudiantes inscritos se mantendrán pero no se podrán agregar más.')) return;
    try {
      await api('../api/slots.php', 'PUT', { slot_id: parseInt(btn.dataset.id), action: 'close' });
      toast('Grupo cerrado 🔒');
      setTimeout(() => location.reload(), 500);
    } catch (e) { toast(e.message, 'error'); }
  });
});

// ── Inline edit ───────────────────────────────────────────
function fmtT(t) {
  if (!t) return '';
  const [h, m] = t.split(':').map(Number);
  return `${h % 12 || 12}:${String(m).padStart(2,'0')} ${h >= 12 ? 'PM' : 'AM'}`;
}

document.querySelectorAll('.btn-edit-slot').forEach(btn => {
  btn.addEventListener('click', () => {
    const row  = document.getElementById('slot-row-' + btn.dataset.id);
    const slot = JSON.parse(row.dataset.slot);

    const tdHora  = row.querySelector('.td-hora');
    const tdClass = row.querySelector('.td-class');
    const tdMax   = row.querySelector('.td-max');
    const tdAct   = row.querySelector('.td-actions');

    // Save original HTML
    const origHora  = tdHora.innerHTML;
    const origClass = tdClass.innerHTML;
    const origMax   = tdMax.innerHTML;

    // Inline inputs
    const inf = (type, val, w, extra='') =>
      `<input type="${type}" class="input-field" style="width:${w};padding:4px 5px;font-size:0.78rem" value="${val}" ${extra}>`;

    tdHora.innerHTML  = inf('time', slot.start_time.slice(0,5), '88px') + ' – ' + inf('time', slot.end_time.slice(0,5), '88px');
    tdClass.innerHTML = inf('text', slot.class_name.replace(/"/g,'&quot;'), '130px');
    tdMax.innerHTML   = inf('number', slot.max_students, '52px', 'min="1" max="20"');

    // Swap buttons
    btn.style.display = 'none';
    const saveBtn = Object.assign(document.createElement('button'), {
      className: 'btn-primary',
      textContent: '✓',
      title: 'Guardar',
      style: { cssText: 'font-size:0.72rem;padding:5px 8px;margin-right:2px' },
    });
    const cancelBtn = Object.assign(document.createElement('button'), {
      className: 'btn-secondary',
      textContent: '✕',
      title: 'Cancelar',
      style: { cssText: 'font-size:0.72rem;padding:5px 8px;margin-right:2px' },
    });
    saveBtn.style.cssText   = 'font-size:0.72rem;padding:5px 8px;margin-right:2px';
    cancelBtn.style.cssText = 'font-size:0.72rem;padding:5px 8px;margin-right:2px';
    tdAct.prepend(cancelBtn);
    tdAct.prepend(saveBtn);

    const restore = () => {
      tdHora.innerHTML  = origHora;
      tdClass.innerHTML = origClass;
      tdMax.innerHTML   = origMax;
      saveBtn.remove(); cancelBtn.remove();
      btn.style.display = '';
    };

    cancelBtn.onclick = restore;

    saveBtn.onclick = async () => {
      const times      = tdHora.querySelectorAll('input');
      const newStart   = times[0].value;
      const newEnd     = times[1].value;
      const newClass   = tdClass.querySelector('input').value.trim();
      const newMax     = parseInt(tdMax.querySelector('input').value) || slot.max_students;
      try {
        await api('../api/slots.php', 'PUT', {
          slot_id:      slot.id,
          program_id:   slot.program_id,
          professor_id: slot.professor_id,
          day_of_week:  slot.day_of_week,
          start_time:   newStart,
          end_time:     newEnd,
          class_name:   newClass,
          class_type:   slot.class_type,
          max_students: newMax,
          notes:        slot.notes,
          start_date:   slot.start_date,
          end_date:     slot.end_date,
          slot_status:  slot.slot_status,
        });
        // Update stored data
        Object.assign(slot, { start_time: newStart+':00', end_time: newEnd+':00', class_name: newClass, max_students: newMax });
        row.dataset.slot = JSON.stringify(slot);
        // Restore with new values
        tdHora.innerHTML  = `${fmtT(newStart)} – ${fmtT(newEnd)}`;
        tdClass.innerHTML = newClass;
        const color = newMax > slot.enrolled ? 'var(--success)' : 'var(--red)';
        tdMax.innerHTML   = `<span style="font-weight:700;color:${color}">${slot.enrolled}/${newMax}</span>`;
        saveBtn.remove(); cancelBtn.remove();
        btn.style.display = '';
        toast('Cambios guardados ✓', 'success');
      } catch (e) { toast(e.message, 'error'); }
    };
  });
});
</script>
</body>
</html>
