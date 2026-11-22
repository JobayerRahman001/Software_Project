<?php
// logout.php: ends the session, then shows a "signed out" page that returns to sign-in.
// Lives in: <project>/api/auth/logout.php
require_once '../../includes/auth.php';

$name = $_SESSION['user']['name'] ?? '';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Content-Type: text/html; charset=utf-8');
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Signed out | Hospital Management</title>
<meta http-equiv="refresh" content="8;url=login.php">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --ice:#EAF3F6; --ink:#0B2A3C; --soft:#A9C4D0; --signal:#00A3A1; --deep:#007B7A; --font:"Sora","Segoe UI",system-ui,sans-serif; }
  * { box-sizing:border-box; margin:0; }
  body { min-height:100vh; display:grid; place-items:center; padding:24px; font-family:var(--font); color:#fff; background:var(--ink); -webkit-font-smoothing:antialiased;
         background-image:linear-gradient(rgba(191,233,234,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(191,233,234,.07) 1px,transparent 1px); background-size:32px 32px; }
  .card { width:100%; max-width:460px; text-align:center; }
  .mark { width:44px; height:44px; border-radius:10px; background:var(--signal); display:grid; place-items:center; margin:0 auto 28px; }
  .mark svg { width:26px; height:26px; }
  svg.line { width:100%; height:80px; display:block; margin:0 auto 24px; }
  svg.line path { fill:none; stroke:var(--signal); stroke-width:2.5; stroke-linecap:round; stroke-linejoin:round; }
  h1 { font-size:1.9rem; font-weight:500; letter-spacing:-.015em; }
  p { color:var(--soft); margin-top:10px; }
  .btn { display:inline-block; margin-top:28px; padding:15px 30px; border-radius:12px; background:var(--signal); color:#fff; font-weight:600; text-decoration:none; transition:background .15s; }
  .btn:hover { background:var(--deep); } .btn:focus-visible { outline:3px solid #fff; outline-offset:3px; }
  .count { margin-top:18px; font-size:.85rem; }
  @media (prefers-reduced-motion:no-preference) { svg.line path { stroke-dasharray:420; stroke-dashoffset:420; animation:draw 1.6s ease-out forwards; } @keyframes draw { to { stroke-dashoffset:0; } } }
</style>
</head>
<body>
<main class="card">
  <div class="mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="#0B2A3C" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></div>
  <svg class="line" viewBox="0 0 400 80" aria-hidden="true" preserveAspectRatio="none"><path d="M0 44 H110 L124 44 L134 30 L146 44 H168 L178 50 L190 8 L202 66 L212 44 H252 L268 34 L284 44 H400"/></svg>
  <h1>You're signed out</h1>
  <p><?= $name ? $e($name) . ', your' : 'Your' ?> session has ended and nothing was left on this device. Thanks for using Hospital Management.</p>
  <a class="btn" href="login.php">Sign in again</a>
  <p class="count">Returning to sign-in in <span id="s">8</span> seconds</p>
</main>
<script>
  let s = 8; const el = document.getElementById('s');
  setInterval(() => { if (s > 0) el.textContent = --s; }, 1000);
</script>
</body>
</html>