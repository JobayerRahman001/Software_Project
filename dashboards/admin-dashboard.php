<?php
require_once '../includes/layout.php';
page_start('Admin Dashboard', ['admin']);
?>
<p class="subtitle">Overview of hospital operations, patient care and system performance.</p>

<div class="stats">
  <div class="stat"><b data-stat="patients">-</b><span>Patients</span></div>
  <div class="stat green"><b data-stat="doctors">-</b><span>Active doctors</span></div>
  <div class="stat amber"><b data-stat="appointments_today">-</b><span>Appointments today</span></div>
  <div class="stat blue"><b data-stat="revenue" data-format="money">-</b><span>Total billed</span></div>
</div>

<div class="grid">
  <div class="card"><h3>Patient registrations</h3><div class="chart-box"><canvas id="patientChart"></canvas></div></div>
  <div class="card"><h3>Disease predictions</h3><div class="chart-box"><canvas id="diseaseChart"></canvas></div></div>
  <div class="card"><h3>Revenue by month</h3><div class="chart-box"><canvas id="revenueChart"></canvas></div></div>
  <div class="card"><h3>AI predictions by severity</h3><div class="chart-box"><canvas id="aiChart"></canvas></div></div>
</div>

<h3 style="margin-bottom:.75rem">Quick access</h3>
<div class="grid">
  <?php foreach (MENU as [$file, $label, $icon]): ?>
    <a class="card" href="<?= $file ?>"><h3><i class="fas <?= $icon ?>"></i> <?= $label ?></h3><span class="subtitle">Open &rarr;</span></a>
  <?php endforeach; ?>
</div>

<script>
loadStats().then(c => {
  chart('patientChart', 'line', c.patients_by_month, 'New patients');
  chart('diseaseChart', 'doughnut', c.diseases);
  chart('revenueChart', 'bar', c.revenue_by_month, 'Revenue');
  chart('aiChart', 'doughnut', c.severity);
});
</script>
<?php page_end();
