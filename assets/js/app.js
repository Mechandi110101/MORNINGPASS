/* ── Morning Pass – main JS ── */

// ── Toast notifications ──────────────────────────
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

// ── Week helpers ─────────────────────────────────
function getMondayOf(d) {
  const dt = new Date(d);
  const day = dt.getDay();
  const diff = (day === 0) ? -6 : 1 - day;
  dt.setDate(dt.getDate() + diff);
  return dt;
}

function formatDate(d) {
  return d.toISOString().split('T')[0];
}

function addDays(d, n) {
  const dt = new Date(d);
  dt.setDate(dt.getDate() + n);
  return dt;
}

function fmtShort(isoDate) {
  const [y, m, dy] = isoDate.split('-');
  return `${dy}/${m}/${y}`;
}

const DAY_NAMES = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

// ── API helpers ──────────────────────────────────
async function api(path, method = 'GET', body = null) {
  const opts = { method, headers: { 'Content-Type': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  const res  = await fetch(path, opts);
  const data = await res.json();
  if (!data.ok) throw new Error(data.error || 'Error desconocido');
  return data;
}

// ── Schedule page ────────────────────────────────
async function initSchedule() {
  const container = document.getElementById('schedule-container');
  if (!container) return;

  let weekStart  = getMondayOf(new Date());
  let allProfs   = [];
  let activeProfs = new Set();

  // Load professors for filter chips
  try {
    const res = await api('api/students.php'); // reuse GET
  } catch (_) {}

  const profRes = await fetch('api/slots.php');
  const profData = await profRes.json();
  // extract unique professors from slots
  const profMap = {};
  (profData.slots || []).forEach(s => {
    profMap[s.professor_id] = { id: s.professor_id, name: s.professor_name, color: s.color_hex };
  });
  allProfs = Object.values(profMap);
  activeProfs = new Set(allProfs.map(p => String(p.id)));

  renderProfChips();
  await renderSchedule();

  // Week nav buttons
  document.getElementById('btn-prev-week').addEventListener('click', async () => {
    weekStart = addDays(weekStart, -7);
    await renderSchedule();
  });
  document.getElementById('btn-next-week').addEventListener('click', async () => {
    weekStart = addDays(weekStart, 7);
    await renderSchedule();
  });
  document.getElementById('btn-today').addEventListener('click', async () => {
    weekStart = getMondayOf(new Date());
    await renderSchedule();
  });

  function renderProfChips() {
    const wrap = document.getElementById('prof-filter');
    if (!wrap) return;
    wrap.innerHTML = '';
    allProfs.forEach(p => {
      const chip = document.createElement('div');
      chip.className = 'prof-chip' + (activeProfs.has(String(p.id)) ? '' : ' inactive');
      chip.style.background = p.color;
      chip.innerHTML = `<span class="dot"></span>${p.name}`;
      chip.addEventListener('click', () => {
        if (activeProfs.has(String(p.id))) activeProfs.delete(String(p.id));
        else activeProfs.add(String(p.id));
        chip.classList.toggle('inactive');
        renderSchedule();
      });
      wrap.appendChild(chip);
    });
  }

  async function renderSchedule() {
    const ws = formatDate(weekStart);
    document.getElementById('week-label').textContent =
      `Semana: ${fmtShort(ws)} – ${fmtShort(formatDate(addDays(weekStart, 4)))}`;

    let data;
    try {
      data = await api(`api/bookings.php?week=${ws}`);
    } catch (e) {
      container.innerHTML = `<p style="color:red">Error al cargar horarios: ${e.message}</p>`;
      return;
    }

    const schedule = data.schedule || {};

    // Collect all unique time rows (start_time)
    const timeSet = new Set();
    for (let day = 1; day <= 5; day++) {
      const daySlots = schedule[day] || {};
      Object.values(daySlots).forEach(s => timeSet.add(s.start_time));
    }
    const times = [...timeSet].sort();

    // Build grid
    const grid = document.createElement('div');
    grid.className = 'schedule-grid';

    // Header row
    grid.appendChild(mkDiv('grid-header', ''));
    for (let d = 1; d <= 5; d++) {
      const dt = addDays(weekStart, d - 1);
      const hdr = mkDiv('grid-header', `${DAY_NAMES[d]}<br><small>${fmtShort(formatDate(dt))}</small>`);
      hdr.innerHTML = `${DAY_NAMES[d]}<br><small style="font-weight:400;opacity:0.8">${fmtShort(formatDate(dt))}</small>`;
      grid.appendChild(hdr);
    }

    // Time rows
    times.forEach(time => {
      grid.appendChild(mkDiv('grid-time-col', fmtTime(time)));

      for (let day = 1; day <= 5; day++) {
        const cell = mkDiv('grid-cell', '');
        const daySlots = schedule[day] || {};

        const slotsAtTime = Object.values(daySlots).filter(
          s => s.start_time === time && activeProfs.has(String(s.professor_id))
        );

        if (!slotsAtTime.length) {
          cell.classList.add('empty');
        } else {
          slotsAtTime.forEach(slot => {
            cell.appendChild(buildSlotCard(slot, ws));
          });
        }
        grid.appendChild(cell);
      }
    });

    container.innerHTML = '';
    container.appendChild(grid);
  }

  function buildSlotCard(slot, weekStr) {
    const booked = slot.bookings.length;
    const max    = slot.max_students;
    const isFull = booked >= max;

    const card = mkDiv('slot-card' + (isFull ? ' full' : ''), '');
    card.style.background = slot.color_hex;

    card.innerHTML = `
      <div class="slot-header">
        <span class="prof-name">${slot.professor_name}</span>
        <span class="slot-capacity">${booked}/${max}</span>
      </div>
      <div class="slot-class">${slot.class_name || fmtTime(slot.start_time) + ' – ' + fmtTime(slot.end_time)}</div>
      <div class="student-list"></div>
      ${!isFull ? '<button class="add-student-btn">+ Agregar estudiante</button>' : ''}
    `;

    const studentList = card.querySelector('.student-list');
    slot.bookings.forEach(b => {
      const tag = mkDiv('student-tag', '');
      tag.innerHTML = `<span>${b.student_name}</span><button class="remove-btn" title="Cancelar reserva">×</button>`;
      tag.querySelector('.remove-btn').addEventListener('click', async (e) => {
        e.stopPropagation();
        if (!confirm(`¿Cancelar reserva de ${b.student_name}?`)) return;
        try {
          await api('api/bookings.php', 'DELETE', { booking_id: b.booking_id });
          toast('Reserva cancelada', 'success');
          renderSchedule();
        } catch (err) {
          toast(err.message, 'error');
        }
      });
      studentList.appendChild(tag);
    });

    const addBtn = card.querySelector('.add-student-btn');
    if (addBtn) {
      addBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openBookingModal(slot, weekStr, () => renderSchedule());
      });
    }

    card.addEventListener('click', () => {
      openBookingModal(slot, weekStr, () => renderSchedule());
    });

    return card;
  }
}

