<?php
require_once '../includes/layout.php';
page_start('Doctor Dashboard', ['doctor']);
?>
<p class="subtitle">Your practice overview for today.</p>

<div class="stats">
  <div class="stat"><b data-stat="appointments_today">-</b><span>Appointments today</span></div>
  <div class="stat green"><b data-stat="patients">-</b><span>Patients</span></div>
  <div class="stat amber"><b data-stat="prescriptions">-</b><span>Active prescriptions</span></div>
  <div class="stat blue"><b data-stat="predictions">-</b><span>AI assisted cases</span></div>
</div>

<div id="today"></div>
<div id="upcoming"></div>
<div id="predictions"></div>

<script>
loadStats();
const mine = ME.doctor_id ? `&doctor_id=${ME.doctor_id}` : '';   // only this doctor's appointments
const complete = r => save('appointments', r.appointment_id, { status: 'Completed' });
const appointmentColumns = [['appointment_time', 'Time'], ['appointment_date', 'Date'], ['patient_name', 'Patient'], ['reason', 'Reason'], ['status', 'Status', 'badge']];

crudTable({
  el: '#today', table: 'appointments', pk: 'appointment_id', title: "Today's Schedule", readonly: true,
  params: mine + '&appointment_date=' + today(), columns: appointmentColumns,
  extra: [{ label: 'Start / Complete', run: complete }],
});
crudTable({
  el: '#upcoming', table: 'appointments', pk: 'appointment_id', title: 'Upcoming Appointments', readonly: true,
  params: mine, where: r => r.appointment_date > today() && r.status !== 'Cancelled', columns: appointmentColumns,
});
crudTable({
  el: '#predictions', table: 'disease_predictions', pk: 'prediction_id', title: 'AI Diagnostic Assistance', readonly: true,
  columns: [['patient_name', 'Patient'], ['predicted_disease', 'Possible condition'], ['confidence_score', 'Confidence', r => r.confidence_score + '%'], ['severity_level', 'Severity', 'badge'], ['suggestions', 'Advice']],
});
</script>
<?php page_end();
