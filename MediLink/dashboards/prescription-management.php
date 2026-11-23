<?php
require_once '../includes/layout.php';
page_start('Prescriptions & Investigations', ['admin', 'doctor']);
?>
<div class="stats">
  <div class="stat"><b id="rx-total">-</b><span>Total prescriptions</span></div>
  <div class="stat green"><b id="rx-today">-</b><span>Written today</span></div>
  <div class="stat amber"><b id="test-pending">-</b><span>Pending tests</span></div>
</div>

<div id="prescriptions"></div>
<div id="investigations"></div>
<div id="records"></div>

<script>
crudTable({
  el: '#prescriptions', table: 'prescriptions', pk: 'prescription_id', title: 'Prescriptions',
  defaults: { prescription_date: today() },
  onChange: rows => {
    document.getElementById('rx-total').textContent = rows.length;
    document.getElementById('rx-today').textContent = rows.filter(r => r.prescription_date === today()).length;
  },
  columns: [
    ['prescription_date', 'Date'], ['patient_name', 'Patient'], ['doctor_name', 'Doctor'],
    ['medicines', 'Medicines'], ['dosage', 'Dosage'], ['frequency', 'Frequency'], ['duration', 'Duration'],
  ],
  fields: [
    ['patient_id', 'Patient *', 'patient'], ['doctor_id', 'Doctor *', 'doctor'],
    ['prescription_date', 'Date *', 'date'], ['route', 'Route', ['Oral', 'Injection', 'Topical', 'Inhalation']],
    ['medicines', 'Medicines *', 'textarea'], ['dosage', 'Dosage'], ['frequency', 'Frequency'], ['duration', 'Duration'],
    ['diagnosis', 'Diagnosis', 'textarea'], ['instructions', 'Instructions', 'textarea'],
  ],
});

crudTable({
  el: '#investigations', table: 'investigations', pk: 'investigation_id', title: 'Investigations',
  defaults: { test_date: today(), status: 'Pending' },
  onChange: rows => (document.getElementById('test-pending').textContent = rows.filter(r => r.status === 'Pending').length),
  filter: { key: 'status', label: 'statuses', options: ['Pending', 'In Progress', 'Completed', 'Cancelled'] },
  columns: [
    ['test_date', 'Date'], ['patient_name', 'Patient'], ['test_name', 'Test'], ['test_type', 'Type'],
    ['results', 'Result'], ['status', 'Status', 'badge'],
  ],
  fields: [
    ['patient_id', 'Patient *', 'patient'], ['doctor_id', 'Doctor', 'doctor'],
    ['test_name', 'Test name *'], ['test_type', 'Type', ['Blood Test', 'X-Ray', 'MRI', 'CT Scan', 'Ultrasound', 'ECG', 'Urine Test']],
    ['test_date', 'Date *', 'date'], ['status', 'Status', ['Pending', 'In Progress', 'Completed', 'Cancelled']],
    ['results', 'Results', 'textarea'], ['doctor_notes', 'Doctor notes', 'textarea'],
  ],
});

crudTable({
  el: '#records', table: 'medical_records', pk: 'record_id', title: 'Medical Records',
  defaults: { visit_date: today(), visit_type: 'Consultation' },
  columns: [['visit_date', 'Date'], ['patient_name', 'Patient'], ['doctor_name', 'Doctor'], ['visit_type', 'Visit'], ['diagnosis', 'Diagnosis'], ['symptoms', 'Symptoms']],
  fields: [
    ['patient_id', 'Patient *', 'patient'], ['doctor_id', 'Doctor', 'doctor'],
    ['visit_date', 'Visit date *', 'date'], ['visit_type', 'Visit type', ['Consultation', 'Follow-up', 'Emergency', 'Routine Checkup', 'Vaccination']],
    ['blood_pressure', 'Blood pressure'], ['temperature', 'Temperature', 'number'], ['heart_rate', 'Heart rate', 'number'],
    ['symptoms', 'Symptoms', 'textarea'], ['diagnosis', 'Diagnosis', 'textarea'], ['treatment', 'Treatment', 'textarea'],
  ],
});
</script>
<?php page_end();
