<?php
require_once '../includes/layout.php';
page_start('AI Prediction Dashboard', ['admin', 'doctor', 'datascientist']);
?>
<p class="subtitle">Overview of automated symptom analysis: volumes, severity and model confidence.</p>

<div class="stats">
  <div class="stat"><b data-stat="predictions">-</b><span>Total predictions</span></div>
  <div class="stat amber"><b data-stat="predictions_today">-</b><span>Predictions today</span></div>
  <div class="stat red"><b data-stat="high_risk">-</b><span>High / Critical cases</span></div>
  <div class="stat blue"><b data-stat="avg_confidence" data-format="percent">-</b><span>Average confidence</span></div>
</div>

<div class="grid">
  <div class="card"><h3>Predictions by month</h3><div class="chart-box"><canvas id="monthChart"></canvas></div></div>
  <div class="card"><h3>Most predicted diseases</h3><div class="chart-box"><canvas id="diseaseChart"></canvas></div></div>
  <div class="card"><h3>Predictions by severity</h3><div class="chart-box"><canvas id="severityChart"></canvas></div></div>
  <div class="card"><h3>Average confidence by disease</h3><div class="chart-box"><canvas id="confidenceChart"></canvas></div></div>
</div>

<p style="margin-bottom:1rem"><a class="btn primary" href="ai-prediction.php"><i class="fas fa-brain"></i> Open symptom checker</a></p>

<div id="high-risk"></div>
<div id="recent"></div>

<p class="subtitle"><small>Predictions are automated suggestions, not medical diagnoses. A doctor must review high severity cases.</small></p>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  // Data scientists work with the model output; they do not need patient names
  const showPatient = ROLE !== 'datascientist';

  const draw = (id, type, data, label) => {
    const canvas = document.getElementById(id);
    if (!data || Object.keys(data).length === 0) {
      canvas.parentElement.innerHTML = '<p class="subtitle">No data yet.</p>';
      return;
    }
    chart(id, type, data, label);
  };

  const columns = [
    ['prediction_date', 'Date'],
    ...(showPatient ? [['patient_name', 'Patient']] : []),
    ['predicted_disease', 'Disease'],
    ['confidence_score', 'Confidence', r => r.confidence_score + '%'],
    ['severity_level', 'Severity', 'badge'],
  ];

  crudTable({
    el: '#high-risk', table: 'disease_predictions', pk: 'prediction_id', title: 'High-risk Cases', readonly: true,
    where: r => ['High', 'Critical'].includes(r.severity_level),
    columns: [...columns, ['suggestions', 'Advice']],
  });

  crudTable({
    el: '#recent', table: 'disease_predictions', pk: 'prediction_id', title: 'Recent Predictions', readonly: true,
    filter: { key: 'severity_level', label: 'severities', options: ['Low', 'Medium', 'High', 'Critical'] },
    columns,
  });

  try {
    const c = await loadStats();
    draw('monthChart',      'line',     c.predictions_by_month,   'Predictions');
    draw('diseaseChart',    'doughnut', c.diseases);
    draw('severityChart',   'doughnut', c.severity);
    draw('confidenceChart', 'bar',      c.confidence_by_disease,  'Avg confidence %');
  } catch (err) {
    console.error('AI dashboard stats failed:', err);
    document.querySelectorAll('.chart-box').forEach(b =>
      b.innerHTML = '<p class="subtitle">Could not load data.</p>');
  }
});
</script>
<?php page_end();