// ── Booking Modal ────────────────────────────────
let allStudents = [];

async function loadStudents() {
  if (allStudents.length) return;
  try {
    const data = await api('api/students.php');
    allStudents = data.students || [];
  } catch (_) {}
}

async function openBookingModal(slot, weekStr, onSuccess) {
  await loadStudents();

  const overlay = document.getElementById('booking-modal');
  overlay.classList.remove('hidden');

  document.getElementById('modal-title').textContent =
    `${slot.professor_name} – ${DAY_NAMES[slot.day_of_week]} ${fmtTime(slot.start_time)}`;

  // Info
  document.getElementById('modal-slot-class').textContent = slot.class_name || '—';
  document.getElementById('modal-slot-time').textContent =
    `${fmtTime(slot.start_time)} – ${fmtTime(slot.end_time)}`;
  document.getElementById('modal-slot-type').textContent = slot.class_type || '—';

  renderBookings(slot, weekStr, onSuccess);
}

function renderBookings(slot, weekStr, onSuccess) {
  const booked = slot.bookings.length;
  const max    = slot.max_students;

  const fill = document.getElementById('capacity-fill');
  fill.style.width = `${Math.round((booked / max) * 100)}%`;
  fill.className   = 'capacity-fill' + (booked >= max ? ' full' : '');

  document.getElementById('modal-capacity').textContent = `${booked} / ${max} estudiantes`;

  const list = document.getElementById('modal-booking-list');
  list.innerHTML = '';
  if (!slot.bookings.length) {
    list.innerHTML = '<div class="booking-empty">Sin reservas aún</div>';
  } else {
    slot.bookings.forEach(b => {
      const item = mkDiv('booking-item', '');
      item.innerHTML = `<span>${b.student_name}</span><button class="del-booking">Cancelar</button>`;
      item.querySelector('.del-booking').addEventListener('click', async () => {
        if (!confirm(`¿Cancelar reserva de ${b.student_name}?`)) return;
        try {
          await api('api/bookings.php', 'DELETE', { booking_id: b.booking_id });
          toast('Reserva cancelada', 'success');
          slot.bookings = slot.bookings.filter(x => x.booking_id !== b.booking_id);
          renderBookings(slot, weekStr, onSuccess);
          if (onSuccess) onSuccess();
        } catch (err) { toast(err.message, 'error'); }
      });
      list.appendChild(item);
    });
  }

  // Student search & select
  const searchInput = document.getElementById('student-search');
  const select      = document.getElementById('student-select');
  searchInput.value = '';
  populateSelect(select, allStudents, slot.bookings.map(b => b.student_id));

  searchInput.oninput = () => {
    const q = searchInput.value.toLowerCase();
    const filtered = allStudents.filter(s => s.name.toLowerCase().includes(q));
    populateSelect(select, filtered, slot.bookings.map(b => b.student_id));
  };

  const form = document.getElementById('add-booking-form');
  form.onsubmit = async (e) => {
    e.preventDefault();
    const studentId = parseInt(select.value);
    if (!studentId) { toast('Selecciona un estudiante', 'error'); return; }
    try {
      const res = await api('api/bookings.php', 'POST', {
        slot_id: slot.slot_id, student_id: studentId, week_start: weekStr
      });
      const student = allStudents.find(s => s.id === studentId);
      slot.bookings.push({ booking_id: res.booking_id, student_id: studentId, student_name: student.name });
      toast('Reserva agregada', 'success');
      renderBookings(slot, weekStr, onSuccess);
      if (onSuccess) onSuccess();
    } catch (err) { toast(err.message, 'error'); }
  };
}

function populateSelect(sel, students, excludeIds = []) {
  sel.innerHTML = '<option value="">— Seleccionar estudiante —</option>';
  students
    .filter(s => !excludeIds.includes(s.id))
    .forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.name;
      sel.appendChild(opt);
    });
}

// Close modal
document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('booking-modal');
  if (!overlay) return;
  document.getElementById('modal-close').addEventListener('click', () => overlay.classList.add('hidden'));
  overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.add('hidden'); });
  initSchedule();
});

// ── Utils ────────────────────────────────────────
function mkDiv(cls, html) {
  const el = document.createElement('div');
  el.className = cls;
  el.innerHTML = html;
  return el;
}

function fmtTime(t) {
  if (!t) return '';
  const [h, m] = t.split(':').map(Number);
  const ampm = h >= 12 ? 'PM' : 'AM';
  return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${ampm}`;
}
