<?php
require_once '../includes/layout.php';
page_start('Financial Dashboard', ['financial']);
?>
<p class="subtitle">Revenue, collections and insurance claims.</p>

<div class="stats">
  <div class="stat"><b data-stat="revenue" data-format="money">-</b><span>Total billed</span></div>
  <div class="stat green"><b data-stat="collected" data-format="money">-</b><span>Collected</span></div>
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
loadStats().then(c => {
  chart('revenueChart', 'bar', c.revenue_by_month, 'Revenue');
  chart('paymentChart', 'doughnut', c.payment_status);
  chart('claimChart', 'doughnut', c.claim_status);
});
crudTable({
  el: '#bills', table: 'billing', pk: 'bill_id', title: 'Recent Transactions', readonly: true,
  filter: { key: 'payment_status', label: 'statuses', options: ['Pending', 'Paid', 'Partially Paid', 'Cancelled'] },
  columns: [['bill_id', 'Bill'], ['bill_date', 'Date'], ['patient_name', 'Patient'], ['total_amount', 'Amount', 'money'], ['payment_status', 'Status', 'badge'], ['payment_method', 'Method']],
});
</script>
<?php page_end();