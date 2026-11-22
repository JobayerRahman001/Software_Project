<?php
// crud.php: one API for all tables, plus a designed records manager.
//   Browser visit (no ?format=json)                 -> the records page
//   GET    crud.php?table=patients[&id=1][&status=Pending]   -> list / single / filtered (JSON)
//   POST   crud.php?table=patients   {json body}             -> create
//   PUT    crud.php?table=patients&id=1   {json body}        -> update
//   DELETE crud.php?table=patients&id=1                      -> delete
//   GET    crud.php?meta=tables                              -> tables this user may open (used by the page)
//   GET    crud.php?table=patients&meta=1                    -> column details (used by the page)
// Lives in: <project>/api/crud.php
require_once '../includes/auth.php';

// Page settings: paths from THIS file. Adjust if your folders differ.
$ROOT = '../';
$LOGIN_PAGE  = 'auth/login.php';
$LOGOUT_PAGE = 'auth/logout.php';

// table => [primary key, has patient_id?, has doctor_id?, roles allowed to change data]
$TABLES = [
    'patients'            => ['patient_id',        0, 0, ['admin', 'staff', 'doctor']],
    'doctors'             => ['doctor_id',         0, 0, ['admin', 'staff']],
    'staff'               => ['staff_id',          0, 0, ['admin']],
    'appointments'        => ['appointment_id',    1, 1, ['admin', 'staff', 'doctor', 'patient']],
    'medical_records'     => ['record_id',         1, 1, ['admin', 'doctor']],
    'prescriptions'       => ['prescription_id',   1, 1, ['admin', 'doctor']],
    'investigations'      => ['investigation_id',  1, 1, ['admin', 'doctor', 'datascientist']],
    'billing'             => ['bill_id',           1, 0, ['admin', 'staff', 'financial']],
    'insurance_claims'    => ['claim_id',          1, 0, ['admin', 'staff', 'financial', 'insurance']],
    'symptoms_input'      => ['symptom_id',        1, 0, ['admin', 'doctor', 'datascientist', 'patient']],
    'disease_predictions' => ['prediction_id',     1, 0, ['admin', 'doctor', 'datascientist']],
    'recommendations'     => ['recommendation_id', 1, 0, ['admin', 'doctor', 'datascientist']],
    'health_reminders'    => ['reminder_id',       1, 0, ['admin', 'doctor', 'datascientist', 'patient']],
];

$wantsHtml = $_SERVER['REQUEST_METHOD'] === 'GET'
    && !isset($_GET['format']) && !isset($_GET['meta'])
    && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html');

