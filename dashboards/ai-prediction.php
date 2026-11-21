<?php
require_once '../includes/layout.php';
page_start('AI Disease Prediction', ['admin', 'doctor', 'datascientist', 'patient']);
$isPatient = $_SESSION['user']['role'] === 'patient';
?>
<div class="card">
  <h3><i class="fas fa-brain"></i> Symptom Checker</h3>
  <div class="fields">
    <?php if (!$isPatient): ?>
      <div class="full"><label>Patient (optional - saves the result)</label><select id="patient"><option value="">-</option></select></div>
    <?php endif; ?>
    <div class="full"><label>Describe the symptoms</label><textarea id="symptoms" rows="3" placeholder="e.g. fever, cough and headache for 3 days"></textarea></div>
  </div>
  <p style="margin-top:.75rem"><button class="btn primary" id="analyze"><i class="fas fa-wand-magic-sparkles"></i> Analyze</button></p>
  <div id="results"></div>
</div>

<div id="symptoms-table"></div>
<div id="predictions-table"></div>

<script>
const canReview = ROLE !== 'patient';   // only staff-side roles can edit predictions
const patientField = ROLE === 'patient' ? [] : [['patient_id', 'Patient *', 'patient']];

const symptoms = crudTable({
  el: '#symptoms-table', table: 'symptoms_input', pk: 'symptom_id', title: 'Symptoms Input',
  filter: { key: 'severity', label: 'severities', options: ['Low', 'Medium', 'High'] },
  columns: [['input_date', 'Date'], ['patient_name', 'Patient'], ['symptoms', 'Symptoms'], ['severity', 'Severity', 'badge'], ['duration_days', 'Days']],
  fields: [...patientField, ['symptoms', 'Symptoms *', 'textarea'], ['severity', 'Severity', ['Low', 'Medium', 'High']], ['duration_days', 'Duration (days)', 'number'], ['notes', 'Notes', 'textarea']],
});

const predictions = crudTable({
  el: '#predictions-table', table: 'disease_predictions', pk: 'prediction_id', title: 'Disease Predictions',
  readonly: !canReview,
  filter: { key: 'severity_level', label: 'severities', options: ['Low', 'Medium', 'High', 'Critical'] },
  columns: [
    ['prediction_date', 'Date'], ['patient_name', 'Patient'], ['predicted_disease', 'Disease'],
    ['confidence_score', 'Confidence', r => r.confidence_score + '%'], ['severity_level', 'Severity', 'badge'], ['suggestions', 'Advice'],
  ],
  fields: [
    ['patient_id', 'Patient *', 'patient'], ['predicted_disease', 'Disease *'], ['confidence_score', 'Confidence % *', 'number'],
    ['severity_level', 'Severity', ['Low', 'Medium', 'High', 'Critical']], ['suggestions', 'Suggestions', 'textarea'], ['notes', 'Notes', 'textarea'],
  ],
});

// patient dropdown for the checker
if (canReview) list('patients').then(rows => {
  document.getElementById('patient').innerHTML += rows.map(p => `<option value="${p.patient_id}">${esc(p.full_name)}</option>`).join('');
});

document.getElementById('analyze').onclick = async () => {
  const text = document.getElementById('symptoms').value.trim();
  if (!text) return toast('Please enter symptoms', true);
  try {
    const { predictions: found } = await api('ai/predict.php', 'POST', { symptoms: text, patient_id: document.getElementById('patient')?.value });
    document.getElementById('results').innerHTML = found.map(p =>
      `<div class="result"><b>${esc(p.disease)}</b> (${p.confidence}%) ${badge(p.severity)}<br><small>${esc(p.suggestions)}</small></div>`).join('')
      + '<p class="subtitle" style="margin-top:.75rem"><small>This is a basic automated suggestion, not a medical diagnosis.</small></p>';
    symptoms.reload(); predictions.reload();
  } catch (err) { toast(err.message, true); }
};
</script>
<?php page_end();
