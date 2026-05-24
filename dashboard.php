<?php
require_once __DIR__ . '/includes/functions.php';
$basePath       = '';
$currentProgram = (int)($_GET['p'] ?? 1);
$programs       = getPrograms();
$db             = getDB();

$currentProg = null;
foreach ($programs as $pg) {
    if ($pg['id'] == $currentProgram) { $currentProg = $pg; break; }
}

$dayNames     = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];
$todayDowRaw  = (int)date('N'); // 1=Mon … 7=Sun
$isWeekend    = $todayDowRaw > 5;
$todayDow     = $isWeekend ? 1 : $todayDowRaw; // Weekend: show Monday
$weekStart    = date('Y-m-d', strtotime('monday this week'));
$weekEnd      = date('Y-m-d', strtotime('sunday this week'));
$monthEnd     = date('Y-m-t');
$nextMonthEnd = date('Y-m-t', strtotime('first day of next month'));

// ── Stats ────────────────────────────────────────────────

// Full groups
$stmt = $db->prepare("
    SELECT COUNT(*) FROM (
        SELECT ts.id, ts.max_students AS ms, COUNT(e.id) AS ec
        FROM time_slots ts
        LEFT JOIN enrollments e ON e.time_slot_id=ts.id AND e.status='active' AND e.is_trial=0 AND e.is_award=0
        WHERE ts.active=1 AND ts.slot_status='active' AND ts.program_id=?
        GROUP BY ts.id, ts.max_students
        HAVING ec >= ms
    ) sq
");
$stmt->execute([$currentProgram]);
$fullGroups = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM time_slots WHERE active=1 AND slot_status='active' AND program_id=?");
$stmt->execute([$currentProgram]);
$totalActive = (int)$stmt->fetchColumn();

// Available spots today
$stmt = $db->prepare("
    SELECT COALESCE(SUM(ms - ec), 0) FROM (
        SELECT ts.id, ts.max_students AS ms, COUNT(e.id) AS ec
        FROM time_slots ts
        LEFT JOIN enrollments e ON e.time_slot_id=ts.id AND e.status='active' AND e.is_trial=0 AND e.is_award=0
        WHERE ts.active=1 AND ts.slot_status='active' AND ts.program_id=? AND ts.day_of_week=?
        GROUP BY ts.id, ts.max_students
        HAVING ec < ms
    ) sq
");
$stmt->execute([$currentProgram, $todayDow]);
$availableToday = (int)$stmt->fetchColumn();

// Special classes this week
$stmt = $db->prepare("
    SELECT e.is_trial, e.is_award, e.trial_date, e.award_date,
           s.name AS student_name, ts.day_of_week, ts.start_time, ts.class_name,
           p.name AS professor_name, p.color_hex
    FROM enrollments e
    JOIN students    s  ON s.id = e.student_id
    JOIN time_slots  ts ON ts.id = e.time_slot_id AND ts.program_id=?
    JOIN professors  p  ON p.id = ts.professor_id
    WHERE e.status='active'
      AND ((e.is_trial=1 AND e.trial_date  BETWEEN ? AND ?)
        OR (e.is_award=1 AND e.award_date BETWEEN ? AND ?))
    ORDER BY COALESCE(e.trial_date, e.award_date), ts.start_time
");
$stmt->execute([$currentProgram, $weekStart, $weekEnd, $weekStart, $weekEnd]);
$specialClasses = $stmt->fetchAll();

// Active professors
$activeProfessors = (int)$db->query("SELECT COUNT(*) FROM professors WHERE active=1")->fetchColumn();

// Membership alerts
$membershipAlerts = $db->query("
    SELECT s.id, s.name, s.membership_status, s.membership_expires,
           COUNT(e.id) AS enrolled_count
    FROM students s
    LEFT JOIN enrollments e ON e.student_id=s.id AND e.status='active' AND e.is_trial=0 AND e.is_award=0
    WHERE s.active=1
      AND (s.membership_status='expired'
           OR (s.membership_expires IS NOT NULL AND s.membership_expires <= '$monthEnd'))
    GROUP BY s.id
    ORDER BY s.membership_expires ASC, s.name
")->fetchAll();

// Today's schedule
$todaySchedule = [];
if (!$isWeekend) {
    $schedule      = getScheduleForWeek($weekStart, $currentProgram);
    $todaySchedule = array_values($schedule[$todayDow] ?? []);
    usort($todaySchedule, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .dash-section { margin-bottom: 28px; }
    .dash-section-title {
      font-size: 0.72rem; font-weight: 800; text-transform: uppercase;
      letter-spacing: 0.8px; color: var(--text-muted); margin-bottom: 12px;
      display: flex; align-items: center; gap: 8px;
    }
    .dash-section-title::after {
      content: ''; flex: 1; height: 1px; background: var(--blue-border);
    }

    /* Membership alert list */
    .membership-list { display: flex; flex-direction: column; gap: 6px; }
    .membership-item {
      display: flex; align-items: center; gap: 12px;
      background: var(--white); border: 1px solid var(--blue-border);
      border-left: 4px solid var(--red); border-radius: 8px;
      padding: 10px 14px; transition: box-shadow 0.1s;
    }
    .membership-item.warning { border-left-color: #e6960a; }
    .membership-item:hover { box-shadow: var(--shadow); }
    .membership-item .mi-name { font-weight: 700; font-size: 0.88rem; flex: 1; }
    .membership-item .mi-date { font-size: 0.75rem; color: var(--text-muted); }
    .membership-item .mi-slots { font-size: 0.72rem; color: var(--text-muted); }

    /* Today's schedule */
    .today-slots { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 6px; }
    .today-slot-card {
      min-width: 160px; max-width: 200px; background: var(--white);
      border: 1px solid var(--blue-border); border-radius: 10px;
      padding: 12px 14px; box-shadow: var(--shadow); flex-shrink: 0;
      border-top: 4px solid var(--blue);
    }
    .today-slot-time  { font-size: 0.72rem; color: var(--text-muted); font-weight: 700; margin-bottom: 3px; }
    .today-slot-name  { font-size: 0.82rem; font-weight: 700; margin-bottom: 6px; }
    .today-slot-prof  { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; margin-bottom: 6px; }
    .today-slot-count { font-size: 0.75rem; font-weight: 700; }
    .today-slot-count.full  { color: var(--red); }
    .today-slot-count.open  { color: var(--success); }

    /* Special classes list */
    .special-list { display: flex; flex-direction: column; gap: 5px; }
    .special-item {
      display: flex; align-items: center; gap: 10px;
      background: var(--white); border: 1px solid var(--blue-border);
      border-radius: 8px; padding: 8px 14px; font-size: 0.82rem;
    }
    .special-item .si-date { font-size: 0.7rem; color: var(--text-muted); min-width: 80px; }
    .special-item .si-name { font-weight: 700; flex: 1; }
    .special-item .si-slot { font-size: 0.75rem; color: var(--text-muted); }

    /* Stats override for larger number */
    .stat-card-lg .stat-num { font-size: 2.2rem; }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page">
  <div class="page-title">
    <?= $currentProg ? $currentProg['icon'] . ' ' : '' ?>Dashboard
    <?php if ($currentProg): ?>
      <span style="font-size:0.75rem;font-weight:400;color:var(--text-muted)">
        — <?= htmlspecialchars($currentProg['name']) ?>
      </span>
    <?php endif; ?>
  </div>
  <div class="page-sub">
    Resumen del día — <?= !$isWeekend ? $dayNames[$todayDow] . ' ' . date('d/m/Y') : 'Fin de semana — mostrando datos del lunes' ?>
  </div>

  <!-- ── Stats row ─────────────────────────────────── -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-num" style="color:<?= $fullGroups > 0 ? 'var(--red)' : 'var(--success)' ?>">
        <?= $fullGroups ?><span style="font-size:1rem;color:var(--text-muted)"> / <?= $totalActive ?></span>
      </div>
      <div class="stat-label">Grupos llenos / activos</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--success)"><?= $availableToday ?></div>
      <div class="stat-label">Cupos libres hoy</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:<?= count($specialClasses) > 0 ? '#e6960a' : 'var(--text-muted)' ?>">
        <?= count($specialClasses) ?>
      </div>
      <div class="stat-label">Pruebas / premios esta semana</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $activeProfessors ?></div>
      <div class="stat-label">Profesores activos</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:<?= count($membershipAlerts) > 0 ? 'var(--red)' : 'var(--success)' ?>">
        <?= count($membershipAlerts) ?>
      </div>
      <div class="stat-label">Membresías vencidas / por vencer</div>
    </div>
  </div>

  <!-- ── Membership alerts ─────────────────────────── -->
  <?php if (!empty($membershipAlerts)): ?>
  <div class="dash-section">
    <div class="dash-section-title">⚠️ Membresías por cobrar / vencidas</div>
    <div class="membership-list">
      <?php foreach ($membershipAlerts as $ma):
        $isExpired  = $ma['membership_status'] === 'expired' || ($ma['membership_expires'] && $ma['membership_expires'] < date('Y-m-d'));
        $classType  = $isExpired ? '' : ' warning';
      ?>
      <div class="membership-item<?= $classType ?>" id="mi-<?= $ma['id'] ?>">
        <div style="flex:1">
          <div class="mi-name"><?= htmlspecialchars($ma['name']) ?></div>
          <div class="mi-date">
            <?php if ($ma['membership_expires']): ?>
              <?= $isExpired ? 'Venció el ' : 'Vence el ' ?><strong><?= date('d/m/Y', strtotime($ma['membership_expires'])) ?></strong>
            <?php else: ?>
              Sin fecha de vencimiento
            <?php endif; ?>
            — <?= $ma['enrolled_count'] ?> grupo<?= $ma['enrolled_count'] != 1 ? 's' : '' ?>
          </div>
        </div>
        <button class="btn-primary btn-renew"
                data-id="<?= $ma['id'] ?>"
                data-name="<?= htmlspecialchars($ma['name']) ?>"
                style="font-size:0.72rem;padding:6px 12px;white-space:nowrap">
          ✓ Renovar al <?= date('M Y', strtotime('first day of next month')) ?>
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Today's schedule ──────────────────────────── -->
  <div class="dash-section">
    <div class="dash-section-title">
      📅 <?= $isWeekend ? 'Lunes próximo' : 'Horario de hoy — ' . $dayNames[$todayDow] ?>
    </div>
    <?php if (empty($todaySchedule)): ?>
      <p style="color:var(--text-muted);font-size:0.85rem">No hay grupos programados para hoy.</p>
    <?php else: ?>
      <div class="today-slots">
        <?php foreach ($todaySchedule as $slot):
          $booked = count($slot['bookings']); $max = $slot['max_students'];
          $isFull = $booked >= $max;
          $isGrpActive = ($slot['slot_status'] ?? 'active') === 'active';
        ?>
        <div class="today-slot-card" style="border-top-color:<?= $isGrpActive ? htmlspecialchars($slot['color_hex']) : '#9eabb0' ?>">
          <div class="today-slot-time"><?= formatTime($slot['start_time']) ?> – <?= formatTime($slot['end_time']) ?></div>
          <div class="today-slot-name"><?= htmlspecialchars($slot['class_name'] ?: '—') ?></div>
          <div class="today-slot-prof">
            <span class="color-dot" style="background:<?= htmlspecialchars($slot['color_hex']) ?>"></span>
            <?= htmlspecialchars($slot['professor_name']) ?>
          </div>
          <div class="today-slot-count <?= $isFull ? 'full' : 'open' ?>">
            <?= $booked ?>/<?= $max ?> <?= $isFull ? '— LLENO' : 'cupos' ?>
          </div>
          <?php if (!$isGrpActive): ?>
            <div style="font-size:0.65rem;color:var(--text-muted);margin-top:3px;font-weight:700;text-transform:uppercase">
              <?= $slot['slot_status'] === 'closed' ? 'CERRADO' : 'PENDIENTE' ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($slot['bookings'])): ?>
            <div style="margin-top:8px;border-top:1px solid var(--blue-border);padding-top:6px">
              <?php foreach ($slot['bookings'] as $b): ?>
                <div style="font-size:0.7rem;color:var(--text);padding:1px 0">
                  <?= htmlspecialchars($b['student_name']) ?>
                  <?php if ($b['is_trial']): ?><span class="trial-badge">PRUEBA</span><?php endif; ?>
                  <?php if ($b['is_award']): ?><span class="award-badge">PREMIO</span><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Special classes this week ─────────────────── -->
  <?php if (!empty($specialClasses)): ?>
  <div class="dash-section">
    <div class="dash-section-title">🔬 Clases de prueba y premio esta semana</div>
    <div class="special-list">
      <?php foreach ($specialClasses as $sc):
        $date      = $sc['is_trial'] ? $sc['trial_date'] : $sc['award_date'];
        $type      = $sc['is_trial'] ? 'PRUEBA' : 'PREMIO';
        $typeClass = $sc['is_trial'] ? 'trial-badge' : 'award-badge';
      ?>
      <div class="special-item">
        <span class="si-date"><?= $date ? date('D d/m', strtotime($date)) : '—' ?></span>
        <span class="<?= $typeClass ?>"><?= $type ?></span>
        <span class="si-name"><?= htmlspecialchars($sc['student_name']) ?></span>
        <span class="si-slot">
          <span class="color-dot" style="background:<?= htmlspecialchars($sc['color_hex']) ?>"></span>
          <?= htmlspecialchars($sc['professor_name']) ?> — <?= formatTime($sc['start_time']) ?>
          <?php if ($sc['class_name']): ?> | <?= htmlspecialchars($sc['class_name']) ?><?php endif; ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="assets/js/app.js"></script>
<script>
const CURRENT_PROGRAM  = <?= $currentProgram ?>;
const NEXT_MONTH_END   = '<?= $nextMonthEnd ?>';

document.querySelectorAll('.btn-renew').forEach(btn => {
  btn.addEventListener('click', async () => {
    const name = btn.dataset.name;
    if (!confirm(`¿Renovar membresía de ${name} hasta ${NEXT_MONTH_END}?`)) return;
    try {
      await api('api/students.php', 'PUT', {
        id:                  parseInt(btn.dataset.id),
        membership_only:     1,
        membership_status:   'active',
        membership_expires:  NEXT_MONTH_END,
      });
      toast(`Membresía de ${name} renovada ✓`, 'success');
      document.getElementById('mi-' + btn.dataset.id)?.remove();
    } catch (e) { toast(e.message, 'error'); }
  });
});
</script>
</body>
</html>
