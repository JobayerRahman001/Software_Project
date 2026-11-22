<?php
// Shared page frame: sidebar + top bar. Every dashboard page calls page_start() and page_end().
require_once __DIR__ . '/auth.php';

// [file, label, icon, roles that can see it]
const MENU = [
    ['patient-management.php',      'Patients',               'fa-user-injured',        ['admin', 'doctor', 'staff']],
    ['doctor-management.php',       'Doctors & Appointments', 'fa-user-md',             ['admin', 'doctor', 'staff']],
    ['prescription-management.php', 'Prescriptions & Tests',  'fa-prescription',        ['admin', 'doctor']],
    ['billing-insurance.php',       'Billing & Insurance',    'fa-file-invoice-dollar', ['admin', 'staff', 'financial', 'insurance']],
    ['ai-prediction.php',           'AI Prediction',          'fa-brain',               ['admin', 'doctor', 'datascientist', 'patient']],
    ['smart-recommendations.php',   'Smart Recommendations',  'fa-lightbulb',           ['admin', 'doctor', 'datascientist', 'patient']],
];

function page_start(string $title, array $roles = []): array {
    $user = require_page_login($roles);
    $current = basename($_SERVER['SCRIPT_NAME']);
    $links = array_merge([[HOME[$user['role']], 'Dashboard', 'fa-home', []]], array_filter(MENU, fn($m) => in_array($user['role'], $m[3])));
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?> - MediLink</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
  <script>const ROLE = <?= json_encode($user['role']) ?>, ME = <?= json_encode($user) ?>;</script>
  <script src="../js/app.js"></script>
</head>
<body>
  <nav class="sidebar" id="sidebar">
    <div class="brand"><i class="fas fa-heartbeat"></i> MediLink</div>
    <?php foreach ($links as [$file, $label, $icon]): ?>
      <a href="<?= $file ?>" class="<?= $file === $current ? 'active' : '' ?>"><i class="fas <?= $icon ?>"></i> <?= $label ?></a>
    <?php endforeach; ?>
    <div class="spacer"></div>
    <a href="../api/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>

  <div class="main">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:.75rem">
        <button class="btn menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
        <h1><?= htmlspecialchars($title) ?></h1>
      </div>
      <div class="right">
        <button class="btn" onclick="toggleTheme()" title="Dark / light mode"><i class="fas fa-circle-half-stroke"></i></button>
        <div class="user-chip">
          <div class="avatar"><?= htmlspecialchars(strtoupper($user['name'][0])) ?></div>
          <div><?= htmlspecialchars($user['name']) ?><small><?= ucfirst($user['role']) ?></small></div>
        </div>
      </div>
    </header>
    <main class="content">
<?php
    return $user;
}

function page_end(): void { ?>
    </main>
  </div>
  <div id="toast"></div>
</body>
</html>
<?php }
