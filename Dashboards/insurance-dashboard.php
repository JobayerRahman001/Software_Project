<?php
require_once '../includes/layout.php';
page_start('Insurance Dashboard', ['insurance']);
?>
<p class="subtitle">Review and process insurance claims.</p>

<div class="stats">
  <div class="stat"><b data-stat="claims">-</b><span>Total claims</span></div>
  <div class="stat amber"><b data-stat="pending_claims">-</b><span>Pending review</span></div>
  <div class="stat green"><b data-stat="approved_claims">-</b><span>Approved</span></div>
  <div class="stat red"><b data-stat="rejection_rate" data-format="percent">-</b><span>Rejection rate</span></div>
</div>

<div class="card"><h3>Claims by status</h3><div class="chart-box"><canvas id="claimChart"></canvas></div></div>
<div id="claims"></div>

<script>
loadStats().then(c => chart('claimChart', 'doughnut', c.claim_status));
crudTable({
  el: '#claims', table: 'insurance_claims', pk: 'claim_id', title: 'Insurance Claims', noAdd: true,
  onChange: loadStats,
  filter: { key: 'status', label: 'statuses', options: ['Submitted', 'Under Review', 'Approved', 'Rejected', 'Paid'] },
  extra: [
    { label: 'Approve', run: r => save('insurance_claims', r.claim_id, { status: 'Approved', approved_amount: r.claim_amount, processed_date: today() }) },
    { label: 'Reject', run: r => save('insurance_claims', r.claim_id, { status: 'Rejected', processed_date: today() }) },
  ],
  columns: [['claim_id', 'Claim'], ['patient_name', 'Patient'], ['insurance_provider', 'Provider'], ['claim_amount', 'Amount', 'money'], ['status', 'Status', 'badge'], ['submission_date', 'Submitted']],
  fields: [
    ['status', 'Status', ['Submitted', 'Under Review', 'Approved', 'Rejected', 'Paid']], ['approved_amount', 'Approved amount', 'number'],
    ['rejection_reason', 'Rejection reason', 'textarea'], ['notes', 'Notes', 'textarea'],
  ],
});
</script>
<?php page_end();