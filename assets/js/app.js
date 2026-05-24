/* ── Morning Pass v3 – JS ── */

// ── Toast ─────────────────────────────────────────────
const toastWrap = document.createElement('div');
toastWrap.className = 'toast-wrap';
document.body.appendChild(toastWrap);

function toast(msg, type = '') {
  const el = document.createElement('div');
  el.className = 'toast ' + type;
  el.textContent = msg;
  toastWrap.appendChild(el);
  setTimeout(() => el.remove(), 3200);
}

// ── Week helpers ──────────────────────────────────────
function getMondayOf(d) {
  const dt  = new Date(d);
  const day = dt.getDay();
  dt.setDate(dt.getDate() - (day === 0 ? 6 : day - 1));
  return dt;
}
function formatDate(d)  { return d.toISOString().split('T')[0]; }
function addDays(d, n)  { const dt = new Date(d); dt.setDate(dt.getDate() + n); return dt; }
function fmtShort(iso)  { const [y,m,dy] = iso.split('-'); return `${dy}/${m}/${y}`; }

const DAY_NAMES = ['','Lunes','Martes','Miércoles','Jueves','Viernes'];

// ── API ───────────────────────────────────────────────
async function api(path, method = 'GET', body = null) {
  const opts = { method, headers: { 'Content-Type': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  const res  = await fetch(path, opts);
  const data = await res.json();
  if (!data.ok) throw new Error(data.error || 'Error desconocido');
  return data;
}

// ── Schedule page ─────────────────────────────────────
async function initSchedule() {
  const container = document.getElementById('schedule-container');
  if (!container) return;

  const programId = typeof CURRENT_PROGRAM !== 'undefined' ? CURRENT_PROGRAM : 1;
  let weekStart   = getMondayOf(new Date());
  let activeProfs = new Set();
  let allProfs    = [];

  try {
    const res  = await fetch(`api/slots.php?p=${programId}`);
    const data = await res.json();
    const map  = {};
    (data.slots || []).forEach(s => {
      map[s.professor_id] = { id: s.professor_id, name: s.professor_name, color: s.color_hex };
    });
    allProfs    = Object.values(map);
    activeProfs = new Set(allProfs.map(p => String(p.id)));
  } catch (_) {}

  renderProfChips();
  await renderSchedule();

  document.getElementById('btn-prev-week').onclick = async () => { weekStart = addDays(weekStart, -7); await renderSchedule(); };
  document.getElementById('btn-next-week').onclick = async () => { weekStart = addDays(weekStart,  7); await renderSchedule(); };
  document.getElementById('btn-today').onclick     = async () => { weekStart = getMondayOf(new Date()); await renderSchedule(); };

  function renderProfChips() {
    const wrap = document.getElementById('prof-filter');
    if (!wrap) return;
    wrap.innerHTML = '';
    allProfs.forEach(p => {
      const chip = document.createElement('div');
      chip.className = 'prof-chip' + (activeProfs.has(String(p.id)) ? '' : ' inactive');
      chip.style.background = p.color;
      chip.innerHTML = `<span class="dot"></span>${p.name}`;
      chip.onclick = () => {
        if (activeProfs.has(String(p.id))) activeProfs.delete(String(p.id));
        else activeProfs.add(String(p.id));
        chip.classList.toggle('inactive');
        renderSchedule();
      };
      wrap.appendChild(chip);
    });
  }

  async function renderSchedule() {
    const ws = formatDate(weekStart);
    document.getElementById('week-label').textContent =
      `Semana ${fmtShort(ws)} – ${fmtShort(formatDate(addDays(weekStart, 4)))}`;

    let data;
    try {
      data = await api(`api/bookings.php?week=${ws}&p=${programId}`);
    } catch (e) {
      container.innerHTML = `<p style="color:red;padding:20px">Error al cargar: ${e.message}</p>`;
      return;
    }

    const schedule = data.schedule || {};

    const timeSet = new Set();
    for (let d = 1; d <= 5; d++) {
      Object.values(schedule[d] || {}).forEach(s => timeSet.add(s.start_time));
    }
    const times = [...timeSet].sort();

    const grid = document.createElement('div');
    grid.className = 'schedule-grid';

    // Header row
    grid.appendChild(mkDiv('grid-header', ''));
    for (let d = 1; d <= 5; d++) {
      const dt  = addDays(weekStart, d - 1);
      const hdr = mkDiv('grid-header', '');
      hdr.innerHTML = `${DAY_NAMES[d]}<br><small style="font-weight:400;opacity:0.75">${fmtShort(formatDate(dt))}</small>`;
      grid.appendChild(hdr);
    }

    // Time rows
    times.forEach(time => {
      grid.appendChild(mkDiv('grid-time-col', fmtTime(time)));
      for (let day = 1; day <= 5; day++) {
        const cell     = mkDiv('grid-cell', '');
        const daySlots = schedule[day] || {};
        const visible  = Object.values(daySlots).filter(
          s => s.start_time === time && activeProfs.has(String(s.professor_id))
        );
        if (!visible.length) cell.classList.add('empty');
        else visible.forEach(slot => cell.appendChild(buildSlotCard(slot, ws)));
        grid.appendChild(cell);
      }
    });

    container.innerHTML = '';
    container.appendChild(grid);
  }

  function buildSlotCard(slot, weekStr) {
    const status = slot.slot_status || 'active';
    const booked = slot.bookings.length;
    const max    = slot.max_students;
    const isFull = booked >= max;
    const isActive = status === 'active';

    // Status label map
    const statusLabels = { closed: 'Grupo cerrado', pending: 'Pendiente de activar' };

    const cardClasses = ['slot-card', !isActive ? status : (isFull ? 'full' : '')].filter(Boolean).join(' ');
    const card = mkDiv(cardClasses, '');
    card.style.background = isActive ? slot.color_hex : '#9eabb0';

    card.innerHTML = `
      <div class="slot-header">
        <span class="prof-name">${slot.professor_name}</span>
        ${isActive ? `<span class="slot-capacity">${booked}/${max}</span>` : ''}
      </div>
      <div class="slot-class">${slot.class_name || fmtTime(slot.start_time)}</div>
      ${!isActive ? `<span class="slot-status-badge">${statusLabels[status] || status}</span>` : ''}
      ${isActive ? '<div class="student-list"></div>' : ''}
      ${isActive && !isFull ? '<button class="add-student-btn">+ Inscribir</button>' : ''}
    `;

    if (isActive) {
      const list = card.querySelector('.student-list');
      slot.bookings.forEach(b => {
        const tag = mkDiv('student-tag' + (b.is_trial ? ' is-trial' : ''), '');
        tag.innerHTML = `
          <span>${b.student_name}${b.is_trial ? '<span class="trial-badge">PRUEBA</span>' : ''}</span>
          <button class="remove-btn" title="Quitar del grupo">×</button>
        `;
        tag.querySelector('.remove-btn').onclick = async (e) => {
          e.stopPropagation();
          if (!confirm(`¿Quitar a ${b.student_name} de este grupo?`)) return;
          try {
            await api('api/bookings.php', 'DELETE', { booking_id: b.booking_id });
            toast('Estudiante quitado del grupo', 'success');
            renderSchedule();
          } catch (err) { toast(err.message, 'error'); }
        };
        list.appendChild(tag);
      });

      const addBtn = card.querySelector('.add-student-btn');
      if (addBtn) addBtn.onclick = (e) => { e.stopPropagation(); openModal(slot, programId, weekStr, () => renderSchedule()); };
      card.onclick = () => openModal(slot, programId, weekStr, () => renderSchedule());
    }

    return card;
  }
}

// ── Enrollment Modal ──────────────────────────────────
let allStudents = [];

async function loadStudents() {
  if (allStudents.length) return;
  try {
    const data  = await api('api/students.php');
    allStudents = data.students || [];
  } catch (_) {}
}

async function openModal(slot, programId, weekStr, onSuccess) {
  await loadStudents();

  const overlay = document.getElementById('booking-modal');
  overlay.classList.remove('hidden');

  document.getElementById('modal-title').textContent =
    `${slot.professor_name} — ${DAY_NAMES[slot.day_of_week]} ${fmtTime(slot.start_time)}`;
  document.getElementById('modal-slot-class').textContent = slot.class_name || '—';
  document.getElementById('modal-slot-time').textContent  =
    `${fmtTime(slot.start_time)} – ${fmtTime(slot.end_time)}`;
  document.getElementById('modal-slot-type').textContent  = slot.class_type || '—';

  // Default trial date = the day of this slot in the current week
  const trialDateDefault = formatDate(addDays(new Date(weekStr), slot.day_of_week - 1));

  renderModalContent(slot, programId, weekStr, trialDateDefault, onSuccess);
}

function renderModalContent(slot, programId, weekStr, trialDateDefault, onSuccess) {
  const booked = slot.bookings.length;
  const max    = slot.max_students;

  const fill = document.getElementById('capacity-fill');
  fill.style.width = `${Math.round((booked / max) * 100)}%`;
  fill.className   = 'capacity-fill' + (booked >= max ? ' full' : '');
  document.getElementById('modal-capacity').textContent = `${booked} / ${max} cupos usados`;

  const list = document.getElementById('modal-booking-list');
  list.innerHTML = '';
  if (!slot.bookings.length) {
    list.innerHTML = '<div class="booking-empty">Sin inscripciones aún</div>';
  } else {
    slot.bookings.forEach(b => {
      const item = mkDiv('booking-item', '');
      item.innerHTML = `
        <span>
          ${b.student_name}
          ${b.is_trial ? `<span class="trial-badge" style="background:var(--red);margin-left:6px">PRUEBA ${b.trial_date || ''}</span>` : ''}
        </span>
        <button class="del-booking">Quitar</button>
      `;
      item.querySelector('.del-booking').onclick = async () => {
        if (!confirm(`¿Quitar a ${b.student_name} del grupo?`)) return;
        try {
          await api('api/bookings.php', 'DELETE', { booking_id: b.booking_id });
          toast('Estudiante quitado', 'success');
          slot.bookings = slot.bookings.filter(x => x.booking_id !== b.booking_id);
          renderModalContent(slot, programId, weekStr, trialDateDefault, onSuccess);
          if (onSuccess) onSuccess();
        } catch (err) { toast(err.message, 'error'); }
      };
      list.appendChild(item);
    });
  }

  const searchInput  = document.getElementById('student-search');
  const select       = document.getElementById('student-select');
  const trialCheck   = document.getElementById('is-trial-check');
  const trialDateWrap= document.getElementById('trial-date-wrap');
  const trialDateIn  = document.getElementById('trial-date-input');

  searchInput.value = '';
  trialCheck.checked = false;
  trialDateWrap.style.display = 'none';
  trialDateIn.value = trialDateDefault;
  populateSelect(select, allStudents, slot.bookings.map(b => b.student_id));

  searchInput.oninput = () => {
    const filtered = allStudents.filter(s => s.name.toLowerCase().includes(searchInput.value.toLowerCase()));
    populateSelect(select, filtered, slot.bookings.map(b => b.student_id));
  };

  trialCheck.onchange = () => {
    trialDateWrap.style.display = trialCheck.checked ? 'block' : 'none';
  };

  const form = document.getElementById('add-booking-form');
  form.onsubmit = async (e) => {
    e.preventDefault();
    const studentId = parseInt(select.value);
    if (!studentId) { toast('Selecciona un estudiante', 'error'); return; }
    const isTrial = trialCheck.checked;
    try {
      const res = await api('api/bookings.php', 'POST', {
        slot_id:    slot.slot_id,
        student_id: studentId,
        program_id: programId,
        is_trial:   isTrial ? 1 : 0,
        trial_date: isTrial ? trialDateIn.value : null,
      });
      const student = allStudents.find(s => s.id === studentId);
      slot.bookings.push({
        booking_id:   res.booking_id,
        student_id:   studentId,
        student_name: student.name,
        is_trial:     isTrial,
        trial_date:   isTrial ? trialDateIn.value : null,
      });
      toast(isTrial ? 'Clase de prueba registrada' : 'Estudiante inscrito en el grupo', 'success');
      renderModalContent(slot, programId, weekStr, trialDateDefault, onSuccess);
      if (onSuccess) onSuccess();
    } catch (err) { toast(err.message, 'error'); }
  };
}

function populateSelect(sel, students, excludeIds = []) {
  sel.innerHTML = '<option value="">— Seleccionar estudiante —</option>';
  students.filter(s => !excludeIds.includes(s.id)).forEach(s => {
    const opt       = document.createElement('option');
    opt.value       = s.id;
    opt.textContent = s.name;
    sel.appendChild(opt);
  });
}

// ── Init ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('booking-modal');
  if (overlay) {
    document.getElementById('modal-close').onclick = () => overlay.classList.add('hidden');
    overlay.onclick = (e) => { if (e.target === overlay) overlay.classList.add('hidden'); };
  }
  initSchedule();
});

// ── Utils ─────────────────────────────────────────────
function mkDiv(cls, html) {
  const el = document.createElement('div');
  el.className = cls;
  el.innerHTML = html;
  return el;
}

function fmtTime(t) {
  if (!t) return '';
  const [h, m] = t.split(':').map(Number);
  return `${h % 12 || 12}:${String(m).padStart(2,'0')} ${h >= 12 ? 'PM' : 'AM'}`;
}
