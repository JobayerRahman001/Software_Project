<?php
// register.php
//  - POST (JSON)  -> create a patient account (same behaviour as before)
//  - GET (browser) -> the sign-up page
// Self sign-up is for patients only. Other roles are created by the admin.
// Lives in: <project>/api/auth/register.php
require_once '../../includes/auth.php';

/* ---------------------------------------------------------------- API */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = body();
    foreach (['full_name', 'email', 'password', 'date_of_birth', 'gender'] as $f) {
        if (empty($in[$f])) json_out(['status' => 'error', 'message' => "$f is required"], 400);
    }
    if (strlen($in['password']) < 6) json_out(['status' => 'error', 'message' => 'Password must be at least 6 characters'], 400);

    [$first, $last] = array_pad(explode(' ', trim($in['full_name']), 2), 2, '');

    $db = db();
    try {
        $db->beginTransaction();
        $db->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'patient')")
           ->execute([$in['email'], password_hash($in['password'], PASSWORD_DEFAULT)]);
        $db->prepare('INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, email) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$db->lastInsertId(), $first, $last, $in['date_of_birth'], $in['gender'], $in['email']]);
        $db->commit();
        json_out(['status' => 'success', 'message' => 'Account created. You can sign in now.'], 201);
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        json_out(['status' => 'error', 'message' => 'This email is already registered'], 409);
    }
    exit;
}

