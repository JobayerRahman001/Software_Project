// MediLink front-end helpers: api calls, toast, dark mode, charts and one reusable CRUD table.
const API = '../api/';

// ---------- small helpers ----------
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const money = n => '$' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const today = () => new Date().toISOString().slice(0, 10);

const BADGES = {
  green: ['paid', 'approved', 'completed', 'confirmed', 'low', 'active'],
  amber: ['pending', 'scheduled', 'under review', 'submitted', 'in progress', 'medium', 'partially paid'],
  red: ['rejected', 'cancelled', 'no-show', 'high', 'critical'],
  blue: ['ai', 'doctor', 'system'],
};
function badge(value) {
  if (!value) return '';
  const color = Object.keys(BADGES).find(c => BADGES[c].includes(String(value).toLowerCase())) || '';
  return `<span class="badge ${color}">${esc(value)}</span>`;
}

let toastTimer;
function toast(message, isError = false) {
  const t = document.getElementById('toast');
  t.textContent = message;
  t.className = 'show' + (isError ? ' error' : '');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (t.className = ''), 2800);
}

// ---------- api ----------
async function api(path, method = 'GET', data) {
  const res = await fetch(API + path, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: data ? JSON.stringify(data) : undefined,
  });
  if (res.status === 401) location.href = '../index.html';
  const json = await res.json();
  if (!res.ok) throw new Error(json.message || 'Something went wrong');
  return json;
}
const list = async (table, params = '') => (await api(`crud.php?table=${table}${params}`)).data;
const save = (table, id, data) => id ? api(`crud.php?table=${table}&id=${id}`, 'PUT', data) : api(`crud.php?table=${table}`, 'POST', data);
const remove = (table, id) => api(`crud.php?table=${table}&id=${id}`, 'DELETE');

// ---------- dark mode ----------
function toggleTheme() {
  const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
  document.documentElement.dataset.theme = next;
  localStorage.setItem('theme', next);
}
document.documentElement.dataset.theme = localStorage.getItem('theme') || 'light';

// ---------- dashboard stats + charts ----------
// Fills every <b data-stat="patients"> with a number from api/stats.php and returns the chart data.
async function loadStats() {
  const { stats, charts } = await api('stats.php');
  document.querySelectorAll('[data-stat]').forEach(el => {
    const v = stats[el.dataset.stat];
    el.textContent = el.dataset.format === 'money' ? money(v) : el.dataset.format === 'percent' ? v + '%' : Number(v).toLocaleString();
  });
  return charts;
}

const COLORS = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899'];
function chart(canvasId, type, data, label = '') {
  const isRound = type === 'doughnut' || type === 'pie';
  new Chart(document.getElementById(canvasId), {
    type,
    data: {
      labels: Object.keys(data),
      datasets: [{ label, data: Object.values(data).map(Number), backgroundColor: isRound ? COLORS : COLORS[0], borderColor: COLORS[0], tension: .3 }],
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: isRound } } },
  });
}

