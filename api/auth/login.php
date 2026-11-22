<?php
// login.php
//  - POST (JSON)  -> the login API (same behaviour as before)
//  - GET (browser) -> the sign-in page
// Lives in: <project>/api/auth/login.php
require_once '../../includes/auth.php';

$ROOT = '../../';   // path from this file to the project root

/* ---------------------------------------------------------------- API */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = body();
    $email = trim($in['email'] ?? '');
    $role  = $in['role'] ?? '';
    if (!$email || empty($in['password']) || !isset(HOME[$role])) {
        json_out(['status' => 'error', 'message' => 'Email, password and role are required'], 400);
    }

    $stmt = db()->prepare('SELECT user_id, email, role, password_hash FROM users WHERE email = ? AND role = ? AND is_active = 1');
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($in['password'], $user['password_hash'])) {
        json_out(['status' => 'error', 'message' => 'Invalid email, password or role'], 401);
    }

    // Find the profile (name + patient/doctor id) that belongs to this login.
    $profile = ['name' => ucfirst($role)];
    $table = ['patient' => 'patients', 'doctor' => 'doctors', 'staff' => 'staff'][$role] ?? null;
    if ($table) {
        $stmt = db()->prepare("SELECT * FROM `$table` WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        if ($row = $stmt->fetch()) {
            $profile['name'] = $row['first_name'] . ' ' . $row['last_name'];
            $profile['patient_id'] = $row['patient_id'] ?? null;
            $profile['doctor_id']  = $row['doctor_id'] ?? null;
        }
    }

    session_regenerate_id(true);
    $_SESSION['user'] = ['user_id' => $user['user_id'], 'email' => $user['email'], 'role' => $role] + $profile;

    json_out(['status' => 'success', 'redirect' => 'dashboards/' . HOME[$role]]);
    exit;
}

/* --------------------------------------------------------------- PAGE */
header('Content-Type: text/html; charset=utf-8');

// Already signed in? Go straight to the dashboard.
$current = $_SESSION['user']['role'] ?? null;
if ($current && isset(HOME[$current])) {
    header('Location: ' . $ROOT . 'dashboards/' . HOME[$current]);
    exit;
}

