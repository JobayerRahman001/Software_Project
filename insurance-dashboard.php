<?php
require_once '../includes/layout.php';
page_start('Insurance Dashboard', ['insurance', 'admin']);
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
document.addEventListener('DOMContentLoaded', async () => {
  const refresh = () => loadStats();

  // Claim life cycle: Submitted -> Under Review -> Approved | Rejected ; Approved -> Paid
  const only = (r, allowed) => {
    if (allowed.includes(r.status)) return true;
    alert(`This action needs a claim that is: ${allowed.join(' or ')}.\nThis claim is "${r.status}".`);
    return false;
  };

  const startReview = r => {
    if (!only(r, ['Submitted'])) return;
    return save('insurance_claims', r.claim_id, { status: 'Under Review' });
  };

  const approve = r => {
    if (!only(r, ['Under Review'])) return;
    const claimed = Number(r.claim_amount) || 0;
    const input = prompt(`Approved amount (claimed: ${money(claimed)})`, claimed);
    if (input === null) return;                       // cancelled
    const amt = Number(input);
    if (!(amt > 0) || amt > claimed) {
      alert('Approved amount must be more than 0 and not more than the claimed amount.');
      return;
    }
    return save('insurance_claims', r.claim_id,
      { status: 'Approved', approved_amount: amt, processed_date: today() });
  };

  const reject = r => {
    if (!only(r, ['Under Review'])) return;
    const reason = (prompt('Reason for rejection (required):') || '').trim();
    if (!reason) { alert('A rejection reason is required.'); return; }
    return save('insurance_claims', r.claim_id,
      { status: 'Rejected', rejection_reason: reason, processed_date: today() });
  };

  const markPaid = r => {
    if (!only(r, ['Approved'])) return;
    if (!confirm(`Mark claim #${r.claim_id} as paid by the insurer?`)) return;
    return save('insurance_claims', r.claim_id, { status: 'Paid' });
  };

  crudTable({
    el: '#claims', table: 'insurance_claims', pk: 'claim_id', title: 'Insurance Claims', noAdd: true,
    onChange: refresh,
    filter: { key: 'status', label: 'statuses', options: ['Submitted', 'Under Review', 'Approved', 'Rejected', 'Paid'] },
    extra: [
      { label: 'Start review', run: startReview },
      { label: 'Approve',      run: approve },
      { label: 'Reject',       run: reject },
      { label: 'Mark paid',    run: markPaid },
    ],
    columns: [['claim_id', 'Claim'], ['patient_name', 'Patient'], ['insurance_provider', 'Provider'],
              ['claim_amount', 'Amount', 'money'], ['status', 'Status', 'badge'], ['submission_date', 'Submitted']],
    // Status is changed only through the buttons above, so steps cannot be skipped from the edit form
    fields: [
      ['approved_amount', 'Approved amount', 'number'],
      ['rejection_reason', 'Rejection reason', 'textarea'], ['notes', 'Notes', 'textarea'],
    ],
  });

  try {
    const c = await loadStats();
    const data = c.claim_status;
    if (!data || Object.keys(data).length === 0) {
      document.querySelector('.chart-box').innerHTML = '<p class="subtitle">No claims yet.</p>';
    } else {
      chart('claimChart', 'doughnut', data);
    }
  } catch (err) {
    console.error('Insurance stats failed:', err);
    document.querySelector('.chart-box').innerHTML = '<p class="subtitle">Could not load data.</p>';
  }
});
</script>
<?php page_end();