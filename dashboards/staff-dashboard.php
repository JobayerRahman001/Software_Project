<?php
require_once '../includes/layout.php';
page_start('Front Desk Dashboard', ['staff']);
?>
<p class="subtitle">Appointments, check-ins and front desk work.</p>

<div class="stats">
  <div class="stat"><b data-stat="appointments_today">-</b><span>Appointments today</span></div>
  <div class="stat green"><b data-stat="checked_in">-</b><span>Checked in</span></div>
  <div class="stat amber"><b data-stat="pending_bills">-</b><span>Pending bills</span></div>
  <div class="stat blue"><b data-stat="pending_claims">-</b><span>Insurance claims pending</span></div>
</div>

<div id="today"></div>
<div id="emergency"></div>
<div id="staff"></div>

<script>
loadStats();
const refresh = () => loadStats();

crudTable({
  el: '#today', table: 'appointments', pk: 'appointment_id', title: "Today's Appointments", readonly: true,
  params: '&appointment_date=' + today(), onChange: refresh,
  extra: [{ label: 'Check in', run: r => save('appointments', r.appointment_id, { status: 'Confirmed' }) }],
  columns: [['appointment_time', 'Time'], ['patient_name', 'Patient'], ['doctor_name', 'Doctor'], ['reason', 'Reason'], ['status', 'Status', 'badge']],
});
crudTable({
  el: '#emergency', table: 'appointments', pk: 'appointment_id', title: 'Emergency Alerts', readonly: true,
  params: '&appointment_type=Emergency', where: r => r.status !== 'Completed' && r.status !== 'Cancelled',
  extra: [{ label: 'Acknowledge', run: r => save('appointments', r.appointment_id, { status: 'Confirmed' }) }],
  columns: [['appointment_date', 'Date'], ['patient_name', 'Patient'], ['doctor_name', 'Doctor'], ['reason', 'Reason'], ['status', 'Status', 'badge']],
});
crudTable({
  el: '#staff', table: 'staff', pk: 'staff_id', title: 'Staff Members', readonly: true,
  filter: { key: 'department', label: 'departments', options: ['Front Desk', 'Billing', 'Pharmacy', 'Laboratory', 'Nursing'] },
  columns: [['staff_id', 'ID'], ['full_name', 'Name'], ['department', 'Department'], ['phone', 'Phone'], ['shift_timing', 'Shift']],
});
</script>
<?php page_end();
