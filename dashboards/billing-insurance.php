<?php
require_once '../includes/layout.php';
page_start('Billing & Insurance', ['admin', 'staff', 'financial', 'insurance']);
?>
<div class="stats">
  <div class="stat"><b data-stat="revenue" data-format="money">-</b><span>Total billed</span></div>
  <div class="stat amber"><b data-stat="pending_bills">-</b><span>Pending bills</span></div>
  <div class="stat amber"><b data-stat="pending_claims">-</b><span>Pending claims</span></div>
  <div class="stat red"><b data-stat="rejection_rate" data-format="percent">-</b><span>Claim rejection rate</span></div>
</div>

<div id="bills"></div>
<div id="claims"></div>

<script>
loadStats();

// Print-friendly invoice in a new window
function invoice(b) {
  const w = window.open('', '_blank');
  w.document.write(`<h2>MediLink Invoice #${b.bill_id}</h2><p>Patient: ${esc(b.patient_name)}<br>Date: ${b.bill_date}</p>
    <p>Total: ${money(b.total_amount)}<br>Insurance covered: ${money(b.insurance_covered)}<br>Paid by patient: ${money(b.patient_paid)}<br>Status: ${esc(b.payment_status)}</p>`);
  w.print();
}

crudTable({
  el: '#bills', table: 'billing', pk: 'bill_id', title: 'Bills',
  readonly: ROLE === 'insurance',
  defaults: { bill_date: today(), payment_status: 'Pending' },
  onChange: loadStats,
  filter: { key: 'payment_status', label: 'statuses', options: ['Pending', 'Paid', 'Partially Paid', 'Cancelled'] },
  extra: [{ label: '<i class="fas fa-print"></i>', run: invoice }],
  columns: [
    ['bill_id', 'Bill'], ['bill_date', 'Date'], ['patient_name', 'Patient'], ['total_amount', 'Total', 'money'],
    ['insurance_covered', 'Insurance', 'money'], ['patient_paid', 'Paid', 'money'],
    ['payment_status', 'Status', 'badge'], ['payment_method', 'Method'],
  ],
  fields: [
    ['patient_id', 'Patient *', 'patient'], ['bill_date', 'Bill date *', 'date'],
    ['total_amount', 'Total amount *', 'number'], ['payment_status', 'Status', ['Pending', 'Paid', 'Partially Paid', 'Cancelled']],
    ['insurance_covered', 'Insurance covered', 'number'], ['patient_paid', 'Paid by patient', 'number'],
    ['payment_method', 'Payment method', ['Cash', 'Card', 'Bank Transfer', 'Insurance']],
    ['notes', 'Notes / services', 'textarea'],
  ],
});

crudTable({
  el: '#claims', table: 'insurance_claims', pk: 'claim_id', title: 'Insurance Claims',
  defaults: { claim_date: today(), submission_date: today(), status: 'Submitted' },
  onChange: loadStats,
  filter: { key: 'status', label: 'statuses', options: ['Submitted', 'Under Review', 'Approved', 'Rejected', 'Paid'] },
  columns: [
    ['claim_id', 'Claim'], ['patient_name', 'Patient'], ['insurance_provider', 'Provider'], ['bill_id', 'Bill'],
    ['claim_amount', 'Amount', 'money'], ['status', 'Status', 'badge'], ['submission_date', 'Submitted'],
  ],
  fields: [
    ['patient_id', 'Patient *', 'patient'], ['bill_id', 'Bill ID *', 'number'],
    ['insurance_provider', 'Provider'], ['policy_number', 'Policy number'],
    ['claim_amount', 'Claim amount *', 'number'], ['claim_date', 'Claim date *', 'date'],
    ['status', 'Status', ['Submitted', 'Under Review', 'Approved', 'Rejected', 'Paid']], ['approved_amount', 'Approved amount', 'number'],
    ['rejection_reason', 'Rejection reason', 'textarea'],
  ],
});
</script>
<?php page_end();
