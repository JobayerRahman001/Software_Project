
<?php
require_once '../includes/layout.php';
// page with the title "Admin Dashboard".
page_start('Admin Dashboard', ['admin']);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<p class="subtitle">
</p>
<div class="stats">
  <!-- Displays the total number of registered patients.
       JavaScript updates this element using data-stat="patients". -->
  <div class="stat">
    <b data-stat="patients">-</b>
    <span>Patients</span>
  </div>
  <!-- Displays the number of active doctors.
       The green class is used for styling. -->
  <div class="stat green">
    <b data-stat="doctors">-</b>
    <span>Active doctors</span>
  </div>
  <!-- Displays the number of active hospital staff. -->
  <div class="stat green">
    <b data-stat="staff">-</b>
    <span>Active staff</span>
  </div>
  <!-- Displays the number of appointments scheduled for today. -->
  <div class="stat amber">
    <b data-stat="appointments_today">-</b>
    <span>Appointments today</span>
  </div>
  <!-- Displays the total billed revenue.
       data-format="money" tells JavaScript to format the value as money. -->
  <div class="stat blue">
    <b data-stat="revenue" data-format="money">-</b>
    <span>Total billed</span>
  </div>
  <!-- Displays the number of insurance claims awaiting processing. -->
  <div class="stat amber">
    <b data-stat="pending_claims">-</b>
    <span>Pending claims</span>
  </div>

</div>
<!-- Displays hospital data visually using Chart.js. -->

<div class="grid">
  <!-- Line chart showing monthly patient registrations.
       JavaScript draws the chart inside this canvas. -->
  <div class="card">
    <h3>Patient registrations</h3>
    <div class="chart-box">
      <canvas id="patientChart"></canvas>
    </div>
  </div>
  <!-- Doughnut chart showing different disease predictions. -->
  <div class="card">
    <h3>Disease predictions</h3>
    <div class="chart-box">
      <canvas id="diseaseChart"></canvas>
    </div>
  </div>
  <!-- Bar chart showing monthly hospital revenue. -->
  <div class="card">
    <h3>Revenue by month</h3>
    <div class="chart-box">
      <canvas id="revenueChart"></canvas>
    </div>
  </div>
  <!-- Doughnut chart showing AI predictions according to severity. -->
  <div class="card">
    <h3>AI predictions by severity</h3>
    <div class="chart-box">
      <canvas id="aiChart"></canvas>
    </div>
  </div>
</div>
<!-- Provides shortcuts to other admin dashboard pages. -->
<h3 style="margin-bottom:.75rem">Quick access</h3>
<div class="grid">
  <?php
  foreach (MENU as [$file, $label, $icon]):
      if (basename($file) === basename(__FILE__)) continue;
  ?>
    <a class="card" href="<?= $e($file) ?>">

      <!-- Display the menu icon and its corresponding label. -->
      <h3>
        <i class="fas <?= $e($icon) ?>"></i>
        <?= $e($label) ?>
      </h3>
      <span class="subtitle">Open &rarr;</span>

    </a>

  <?php endforeach; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const draw = (id, type, data, label) => {

    const canvas = document.getElementById(id);
    if (!data || Object.keys(data).length === 0) {
      canvas.parentElement.innerHTML = '<p class="subtitle">No data yet.</p>'
      return;
    }
    chart(id, type, data, label);
  };
  try {
    const c = await loadStats();
    draw('patientChart', 'line', c.patients_by_month, 'New patients');
    draw('diseaseChart', 'doughnut', c.diseases);
    draw('revenueChart', 'bar', c.revenue_by_month, 'Revenue');
    draw('aiChart', 'doughnut', c.severity);

  } catch (err) {
    console.error('Dashboard stats failed:', err);
    document.querySelectorAll('.chart-box').forEach(b =>
      b.innerHTML = '<p class="subtitle">Could not load data.</p>'
    );
  }

});
</script>


<?php
page_end();
?>
