<?php
// stats.php
//  - fetch / ?format=json -> JSON numbers and chart data (same output as before)
//  - browser page visit   -> a designed analytics page
// Lives in: <project>/api/stats.php
require_once '../includes/auth.php';

// Page settings: paths from THIS file. Adjust if your folders differ.
$ROOT = '../';
$LOGIN_PAGE  = 'auth/login.php';
$LOGOUT_PAGE = 'auth/logout.php';
$ALLOWED = ['admin', 'doctor', 'staff', 'financial', 'datascientist', 'insurance'];

$wantsHtml = $_SERVER['REQUEST_METHOD'] === 'GET'
    && !isset($_GET['format'])
    && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html');

/* --------------------------------------------------------------- PAGE */
if ($wantsHtml) {
    header('Content-Type: text/html; charset=utf-8');
    $u = $_SESSION['user'] ?? null;
    if (!$u) { header('Location: ' . $LOGIN_PAGE); exit; }
    $home = isset(HOME[$u['role']]) ? $ROOT . 'dashboards/' . HOME[$u['role']] : $ROOT;
    if (!in_array($u['role'], $ALLOWED, true)) { header('Location: ' . $home); exit; }
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Insights | Hospital Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
  :root { --ice:#EAF3F6; --ink:#0B2A3C; --soft:#3E5A6B; --signal:#00A3A1; --deep:#007B7A; --line:#C9DCE3; --alert:#B3312F; --font:"Sora","Segoe UI",system-ui,sans-serif; }
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
  main { max-width:1280px; margin:0 auto; padding:30px 24px 64px; }
  .title { display:flex; justify-content:space-between; align-items:end; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
  h1 { font-size:1.8rem; font-weight:500; letter-spacing:-.015em; }
  .sub { color:var(--soft); font-size:.92rem; }
  .btn { border:1px solid var(--line); background:#fff; color:var(--ink); font:inherit; font-size:.9rem; padding:9px 16px; border-radius:10px; cursor:pointer; }
  .btn:hover { border-color:var(--signal); } .btn:focus-visible { outline:3px solid var(--signal); }
  h2.group { font-size:.95rem; font-weight:600; margin:26px 0 12px; color:var(--soft); }
  .kpis { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:14px; }
  .kpi { background:#fff; border:1px solid var(--line); border-radius:14px; padding:16px 18px; }
  .kpi .n { font-size:1.75rem; font-weight:500; letter-spacing:-.02em; }
  .kpi .l { font-size:.85rem; color:var(--soft); margin-top:2px; }
  .kpi.hot { background:var(--ink); border-color:var(--ink); } .kpi.hot .n { color:#7FE3E1; } .kpi.hot .l { color:#A9C4D0; }
  .charts { display:grid; grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); gap:18px; margin-top:6px; }
  .chart { background:#fff; border:1px solid var(--line); border-radius:14px; padding:18px; }
  .chart h3 { font-size:.95rem; font-weight:600; margin-bottom:12px; }
  .chart .box { position:relative; height:250px; }
  .none { display:grid; place-items:center; height:100%; color:var(--soft); font-size:.9rem; }
  .err { background:#FBECEB; border:1px solid #F0C4C3; color:var(--alert); padding:14px 16px; border-radius:12px; }
  .skeleton { height:92px; border-radius:14px; background:linear-gradient(90deg,#DDEAEF,#EEF5F7,#DDEAEF); background-size:200% 100%; animation:sh 1.4s linear infinite; }
  @keyframes sh { to { background-position:-200% 0; } }
  @media (max-width:640px) { .charts { grid-template-columns:1fr; } .who { display:none; } }
  @media (prefers-reduced-motion:reduce) { .skeleton { animation:none; } }
</style>
</head>
<body>
<header class="top">
  <div class="brand"><span class="mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="#0B2A3C" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>Hospital Management</div>
  <nav>
    <span class="who"><?= $e($u['name'] ?? '') ?> · <?= $e(ucfirst($u['role'])) ?></span>
    <a href="<?= $e($home) ?>">Dashboard</a>
    <a href="crud.php">Records</a>
    <a href="stats.php" class="on">Insights</a>
    <a href="<?= $e($LOGOUT_PAGE) ?>">Sign out</a>
  </nav>
</header>

<main>
  <div class="title">
    <div><h1>Insights</h1><p class="sub" id="stamp">Loading the latest numbers...</p></div>
    <button class="btn" id="refresh">Refresh</button>
  </div>
  <div id="root"><div class="kpis"><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div></div></div>
</main>

<script>
  const root = document.getElementById('root');
  const PALETTE = ['#00A3A1', '#0B2A3C', '#7FC8C6', '#3E5A6B', '#C98A1B', '#B3312F', '#8FB5C4'];
  const num = v => Number(v).toLocaleString(undefined, { maximumFractionDigits: 1 });

  const GROUPS = [
    ['People', [['patients', 'Patients'], ['doctors', 'Active doctors'], ['staff', 'Active staff']]],
    ['Today', [['appointments_today', 'Appointments today', 1], ['checked_in', 'Checked in'], ['prescriptions', 'Active prescriptions'], ['reminders', 'Open reminders']]],
    ['Billing', [['revenue', 'Total billed'], ['collected', 'Collected'], ['pending_bills', 'Pending bills']]],
    ['Insurance', [['claims', 'Claims'], ['pending_claims', 'Pending claims'], ['approved_claims', 'Approved claims'], ['rejection_rate', 'Rejection rate (%)']]],
    ['Clinical AI', [['predictions', 'Predictions'], ['avg_confidence', 'Average confidence (%)'], ['investigations', 'Investigations']]],
  ];
  const CHARTS = [
    ['patients_by_month', 'New patients per month', 'line'], ['revenue_by_month', 'Billing per month', 'bar'],
    ['diseases', 'Top predicted conditions', 'bar-h'], ['age_groups', 'Patients by age group', 'bar'],
    ['payment_status', 'Bill status', 'doughnut'], ['claim_status', 'Claim status', 'doughnut'], ['severity', 'Prediction severity', 'doughnut'],
  ];

  function el(tag, cls, text) { const n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; }
  let instances = [];

  function chart(canvas, type, obj) {
    const labels = Object.keys(obj), values = Object.values(obj).map(Number);
    const round = type === 'doughnut';
    const cfg = {
      type: type === 'bar-h' ? 'bar' : type,
      data: { labels, datasets: [{ data: values, backgroundColor: round ? PALETTE : '#00A3A1', borderColor: type === 'line' ? '#00A3A1' : undefined, borderWidth: type === 'line' ? 2.5 : 0, borderRadius: round ? 0 : 6, tension: .35, pointRadius: type === 'line' ? 3 : 0, fill: false }] },
      options: {
        indexAxis: type === 'bar-h' ? 'y' : 'x', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: round, position: 'bottom', labels: { boxWidth: 12, font: { family: 'Sora' } } } },
        scales: round ? {} : { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#E3EDF1' } } },
        cutout: round ? '62%' : undefined
      }
    };
    return new Chart(canvas, cfg);
  }

  function draw(data) {
    instances.forEach(c => c.destroy()); instances = [];
    root.replaceChildren();
    GROUPS.forEach(([name, items]) => {
      root.appendChild(el('h2', 'group', name));
      const wrap = el('div', 'kpis');
      items.forEach(([key, label, hot]) => {
        const k = el('div', 'kpi' + (hot ? ' hot' : '')); k.append(el('div', 'n', num(data.stats[key] ?? 0)), el('div', 'l', label)); wrap.appendChild(k);
      });
      root.appendChild(wrap);
    });
    root.appendChild(el('h2', 'group', 'Trends'));
    const grid = el('div', 'charts');
    CHARTS.forEach(([key, title, type]) => {
      const card = el('div', 'chart'), box = el('div', 'box');
      card.appendChild(el('h3', '', title)); card.appendChild(box);
      const obj = (data.charts && data.charts[key]) || {};
      if (!Object.keys(obj).length) box.appendChild(el('div', 'none', 'No data yet'));
      else { const c = document.createElement('canvas'); box.appendChild(c); instances.push(chart(c, type, obj)); }
      grid.appendChild(card);
    });
    root.appendChild(grid);
    document.getElementById('stamp').textContent = 'Updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  async function load() {
    try {
      const res = await fetch(location.pathname + '?format=json', { credentials: 'same-origin' });
      const data = await res.json();
      if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Could not load the numbers.');
      draw(data);
    } catch (err) {
      root.replaceChildren(el('div', 'err', err.message || 'Could not load the numbers. Try again.'));
      document.getElementById('stamp').textContent = 'Not updated';
    }
  }
  document.getElementById('refresh').onclick = load;
  load();
</script>
</body>
</html>
<?php
    exit;
}

/* ---------------------------------------------------------------- API */
// Numbers and chart data for the dashboards (everything comes from the database).
require_api_login($ALLOWED);
$db = db();

function one(PDO $db, string $sql) { return $db->query($sql)->fetchColumn() ?: 0; }
// Two columns -> ['label' => value, ...]
function pairs(PDO $db, string $sql): array { return $db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR); }

$claims = one($db, 'SELECT COUNT(*) FROM insurance_claims');
$rejected = one($db, "SELECT COUNT(*) FROM insurance_claims WHERE status = 'Rejected'");

json_out([
    'status' => 'success',
    'stats' => [
        'patients'            => one($db, 'SELECT COUNT(*) FROM patients'),
        'doctors'             => one($db, 'SELECT COUNT(*) FROM doctors WHERE is_active = 1'),
        'staff'               => one($db, 'SELECT COUNT(*) FROM staff WHERE is_active = 1'),
        'appointments_today'  => one($db, 'SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()'),
        'checked_in'          => one($db, "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status = 'Confirmed'"),
        'prescriptions'       => one($db, 'SELECT COUNT(*) FROM prescriptions WHERE is_active = 1'),
        'pending_bills'       => one($db, "SELECT COUNT(*) FROM billing WHERE payment_status = 'Pending'"),
        'revenue'             => round(one($db, "SELECT SUM(total_amount) FROM billing WHERE payment_status <> 'Cancelled'"), 2),
        'collected'           => round(one($db, 'SELECT SUM(patient_paid + insurance_covered) FROM billing'), 2),
        'claims'              => $claims,
        'pending_claims'      => one($db, "SELECT COUNT(*) FROM insurance_claims WHERE status IN ('Submitted', 'Under Review')"),
        'approved_claims'     => one($db, "SELECT COUNT(*) FROM insurance_claims WHERE status IN ('Approved', 'Paid')"),
        'rejection_rate'      => $claims ? round($rejected / $claims * 100, 1) : 0,
        'predictions'         => one($db, 'SELECT COUNT(*) FROM disease_predictions'),
        'avg_confidence'      => round(one($db, 'SELECT AVG(confidence_score) FROM disease_predictions'), 1),
        'investigations'      => one($db, 'SELECT COUNT(*) FROM investigations'),
        'reminders'           => one($db, 'SELECT COUNT(*) FROM health_reminders WHERE is_completed = 0'),
    ],
    'charts' => [
        'patients_by_month' => pairs($db, "SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM patients GROUP BY 1 ORDER BY 1"),
        'diseases'          => pairs($db, 'SELECT predicted_disease, COUNT(*) FROM disease_predictions GROUP BY 1 ORDER BY 2 DESC LIMIT 6'),
        'revenue_by_month'  => pairs($db, "SELECT DATE_FORMAT(bill_date, '%Y-%m'), SUM(total_amount) FROM billing GROUP BY 1 ORDER BY 1"),
        'payment_status'    => pairs($db, 'SELECT payment_status, COUNT(*) FROM billing GROUP BY 1'),
        'claim_status'      => pairs($db, 'SELECT status, COUNT(*) FROM insurance_claims GROUP BY 1'),
        'age_groups'        => pairs($db, "SELECT CASE WHEN age < 18 THEN '0-17' WHEN age < 36 THEN '18-35' WHEN age < 56 THEN '36-55' ELSE '56+' END g, COUNT(*)
                                           FROM (SELECT TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) age FROM patients) x GROUP BY 1 ORDER BY 1"),
        'severity'          => pairs($db, 'SELECT severity_level, COUNT(*) FROM disease_predictions GROUP BY 1'),
    ],
]);