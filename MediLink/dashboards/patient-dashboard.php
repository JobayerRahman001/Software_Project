<?php
require_once '../includes/layout.php';
page_start('Patient Dashboard', ['patient']);
?>

<!-- Clean, Modern Styling (Self-contained & Easy to Explain) -->
<style>
  :root {
    --primary: #0f766e;       /* Medical Teal */
    --primary-light: #f0fdfa;
    --bg-surface: #ffffff;
    --bg-page: #f8fafc;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
    --radius: 12px;
  }

  /* Welcome Banner */
  .welcome-banner {
    background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
    color: white;
    padding: 28px 32px;
    border-radius: var(--radius);
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: var(--shadow-md);
  }
  .welcome-banner h2 {
    margin: 0 0 6px 0;
    font-size: 1.5rem;
    font-weight: 600;
  }
  .welcome-banner p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
  }
  .date-badge {
    background: rgba(255, 255, 255, 0.15);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.875rem;
    backdrop-filter: blur(4px);
  }

  /* Modern Stats Grid */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
  }
  .stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
  }
  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
  }
  .icon-teal   { background: #ccfbf1; color: #0f766e; }
  .icon-green  { background: #dcfce7; color: #15803d; }
  .icon-amber  { background: #fef3c7; color: #b45309; }
  .icon-blue   { background: #dbeafe; color: #1d4ed8; }

  .stat-info b {
    display: block;
    font-size: 1.5rem;
    color: var(--text-main);
    line-height: 1.2;
    margin-bottom: 4px;
  }
  .stat-info span {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
  }

  /* Quick Jump Navigation Tabs */
  .quick-nav {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
    flex-wrap: wrap;
  }
  .quick-nav a {
    text-decoration: none;
    background: var(--bg-surface);
    color: var(--text-main);
    border: 1px solid var(--border-color);
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
  }
  .quick-nav a:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
  }

  /* Table Card Wrappers */
  .section-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
  }
</style>

<!-- 1. Welcome Header -->
<div class="welcome-banner">
  <div>
    <h2>Welcome back, <?= htmlspecialchars($_SESSION['user']['name']) ?></h2>
    <p>Here is your personal health overview, upcoming visits, and medical history.</p>
  </div>
  <div class="date-badge">
    📅 <?= date('l, F j, Y') ?>
  </div>
</div>

<!-- 2. Summary Metric Cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon icon-teal">🗓️</div>
    <div class="stat-info">
      <b id="s-appt">-</b>
      <span>Upcoming Appointments</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon icon-green">💊</div>
    <div class="stat-info">
      <b id="s-rx">-</b>
      <span>Active Prescriptions</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon icon-amber">💳</div>
    <div class="stat-info">
      <b id="s-due">-</b>
      <span>Amount Due</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon icon-blue">🔔</div>
    <div class="stat-info">
      <b id="s-rem">-</b>
      <span>Open Reminders</span>
    </div>
  </div>
</div>

<!-- 3. Quick Navigation Links -->
<nav class="quick-nav">
  <a href="#appointments">Appointments</a>
  <a href="#records">Medical Records</a>
  <a href="#prescriptions">Prescriptions</a>
  <a href="#bills">Billing & Invoices</a>
</nav>

<!-- 4. Data Tables Wrapped in Clean Cards -->
<div class="section-card">
  <div id="appointments"></div>
</div>

<div class="section-card">
  <div id="records"></div>
</div>

<div class="section-card">
  <div id="prescriptions"></div>
</div>

<div class="section-card">
  <div id="bills"></div>
</div>

<!-- 5. Original Data Loading & CRUD Table Logic (Unchanged) -->
<script>
const set = (id, v) => (document.getElementById(id).textContent = v);

// Fetch summary counts for prescriptions, unpaid bills, and open reminders
Promise.all([list('prescriptions'), list('billing'), list('health_reminders')]).then(([rx, bills, rem]) => {
  set('s-rx', rx.filter(r => r.is_active == 1).length);
  
  const totalDue = bills
    .filter(b => b.payment_status !== 'Paid' && b.payment_status !== 'Cancelled')
    .reduce((sum, b) => sum + b.total_amount - b.patient_paid - b.insurance_covered, 0);
  set('s-due', money(totalDue));
  
  set('s-rem', rem.filter(r => r.is_completed == 0).length);
});

// Initialize interactive tables
crudTable({
  el: '#appointments', table: 'appointments', pk: 'appointment_id', title: 'My Appointments',
  defaults: { status: 'Scheduled', appointment_date: today() },
  onChange: rows => set('s-appt', rows.filter(r => r.appointment_date >= today() && r.status !== 'Cancelled').length),
  columns: [['appointment_date', 'Date'], ['appointment_time', 'Time'], ['doctor_name', 'Doctor'], ['reason', 'Reason'], ['status', 'Status', 'badge']],
  fields: [['doctor_id', 'Doctor *', 'doctor'], ['appointment_date', 'Date *', 'date'], ['appointment_time', 'Time *', 'time'], ['reason', 'Reason', 'textarea']],
});

crudTable({
  el: '#records', table: 'medical_records', pk: 'record_id', title: 'Medical Records', readonly: true,
  columns: [['visit_date', 'Date'], ['doctor_name', 'Doctor'], ['visit_type', 'Visit'], ['diagnosis', 'Diagnosis'], ['symptoms', 'Symptoms']],
});

crudTable({
  el: '#prescriptions', table: 'prescriptions', pk: 'prescription_id', title: 'My Prescriptions', readonly: true,
  columns: [['prescription_date', 'Date'], ['doctor_name', 'Doctor'], ['medicines', 'Medicines'], ['dosage', 'Dosage'], ['frequency', 'Frequency'], ['duration', 'Duration']],
});

crudTable({
  el: '#bills', table: 'billing', pk: 'bill_id', title: 'My Bills', readonly: true,
  columns: [['bill_date', 'Date'], ['total_amount', 'Total', 'money'], ['patient_paid', 'Paid', 'money'], ['payment_status', 'Status', 'badge']],
});
</script>

<?php page_end(); ?>