/* --------------------------------------------------------------- PAGE */
if ($wantsHtml) {
    header('Content-Type: text/html; charset=utf-8');
    $u = $_SESSION['user'] ?? null;
    if (!$u) { header('Location: ' . $LOGIN_PAGE); exit; }
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $home = isset(HOME[$u['role']]) ? $ROOT . 'dashboards/' . HOME[$u['role']] : $ROOT;
    $canInsights = in_array($u['role'], ['admin', 'doctor', 'staff', 'financial', 'datascientist', 'insurance'], true);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Records | Hospital Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --ice:#EAF3F6; --ink:#0B2A3C; --soft:#3E5A6B; --signal:#00A3A1; --deep:#007B7A; --line:#C9DCE3; --alert:#B3312F; --ok:#0A7A5C; --font:"Sora","Segoe UI",system-ui,sans-serif; }
  * { box-sizing:border-box; margin:0; }
  body { font-family:var(--font); background:var(--ice); color:var(--ink); line-height:1.5; -webkit-font-smoothing:antialiased; }
  .top { background:var(--ink); color:#fff; height:60px; padding:0 24px; display:flex; align-items:center; gap:20px;
         background-image:linear-gradient(rgba(191,233,234,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(191,233,234,.07) 1px,transparent 1px); background-size:32px 32px; }
  .brand { display:flex; align-items:center; gap:10px; font-weight:600; }
  .mark { width:30px; height:30px; border-radius:8px; background:var(--signal); display:grid; place-items:center; } .mark svg { width:18px; height:18px; }
  .top nav { margin-left:auto; display:flex; align-items:center; gap:18px; font-size:.9rem; }
  .top a { color:#CFE3EA; text-decoration:none; } .top a:hover,.top a.on { color:#fff; }
  .top a.on { border-bottom:2px solid var(--signal); padding-bottom:3px; }
  .who { color:#A9C4D0; font-size:.85rem; }
  .layout { display:grid; grid-template-columns:230px minmax(0,1fr); gap:24px; max-width:1400px; margin:0 auto; padding:26px 24px 60px; align-items:start; }
  .side { background:#fff; border:1px solid var(--line); border-radius:14px; padding:10px; position:sticky; top:16px; }
  .side h2 { font-size:.8rem; font-weight:500; color:var(--soft); padding:8px 10px 6px; }
  .side button { display:block; width:100%; text-align:left; border:0; background:transparent; font:inherit; font-size:.92rem; color:var(--ink); padding:9px 12px; border-radius:9px; cursor:pointer; }
  .side button:hover { background:var(--ice); }
  .side button[aria-current="true"] { background:var(--ink); color:#fff; }
  .side button:focus-visible { outline:3px solid var(--signal); outline-offset:1px; }
  .bar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
  .bar h1 { font-size:1.6rem; font-weight:500; letter-spacing:-.015em; margin-right:auto; }
  .count { font-size:.85rem; color:var(--soft); }
  .search { height:42px; width:240px; padding:0 14px; font:inherit; border:1px solid var(--line); border-radius:10px; background:#fff; color:var(--ink); }
  .search:focus,.in:focus { outline:none; border-color:var(--signal); box-shadow:0 0 0 4px rgba(0,163,161,.18); }
  .btn { height:42px; padding:0 18px; border:0; border-radius:10px; background:var(--signal); color:#fff; font:inherit; font-weight:600; font-size:.92rem; cursor:pointer; }
  .btn:hover { background:var(--deep); } .btn:focus-visible { outline:3px solid var(--ink); outline-offset:2px; }
  .btn.ghost { background:#fff; color:var(--ink); border:1px solid var(--line); font-weight:500; } .btn.ghost:hover { border-color:var(--signal); background:#fff; }
  .btn.danger { background:var(--alert); } .btn.danger:hover { background:#8E2523; }
  .btn[disabled] { opacity:.7; cursor:progress; }
  .tablewrap { background:#fff; border:1px solid var(--line); border-radius:14px; overflow:auto; max-height:calc(100vh - 210px); }
  table { border-collapse:collapse; width:100%; font-size:.88rem; }
  th { position:sticky; top:0; background:#F4F9FB; text-align:left; font-weight:600; font-size:.8rem; color:var(--soft); padding:12px 14px; border-bottom:1px solid var(--line); white-space:nowrap; }
  td { padding:11px 14px; border-bottom:1px solid #E6EFF3; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  tr:hover td { background:#F8FCFD; }
  td.null { color:#9BB1BC; }
  td.act { white-space:nowrap; text-align:right; position:sticky; right:0; background:#fff; }
  td.act button { border:1px solid var(--line); background:#fff; font:inherit; font-size:.8rem; padding:5px 11px; border-radius:8px; cursor:pointer; margin-left:6px; }
  td.act button:hover { border-color:var(--signal); } td.act button.del:hover { border-color:var(--alert); color:var(--alert); }
  .state { padding:50px 20px; text-align:center; color:var(--soft); }
  dialog { border:0; border-radius:16px; padding:0; width:min(640px,94vw); box-shadow:0 20px 60px rgba(11,42,60,.3); color:var(--ink); font-family:var(--font); }
  dialog::backdrop { background:rgba(11,42,60,.55); }
  .dh { padding:20px 24px 4px; } .dh h3 { font-size:1.25rem; font-weight:500; }
  .db { padding:12px 24px; max-height:60vh; overflow:auto; display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .db .full { grid-column:1/-1; }
  .db label { display:block; font-size:.85rem; font-weight:500; margin-bottom:5px; }
  .db label b { color:var(--alert); font-weight:500; }
  .in { width:100%; height:44px; padding:0 12px; font:inherit; font-size:.92rem; color:var(--ink); background:#fff; border:1px solid var(--line); border-radius:10px; }
  textarea.in { height:90px; padding:10px 12px; resize:vertical; }
  .df { padding:14px 24px 22px; display:flex; justify-content:flex-end; gap:10px; }
  .dmsg { margin:0 24px; padding:10px 12px; border-radius:10px; background:#FBECEB; color:var(--alert); border:1px solid #F0C4C3; font-size:.88rem; display:none; }
  .dmsg.show { display:block; }
  .toast { position:fixed; left:50%; bottom:28px; transform:translateX(-50%) translateY(20px); background:var(--ink); color:#fff; padding:12px 20px; border-radius:12px; font-size:.9rem; opacity:0; pointer-events:none; transition:opacity .2s,transform .2s; z-index:10; }
  .toast.show { opacity:1; transform:translateX(-50%); } .toast.err { background:var(--alert); }
  @media (max-width:860px) {
    .layout { grid-template-columns:1fr; } .side { position:static; display:flex; overflow-x:auto; gap:4px; padding:8px; } .side h2 { display:none; }
    .side button { white-space:nowrap; width:auto; } .who { display:none; } .search { width:100%; } .db { grid-template-columns:1fr; }
  }
  @media (prefers-reduced-motion:reduce) { .toast { transition:none; } }
</style>
</head>
<body>
<header class="top">
  <div class="brand"><span class="mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="#0B2A3C" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>Hospital Management</div>
  <nav>
    <span class="who"><?= $e($u['name'] ?? '') ?> · <?= $e(ucfirst($u['role'])) ?></span>
    <a href="<?= $e($home) ?>">Dashboard</a>
    <a href="crud.php" class="on">Records</a>
    <?php if ($canInsights): ?><a href="stats.php">Insights</a><?php endif; ?>
    <a href="<?= $e($LOGOUT_PAGE) ?>">Sign out</a>
  </nav>
</header>

<div class="layout">
  <nav class="side" aria-label="Tables"><h2>Tables</h2><div id="tables"></div></nav>
  <section>
    <div class="bar">
      <h1 id="title">Records</h1>
      <span class="count" id="count"></span>
      <input class="search" id="q" type="search" placeholder="Search this table" aria-label="Search this table">
      <button class="btn" id="add" hidden>New record</button>
    </div>
    <div class="tablewrap" id="wrap"><div class="state">Loading...</div></div>
  </section>
</div>

<dialog id="form"><form method="dialog" id="f" novalidate>
  <div class="dh"><h3 id="ft">New record</h3></div>
  <div class="db" id="fields"></div>
  <div class="dmsg" id="fmsg" role="alert"></div>
  <div class="df"><button type="button" class="btn ghost" id="fc">Cancel</button><button type="submit" class="btn" id="fs">Save</button></div>
</form></dialog>

<dialog id="confirm"><div class="dh"><h3>Delete this record?</h3></div>
  <div class="db"><p class="full" style="color:var(--soft)">This cannot be undone.</p></div>
  <div class="df"><button type="button" class="btn ghost" id="cn">Keep it</button><button type="button" class="btn danger" id="cy">Delete</button></div>
</dialog>
<div class="toast" id="toast" role="status"></div>

<script>
  const $ = id => document.getElementById(id);
  const API = location.pathname;
  const HIDE = ['created_at', 'updated_at', 'password_hash'];
  let tables = [], cur = null, meta = null, rows = [], canWrite = false, editRow = null, role = '';

  const human = s => s.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase());
  function el(tag, cls, text) { const n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; }

  async function api(qs, opts = {}) {
    const res = await fetch(API + qs, { credentials: 'same-origin', ...opts, headers: opts.body ? { 'Content-Type': 'application/json' } : {} });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Request failed');
    return data;
  }
  let tt;
  function toast(text, bad) { const t = $('toast'); t.textContent = text; t.className = 'toast show' + (bad ? ' err' : ''); clearTimeout(tt); tt = setTimeout(() => t.className = 'toast', 2800); }

  function renderTables() {
    const box = $('tables'); box.replaceChildren();
    tables.forEach(t => {
      const b = el('button', '', t.label); b.type = 'button'; b.setAttribute('aria-current', t.name === cur);
      b.onclick = () => open(t.name); box.appendChild(b);
    });
  }

  async function open(name) {
    cur = name; $('q').value = ''; renderTables();
    const t = tables.find(x => x.name === name);
    $('title').textContent = t ? t.label : human(name);
    $('wrap').replaceChildren(el('div', 'state', 'Loading...'));
    history.replaceState(null, '', '?table=' + encodeURIComponent(name));
    try {
      const [m, d] = await Promise.all([api('?table=' + name + '&meta=1'), api('?table=' + name)]);
      meta = m; rows = d.data; canWrite = !!m.can_write;
      $('add').hidden = !canWrite;
      drawTable();
    } catch (err) { $('wrap').replaceChildren(el('div', 'state', err.message)); $('count').textContent = ''; $('add').hidden = true; }
  }

  function drawTable() {
    const q = $('q').value.trim().toLowerCase();
    const list = q ? rows.filter(r => Object.values(r).join(' ').toLowerCase().includes(q)) : rows;
    $('count').textContent = list.length + (list.length === 1 ? ' record' : ' records');
    const keys = (rows[0] ? Object.keys(rows[0]) : meta.columns.map(c => c.name)).filter(k => !HIDE.includes(k));
    if (!list.length) { $('wrap').replaceChildren(el('div', 'state', rows.length ? 'Nothing matches your search.' : 'No records yet.' + (canWrite ? ' Use "New record" to add the first one.' : ''))); return; }
    const table = el('table'), head = el('tr');
    keys.forEach(k => head.appendChild(el('th', '', human(k))));
    if (canWrite) head.appendChild(el('th', ''));
    const thead = el('thead'); thead.appendChild(head); table.appendChild(thead);
    const body = el('tbody');
    list.slice(0, 300).forEach(r => {
      const tr = el('tr');
      keys.forEach(k => { const v = r[k]; const td = el('td', v === null || v === '' ? 'null' : '', v === null || v === '' ? '—' : String(v)); if (v) td.title = String(v); tr.appendChild(td); });
      if (canWrite) {
        const td = el('td', 'act'), ed = el('button', '', 'Edit'), dl = el('button', 'del', 'Delete');
        ed.type = dl.type = 'button'; ed.onclick = () => form(r); dl.onclick = () => ask(r);
        td.append(ed, dl); tr.appendChild(td);
      }
      body.appendChild(tr);
    });
    table.appendChild(body);
    const wrap = $('wrap'); wrap.replaceChildren(table);
    if (list.length > 300) wrap.appendChild(el('div', 'state', 'Showing the first 300 records. Use search to narrow down.'));
  }
  $('q').addEventListener('input', () => meta && drawTable());

  /* ---- create / edit form ---- */
  function field(c, value) {
    let inp;
    if (c.kind === 'textarea') { inp = el('textarea', 'in'); inp.value = value ?? ''; }
    else if (c.kind === 'enum' || c.kind === 'bool') {
      inp = el('select', 'in');
      const opts = c.kind === 'bool' ? [['0', 'No'], ['1', 'Yes']] : c.options.map(o => [o, o]);
      if (!c.required || value == null) inp.appendChild(new Option('Select', ''));
      opts.forEach(([v, l]) => inp.appendChild(new Option(l, v)));
      inp.value = value == null ? '' : String(value);
    } else {
      inp = el('input', 'in');
      inp.type = { number: 'number', decimal: 'number', date: 'date', datetime: 'datetime-local', time: 'time' }[c.kind] || (/email/.test(c.name) ? 'email' : 'text');
      if (c.kind === 'decimal') inp.step = 'any';
      inp.value = value == null ? '' : (c.kind === 'datetime' ? String(value).replace(' ', 'T').slice(0, 16) : String(value));
    }
    inp.name = c.name; inp.id = 'f_' + c.name;
    return inp;
  }

  function form(row) {
    editRow = row || null; $('fmsg').className = 'dmsg';
    $('ft').textContent = (row ? 'Edit ' : 'New ') + ($('title').textContent.toLowerCase());
    const box = $('fields'); box.replaceChildren();
    meta.columns.filter(c => !c.auto && c.name !== meta.pk && !HIDE.includes(c.name) && !(role === 'patient' && c.name === 'patient_id')).forEach(c => {
      const w = el('div', c.kind === 'textarea' ? 'full' : ''), l = el('label', '', human(c.name)); l.htmlFor = 'f_' + c.name;
      if (c.required) { const s = el('b', '', ' *'); l.appendChild(s); }
      w.append(l, field(c, row ? row[c.name] : null)); box.appendChild(w);
    });
    $('form').showModal();
  }
  $('add').onclick = () => form(null);
  $('fc').onclick = () => $('form').close();

  $('f').addEventListener('submit', async ev => {
    ev.preventDefault();
    const data = {};
    meta.columns.forEach(c => {
      const i = $('f_' + c.name); if (!i) return;
      let v = i.value;
      if (c.kind === 'datetime' && v) v = v.replace('T', ' ') + (v.length === 16 ? ':00' : '');
      data[c.name] = v;
    });
    const missing = meta.columns.find(c => c.required && $('f_' + c.name) && $('f_' + c.name).value === '');
    if (missing) { $('fmsg').textContent = human(missing.name) + ' is required.'; $('fmsg').className = 'dmsg show'; $('f_' + missing.name).focus(); return; }
    const btn = $('fs'); btn.disabled = true; btn.textContent = 'Saving...';
    try {
      if (editRow) await api('?table=' + cur + '&id=' + encodeURIComponent(editRow[meta.pk]), { method: 'PUT', body: JSON.stringify(data) });
      else await api('?table=' + cur, { method: 'POST', body: JSON.stringify(data) });
      $('form').close(); toast(editRow ? 'Changes saved' : 'Record added'); open(cur);
    } catch (err) { $('fmsg').textContent = err.message; $('fmsg').className = 'dmsg show'; }
    btn.disabled = false; btn.textContent = 'Save';
  });

  /* ---- delete ---- */
  let toDelete = null;
  function ask(r) { toDelete = r; $('confirm').showModal(); }
  $('cn').onclick = () => $('confirm').close();
  $('cy').onclick = async () => {
    try { await api('?table=' + cur + '&id=' + encodeURIComponent(toDelete[meta.pk]), { method: 'DELETE' }); $('confirm').close(); toast('Record deleted'); open(cur); }
    catch (err) { $('confirm').close(); toast(err.message, true); }
  };

  (async function init() {
    try {
      const d = await api('?meta=tables'); tables = d.tables; role = d.role;
      if (!tables.length) { $('wrap').replaceChildren(el('div', 'state', 'No tables are available for your role.')); return; }
      const wanted = new URLSearchParams(location.search).get('table');
      open(tables.some(t => t.name === wanted) ? wanted : tables[0].name);
    } catch (err) { $('wrap').replaceChildren(el('div', 'state', err.message)); }
  })();
</script>
</body>
</html>
<?php
    exit;
}

/* ---------------------------------------------------------------- API */
$user = require_api_login();
$db = db();

// Which tables can this person open? (used by the page's sidebar)
if (($_GET['meta'] ?? '') === 'tables') {
    $list = [];
    foreach ($TABLES as $name => [$pk, $hasPatient, $hasDoctor, $writeRoles]) {
        $readable = $user['role'] !== 'patient' || $name === 'patients' || $hasPatient || $name === 'doctors';
        if ($readable) $list[] = ['name' => $name, 'label' => ucfirst(str_replace('_', ' ', $name)), 'can_write' => in_array($user['role'], $writeRoles)];
    }
    json_out(['status' => 'success', 'role' => $user['role'], 'tables' => $list]);
}

$table = $_GET['table'] ?? '';
if (!isset($TABLES[$table])) json_out(['status' => 'error', 'message' => 'Unknown table'], 404);
[$pk, $hasPatient, $hasDoctor, $writeRoles] = $TABLES[$table];

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$ownKey = ($table === 'patients' || $hasPatient) ? 'patient_id' : null;
$isOwn  = $user['role'] === 'patient' && $ownKey;   // patients only see their own rows

if ($method !== 'GET' && !in_array($user['role'], $writeRoles)) {
    json_out(['status' => 'error', 'message' => 'You cannot change this data'], 403);
}
// Patients may only read the doctor list (needed to book an appointment), nothing else outside their own data.
if ($user['role'] === 'patient' && !$ownKey && $table !== 'doctors') json_out(['status' => 'error', 'message' => 'Not allowed'], 403);

$colRows = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll();
$cols = array_column($colRows, 'Field');

// Column details so the page can build a proper form.
if (isset($_GET['meta'])) {
    $columns = array_map(function ($c) {
        $type = strtolower($c['Type']);
        $auto = stripos($c['Extra'], 'auto_increment') !== false;
        $kind = 'text'; $options = null;
        if ($type === 'tinyint(1)')                                        $kind = 'bool';
        elseif (preg_match('/^(tiny|small|medium|big)?int/', $type))       $kind = 'number';
        elseif (preg_match('/^(decimal|numeric|float|double)/', $type))    $kind = 'decimal';
        elseif (str_starts_with($type, 'datetime') || str_starts_with($type, 'timestamp')) $kind = 'datetime';
        elseif ($type === 'date')                                          $kind = 'date';
        elseif (str_starts_with($type, 'time'))                            $kind = 'time';
        elseif (str_contains($type, 'text'))                               $kind = 'textarea';
        elseif (str_starts_with($type, 'enum(')) {
            $kind = 'enum';
            preg_match_all("/'((?:[^']|'')*)'/", $c['Type'], $m);
            $options = array_map(fn($s) => str_replace("''", "'", $s), $m[1]);
        }
        return ['name' => $c['Field'], 'kind' => $kind, 'options' => $options, 'auto' => $auto,
                'required' => $c['Null'] === 'NO' && $c['Default'] === null && !$auto];
    }, $colRows);
    json_out(['status' => 'success', 'pk' => $pk, 'columns' => $columns, 'can_write' => in_array($user['role'], $writeRoles)]);
}

try {
    if ($method === 'GET') {
        $select = 't.*';
        $join = '';
        if (in_array('first_name', $cols))    $select .= ", CONCAT(t.first_name, ' ', t.last_name) AS full_name";
        if (in_array('date_of_birth', $cols)) $select .= ', TIMESTAMPDIFF(YEAR, t.date_of_birth, CURDATE()) AS age';
        if ($hasPatient) { $select .= ", CONCAT(p.first_name, ' ', p.last_name) AS patient_name"; $join .= ' LEFT JOIN patients p ON p.patient_id = t.patient_id'; }
        if ($hasDoctor)  { $select .= ", CONCAT(d.first_name, ' ', d.last_name) AS doctor_name";  $join .= ' LEFT JOIN doctors d ON d.doctor_id = t.doctor_id'; }

        // Filters: id, plus any real column given in the URL (?status=Paid)
        $where = ['1=1'];
        $args = [];
        if ($id !== null) { $where[] = "t.$pk = ?"; $args[] = $id; }
        foreach ($_GET as $key => $value) {
            if (in_array($key, $cols) && $key !== $pk) { $where[] = "t.`$key` = ?"; $args[] = $value; }
        }
        if ($isOwn) { $where[] = "t.$ownKey = ?"; $args[] = $user['patient_id']; }

        $stmt = $db->prepare("SELECT $select FROM `$table` t $join WHERE " . implode(' AND ', $where) . " ORDER BY t.$pk DESC");
        $stmt->execute($args);
        $rows = $stmt->fetchAll();

        if ($id !== null) {
            $rows ? json_out(['status' => 'success', 'data' => $rows[0]]) : json_out(['status' => 'error', 'message' => 'Not found'], 404);
        }
        json_out(['status' => 'success', 'count' => count($rows), 'data' => $rows]);
    }

    if ($method === 'DELETE') {
        if (!$id) json_out(['status' => 'error', 'message' => 'id is required'], 400);
        $sql = "DELETE FROM `$table` WHERE $pk = ?";
        $args = [$id];
        if ($isOwn) { $sql .= " AND $ownKey = ?"; $args[] = $user['patient_id']; }
        $db->prepare($sql)->execute($args);
        json_out(['status' => 'success', 'message' => 'Deleted']);
    }

    // POST / PUT: keep only real columns, turn "" into NULL
    $data = [];
    foreach (body() as $key => $value) {
        if (in_array($key, $cols) && !in_array($key, [$pk, 'created_at', 'updated_at'])) $data[$key] = $value === '' ? null : $value;
    }
    if ($isOwn && $hasPatient) $data['patient_id'] = $user['patient_id'];   // patients can only write for themselves

    // Patients: calculate BMI from height (cm) and weight (kg)
    if ($table === 'patients' && !empty($data['height']) && !empty($data['weight'])) {
        $data['bmi'] = round($data['weight'] / (($data['height'] / 100) ** 2), 2);
    }
    if (!$data) json_out(['status' => 'error', 'message' => 'No data received'], 400);

    if ($method === 'POST') {
        $names = '`' . implode('`, `', array_keys($data)) . '`';
        $marks = implode(', ', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO `$table` ($names) VALUES ($marks)")->execute(array_values($data));
        json_out(['status' => 'success', 'id' => $db->lastInsertId()], 201);
    }

    if ($method === 'PUT') {
        if (!$id) json_out(['status' => 'error', 'message' => 'id is required'], 400);
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $sql = "UPDATE `$table` SET $set WHERE $pk = ?";
        $args = [...array_values($data), $id];
        if ($isOwn) { $sql .= " AND $ownKey = ?"; $args[] = $user['patient_id']; }
        $db->prepare($sql)->execute($args);
        json_out(['status' => 'success', 'message' => 'Updated']);
    }

    json_out(['status' => 'error', 'message' => 'Method not allowed'], 405);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_out(['status' => 'error', 'message' => 'Could not save. Check required fields and duplicates.'], 400);
}