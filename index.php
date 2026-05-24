<?php
require_once __DIR__ . '/includes/functions.php';
$basePath       = '';
$currentProgram = (int)($_GET['p'] ?? 1);
$programs       = getPrograms();
$professors     = getProfessors();

$currentProg = null;
foreach ($programs as $pg) {
    if ($pg['id'] == $currentProgram) { $currentProg = $pg; break; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Horario</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page">
  <div class="page-title">
    <?= $currentProg ? $currentProg['icon'] . ' ' . htmlspecialchars($currentProg['name']) : 'Horario' ?>
  </div>
  <div class="page-sub">Haz clic en un bloque para inscribir o quitar un estudiante del grupo.</div>

  <!-- Week nav -->
  <div class="week-nav">
    <button id="btn-prev-week">&#8592; Anterior</button>
    <span class="week-label" id="week-label">Cargando…</span>
    <button id="btn-today">Hoy</button>
    <button id="btn-next-week">Siguiente &#8594;</button>
  </div>

  <!-- Professor filter -->
  <div id="prof-filter" class="prof-filter"></div>

  <!-- Schedule -->
  <div class="scroll-x">
    <div id="schedule-container">
      <p style="color:var(--text-muted);padding:20px">Cargando horarios…</p>
    </div>
  </div>
</div>

<!-- Enrollment Modal -->
<div id="booking-modal" class="modal-overlay hidden">
  <div class="modal">
    <div class="modal-header">
      <h2 id="modal-title">Grupo</h2>
      <button class="modal-close" id="modal-close">×</button>
    </div>
    <div class="modal-body">

      <div class="modal-slot-info">
        <div class="info-row"><span class="info-label">Clase:</span>   <span class="info-val" id="modal-slot-class"></span></div>
        <div class="info-row"><span class="info-label">Horario:</span> <span class="info-val" id="modal-slot-time"></span></div>
        <div class="info-row"><span class="info-label">Tipo:</span>    <span class="info-val" id="modal-slot-type"></span></div>
        <div class="info-row"><span class="info-label">Cupos:</span>   <span class="info-val" id="modal-capacity"></span></div>
      </div>

      <div class="capacity-bar"><div class="capacity-fill" id="capacity-fill" style="width:0%"></div></div>

      <div class="modal-section-title">Estudiantes inscritos</div>
      <div id="modal-booking-list" class="booking-list"></div>

      <form id="add-booking-form" class="add-booking-form">
        <div class="form-group">
          <label class="form-label">Buscar estudiante</label>
          <input type="text" id="student-search" class="input-field" placeholder="Escribir nombre…" autocomplete="off">
        </div>
        <div class="form-group">
          <label class="form-label">Seleccionar</label>
          <select id="student-select" class="select-field">
            <option value="">— Seleccionar estudiante —</option>
          </select>
        </div>
        <!-- Trial class option -->
        <div class="trial-row">
          <label class="trial-check-label">
            <input type="checkbox" id="is-trial-check">
            Clase de prueba (solo esta fecha, no recurrente)
          </label>
          <div id="trial-date-wrap">
            <label class="form-label">Fecha de la clase de prueba</label>
            <input type="date" id="trial-date-input" class="input-field">
          </div>
        </div>
        <button type="submit" class="btn-primary">Inscribir estudiante</button>
      </form>

    </div>
  </div>
</div>

<script>
const CURRENT_PROGRAM = <?= $currentProgram ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
