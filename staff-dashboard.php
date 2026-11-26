<?php
require_once '../includes/layout.php';
// Admin is allowed too, so the admin can see exactly what front desk sees
page_start('Front Desk Dashboard', ['staff', 'admin']);
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
document.addEventListener('DOMContentLoaded', () => {
  // Status written on check-in / acknowledge.
  // TODO: if your appointments.status ENUM has a 'Checked In' value, use it here so
  // "Checked in" in the stats is not the same thing as "Confirmed".
  const CHECKED_IN = 'Confirmed';

  const refresh = () => loadStats();
  refresh();

  crudTable({
    el: '#today', table: 'appointments', pk: 'appointment_id', title: "Today's Appointments", readonly: true,
    params: '&appointment_date=' + today(), onChange: refresh,
    extra: [{ label: 'Check in', run: r => save('appointments', r.appointment_id, { status: CHECKED_IN }) }],
    columns: [['appointment_time', 'Time'], ['patient_name', 'Patient'], ['doctor_name', 'Doctor'],
              ['reason', 'Reason'], ['status', 'Status', 'badge']],
  });

  crudTable({
    el: '#emergency', table: 'appointments', pk: 'appointment_id', title: 'Emergency Alerts', readonly: true,
    params: '&appointment_type=Emergency', onChange: refresh,
    where: r => r.status !== 'Completed' && r.status !== 'Cancelled',
    extra: [{ label: 'Acknowledge', run: r => save('appointments', r.appointment_id, { status: CHECKED_IN }) }],
    columns: [['appointment_date', 'Date'], ['patient_name', 'Patient'], ['doctor_name', 'Doctor'],
              ['reason', 'Reason'], ['status', 'Status', 'badge']],
  });

  crudTable({
    el: '#staff', table: 'staff', pk: 'staff_id', title: 'Staff Members', readonly: true,
    filter: { key: 'department', label: 'departments',
              options: ['Front Desk', 'Billing', 'Pharmacy', 'Laboratory', 'Nursing'] },
    columns: [['staff_id', 'ID'], ['full_name', 'Name'], ['department', 'Department'],
              ['phone', 'Phone'], ['shift_timing', 'Shift']],
  });

  // Keep stats and emergency alerts fresh without a manual reload (every 60 s)
  setInterval(refresh, 60000);
});
</script>
<?php page_end();