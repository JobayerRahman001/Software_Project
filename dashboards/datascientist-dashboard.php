<?php
require_once '../includes/layout.php';
page_start('Data Scientist Dashboard', ['datascientist']);
?>
<p class="subtitle">AI research overview. Every table has a CSV export button for dataset downloads.</p>

<div class="stats">
  <div class="stat"><b data-stat="avg_confidence" data-format="percent">-</b><span>Average model confidence</span></div>
  <div class="stat green"><b data-stat="predictions">-</b><span>Predictions made</span></div>
  <div class="stat amber"><b data-stat="investigations">-</b><span>Investigations</span></div>
  <div class="stat blue"><b data-stat="patients">-</b><span>Patients in dataset</span></div>
</div>

<div class="grid">
  <div class="card"><h3>Predicted diseases</h3><div class="chart-box"><canvas id="diseaseChart"></canvas></div></div>
  <div class="card"><h3>Patients by age group</h3><div class="chart-box"><canvas id="ageChart"></canvas></div></div>
  <div class="card"><h3>Prediction severity</h3><div class="chart-box"><canvas id="severityChart"></canvas></div></div>
</div>

<div id="predictions"></div>
<div id="investigations"></div>
<div id="reminders"></div>

<script>
loadStats().then(c => {
  chart('diseaseChart', 'bar', c.diseases, 'Predictions');
  chart('ageChart', 'doughnut', c.age_groups);
  chart('severityChart', 'doughnut', c.severity);
});
crudTable({
  el: '#predictions', table: 'disease_predictions', pk: 'prediction_id', title: 'Recent AI Predictions', readonly: true,
  columns: [['prediction_date', 'Date'], ['patient_name', 'Patient'], ['predicted_disease', 'Disease'], ['confidence_score', 'Confidence', r => r.confidence_score + '%'], ['severity_level', 'Severity', 'badge']],
});
crudTable({
  el: '#investigations', table: 'investigations', pk: 'investigation_id', title: 'Investigations',
  defaults: { test_date: today(), status: 'Pending' },
  filter: { key: 'status', label: 'statuses', options: ['Pending', 'In Progress', 'Completed', 'Cancelled'] },
  columns: [['test_date', 'Date'], ['patient_name', 'Patient'], ['test_name', 'Test'], ['results', 'Result'], ['status', 'Status', 'badge']],
  fields: [
    ['patient_id', 'Patient *', 'patient'], ['test_name', 'Test name *'],
    ['test_type', 'Type', ['Blood Test', 'X-Ray', 'MRI', 'CT Scan', 'Ultrasound', 'ECG', 'Urine Test']],
    ['test_date', 'Date *', 'date'], ['status', 'Status', ['Pending', 'In Progress', 'Completed', 'Cancelled']], ['results', 'Results', 'textarea'],
  ],
});
crudTable({
  el: '#reminders', table: 'health_reminders', pk: 'reminder_id', title: 'Health Reminders',
  defaults: { reminder_date: today(), priority: 'Medium' },
  columns: [['reminder_date', 'Due'], ['patient_name', 'Patient'], ['reminder_type', 'Type'], ['reminder_text', 'Reminder'], ['priority', 'Priority', 'badge']],
  fields: [['patient_id', 'Patient *', 'patient'], ['reminder_type', 'Type *'], ['reminder_date', 'Due date *', 'date'], ['priority', 'Priority', ['Low', 'Medium', 'High']], ['reminder_text', 'Reminder *', 'textarea']],
});
</script>
<?php page_end();
