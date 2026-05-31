<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$basePath       = '';
$currentProgram = (int)($_GET['p']  ?? 1);
$selectedId     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$professors = getProfessors();
$programs   = getPrograms();
if (!$selectedId && $professors) $selectedId = $professors[0]['id'];

$dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];

$schedule    = $selectedId ? getEnrollmentsForProfessor($selectedId, $currentProgram) : [];
$selectedProf = null;
foreach ($professors as $p) {
    if ($p['id'] == $selectedId) { $selectedProf = $p; break; }
}

$currentProg = null;
foreach ($programs as $pg) {
    if ($pg['id'] == $currentProgram) { $currentProg = $pg; break; }
}

// Group by day
$byDay = [];
foreach ($schedule as $slot) { $byDay[$slot['day_of_week']][] = $slot; }
for ($d = 1; $d <= 5; $d++) {
    if (!isset($byDay[$d])) $byDay[$d] = [];
    usort($byDay[$d], fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
}

$totalSlots    = count($schedule);
$activeSlots   = count(array_filter($schedule, fn($s) => ($s['slot_status'] ?? 'active') === 'active'));
$totalStudents = array_sum(array_map(fn($s) => count($s['bookings']), $schedule));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Profesor</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page">
  <div class="page-title">
    <?= $currentProg ? $currentProg['icon'] . ' ' : '' ?>Por Profesor
  </div>
  <div class="page-sub">Vista de horarios por profesor — incluye grupos activos, pendientes y cerrados.</div>

  <!-- Professor selector -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
    <span style="font-size:0.78rem;color:var(--text-muted);font-weight:700;margin-right:4px">PROFESOR:</span>
    <?php foreach ($professors as $p): ?>
      <a href="professor.php?id=<?= $p['id'] ?>&p=<?= $currentProgram ?>"
         class="prof-chip<?= ($p['id'] == $selectedId ? '' : ' inactive') ?>"
         style="background:<?= htmlspecialchars($p['color_hex']) ?>">
        <?= htmlspecialchars($p['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($selectedProf): ?>

  <!-- Stats -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-num"><?= $activeSlots ?> <span style="font-size:1rem;color:var(--text-muted)">/ <?= $totalSlots ?></span></div>
      <div class="stat-label">Grupos activos / total</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $totalStudents ?></div>
      <div class="stat-label">Estudiantes inscritos</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--blue)"><?= htmlspecialchars($selectedProf['name']) ?></div>
      <div class="stat-label">Profesor seleccionado</div>
    </div>
  </div>

  <!-- Weekly grid -->
  <div class="prof-schedule-grid">
    <?php for ($d = 1; $d <= 5; $d++): ?>
    <div class="prof-day-col">
      <h3><?= $dayNames[$d] ?></h3>

      <?php if (empty($byDay[$d])): ?>
        <div style="padding:12px;text-align:center;color:var(--text-muted);font-size:0.78rem;
                    background:var(--white);border:1px solid var(--blue-border);margin-top:2px;border-radius:0 0 4px 4px">
          Sin grupos
        </div>
      <?php else: ?>
        <?php foreach ($byDay[$d] as $slot):
              $booked = count($slot['bookings']); $max = $slot['max_students'];
              $st = $slot['slot_status'] ?? 'active';
              $stClass = $st !== 'active' ? 'status-' . $st : '';
              $stLabels = ['pending' => 'Pendiente', 'closed' => 'Cerrado'];
      ?>
        <div class="prof-slot-block <?= $stClass ?>"
             style="border-left-color:<?= $st === 'active' ? htmlspecialchars($selectedProf['color_hex']) : ($st === 'pending' ? '#e6960a' : '#9eabb0') ?>">
          <?php if (isset($stLabels[$st])): ?>
            <span class="prof-slot-status <?= $st ?>"><?= $stLabels[$st] ?></span>
          <?php endif; ?>
          <div class="prof-slot-time">
            <?= formatTime($slot['start_time']) ?> – <?= formatTime($slot['end_time']) ?>
          </div>
          <div class="prof-slot-name"><?= htmlspecialchars($slot['class_name'] ?: '—') ?></div>
          <div style="display:flex;gap:5px;align-items:center;margin-bottom:6px">
            <span style="font-size:0.68rem;color:var(--text-muted);font-weight:700"><?= $booked ?>/<?= $max ?></span>
            <?php if ($booked >= $max && $st === 'active'): ?>
              <span class="badge red">LLENO</span>
            <?php endif; ?>
          </div>
          <div class="prof-slot-students">
            <?php if (empty($slot['bookings'])): ?>
              <div style="color:var(--text-muted);font-style:italic;font-size:0.7rem">Sin inscripciones</div>
            <?php else: ?>
              <?php foreach ($slot['bookings'] as $b): ?>
                <div class="prof-student-name"><?= htmlspecialchars($b['student_name']) ?></div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endfor; ?>
  </div>

  <?php else: ?>
    <div class="empty-state">Selecciona un profesor para ver su horario.</div>
  <?php endif; ?>
</div>

</body>
</html>