// ---------- reusable CRUD table ----------
// crudTable({ el, table, pk, title, columns, fields, filter, readonly, ... }) - see the pages for examples.
//   columns: [key, label, type?]  type = 'badge' | 'money' | 'date' | function(row) -> html
//   noAdd: true hides the Add button;  extra: [{label, run(row)}] adds custom row buttons
//   fields:  [name, label, type?, options?]  type = text | number | date | time | email | textarea | array of options | 'patient' | 'doctor'
function crudTable(o) {
  const root = document.querySelector(o.el);
  const modal = document.createElement('div');
  modal.className = 'modal';
  document.body.appendChild(modal);
  let rows = [], lookups = {};

  root.innerHTML = `<div class="card">
    <div class="toolbar">
      <h3>${o.title}</h3>
      ${o.filter ? `<select class="flt"><option value="">All ${o.filter.label}</option>${o.filter.options.map(x => `<option>${x}</option>`).join('')}</select>` : ''}
      <input class="q" placeholder="Search...">
      <button class="btn csv" title="Export CSV"><i class="fas fa-download"></i></button>
      ${o.readonly || o.noAdd || !o.fields ? '' : '<button class="btn primary add"><i class="fas fa-plus"></i> Add</button>'}
    </div>
    <div class="table-wrap"></div></div>`;
  const box = root.querySelector('.table-wrap');

  const cell = (r, [key, , type]) =>
    typeof type === 'function' ? type(r) : type === 'badge' ? badge(r[key]) : type === 'money' ? money(r[key]) : esc(r[key] ?? '-');

  function draw() {
    const q = root.querySelector('.q').value.toLowerCase();
    const f = root.querySelector('.flt')?.value;
    const shown = rows.filter(r => (!f || r[o.filter.key] === f) && (!q || JSON.stringify(Object.values(r)).toLowerCase().includes(q)));
    const canEdit = !o.readonly && o.fields;
    const actions = r => `${(o.extra || []).map((x, i) => `<button class="btn small" data-x="${i}" data-id="${r[o.pk]}">${x.label}</button>`).join(' ')}
      ${canEdit ? `<button class="btn small" data-edit="${r[o.pk]}"><i class="fas fa-pen"></i></button>
      <button class="btn small danger" data-del="${r[o.pk]}"><i class="fas fa-trash"></i></button>` : ''}`;
    const hasActions = canEdit || o.extra;
    box.innerHTML = shown.length ? `<table><thead><tr>${o.columns.map(c => `<th>${c[1]}</th>`).join('')}${hasActions ? '<th></th>' : ''}</tr></thead>
      <tbody>${shown.map(r => `<tr>${o.columns.map(c => `<td>${cell(r, c)}</td>`).join('')}${hasActions ? `<td>${actions(r)}</td>` : ''}</tr>`).join('')}</tbody></table>`
      : '<div class="empty">No records found</div>';
  }

  async function reload() {
    rows = await list(o.table, o.params || '');
    if (o.where) rows = rows.filter(o.where);
    draw();
    o.onChange?.(rows);
  }

  // form modal
  async function lookup(kind) {
    if (!lookups[kind]) lookups[kind] = (await list(kind + 's')).map(x => [x[kind + '_id'], x.full_name]);
    return lookups[kind];
  }
  async function openForm(row = {}) {
    const inputs = await Promise.all(o.fields.map(async ([name, label, type = 'text']) => {
      const required = label.endsWith('*') ? 'required' : '';
      const value = row[name] ?? (o.defaults?.[name] ?? '');
      let input;
      if (Array.isArray(type) || type === 'patient' || type === 'doctor') {
        const opts = Array.isArray(type) ? type.map(x => [x, x]) : await lookup(type);
        input = `<select name="${name}" ${required}><option value="">-</option>${opts.map(([v, t]) => `<option value="${esc(v)}" ${String(v) === String(value) ? 'selected' : ''}>${esc(t)}</option>`).join('')}</select>`;
      } else if (type === 'textarea') {
        input = `<textarea name="${name}" rows="2" ${required}>${esc(value)}</textarea>`;
      } else {
        input = `<input type="${type}" name="${name}" value="${esc(value)}" ${required} ${type === 'number' ? 'step="any"' : ''}>`;
      }
      return `<div class="${type === 'textarea' ? 'full' : ''}"><label>${label}</label>${input}</div>`;
    }));
    modal.innerHTML = `<form><h3>${row[o.pk] ? 'Edit' : 'Add'} ${o.title}</h3><div class="fields">${inputs.join('')}</div>
      <div class="actions"><button type="button" class="btn cancel">Cancel</button><button class="btn primary">Save</button></div></form>`;
    modal.classList.add('open');
    modal.querySelector('.cancel').onclick = () => modal.classList.remove('open');
    modal.querySelector('form').onsubmit = async e => {
      e.preventDefault();
      try {
        await save(o.table, row[o.pk], Object.fromEntries(new FormData(e.target)));
        modal.classList.remove('open');
        toast('Saved');
        reload();
      } catch (err) { toast(err.message, true); }
    };
  }

  // events
  root.querySelector('.q').oninput = draw;
  root.querySelector('.flt')?.addEventListener('change', draw);
  root.querySelector('.add')?.addEventListener('click', () => openForm());
  root.querySelector('.csv').onclick = () => {
    const lines = [o.columns.map(c => c[1]), ...rows.map(r => o.columns.map(c => r[c[0]] ?? ''))];
    const csv = lines.map(l => l.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
    const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob([csv])), download: o.table + '.csv' });
    a.click();
  };
  box.onclick = async e => {
    const b = e.target.closest('button');
    if (!b) return;
    const find = id => rows.find(r => String(r[o.pk]) === id);
    if (b.dataset.edit) openForm(find(b.dataset.edit));
    if (b.dataset.del && confirm('Delete this record?')) {
      try { await remove(o.table, b.dataset.del); toast('Deleted'); reload(); } catch (err) { toast(err.message, true); }
    }
    if (b.dataset.x) { await o.extra[b.dataset.x].run(find(b.dataset.id)); reload(); }
  };

  reload().catch(err => (box.innerHTML = `<div class="empty">${esc(err.message)}</div>`));
  return { reload };
}
