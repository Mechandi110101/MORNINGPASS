<?php
require_once __DIR__ . '/includes/functions.php';

$professors = getProfessors();
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : ($professors[0]['id'] ?? 0);
$weekStart  = isset($_GET['week']) ? $_GET['week'] : getWeekStart();

// Validate / normalize week to Monday
$dt = new DateTime($weekStart);
$wd = (int)$dt->format('N');
$dt->modify('-' . ($wd - 1) . ' days');
$weekStart = $dt->format('Y-m-d');

$prevWeek = (clone $dt)->modify('-7 days')->format('Y-m-d');
$nextWeek = (clone $dt)->modify('+7 days')->format('Y-m-d');
$weekEnd  = (clone $dt)->modify('+4 days')->format('Y-m-d');

$dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];

$schedule = [];
$selectedProf = null;
if ($selectedId) {
    $schedule     = getBookingsForProfessor($selectedId, $weekStart);
    foreach ($professors as $p) {
        if ($p['id'] == $selectedId) { $selectedProf = $p; break; }
    }
}

// Group by day
$byDay = [];
foreach ($schedule as $slot) {
    $byDay[$slot['day_of_week']][] = $slot;
}
for ($d = 1; $d <= 5; $d++) {
    if (!isset($byDay[$d])) $byDay[$d] = [];
    usort($byDay[$d], fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
}
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

<header class="site-header">
  <h1>🎓 Morning Pass</h1>
  <nav>
    <a href="index.php">Horario Semanal</a>
    <a href="professor.php" class="active">Por Profesor</a>
    <a href="students.php">Estudiantes</a>
    <a href="admin/slots.php">Admin Horarios</a>
  </nav>
</header>

<div class="page">
  <div class="page-title">Horario por Profesor</div>

  <!-- Professor selector -->
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
    <?php foreach ($professors as $p): ?>
      <a href="professor.php?id=<?= $p['id'] ?>&week=<?= $weekStart ?>"
         class="prof-chip<?= ($p['id'] == $selectedId ? '' : ' inactive') ?>"
         style="background:<?= htmlspecialchars($p['color_hex']) ?>;text-decoration:none">
        <?= htmlspecialchars($p['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Week navigation -->
  <div class="week-nav" style="margin-bottom:20px">
    <a href="professor.php?id=<?= $selectedId ?>&week=<?= $prevWeek ?>" style="text-decoration:none">
      <button>&#8592; Anterior</button>
    </a>
    <span class="week-label">
      Semana: <?= date('d/m/Y', strtotime($weekStart)) ?> – <?= date('d/m/Y', strtotime($weekEnd)) ?>
    </span>
    <a href="professor.php?id=<?= $selectedId ?>&week=<?= getWeekStart() ?>" style="text-decoration:none">
      <button>Hoy</button>
    </a>
    <a href="professor.php?id=<?= $selectedId ?>&week=<?= $nextWeek ?>" style="text-decoration:none">
      <button>Siguiente &#8594;</button>
    </a>
  </div>

  <?php if ($selectedProf): ?>
  <div style="margin-bottom:16px;display:flex;align-items:center;gap:10px">
    <div style="width:14px;height:14px;border-radius:50%;background:<?= htmlspecialchars($selectedProf['color_hex']) ?>"></div>
    <span style="font-weight:700;font-size:1.1rem"><?= htmlspecialchars($selectedProf['name']) ?></span>
    <span style="color:var(--text-muted);font-size:0.85rem">
      <?= count($schedule) ?> bloque(s) esta semana
    </span>
  </div>

  <div class="prof-schedule-grid">
    <?php for ($d = 1; $d <= 5; $d++): ?>
    <div class="prof-day-col">
      <h3><?= $dayNames[$d] ?><br>
          <small style="font-weight:400;opacity:0.8">
            <?= date('d/m', strtotime($weekStart . ' +' . ($d - 1) . ' days')) ?>
          </small>
      </h3>
      <?php if (empty($byDay[$d])): ?>
        <div style="padding:10px;text-align:center;color:var(--text-muted);font-size:0.8rem;background:var(--white);border:1px solid var(--beige-border);border-radius:4px;margin-top:2px">
          Sin clases
        </div>
      <?php else: ?>
        <?php foreach ($byDay[$d] as $slot): ?>
          <?php $booked = count($slot['bookings']); $max = $slot['max_students']; ?>
          <div class="prof-slot-block" style="border-left:4px solid <?= htmlspecialchars($selectedProf['color_hex']) ?>">
            <div class="prof-slot-time">
              <?= formatTime($slot['start_time']) ?> – <?= formatTime($slot['end_time']) ?>
            </div>
            <div class="prof-slot-name"><?= htmlspecialchars($slot['class_name'] ?: '—') ?></div>
            <div style="font-size:0.7rem;color:var(--text-muted);margin-bottom:5px">
              <?= $booked ?>/<?= $max ?> estudiantes
              <?php if ($slot['class_type']): ?>
                · <span style="background:var(--beige-dark);border-radius:3px;padding:1px 4px"><?= htmlspecialchars($slot['class_type']) ?></span>
              <?php endif; ?>
            </div>
            <div class="prof-slot-students">
              <?php if (empty($slot['bookings'])): ?>
                <div style="color:var(--text-muted);font-style:italic;font-size:0.7rem">Sin estudiantes</div>
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
    <p style="color:var(--text-muted)">Selecciona un profesor para ver su horario.</p>
  <?php endif; ?>
</div>

</body>
</html>
