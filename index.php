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

<!-- Quick-Create Slot Modal -->
<div id="quick-create-modal" class="modal-overlay hidden">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <h2 id="qc-title">Nuevo grupo</h2>
      <button class="modal-close" id="qc-close">×</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Profesor *</label>
          <select id="qc-prof" class="select-field"></select>
        </div>
        <div class="form-group">
          <label class="form-label">Día *</label>
          <select id="qc-day" class="select-field">
            <option value="1">Lunes</option><option value="2">Martes</option>
            <option value="3">Miércoles</option><option value="4">Jueves</option>
            <option value="5">Viernes</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Hora inicio *</label>
          <input type="time" id="qc-start" class="input-field">
        </div>
        <div class="form-group">
          <label class="form-label">Hora fin *</label>
          <input type="time" id="qc-end" class="input-field">
        </div>
        <div class="form-group full">
          <label class="form-label">Nombre del grupo</label>
          <input type="text" id="qc-name" class="input-field" placeholder="Ej: CAT 5TA JEAN">
        </div>
        <div class="form-group">
          <label class="form-label">Máx. estudiantes</label>
          <input type="number" id="qc-max" class="input-field" value="4" min="1" max="20">
        </div>
        <div class="form-group">
          <label class="form-label">Estado inicial</label>
          <select id="qc-status" class="select-field">
            <option value="active">✅ Activo</option>
            <option value="pending">⏳ Pendiente</option>
          </select>
        </div>
      </div>
      <button class="btn-primary" id="btn-qc-save" style="margin-top:14px;width:100%">Crear grupo</button>
    </div>
  </div>
</div>

<!-- Enrollment Modal -->
<div id="booking-modal" class="modal-overlay hidden">
  <div class="modal">
    <div class="modal-header">
      <h2 id="modal-title">Grupo</h2>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="btn-secondary" id="btn-copy-list" style="font-size:0.72rem;padding:5px 10px" title="Copiar lista">📋 Copiar</button>
        <button class="modal-close" id="modal-close">×</button>
      </div>
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
          <div id="membership-alert" class="membership-modal-alert hidden">
            ⚠️ <span id="membership-alert-text"></span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Nota (opcional)</label>
          <input type="text" id="booking-note" class="input-field" placeholder="Ej: viene con su hermano">
        </div>
        <!-- Booking type -->
        <div class="form-group">
          <label class="form-label">Tipo de inscripción</label>
          <select id="booking-type" class="select-field">
            <option value="regular">Inscripción regular (recurrente)</option>
            <option value="trial">Clase de prueba (fecha única)</option>
            <option value="award">Clase premio (fecha única)</option>
          </select>
        </div>
        <div id="special-date-wrap" style="display:none">
          <label class="form-label" id="special-date-label">Fecha</label>
          <input type="date" id="special-date-input" class="input-field">
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