$labels = [
    'patient' => 'Patient', 'doctor' => 'Doctor', 'staff' => 'Staff', 'admin' => 'Administrator',
    'financial' => 'Finance', 'datascientist' => 'Data scientist', 'insurance' => 'Insurance',
];
$roles = array_keys(HOME);
usort($roles, fn($a, $b) => ($a === 'patient' ? 0 : 1) <=> ($b === 'patient' ? 0 : 1));
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | Hospital Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --ice:#EAF3F6; --ink:#0B2A3C; --soft:#3E5A6B; --signal:#00A3A1; --deep:#007B7A; --line:#C9DCE3; --alert:#B3312F; --white:#fff;
          --font:"Sora","Segoe UI",system-ui,-apple-system,sans-serif; }
  * { box-sizing:border-box; margin:0; }
  html,body { height:100%; }
  body { font-family:var(--font); color:var(--ink); background:var(--ice); line-height:1.6; -webkit-font-smoothing:antialiased; }
  .shell { min-height:100%; display:grid; grid-template-columns:minmax(0,1.05fr) minmax(0,1fr); }
  .monitor { background:var(--ink); color:#fff; display:flex; flex-direction:column; justify-content:space-between; padding:48px clamp(28px,5vw,72px); overflow:hidden;
             background-image:linear-gradient(rgba(191,233,234,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(191,233,234,.07) 1px,transparent 1px); background-size:32px 32px; }
  .brand { display:flex; align-items:center; gap:12px; font-weight:600; font-size:1.05rem; }
  .mark { width:34px; height:34px; border-radius:8px; background:var(--signal); display:grid; place-items:center; }
  .mark svg { width:20px; height:20px; }
  .pitch h1 { font-size:clamp(1.9rem,3.2vw,2.9rem); line-height:1.15; font-weight:500; letter-spacing:-.02em; max-width:14ch; }
  .pitch p { margin-top:16px; color:#A9C4D0; max-width:38ch; }
  #ecg { width:100%; height:150px; display:block; margin:36px 0 12px; }
  .status { display:flex; align-items:center; gap:10px; font-size:.85rem; color:#A9C4D0; }
  .dot { width:8px; height:8px; border-radius:50%; background:var(--signal); animation:pulse 1.6s ease-in-out infinite; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
  .panel { display:grid; place-items:center; padding:40px 24px; }
  .card { width:100%; max-width:420px; }
  .card h2 { font-size:1.7rem; font-weight:500; letter-spacing:-.01em; }
  .card .sub { color:var(--soft); margin:6px 0 28px; }
  .field { margin-bottom:18px; }
  .field label { display:block; font-size:.9rem; font-weight:500; margin-bottom:6px; }
  .control { position:relative; }
  .control input,.control select { width:100%; height:50px; padding:0 16px; font:inherit; color:var(--ink); background:var(--white); border:1px solid var(--line); border-radius:12px; appearance:none; transition:border-color .15s,box-shadow .15s; }
  .control select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%233E5A6B' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 16px center; padding-right:40px; }
  .control input::placeholder { color:#8BA3AF; }
  .control input:focus,.control select:focus { outline:none; border-color:var(--signal); box-shadow:0 0 0 4px rgba(0,163,161,.18); }
  [aria-invalid="true"] { border-color:var(--alert)!important; }
  .toggle { position:absolute; right:6px; top:6px; height:38px; padding:0 12px; border:0; background:transparent; color:var(--deep); font:inherit; font-size:.85rem; font-weight:500; border-radius:8px; cursor:pointer; }
  .toggle:hover { background:var(--ice); }
  .toggle:focus-visible { outline:3px solid var(--signal); }
  .pw input { padding-right:76px; }
  .msg { display:none; margin-bottom:16px; padding:12px 14px; border-radius:10px; font-size:.9rem; background:#FBECEB; color:var(--alert); border:1px solid #F0C4C3; }
  .msg.show { display:block; }
  .submit { width:100%; height:52px; border:0; border-radius:12px; cursor:pointer; background:var(--signal); color:#fff; font:inherit; font-weight:600; font-size:1rem; transition:background .15s,transform .08s; }
  .submit:hover { background:var(--deep); } .submit:active { transform:translateY(1px); }
  .submit:focus-visible { outline:3px solid var(--ink); outline-offset:3px; }
  .submit[disabled] { opacity:.7; cursor:progress; }
  .alt { margin-top:22px; text-align:center; font-size:.92rem; color:var(--soft); }
  .alt a { color:var(--deep); font-weight:500; text-decoration:none; }
  .alt a:hover { text-decoration:underline; }
  @media (max-width:860px) {
    .shell { grid-template-columns:1fr; } .monitor { padding:28px 24px 20px; }
    .pitch p,.status { display:none; } .pitch h1 { font-size:1.6rem; max-width:none; }
    #ecg { height:90px; margin:16px 0 0; } .panel { align-items:start; padding-top:28px; }
  }
  @media (prefers-reduced-motion:reduce) { .dot { animation:none; } }
</style>
</head>
<body>
<div class="shell">
  <aside class="monitor">
    <div class="brand"><span class="mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="#0B2A3C" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>Hospital Management</div>
    <div class="pitch">
      <h1>One secure sign-in for every care team.</h1>
      <p>Patients, doctors and staff each get their own workspace, with records and schedules kept in sync.</p>
      <canvas id="ecg" aria-hidden="true"></canvas>
      <div class="status"><span class="dot"></span> System online</div>
    </div>
    <div></div>
  </aside>

  <main class="panel">
    <div class="card">
      <h2>Sign in</h2>
      <p class="sub">Choose your role, then enter your details.</p>
      <form id="form" novalidate>
        <div class="field">
          <label for="role">I am a</label>
          <div class="control">
            <select id="role" name="role">
              <?php foreach ($roles as $r): ?>
                <option value="<?= $e($r) ?>"><?= $e($labels[$r] ?? ucfirst($r)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field">
          <label for="email">Email</label>
          <div class="control"><input id="email" type="email" autocomplete="username" placeholder="name@hospital.com"></div>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="control pw">
            <input id="password" type="password" autocomplete="current-password" placeholder="Enter your password">
            <button type="button" class="toggle" id="toggle" aria-label="Show password" aria-pressed="false">Show</button>
          </div>
        </div>
        <div class="msg" id="msg" role="alert" aria-live="assertive"></div>
        <button class="submit" id="submit" type="submit">Sign in</button>
      </form>
      <p class="alt">New patient? <a href="register.php">Create an account</a></p>
    </div>
  </main>
</div>

<script>
  const ROOT = <?= json_encode($ROOT) ?>;
  const $ = id => document.getElementById(id);
  const msg = $('msg'), email = $('email'), pass = $('password'), btn = $('submit');

  function fail(text, field) {
    msg.textContent = text; msg.classList.add('show');
    document.querySelectorAll('[aria-invalid]').forEach(x => x.removeAttribute('aria-invalid'));
    if (field) { field.setAttribute('aria-invalid', 'true'); field.focus(); }
  }
  function clear() { msg.classList.remove('show'); document.querySelectorAll('[aria-invalid]').forEach(x => x.removeAttribute('aria-invalid')); }
  [email, pass].forEach(x => x.addEventListener('input', clear));

  $('toggle').addEventListener('click', function () {
    const show = pass.type === 'password';
    pass.type = show ? 'text' : 'password'; this.textContent = show ? 'Hide' : 'Show';
    this.setAttribute('aria-pressed', show); this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });

  $('form').addEventListener('submit', async e => {
    e.preventDefault(); clear();
    const payload = { email: email.value.trim(), password: pass.value, role: $('role').value };
    if (!payload.email) return fail('Enter your email address.', email);
    if (!payload.password) return fail('Enter your password.', pass);
    btn.disabled = true; btn.textContent = 'Signing in...';
    try {
      const res = await fetch(location.pathname, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(payload) });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.status === 'success') { btn.textContent = 'Signed in'; location.href = ROOT + data.redirect; return; }
      fail(data.message || 'Sign-in failed. Check your details and try again.');
    } catch (err) { fail('Cannot reach the server. Check your connection and try again.'); }
    btn.disabled = false; btn.textContent = 'Sign in';
  });

  (function () {
    const c = $('ecg'), x = c.getContext('2d'), reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let W, H, off = 40, last = 0;
    const g = (v, m, w) => Math.exp(-Math.pow((v - m) / w, 2));
    const beat = p => .14*g(p,.16,.035) - .14*g(p,.30,.010) + g(p,.33,.011) - .28*g(p,.365,.012) + .26*g(p,.60,.060);
    function size() { const d = devicePixelRatio || 1; W = c.clientWidth; H = c.clientHeight; c.width = W*d; c.height = H*d; x.setTransform(d,0,0,d,0,0); }
    function draw() {
      x.clearRect(0,0,W,H);
      const bw = Math.max(220, W*.42), mid = H*.62, amp = H*.5, gr = x.createLinearGradient(0,0,W,0);
      gr.addColorStop(0,'rgba(0,163,161,0)'); gr.addColorStop(.55,'rgba(0,163,161,.9)'); gr.addColorStop(1,'#7FE3E1');
      x.strokeStyle = gr; x.lineWidth = 2.5; x.lineJoin = 'round'; x.beginPath();
      let y = mid;
      for (let i = 0; i <= W; i += 2) { const p = (((i+off) % bw) + bw) % bw / bw; y = mid - beat(p)*amp; i ? x.lineTo(i,y) : x.moveTo(i,y); }
      x.stroke(); x.fillStyle = '#BFF4F2'; x.beginPath(); x.arc(W-3,y,4.5,0,Math.PI*2); x.fill();
    }
    function tick(t) { off += (last ? t-last : 16)*.09; last = t; draw(); requestAnimationFrame(tick); }
    size(); draw(); addEventListener('resize', () => { size(); draw(); });
    if (!reduce) requestAnimationFrame(tick);
  })();
</script>
</body>
</html>