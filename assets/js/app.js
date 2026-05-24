/* ── Morning Pass v5 – JS ── */

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

  let allProfessors = [];
  try {
    const [slotRes, profRes] = await Promise.all([
      fetch(`api/slots.php?p=${programId}`).then(r => r.json()),
      fetch('api/professors.php').then(r => r.json()),
    ]);
    const map = {};
    (slotRes.slots || []).forEach(s => {
      map[s.professor_id] = { id: s.professor_id, name: s.professor_name, color: s.color_hex };
    });
    allProfs      = Object.values(map);
    activeProfs   = new Set(allProfs.map(p => String(p.id)));
    allProfessors = (profRes.professors || []).filter(p => p.active);
    // Populate quick-create professor dropdown
    const qcProf = document.getElementById('qc-prof');
    if (qcProf) {
      qcProf.innerHTML = allProfessors.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
    }
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
        if (!visible.length) {
          cell.classList.add('empty');
          const plus = mkDiv('grid-cell-add', '+');
          plus.onclick = () => openQuickCreate(day, time, programId);
          cell.appendChild(plus);
        } else {
          visible.forEach(slot => cell.appendChild(buildSlotCard(slot, ws)));
        }
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
        <span class="slot-capacity" style="${!isActive ? 'opacity:0.7' : ''}">${booked}/${max}</span>
      </div>
      <div class="slot-class">${slot.class_name || fmtTime(slot.start_time)}</div>
      ${!isActive ? `<span class="slot-status-badge">${statusLabels[status] || status}</span>` : ''}
      <div class="student-list"></div>
      ${isActive && !isFull ? '<button class="add-student-btn">+ Inscribir</button>' : ''}
    `;

    const list = card.querySelector('.student-list');
    slot.bookings.forEach(b => {
      const tag = mkDiv('student-tag' + (b.is_trial ? ' is-trial' : b.is_award ? ' is-award' : ''), '');
      tag.innerHTML = `
        <span>${b.student_name}${b.is_trial ? '<span class="trial-badge">PRUEBA</span>' : b.is_award ? '<span class="award-badge">PREMIO</span>' : ''}</span>
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

    return card;
  }
}

// ── Enrollment Modal ──────────────────────────────────
let allStudents    = [];
let currentModalSlot = null;

async function loadStudents() {
  if (allStudents.length) return;
  try {
    const data  = await api('api/students.php');
    allStudents = data.students || [];
  } catch (_) {}
}

