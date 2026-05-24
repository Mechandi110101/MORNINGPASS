<?php
require_once __DIR__ . '/includes/functions.php';
$professors = getProfessors();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Horarios</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <h1>🎓 Morning Pass</h1>
  <nav>
    <a href="index.php" class="active">Horario Semanal</a>
    <a href="professor.php">Por Profesor</a>
    <a href="students.php">Estudiantes</a>
    <a href="admin/slots.php">Admin Horarios</a>
  </nav>
</header>

<div class="page">
  <div class="page-title">Horario Semanal</div>
  <div class="page-sub">Haz clic en un bloque para ver reservas o agregar estudiantes.</div>

  <!-- Week navigation -->
  <div class="week-nav">
    <button id="btn-prev-week">&#8592; Semana anterior</button>
    <span class="week-label" id="week-label">Cargando…</span>
    <button id="btn-today">Hoy</button>
    <button id="btn-next-week">Siguiente semana &#8594;</button>
  </div>

  <!-- Professor filter chips -->
  <div id="prof-filter" class="prof-filter"></div>

  <!-- Schedule grid -->
  <div class="scroll-x">
    <div id="schedule-container">
      <p style="color:var(--text-muted);padding:20px">Cargando horarios…</p>
    </div>
  </div>
</div>

<!-- Booking Modal -->
<div id="booking-modal" class="modal-overlay hidden">
  <div class="modal">
    <div class="modal-header" id="modal-header-bar">
      <h2 id="modal-title">Horario</h2>
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

      <h3 style="font-size:0.85rem;color:var(--brown);margin-bottom:8px">Estudiantes reservados</h3>
      <div id="modal-booking-list" class="booking-list"></div>

      <form id="add-booking-form" class="add-booking-form">
        <div>
          <label>Buscar estudiante</label>
          <input type="text" id="student-search" class="input-search" placeholder="Escribir nombre…" autocomplete="off">
        </div>
        <div>
          <label>Seleccionar</label>
          <select id="student-select" class="select-student">
            <option value="">— Seleccionar estudiante —</option>
          </select>
        </div>
        <button type="submit" class="btn-primary">Agregar reserva</button>
      </form>

    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
