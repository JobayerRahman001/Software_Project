<?php
require_once '../includes/layout.php';
page_start('Patient Management', ['admin', 'doctor', 'staff']);
?>
<div id="patients"></div>

<script>
crudTable({
  el: '#patients', table: 'patients', pk: 'patient_id', title: 'Patients',
  filter: { key: 'gender', label: 'genders', options: ['Male', 'Female', 'Other'] },
  columns: [
    ['patient_id', 'ID'], ['full_name', 'Name'], ['age', 'Age'], ['gender', 'Gender'],
    ['phone', 'Phone'], ['email', 'Email'], ['blood_group', 'Blood'], ['insurance_provider', 'Insurance'],
  ],
  fields: [
    ['first_name', 'First name *'], ['last_name', 'Last name *'],
    ['date_of_birth', 'Date of birth *', 'date'], ['gender', 'Gender *', ['Male', 'Female', 'Other']],
    ['phone', 'Phone'], ['email', 'Email', 'email'],
    ['blood_group', 'Blood group'], ['emergency_contact', 'Emergency contact'],
    ['height', 'Height (cm)', 'number'], ['weight', 'Weight (kg)', 'number'],
    ['insurance_provider', 'Insurance provider'], ['policy_number', 'Policy number'],
    ['address', 'Address', 'textarea'], ['allergies', 'Allergies', 'textarea'], ['chronic_conditions', 'Chronic conditions', 'textarea'],
  ],
});
</script>
<?php page_end();
