<?php
require_once '../includes/layout.php';
page_start('Smart Recommendations', ['admin', 'doctor', 'datascientist', 'patient']);
?>
<div class="stats">
  <div class="stat"><b id="rec-total">-</b><span>Recommendations</span></div>
  <div class="stat red"><b id="rec-high">-</b><span>High priority</span></div>
  <div class="stat blue"><b id="rec-ai">-</b><span>AI generated</span></div>
</div>
<?php if ($_SESSION['user']['role'] !== 'patient'): ?>
  <p style="margin-bottom:1rem"><button class="btn primary" onclick="generateFromPredictions()"><i class="fas fa-wand-magic-sparkles"></i> Generate from AI predictions</button></p>
<?php endif; ?>


<div id="recs"></div>
<div id="reminders"></div>


<script>
const isPatient = ROLE === 'patient';
const patientField = isPatient ? [] : [['patient_id', 'Patient *', 'patient']];


const recs = crudTable({
  el: '#recs', table: 'recommendations', pk: 'recommendation_id', title: 'Recommendations',
  readonly: isPatient,
  defaults: { priority: 'Medium', source: 'Doctor', status: 'Pending' },
  filter: { key: 'priority', label: 'priorities', options: ['Low', 'Medium', 'High'] },
  onChange: rows => {
    document.getElementById('rec-total').textContent = rows.length;
    document.getElementById('rec-high').textContent = rows.filter(r => r.priority === 'High').length;
    document.getElementById('rec-ai').textContent = rows.filter(r => r.is_ai_generated == 1).length;
  },
  columns: [
    ['patient_name', 'Patient'], ['recommendation_type', 'Type'], ['recommendation_text', 'Recommendation'],
    ['priority', 'Priority', 'badge'], ['source', 'Source', 'badge'], ['status', 'Status', 'badge'],
  ],
  fields: [
    ...patientField, ['recommendation_type', 'Type *', ['Doctor', 'Test', 'Lifestyle', 'Medication', 'Checkup', 'Diet', 'Exercise']],
    ['priority', 'Priority', ['Low', 'Medium', 'High']], ['source', 'Source', ['AI', 'Doctor', 'System']],
    ['status', 'Status', ['Pending', 'In Progress', 'Completed', 'Cancelled']],
    ['recommendation_text', 'Recommendation *', 'textarea'], ['notes', 'Notes', 'textarea'],
  ],
});


crudTable({
  el: '#reminders', table: 'health_reminders', pk: 'reminder_id', title: 'Health Reminders',
  defaults: { reminder_date: today(), priority: 'Medium' },
  extra: [{ label: '<i class="fas fa-check"></i> Done', run: r => save('health_reminders', r.reminder_id, { is_completed: 1, completed_date: today() }) }],
  columns: [
    ['reminder_date', 'Due'], ['patient_name', 'Patient'], ['reminder_type', 'Type'], ['reminder_text', 'Reminder'],
    ['priority', 'Priority', 'badge'], ['is_completed', 'Done', r => badge(r.is_completed == 1 ? 'Completed' : 'Pending')],
  ],
  fields: [
    ...patientField, ['reminder_type', 'Type *'], ['reminder_date', 'Due date *', 'date'],
    ['frequency', 'Repeats', ['Once', 'Daily', 'Weekly', 'Monthly', 'Yearly']], ['priority', 'Priority', ['Low', 'Medium', 'High']],
    ['reminder_text', 'Reminder *', 'textarea'],
  ],
});


// Turn each AI prediction into a recommendation (skips ones that already exist)
async function generateFromPredictions() {
  const [predictions, existing] = await Promise.all([list('disease_predictions'), list('recommendations')]);
  const have = new Set(existing.map(r => r.recommendation_text));
  let added = 0;
  for (const p of predictions) {
    const text = Follow up on ${p.predicted_disease}: ${p.suggestions || 'see a doctor'};
    if (have.has(text)) continue;
    await save('recommendations', null, {
      patient_id: p.patient_id, recommendation_type: 'Checkup', recommendation_text: text, source: 'AI', is_ai_generated: 1,
      priority: ['High', 'Critical'].includes(p.severity_level) ? 'High' : p.severity_level, confidence_score: p.confidence_score,
    });
    added++;
  }
  toast(added ? ${added} recommendation(s) created : 'Nothing new to add');
  recs.reload();
}
</script>
<?php page_end();