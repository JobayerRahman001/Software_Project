<?php
// predict.php
//  - POST (JSON)  -> rule-based symptom checker API (same behaviour as before)
//  - GET (browser) -> the symptom checker page
// Simple rule-based symptom checker. Replace $RULES with a real model later.
require_once '../../includes/auth.php';

// Page settings: paths from THIS file. Adjust if your folders differ.
$ROOT = '../../';
$LOGIN_PAGE  = '../auth/login.php';
$LOGOUT_PAGE = '../auth/logout.php';

/* ---------------------------------------------------------------- PAGE */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    $u = $_SESSION['user'] ?? null;
    if (!$u) { header('Location: ' . $LOGIN_PAGE); exit; }
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $home = isset(HOME[$u['role']]) ? $ROOT . 'dashboards/' . HOME[$u['role']] : $ROOT;
    $isPatient = $u['role'] === 'patient';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Symptom checker | Hospital Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --ice:#EAF3F6; --ink:#0B2A3C; --soft:#3E5A6B; --signal:#00A3A1; --deep:#007B7A; --line:#C9DCE3; --alert:#B3312F; --warn:#9A6408; --ok:#0A7A5C;
          --font:"Sora","Segoe UI",system-ui,sans-serif; }
  * { box-sizing:border-box; margin:0; }
  body { font-family:var(--font); background:var(--ice); color:var(--ink); line-height:1.55; -webkit-font-smoothing:antialiased; }
  .top { background:var(--ink); color:#fff; height:60px; padding:0 24px; display:flex; align-items:center; gap:20px;
         background-image:linear-gradient(rgba(191,233,234,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(191,233,234,.07) 1px,transparent 1px); background-size:32px 32px; }
  .brand { display:flex; align-items:center; gap:10px; font-weight:600; }
  .mark { width:30px; height:30px; border-radius:8px; background:var(--signal); display:grid; place-items:center; }
  .mark svg { width:18px; height:18px; }
  .top nav { margin-left:auto; display:flex; align-items:center; gap:18px; font-size:.9rem; }
  .top a { color:#CFE3EA; text-decoration:none; } .top a:hover { color:#fff; }
  .who { color:#A9C4D0; font-size:.85rem; }
  main { max-width:1080px; margin:0 auto; padding:32px 24px 64px; }
  h1 { font-size:1.8rem; font-weight:500; letter-spacing:-.015em; }
  .lead { color:var(--soft); margin:6px 0 26px; max-width:60ch; }
  .grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:22px; align-items:start; }
  .card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:22px; }
  .card h2 { font-size:1.05rem; font-weight:600; margin-bottom:14px; }
  label { display:block; font-size:.9rem; font-weight:500; margin-bottom:6px; }
  textarea,input { width:100%; font:inherit; color:var(--ink); background:#fff; border:1px solid var(--line); border-radius:12px; padding:14px 16px; }
  textarea { min-height:130px; resize:vertical; }
  input { height:48px; padding:0 16px; }
  textarea:focus,input:focus { outline:none; border-color:var(--signal); box-shadow:0 0 0 4px rgba(0,163,161,.18); }
  .chips { display:flex; flex-wrap:wrap; gap:8px; margin:14px 0 18px; }
  .chip { border:1px solid var(--line); background:var(--ice); color:var(--ink); font:inherit; font-size:.85rem; padding:7px 13px; border-radius:999px; cursor:pointer; transition:background .15s,border-color .15s; }
  .chip:hover { border-color:var(--signal); }
  .chip[aria-pressed="true"] { background:var(--ink); color:#fff; border-color:var(--ink); }
  .chip:focus-visible { outline:3px solid var(--signal); outline-offset:2px; }
  .field { margin-top:16px; }
  .btn { width:100%; height:52px; margin-top:20px; border:0; border-radius:12px; background:var(--signal); color:#fff; font:inherit; font-weight:600; font-size:1rem; cursor:pointer; transition:background .15s; }
  .btn:hover { background:var(--deep); } .btn[disabled] { opacity:.7; cursor:progress; } .btn:focus-visible { outline:3px solid var(--ink); outline-offset:3px; }
  .msg { display:none; margin-top:14px; padding:12px 14px; border-radius:10px; font-size:.9rem; background:#FBECEB; color:var(--alert); border:1px solid #F0C4C3; }
  .msg.show { display:block; }
  .empty { text-align:center; color:var(--soft); padding:40px 10px; }
  .empty svg { width:140px; height:48px; margin-bottom:10px; }
  .urgent { background:#FBECEB; border:1px solid #F0C4C3; color:var(--alert); border-radius:12px; padding:14px 16px; margin-bottom:14px; font-weight:500; }
  .result { border:1px solid var(--line); border-radius:12px; padding:16px; margin-bottom:12px; }
  .result:first-of-type { border-color:var(--signal); }
  .head { display:flex; justify-content:space-between; gap:10px; align-items:center; }
  .head h3 { font-size:1.05rem; font-weight:600; }
  .pill { font-size:.78rem; font-weight:600; padding:3px 11px; border-radius:999px; }
  .pill.Low { background:#E3F6F1; color:var(--ok); } .pill.Medium { background:#FCF1D8; color:var(--warn); } .pill.High { background:#FBECEB; color:var(--alert); }
  .bar { height:8px; background:var(--ice); border-radius:4px; margin:12px 0 6px; overflow:hidden; }
  .bar i { display:block; height:100%; background:var(--signal); border-radius:4px; width:0; transition:width .6s ease; }
  .conf { font-size:.82rem; color:var(--soft); }
  .adv { margin-top:10px; font-size:.92rem; }
  .note { font-size:.8rem; color:var(--soft); margin-top:14px; }
  @media (max-width:820px) { .grid { grid-template-columns:1fr; } .who { display:none; } }
  @media (prefers-reduced-motion:reduce) { .bar i { transition:none; } }
</style>
</head>
<body>
<header class="top">
  <div class="brand"><span class="mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="#0B2A3C" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>Hospital Management</div>
  <nav>
    <span class="who"><?= $e($u['name'] ?? '') ?> · <?= $e(ucfirst($u['role'])) ?></span>
    <a href="<?= $e($home) ?>">Dashboard</a>
    <a href="<?= $e($LOGOUT_PAGE) ?>">Sign out</a>
  </nav>
</header>

<main>
  <h1>Symptom checker</h1>
  <p class="lead">Describe what you are feeling. We compare it with known patterns and suggest what to do next.</p>

  <div class="grid">
    <section class="card" aria-labelledby="h-in">
      <h2 id="h-in">Your symptoms</h2>
      <label for="symptoms">What are you experiencing?</label>
      <textarea id="symptoms" placeholder="For example: fever and a dry cough since yesterday"></textarea>
      <div class="chips" id="chips" role="group" aria-label="Common symptoms"></div>
      <?php if (!$isPatient): ?>
      <div class="field">
        <label for="pid">Patient ID</label>
        <input id="pid" type="number" min="1" placeholder="Needed to save the result to a patient record">
      </div>
      <?php endif; ?>
      <div class="msg" id="msg" role="alert" aria-live="assertive"></div>
      <button class="btn" id="go">Check symptoms</button>
      <p class="note">This is a guide, not a diagnosis. If symptoms are severe or getting worse, contact a doctor or emergency services.</p>
    </section>

    <section class="card" aria-labelledby="h-out" aria-live="polite">
      <h2 id="h-out">Possible matches</h2>
      <div id="out">
        <div class="empty">
          <svg viewBox="0 0 140 48" fill="none" stroke="#00A3A1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M0 26H40L46 26L52 14L58 26H66L72 30L80 4L88 42L94 26H140"/></svg>
          <p>Results appear here once you check your symptoms.</p>
        </div>
      </div>
    </section>
  </div>
</main>

<script>
  const $ = id => document.getElementById(id);
  const box = $('symptoms'), out = $('out'), msg = $('msg'), go = $('go');
  const COMMON = ['fever', 'cough', 'headache', 'fatigue', 'chest pain', 'sore throat', 'rash', 'itch'];

  function parts() { return box.value.split(/[,\n]+/).map(s => s.trim().toLowerCase()).filter(Boolean); }
  function syncChips() { const have = parts(); document.querySelectorAll('.chip').forEach(c => c.setAttribute('aria-pressed', have.includes(c.dataset.s))); }
  COMMON.forEach(s => {
    const b = document.createElement('button');
    b.type = 'button'; b.className = 'chip'; b.dataset.s = s; b.textContent = s; b.setAttribute('aria-pressed', 'false');
    b.onclick = () => {
      const have = parts();
      box.value = (have.includes(s) ? have.filter(x => x !== s) : [...have, s]).join(', ');
      syncChips(); msg.classList.remove('show');
    };
    $('chips').appendChild(b);
  });
  box.addEventListener('input', () => { syncChips(); msg.classList.remove('show'); });

  function el(tag, cls, text) { const n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; }

  function render(list) {
    out.replaceChildren();
    if (list.some(p => p.severity === 'High')) out.appendChild(el('div', 'urgent', 'Some of these results need prompt attention. Seek medical help now.'));
    list.forEach(p => {
      const card = el('div', 'result'), head = el('div', 'head');
      head.append(el('h3', '', p.disease), el('span', 'pill ' + p.severity, p.severity + ' severity'));
      const bar = el('div', 'bar'), fill = el('i'); bar.appendChild(fill);
      card.append(head, bar, el('div', 'conf', 'Match confidence ' + Number(p.confidence).toFixed(1) + '%'), el('p', 'adv', p.suggestions));
      out.appendChild(card);
      requestAnimationFrame(() => fill.style.width = Math.min(100, p.confidence) + '%');
    });
  }

  go.addEventListener('click', async () => {
    msg.classList.remove('show');
    const symptoms = box.value.trim();
    if (!symptoms) { msg.textContent = 'Describe at least one symptom.'; msg.classList.add('show'); box.focus(); return; }
    const payload = { symptoms };
    const pid = $('pid'); if (pid && pid.value) payload.patient_id = pid.value;
    go.disabled = true; go.textContent = 'Checking...';
    try {
      const res = await fetch(location.pathname, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(payload) });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.status === 'success') render(data.predictions);
      else { msg.textContent = data.message || 'Something went wrong. Try again.'; msg.classList.add('show'); }
    } catch (err) { msg.textContent = 'Cannot reach the server. Check your connection and try again.'; msg.classList.add('show'); }
    go.disabled = false; go.textContent = 'Check symptoms';
  });
</script>
</body>
</html>
<?php
    exit;
}

/* ---------------------------------------------------------------- API */
$user = require_api_login();

// [keywords that must ALL appear, disease, confidence %, advice, severity]
$RULES = [
    [['fever', 'cough'],       'Influenza (Flu)', 85.5, 'Rest, hydration, antiviral medication', 'Medium'],
    [['headache', 'fatigue'],  'Migraine',        72.3, 'Avoid triggers, pain relief',          'Low'],
    [['chest pain'],           'Cardiac Issue',   45.0, 'Seek immediate medical attention',     'High'],
    [['sore throat'],          'Throat Infection',68.0, 'Warm fluids, see a doctor if it lasts', 'Low'],
    [['rash', 'itch'],         'Skin Allergy',    70.0, 'Antihistamine, avoid the trigger',     'Low'],
];

$in = body();
$symptoms = trim($in['symptoms'] ?? '');
if ($symptoms === '') json_out(['status' => 'error', 'message' => 'Symptoms required'], 400);

$text = strtolower($symptoms);
$predictions = [];
foreach ($RULES as [$words, $disease, $confidence, $advice, $severity]) {
    if (count(array_filter($words, fn($w) => str_contains($text, $w))) === count($words)) {
        $predictions[] = ['disease' => $disease, 'confidence' => $confidence, 'suggestions' => $advice, 'severity' => $severity];
    }
}
if (!$predictions) {
    $predictions[] = ['disease' => 'Common Cold', 'confidence' => 65.0, 'suggestions' => 'Rest, fluids, over-the-counter medication', 'severity' => 'Low'];
}

// Save the result when we know which patient it is for.
$patientId = $user['role'] === 'patient' ? $user['patient_id'] : ($in['patient_id'] ?? null);
if ($patientId) {
    $db = db();
    $db->prepare('INSERT INTO symptoms_input (patient_id, symptoms, severity) VALUES (?, ?, ?)')
       ->execute([$patientId, $symptoms, $predictions[0]['severity']]);
    $symptomId = $db->lastInsertId();
    $save = $db->prepare('INSERT INTO disease_predictions (patient_id, symptom_id, predicted_disease, confidence_score, suggestions, severity_level) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($predictions as $p) {
        $save->execute([$patientId, $symptomId, $p['disease'], $p['confidence'], $p['suggestions'], $p['severity']]);
    }
}

json_out(['status' => 'success', 'predictions' => $predictions]);