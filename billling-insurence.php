<?php
require_once '../includes/layout.php';
page_start('Billing & Insurance', ['admin', 'staff', 'financial', 'insurance']);
?>
<p class="subtitle">Invoices, payments and insurance claims.</p>

<div class="stats">
  <div class="stat"><b data-stat="revenue" data-format="money">-</b><span>Total billed</span></div>
  <div class="stat amber"><b data-stat="pending_bills">-</b><span>Pending bills</span></div>
  <div class="stat amber"><b data-stat="pending_claims">-</b><span>Pending claims</span></div>
  <div class="stat red"><b data-stat="rejection_rate" data-format="percent">-</b><span>Claim rejection rate</span></div>
</div>

<div id="bills"></div>
<div id="claims"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const refresh = () => loadStats();
  const E = v => esc(String(v ?? ''));          // escape everything printed into HTML
  refresh();

  // Print-friendly invoice in a new window
  function invoice(b) {
    const w = window.open('', '_blank');
    if (!w) { alert('Please allow pop-ups for this site to print invoices.'); return; }

    const total = Number(b.total_amount) || 0;
    const ins   = Number(b.insurance_covered) || 0;
    const paid  = Number(b.patient_paid) || 0;
    const balance = Math.max(total - ins - paid, 0);

    w.document.write(`<!doctype html><html><head><meta charset="utf-8">
      <title>Invoice #${E(b.bill_id)}</title>
      <style>
        body{font-family:Arial,sans-serif;max-width:640px;margin:2rem auto;color:#222}
        table{width:100%;border-collapse:collapse;margin-top:1rem}
        td{padding:.4rem .6rem;border-bottom:1px solid #ddd}
        td:last-child{text-align:right}
      </style></head><body>
      <h2>MediLink Invoice #${E(b.bill_id)}</h2>
      <p>Patient: ${E(b.patient_name)}<br>Date: ${E(b.bill_date)}<br>Status: ${E(b.payment_status)}</p>
      <table>
        <tr><td>Total</td><td>${E(money(total))}</td></tr>
        <tr><td>Insurance covered</td><td>${E(money(ins))}</td></tr>
        <tr><td>Paid by patient</td><td>${E(money(paid))}</td></tr>
        <tr><td><b>Balance due</b></td><td><b>${E(money(balance))}</b></td></tr>
      </table>
      ${b.notes ? '<p>' + E(b.notes) + '</p>' : ''}
      </body></html>`);
    w.document.close();
    w.focus();
    w.print();
  }

  crudTable({
    el: '#bills', table: 'billing', pk: 'bill_id', title: 'Bills',
    readonly: ROLE === 'insurance',
    defaults: { bill_date: today(), payment_status: 'Pending' },
    onChange: refresh,
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
    readonly: ROLE === 'staff',      // front desk can view claims but not create or approve them
    defaults: { claim_date: today(), submission_date: today(), status: 'Submitted' },
    onChange: refresh,
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
});
</script>
<?php page_end();