async function openModal(slot, programId, weekStr, onSuccess) {
  await loadStudents();
  currentModalSlot = slot;

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
        <span style="flex:1">
          ${b.student_name}
          ${b.is_trial ? `<span class="trial-badge" style="background:var(--red);margin-left:6px">PRUEBA ${b.trial_date || ''}</span>` : ''}
          ${b.is_award ? `<span class="award-badge" style="margin-left:6px">PREMIO ${b.award_date || ''}</span>` : ''}
          ${b.notes ? `<span style="font-size:0.7rem;color:var(--text-muted);display:block;margin-top:2px">📝 ${b.notes}</span>` : ''}
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

  const searchInput    = document.getElementById('student-search');
  const select         = document.getElementById('student-select');
  const bookingType    = document.getElementById('booking-type');
  const specialWrap    = document.getElementById('special-date-wrap');
  const specialLabel   = document.getElementById('special-date-label');
  const specialDateIn  = document.getElementById('special-date-input');
  const noteInput      = document.getElementById('booking-note');
  const membAlert      = document.getElementById('membership-alert');
  const membAlertText  = document.getElementById('membership-alert-text');

  const form = document.getElementById('add-booking-form');
  const isSlotActive = (slot.slot_status || 'active') === 'active';
  form.style.display = isSlotActive ? 'block' : 'none';

  searchInput.value   = '';
  bookingType.value   = 'regular';
  specialWrap.style.display = 'none';
  specialDateIn.value = trialDateDefault;
  if (noteInput) noteInput.value = '';
  if (membAlert) membAlert.classList.add('hidden');
  populateSelect(select, allStudents, slot.bookings.map(b => b.student_id));

  searchInput.oninput = () => {
    const filtered = allStudents.filter(s => s.name.toLowerCase().includes(searchInput.value.toLowerCase()));
    populateSelect(select, filtered, slot.bookings.map(b => b.student_id));
  };

  select.onchange = () => {
    if (!membAlert || !membAlertText) return;
    const sid = parseInt(select.value);
    const st  = allStudents.find(s => s.id === sid);
    if (st && (st.membership_status === 'expired' ||
        (st.membership_expires && st.membership_expires < new Date().toISOString().slice(0,10)))) {
      membAlertText.textContent = st.membership_expires
        ? `Membresía vencida el ${st.membership_expires}` : 'Membresía vencida';
      membAlert.classList.remove('hidden');
    } else {
      membAlert.classList.add('hidden');
    }
  };

  bookingType.onchange = () => {
    const t = bookingType.value;
    if (t !== 'regular') {
      specialWrap.style.display = 'block';
      specialLabel.textContent  = t === 'trial' ? 'Fecha de la clase de prueba' : 'Fecha de la clase premio';
    } else {
      specialWrap.style.display = 'none';
    }
  };

  form.onsubmit = async (e) => {
    e.preventDefault();
    const studentId = parseInt(select.value);
    if (!studentId) { toast('Selecciona un estudiante', 'error'); return; }
    const type    = bookingType.value;
    const isTrial = type === 'trial' ? 1 : 0;
    const isAward = type === 'award' ? 1 : 0;
    if ((isTrial || isAward) && !specialDateIn.value) {
      toast('Selecciona una fecha', 'error'); return;
    }
    try {
      const note = noteInput ? noteInput.value.trim() : '';
      const res = await api('api/bookings.php', 'POST', {
        slot_id:    slot.slot_id,
        student_id: studentId,
        program_id: programId,
        is_trial:   isTrial,
        trial_date: isTrial ? specialDateIn.value : null,
        is_award:   isAward,
        award_date: isAward ? specialDateIn.value : null,
        notes:      note,
      });
      const student = allStudents.find(s => s.id === studentId);
      slot.bookings.push({
        booking_id:   res.booking_id,
        student_id:   studentId,
        student_name: student.name,
        is_trial:     !!isTrial,
        trial_date:   isTrial ? specialDateIn.value : null,
        is_award:     !!isAward,
        award_date:   isAward ? specialDateIn.value : null,
        notes:        note,
      });
      const label = isTrial ? 'Clase de prueba registrada' : isAward ? 'Clase premio registrada' : 'Estudiante inscrito en el grupo';
      toast(label, 'success');
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

// ── Dark mode ─────────────────────────────────────────
function initDarkMode() {
  const btns = [
    document.getElementById('dark-toggle'),
    document.getElementById('dark-toggle-mobile'),
  ].filter(Boolean);
  if (!btns.length) return;
  const apply = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('morning-pass-theme', theme);
    btns.forEach(b => {
      b.textContent = theme === 'dark' ? '☀️' : '🌙';
      b.title = theme === 'dark' ? 'Modo claro' : 'Modo oscuro';
    });
  };
  apply(localStorage.getItem('morning-pass-theme') || 'light');
  btns.forEach(b => b.onclick = () => apply(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));
}

// ── Global search ─────────────────────────────────────
const DAY_SHORT = ['','Lun','Mar','Mié','Jue','Vie'];
const PROG_ICONS = { 1: '🌅', 2: '🏫', 3: '🏆' };

function initNavSearch() {
  const input = document.getElementById('nav-search-input');
  const drop  = document.getElementById('nav-search-drop');
  if (!input || !drop) return;

  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { drop.classList.add('hidden'); drop.innerHTML = ''; return; }
    timer = setTimeout(async () => {
      try {
        const data = await api('api/students.php?with_slots=1&q=' + encodeURIComponent(q), 'GET');
        const students = data.students || [];
        if (!students.length) { drop.innerHTML = '<div class="nsd-empty">Sin resultados</div>'; drop.classList.remove('hidden'); return; }
        drop.innerHTML = students.slice(0, 8).map(s => {
          const ms = s.membership_status === 'expired' ? '<span class="trial-badge" style="background:var(--red)">SIN MEMBRESÍA</span>' : '';
          const slots = (s.slots || []).map(sl =>
            `<div class="nsd-slot">${PROG_ICONS[sl.program_id] || ''} ${DAY_SHORT[sl.day_of_week]} ${fmtTime(sl.start_time)} — ${sl.professor_name}${sl.class_name ? ' · '+sl.class_name : ''}</div>`
          ).join('');
          return `<div class="nsd-item" data-pid="${s.slots?.[0]?.program_id || 1}">
            <div class="nsd-name">${s.name} ${ms}</div>
            ${slots || '<div class="nsd-slot" style="color:var(--text-muted)">Sin grupos activos</div>'}
          </div>`;
        }).join('');
        drop.classList.remove('hidden');
      } catch (_) {}
    }, 280);
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#schedule-search')) { drop.classList.add('hidden'); }
  });
}

