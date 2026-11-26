<?php
require_once '../includes/layout.php';
page_start('Financial Dashboard', ['financial', 'admin']);

// Optional report period (?from=YYYY-MM-DD&to=YYYY-MM-DD), validated server-side
$cleanDate = function ($d) {
    $dt = DateTime::createFromFormat('Y-m-d', (string)$d);
    return ($dt && $dt->format('Y-m-d') === $d) ? $d : '';
};
$from = $cleanDate($_GET['from'] ?? '');
$to   = $cleanDate($_GET['to'] ?? '');
if ($from && $to && $from > $to) { [$from, $to] = [$to, $from]; }
$range = ($from ? '&from=' . $from : '') . ($to ? '&to=' . $to : '');
?>
<p class="subtitle">Revenue, collections and insurance claims.</p>

<form method="get" style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap;margin-bottom:1rem">
  <label>From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
  <label>To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
  <button class="btn primary" type="submit">Apply</button>
  <a class="btn" href="financial-dashboard.php">Reset</a>
</form>

<div class="stats">
  <div class="stat"><b data-stat="revenue" data-format="money">-</b><span>Total billed</span></div>
  <div class="stat green"><b data-stat="collected" data-format="money">-</b><span>Collected</span></div>
  <div class="stat amber"><b data-stat="outstanding" data-format="money">-</b><span>Outstanding</span></div>
  <div class="stat amber"><b data-stat="pending_claims">-</b><span>Pending claims</span></div>
  <div class="stat green"><b data-stat="approved_claims">-</b><span>Approved claims</span></div>
  <div class="stat red"><b data-stat="rejection_rate" data-format="percent">-</b><span>Rejection rate</span></div>
</div>

<div class="grid">
  <div class="card"><h3>Revenue by month</h3><div class="chart-box"><canvas id="revenueChart"></canvas></div></div>
  <div class="card"><h3>Payment status</h3><div class="chart-box"><canvas id="paymentChart"></canvas></div></div>
  <div class="card"><h3>Claims by status</h3><div class="chart-box"><canvas id="claimChart"></canvas></div></div>
</div>

<p style="margin-bottom:1rem"><a class="btn primary" href="billing-insurance.php"><i class="fas fa-file-invoice-dollar"></i> Manage bills &amp; claims</a></p>

<div id="bills"></div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const RANGE = <?= json_encode($range, JSON_HEX_TAG | JSON_HEX_AMP) ?>;   // '' or '&from=..&to=..'

  const draw = (id, type, data, label) => {
    const canvas = document.getElementById(id);
    if (!data || Object.keys(data).length === 0) {
      canvas.parentElement.innerHTML = '<p class="subtitle">No data for this period.</p>';
      return;
    }
    chart(id, type, data, label);
  };

  crudTable({
    el: '#bills', table: 'billing', pk: 'bill_id', title: 'Recent Transactions', readonly: true,
    params: RANGE,
    filter: { key: 'payment_status', label: 'statuses', options: ['Pending', 'Paid', 'Partially Paid', 'Cancelled'] },
    columns: [['bill_id', 'Bill'], ['bill_date', 'Date'], ['patient_name', 'Patient'],
              ['total_amount', 'Amount', 'money'], ['payment_status', 'Status', 'badge'], ['payment_method', 'Method']],
  });

  try {
    const c = await loadStats(RANGE);
    draw('revenueChart', 'bar',      c.revenue_by_month, 'Revenue');
    draw('paymentChart', 'doughnut', c.payment_status);
    draw('claimChart',   'doughnut', c.claim_status);
  } catch (err) {
    console.error('Financial stats failed:', err);
    document.querySelectorAll('.chart-box').forEach(b =>
      b.innerHTML = '<p class="subtitle">Could not load data.</p>');
  }
});
</script>
<?php page_end();