<?php
// Numbers and chart data for the dashboards (everything comes from the database).
require_once '../includes/auth.php';
require_api_login(['admin', 'doctor', 'staff', 'financial', 'datascientist', 'insurance']);
$db = db();

function one(PDO $db, string $sql) { return $db->query($sql)->fetchColumn() ?: 0; }
// Two columns -> ['label' => value, ...]
function pairs(PDO $db, string $sql): array { return $db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR); }

$claims = one($db, 'SELECT COUNT(*) FROM insurance_claims');
$rejected = one($db, "SELECT COUNT(*) FROM insurance_claims WHERE status = 'Rejected'");

json_out([
    'status' => 'success',
    'stats' => [
        'patients'            => one($db, 'SELECT COUNT(*) FROM patients'),
        'doctors'             => one($db, 'SELECT COUNT(*) FROM doctors WHERE is_active = 1'),
        'staff'               => one($db, 'SELECT COUNT(*) FROM staff WHERE is_active = 1'),
        'appointments_today'  => one($db, 'SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()'),
        'checked_in'          => one($db, "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status = 'Confirmed'"),
        'prescriptions'       => one($db, 'SELECT COUNT(*) FROM prescriptions WHERE is_active = 1'),
        'pending_bills'       => one($db, "SELECT COUNT(*) FROM billing WHERE payment_status = 'Pending'"),
        'revenue'             => round(one($db, "SELECT SUM(total_amount) FROM billing WHERE payment_status <> 'Cancelled'"), 2),
        'collected'           => round(one($db, 'SELECT SUM(patient_paid + insurance_covered) FROM billing'), 2),
        'claims'              => $claims,
        'pending_claims'      => one($db, "SELECT COUNT(*) FROM insurance_claims WHERE status IN ('Submitted', 'Under Review')"),
        'approved_claims'     => one($db, "SELECT COUNT(*) FROM insurance_claims WHERE status IN ('Approved', 'Paid')"),
        'rejection_rate'      => $claims ? round($rejected / $claims * 100, 1) : 0,
        'predictions'         => one($db, 'SELECT COUNT(*) FROM disease_predictions'),
        'avg_confidence'      => round(one($db, 'SELECT AVG(confidence_score) FROM disease_predictions'), 1),
        'investigations'      => one($db, 'SELECT COUNT(*) FROM investigations'),
        'reminders'           => one($db, 'SELECT COUNT(*) FROM health_reminders WHERE is_completed = 0'),
    ],
    'charts' => [
        'patients_by_month' => pairs($db, "SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM patients GROUP BY 1 ORDER BY 1"),
        'diseases'          => pairs($db, 'SELECT predicted_disease, COUNT(*) FROM disease_predictions GROUP BY 1 ORDER BY 2 DESC LIMIT 6'),
        'revenue_by_month'  => pairs($db, "SELECT DATE_FORMAT(bill_date, '%Y-%m'), SUM(total_amount) FROM billing GROUP BY 1 ORDER BY 1"),
        'payment_status'    => pairs($db, 'SELECT payment_status, COUNT(*) FROM billing GROUP BY 1'),
        'claim_status'      => pairs($db, 'SELECT status, COUNT(*) FROM insurance_claims GROUP BY 1'),
        'age_groups'        => pairs($db, "SELECT CASE WHEN age < 18 THEN '0-17' WHEN age < 36 THEN '18-35' WHEN age < 56 THEN '36-55' ELSE '56+' END g, COUNT(*)
                                           FROM (SELECT TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) age FROM patients) x GROUP BY 1 ORDER BY 1"),
        'severity'          => pairs($db, 'SELECT severity_level, COUNT(*) FROM disease_predictions GROUP BY 1'),
    ],
]);