/* --------------------------------------------------------------- PAGE */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create account | Hospital Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --ice:#EAF3F6; --ink:#0B2A3C; --soft:#3E5A6B; --signal:#00A3A1; --deep:#007B7A; --line:#C9DCE3; --alert:#B3312F; --white:#fff;
          --font:"Sora","Segoe UI",system-ui,-apple-system,sans-serif; }
  * { box-sizing:border-box; margin:0; }
  html,body { min-height:100%; }
  body { font-family:var(--font); color:var(--ink); background:var(--ice); line-height:1.6; -webkit-font-smoothing:antialiased; }
  .shell { min-height:100vh; display:grid; grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr); }
  .monitor { background:var(--ink); color:#fff; display:flex; flex-direction:column; justify-content:space-between; padding:48px clamp(28px,5vw,72px);
             background-image:linear-gradient(rgba(191,233,234,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(191,233,234,.07) 1px,transparent 1px); background-size:32px 32px; }
  .brand { display:flex; align-items:center; gap:12px; font-weight:600; font-size:1.05rem; }
  .mark { width:34px; height:34px; border-radius:8px; background:var(--signal); display:grid; place-items:center; }
  .mark svg { width:20px; height:20px; }
  .pitch h1 { font-size:clamp(1.8rem,3vw,2.6rem); line-height:1.15; font-weight:500; letter-spacing:-.02em; max-width:15ch; }
  .perks { list-style:none; padding:0; margin-top:22px; display:grid; gap:12px; color:#A9C4D0; }
  .perks li { display:flex; gap:12px; align-items:flex-start; }
  .perks svg { flex:none; width:20px; height:20px; margin-top:2px; color:var(--signal); }
  .panel { display:grid; place-items:center; padding:40px 24px; }
  .card { width:100%; max-width:460px; }
  .card h2 { font-size:1.7rem; font-weight:500; letter-spacing:-.01em; }
  .card .sub { color:var(--soft); margin:6px 0 26px; }
  .field { margin-bottom:16px; }
  .field label { display:block; font-size:.9rem; font-weight:500; margin-bottom:6px; }
  .row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .control { position:relative; }
  .control input,.control select { width:100%; height:50px; padding:0 16px; font:inherit; color:var(--ink); background:var(--white); border:1px solid var(--line); border-radius:12px; appearance:none; transition:border-color .15s,box-shadow .15s; }
  .control select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%233E5A6B' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 16px center; padding-right:40px; }
  .control input::placeholder { color:#8BA3AF; }
  .control input:focus,.control select:focus { outline:none; border-color:var(--signal); box-shadow:0 0 0 4px rgba(0,163,161,.18); }
  [aria-invalid="true"] { border-color:var(--alert)!important; }
  .toggle { position:absolute; right:6px; top:6px; height:38px; padding:0 12px; border:0; background:transparent; color:var(--deep); font:inherit; font-size:.85rem; font-weight:500; border-radius:8px; cursor:pointer; }
  .toggle:hover { background:var(--ice); } .toggle:focus-visible { outline:3px solid var(--signal); }
  .pw input { padding-right:76px; }
  .meter { height:5px; border-radius:3px; background:var(--line); margin-top:8px; overflow:hidden; }
  .meter i { display:block; height:100%; width:0; background:var(--alert); transition:width .2s,background .2s; }
  .hint { font-size:.8rem; color:var(--soft); margin-top:6px; }
  .msg { display:none; margin-bottom:16px; padding:12px 14px; border-radius:10px; font-size:.9rem; }
  .msg.show { display:block; }
  .msg.error { background:#FBECEB; color:var(--alert); border:1px solid #F0C4C3; }
  .msg.ok { background:#E3F6F1; color:#0A5A4C; border:1px solid #A9E0D2; }
  .submit { width:100%; height:52px; border:0; border-radius:12px; cursor:pointer; background:var(--signal); color:#fff; font:inherit; font-weight:600; font-size:1rem; transition:background .15s,transform .08s; margin-top:4px; }
  .submit:hover { background:var(--deep); } .submit:active { transform:translateY(1px); }
  .submit:focus-visible { outline:3px solid var(--ink); outline-offset:3px; }
  .submit[disabled] { opacity:.7; cursor:progress; }
  .alt { margin-top:22px; text-align:center; font-size:.92rem; color:var(--soft); }
  .alt a { color:var(--deep); font-weight:500; text-decoration:none; } .alt a:hover { text-decoration:underline; }
  @media (max-width:860px) {
    .shell { grid-template-columns:1fr; } .monitor { padding:28px 24px; }
    .pitch h1 { font-size:1.6rem; max-width:none; } .perks { display:none; }
    .panel { align-items:start; padding-top:28px; } .row { grid-template-columns:1fr; gap:0; }
  }
</style>
</head>
<body>
<div class="shell">
  <aside class="monitor">
    <div class="brand"><span class="mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="#0B2A3C" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>Hospital Management</div>
    <div class="pitch">
      <h1>Your health, in one place.</h1>
      <ul class="perks">
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>Book and manage appointments</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>See prescriptions, records and bills</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>Check symptoms and get health reminders</li>
      </ul>
    </div>
    <div></div>
  </aside>

  <main class="panel">
    <div class="card">
      <h2>Create your account</h2>
      <p class="sub">Patient sign-up. Doctors and staff are added by an administrator.</p>
      <form id="form" novalidate>
        <div class="field">
          <label for="full_name">Full name</label>
          <div class="control"><input id="full_name" type="text" autocomplete="name" placeholder="First and last name"></div>
        </div>
        <div class="field">
          <label for="email">Email</label>
          <div class="control"><input id="email" type="email" autocomplete="email" placeholder="name@example.com"></div>
        </div>
        <div class="row">
          <div class="field">
            <label for="dob">Date of birth</label>
            <div class="control"><input id="dob" type="date" max="<?= date('Y-m-d') ?>"></div>
          </div>
          <div class="field">
            <label for="gender">Gender</label>
            <div class="control">
              <select id="gender"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select>
            </div>
          </div>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="control pw">
            <input id="password" type="password" autocomplete="new-password" placeholder="At least 6 characters">
            <button type="button" class="toggle" id="toggle" aria-label="Show password" aria-pressed="false">Show</button>
          </div>
          <div class="meter" aria-hidden="true"><i id="bar"></i></div>
        </div>
        <div class="field">
          <label for="confirm">Confirm password</label>
          <div class="control"><input id="confirm" type="password" autocomplete="new-password" placeholder="Type it again"></div>
        </div>
        <div class="msg" id="msg" role="alert" aria-live="assertive"></div>
        <button class="submit" id="submit" type="submit">Create account</button>
      </form>
      <p class="alt">Already registered? <a href="login.php">Sign in</a></p>
    </div>
  </main>
</div>

<script>
  const $ = id => document.getElementById(id);
  const msg = $('msg'), btn = $('submit'), pass = $('password');

  function say(text, kind, field) {
    msg.textContent = text; msg.className = 'msg show ' + kind;
    document.querySelectorAll('[aria-invalid]').forEach(x => x.removeAttribute('aria-invalid'));
    if (field) { field.setAttribute('aria-invalid', 'true'); field.focus(); }
  }
  function clear() { msg.className = 'msg'; document.querySelectorAll('[aria-invalid]').forEach(x => x.removeAttribute('aria-invalid')); }
  document.querySelectorAll('input,select').forEach(x => x.addEventListener('input', clear));

  $('toggle').addEventListener('click', function () {
    const show = pass.type === 'password';
    pass.type = show ? 'text' : 'password'; $('confirm').type = pass.type;
    this.textContent = show ? 'Hide' : 'Show'; this.setAttribute('aria-pressed', show);
    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });

  pass.addEventListener('input', () => {
    const v = pass.value; let s = 0;
    if (v.length >= 6) s++; if (v.length >= 10) s++; if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++; if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
    const bar = $('bar'); bar.style.width = (v ? Math.max(1, s) * 25 : 0) + '%';
    bar.style.background = s <= 1 ? '#B3312F' : s === 2 ? '#C98A1B' : '#0A8F6C';
  });

  $('form').addEventListener('submit', async e => {
    e.preventDefault(); clear();
    const p = { full_name: $('full_name').value.trim(), email: $('email').value.trim(), password: pass.value, date_of_birth: $('dob').value, gender: $('gender').value };
    if (!p.full_name) return say('Enter your full name.', 'error', $('full_name'));
    if (!p.email) return say('Enter your email address.', 'error', $('email'));
    if (!p.date_of_birth) return say('Enter your date of birth.', 'error', $('dob'));
    if (!p.gender) return say('Select your gender.', 'error', $('gender'));
    if (p.password.length < 6) return say('Password must be at least 6 characters.', 'error', pass);
    if (p.password !== $('confirm').value) return say('The two passwords do not match.', 'error', $('confirm'));
    btn.disabled = true; btn.textContent = 'Creating account...';
    try {
      const res = await fetch(location.pathname, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(p) });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.status === 'success') {
        say((data.message || 'Account created.') + ' Taking you to sign in...', 'ok');
        btn.textContent = 'Account created';
        setTimeout(() => location.href = 'login.php', 1600);
        return;
      }
      say(data.message || 'Could not create the account. Try again.', 'error');
    } catch (err) { say('Cannot reach the server. Check your connection and try again.', 'error'); }
    btn.disabled = false; btn.textContent = 'Create account';
  });
</script>
</body>
</html>