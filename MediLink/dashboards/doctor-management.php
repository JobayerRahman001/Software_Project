<?php
require_once '../includes/layout.php';
page_start('Doctors & Appointments', ['admin', 'doctor', 'staff']);
?>
<div id="doctors"></div>
<div id="appointments"></div>
<?php if ($_SESSION['user']['role'] === 'admin'): ?><div id="staff"></div><?php endif; ?>

<script>
crudTable({
  el: '#doctors', table: 'doctors', pk: 'doctor_id', title: 'Doctors',
  readonly: ROLE === 'doctor',   // doctors can look, admin/staff can edit
  filter: { key: 'specialization', label: 'specializations', options: ['Cardiology', 'Neurology', 'Pediatrics', 'General Medicine', 'Orthopedics', 'Dermatology'] },
  columns: [
    ['doctor_id', 'ID'], ['full_name', 'Name'], ['specialization', 'Specialization'], ['qualification', 'Qualification'],
    ['phone', 'Phone'], ['consultation_fee', 'Fee', 'money'],
    ['available_days', 'Availability', r => esc((r.available_days || '-') + ' ' + (r.available_time || ''))],
  ],
  fields: [
    ['first_name', 'First name *'], ['last_name', 'Last name *'],
    ['specialization', 'Specialization *'], ['qualification', 'Qualification'],
    ['license_number', 'License number'], ['experience_years', 'Experience (years)', 'number'],
    ['phone', 'Phone'], ['email', 'Email', 'email'],
    ['consultation_fee', 'Consultation fee', 'number'], ['room_number', 'Room'],
    ['available_days', 'Available days (e.g. Mon-Fri)'], ['available_time', 'Available time (e.g. 9AM-5PM)'],
  ],
});

crudTable({
  el: '#appointments', table: 'appointments', pk: 'appointment_id', title: 'Appointments',
  defaults: { status: 'Scheduled', appointment_date: today() },
  filter: { key: 'status', label: 'statuses', options: ['Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'No-show'] },
  columns: [
    ['appointment_date', 'Date'], ['appointment_time', 'Time'], ['patient_name', 'Patient'],
    ['doctor_name', 'Doctor'], ['appointment_type', 'Type'], ['status', 'Status', 'badge'], ['reason', 'Reason'],
  ],
  fields: [
    ['patient_id', 'Patient *', 'patient'], ['doctor_id', 'Doctor *', 'doctor'],
    ['appointment_date', 'Date *', 'date'], ['appointment_time', 'Time *', 'time'],
    ['status', 'Status', ['Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'No-show']],
    ['appointment_type', 'Type', ['Consultation', 'Follow-up', 'Emergency', 'Test']],
    ['reason', 'Reason', 'textarea'],
  ],
});

if (ROLE === 'admin') crudTable({
  el: '#staff', table: 'staff', pk: 'staff_id', title: 'Staff',
  columns: [['staff_id', 'ID'], ['full_name', 'Name'], ['department', 'Department'], ['phone', 'Phone'], ['shift_timing', 'Shift']],
  fields: [
    ['first_name', 'First name *'], ['last_name', 'Last name *'],
    ['department', 'Department', ['Front Desk', 'Billing', 'Pharmacy', 'Laboratory', 'Nursing']],
    ['phone', 'Phone'], ['email', 'Email', 'email'], ['shift_timing', 'Shift (e.g. 8AM-4PM)'],
  ],
});
</script>
<?php page_end();
