<?php
require_once '../includes/layout.php';
page_start('Patient Dashboard', ['patient']);
?>
<p class="subtitle">Your health overview, <?= htmlspecialchars($_SESSION['user']['name']) ?>.</p>

<div class="stats">
  <div class="stat"><b id="s-appt">-</b><span>Upcoming appointments</span></div>
  <div class="stat green"><b id="s-rx">-</b><span>Active prescriptions</span></div>
  <div class="stat amber"><b id="s-due">-</b><span>Amount due</span></div>
  <div class="stat blue"><b id="s-rem">-</b><span>Open reminders</span></div>
</div>

<div id="appointments"></div>
<div id="records"></div>
<div id="prescriptions"></div>
<div id="bills"></div>

<script>
const set = (id, v) => (document.getElementById(id).textContent = v);

// The server only returns this patient's own rows, so no filtering by id is needed here.
Promise.all([list('prescriptions'), list('billing'), list('health_reminders')]).then(([rx, bills, rem]) => {
  set('s-rx', rx.filter(r => r.is_active == 1).length);
  set('s-due', money(bills.filter(b => b.payment_status !== 'Paid' && b.payment_status !== 'Cancelled').reduce((sum, b) => sum + b.total_amount - b.patient_paid - b.insurance_covered, 0)));
  set('s-rem', rem.filter(r => r.is_completed == 0).length);
});

crudTable({
  el: '#appointments', table: 'appointments', pk: 'appointment_id', title: 'My Appointments',
  defaults: { status: 'Scheduled', appointment_date: today() },
  onChange: rows => set('s-appt', rows.filter(r => r.appointment_date >= today() && r.status !== 'Cancelled').length),
  columns: [['appointment_date', 'Date'], ['appointment_time', 'Time'], ['doctor_name', 'Doctor'], ['reason', 'Reason'], ['status', 'Status', 'badge']],
  fields: [['doctor_id', 'Doctor *', 'doctor'], ['appointment_date', 'Date *', 'date'], ['appointment_time', 'Time *', 'time'], ['reason', 'Reason', 'textarea']],
});
crudTable({
  el: '#records', table: 'medical_records', pk: 'record_id', title: 'Medical Records', readonly: true,
  columns: [['visit_date', 'Date'], ['doctor_name', 'Doctor'], ['visit_type', 'Visit'], ['diagnosis', 'Diagnosis'], ['symptoms', 'Symptoms']],
});
crudTable({
  el: '#prescriptions', table: 'prescriptions', pk: 'prescription_id', title: 'My Prescriptions', readonly: true,
  columns: [['prescription_date', 'Date'], ['doctor_name', 'Doctor'], ['medicines', 'Medicines'], ['dosage', 'Dosage'], ['frequency', 'Frequency'], ['duration', 'Duration']],
});
crudTable({
  el: '#bills', table: 'billing', pk: 'bill_id', title: 'My Bills', readonly: true,
  columns: [['bill_date', 'Date'], ['total_amount', 'Total', 'money'], ['patient_paid', 'Paid', 'money'], ['payment_status', 'Status', 'badge']],
});
</script>
<?php page_end();