// ── Quick-create slot from schedule ──────────────────
function openQuickCreate(day, time, programId) {
  const modal = document.getElementById('quick-create-modal');
  if (!modal) return;
  document.getElementById('qc-day').value   = day;
  document.getElementById('qc-start').value = time;
  // Default end = start + 1h
  const [h, m] = time.split(':').map(Number);
  const endH = (h + 1) % 24;
  document.getElementById('qc-end').value   = `${String(endH).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
  document.getElementById('qc-title').textContent = `Nuevo grupo — ${DAY_NAMES[day]} ${fmtTime(time)}`;
  modal.classList.remove('hidden');
}

// ── Copy slot list to clipboard ───────────────────────
function initCopyList() {
  const btn = document.getElementById('btn-copy-list');
  if (!btn) return;
  btn.onclick = () => {
    const slot = currentModalSlot;
    if (!slot) return;
    const lines = [
      `🎾 ${slot.professor_name} — ${DAY_NAMES[slot.day_of_week]} ${fmtTime(slot.start_time)}`,
      `Grupo: ${slot.class_name || '—'} (${slot.bookings.length}/${slot.max_students} cupos)`,
      '──────────────────',
      ...slot.bookings.map(b =>
        `• ${b.student_name}` +
        (b.is_trial ? ` (PRUEBA ${b.trial_date || ''})` : '') +
        (b.is_award ? ` (PREMIO ${b.award_date || ''})` : '') +
        (b.notes ? ` — ${b.notes}` : '')
      ),
      '──────────────────',
      'Morning Pass',
    ];
    navigator.clipboard.writeText(lines.join('\n'))
      .then(() => toast('Lista copiada ✓', 'success'))
      .catch(() => toast('No se pudo copiar', 'error'));
  };
}

// ── Hamburger menu ────────────────────────────────────
function initHamburger() {
  const btn = document.getElementById('hamburger-btn');
  const nav = document.getElementById('mobile-nav');
  if (!btn || !nav) return;
  btn.onclick = () => {
    const open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open);
    btn.textContent = open ? '✕' : '☰';
    nav.setAttribute('aria-hidden', !open);
  };
  document.addEventListener('click', (e) => {
    if (nav.classList.contains('open') && !e.target.closest('#mobile-nav') && !e.target.closest('#hamburger-btn')) {
      nav.classList.remove('open');
      btn.setAttribute('aria-expanded', false);
      btn.textContent = '☰';
    }
  });
}

// ── Init ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initDarkMode();
  initHamburger();
  initNavSearch();
  initCopyList();

  // Enrollment modal close
  const overlay = document.getElementById('booking-modal');
  if (overlay) {
    document.getElementById('modal-close').onclick = () => overlay.classList.add('hidden');
    overlay.onclick = (e) => { if (e.target === overlay) overlay.classList.add('hidden'); };
  }

  // Quick-create modal
  const qcModal = document.getElementById('quick-create-modal');
  if (qcModal) {
    document.getElementById('qc-close').onclick = () => qcModal.classList.add('hidden');
    qcModal.onclick = (e) => { if (e.target === qcModal) qcModal.classList.add('hidden'); };

    document.getElementById('btn-qc-save')?.addEventListener('click', async () => {
      const profId = parseInt(document.getElementById('qc-prof').value);
      const day    = parseInt(document.getElementById('qc-day').value);
      const start  = document.getElementById('qc-start').value;
      const end    = document.getElementById('qc-end').value;
      if (!profId || !start || !end) { toast('Completa todos los campos requeridos', 'error'); return; }
      try {
        const programId = typeof CURRENT_PROGRAM !== 'undefined' ? CURRENT_PROGRAM : 1;
        await api('api/slots.php', 'POST', {
          program_id:   programId,
          professor_id: profId,
          day_of_week:  day,
          start_time:   start,
          end_time:     end,
          class_name:   document.getElementById('qc-name').value.trim(),
          max_students: parseInt(document.getElementById('qc-max').value) || 4,
          slot_status:  document.getElementById('qc-status').value,
        });
        toast('Grupo creado ✓', 'success');
        qcModal.classList.add('hidden');
        setTimeout(() => location.reload(), 600);
      } catch (e) { toast(e.message, 'error'); }
    });
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
