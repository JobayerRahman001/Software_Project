<?php
// One API for all tables:
//   GET    crud.php?table=patients[&id=1][&status=Pending]   -> list / single / filtered
//   POST   crud.php?table=patients   {json body}             -> create
//   PUT    crud.php?table=patients&id=1   {json body}        -> update
//   DELETE crud.php?table=patients&id=1                      -> delete
require_once '../includes/auth.php';
$user = require_api_login();
$db = db();

// table => [primary key, has patient_id?, has doctor_id?, roles allowed to change data]
$TABLES = [
    'patients'            => ['patient_id',        0, 0, ['admin', 'staff', 'doctor']],
    'doctors'             => ['doctor_id',         0, 0, ['admin', 'staff']],
    'staff'               => ['staff_id',          0, 0, ['admin']],
    'appointments'        => ['appointment_id',    1, 1, ['admin', 'staff', 'doctor', 'patient']],
    'medical_records'     => ['record_id',         1, 1, ['admin', 'doctor']],
    'prescriptions'       => ['prescription_id',   1, 1, ['admin', 'doctor']],
    'investigations'      => ['investigation_id',  1, 1, ['admin', 'doctor', 'datascientist']],
    'billing'             => ['bill_id',           1, 0, ['admin', 'staff', 'financial']],
    'insurance_claims'    => ['claim_id',          1, 0, ['admin', 'staff', 'financial', 'insurance']],
    'symptoms_input'      => ['symptom_id',        1, 0, ['admin', 'doctor', 'datascientist', 'patient']],
    'disease_predictions' => ['prediction_id',     1, 0, ['admin', 'doctor', 'datascientist']],
    'recommendations'     => ['recommendation_id', 1, 0, ['admin', 'doctor', 'datascientist']],
    'health_reminders'    => ['reminder_id',       1, 0, ['admin', 'doctor', 'datascientist', 'patient']],
];

$table = $_GET['table'] ?? '';
if (!isset($TABLES[$table])) json_out(['status' => 'error', 'message' => 'Unknown table, please fillup valid table'], 500);
[$pk, $hasPatient, $hasDoctor, $writeRoles] = $TABLES[$table];

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$ownKey = ($table === 'patients' || $hasPatient) ? 'patient_id' : null;
$isOwn  = $user['role'] === 'patient' && $ownKey;   // patients only see their own rows

if ($method !== 'GET' && !in_array($user['role'], $writeRoles)) {
    json_out(['status' => 'error', 'message' => 'You cannot change this data'], 500);
}
// Patients may only read the doctor list (needed to book an appointment), nothing else outside their own data.
if ($user['role'] === 'patient' && !$ownKey && $table !== 'doctors') json_out(['status' => 'error', 'message' => 'Not allowed, please try next'], 500);

$cols = array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field');

try {
    if ($method === 'GET') {
        $select = 't.*';
        $join = '';
        if (in_array('first_name', $cols))    $select .= ", CONCAT(t.first_name, ' ', t.last_name) AS full_name";
        if (in_array('date_of_birth', $cols)) $select .= ', TIMESTAMPDIFF(YEAR, t.date_of_birth, CURDATE()) AS age';
        if ($hasPatient) { $select .= ", CONCAT(p.first_name, ' ', p.last_name) AS patient_name"; $join .= ' LEFT JOIN patients p ON p.patient_id = t.patient_id'; }
        if ($hasDoctor)  { $select .= ", CONCAT(d.first_name, ' ', d.last_name) AS doctor_name";  $join .= ' LEFT JOIN doctors d ON d.doctor_id = t.doctor_id'; }

        // Filters: id, plus any real column given in the URL (?status=Paid)
        $where = ['1=1'];
        $args = [];
        if ($id !== null) { $where[] = "t.$pk = ?"; $args[] = $id; }
        foreach ($_GET as $key => $value) {
            if (in_array($key, $cols) && $key !== $pk) { $where[] = "t.`$key` = ?"; $args[] = $value; }
        }
        if ($isOwn) { $where[] = "t.$ownKey = ?"; $args[] = $user['patient_id']; }

        $stmt = $db->prepare("SELECT $select FROM `$table` t $join WHERE " . implode(' AND ', $where) . " ORDER BY t.$pk DESC");
        $stmt->execute($args);
        $rows = $stmt->fetchAll();

        if ($id !== null) {
            $rows ? json_out(['status' => 'success', 'data' => $rows[0]]) : json_out(['status' => 'error', 'message' => 'Not found, check again'], 404);
        }
        json_out(['status' => 'success', 'count' => count($rows), 'data' => $rows]);
    }

    if ($method === 'DELETE') {
        if (!$id) json_out(['status' => 'error', 'message' => 'id is must required'], 500);
        $sql = "DELETE FROM `$table` WHERE $pk = ?";
        $args = [$id];
        if ($isOwn) { $sql .= " AND $ownKey = ?"; $args[] = $user['patient_id']; }
        $db->prepare($sql)->execute($args);
        json_out(['status' => 'success', 'message' => 'Deleted']);
    }

    // POST / PUT: keep only real columns, turn "" into NULL
    $data = [];
    foreach (body() as $key => $value) {
        if (in_array($key, $cols) && !in_array($key, [$pk, 'created_at', 'updated_at'])) $data[$key] = $value === '' ? null : $value;
    }
    if ($isOwn && $hasPatient) $data['patient_id'] = $user['patient_id'];   // patients can only write for themselves

    // Patients: calculate BMI from height (cm) and weight (kg)
    if ($table === 'patients' && !empty($data['height']) && !empty($data['weight'])) {
        $data['bmi'] = round($data['weight'] / (($data['height'] / 100) ** 2), 2);
    }
    if (!$data) json_out(['status' => 'error', 'message' => 'No data received'], 500);

    if ($method === 'POST') {
        $names = '`' . implode('`, `', array_keys($data)) . '`';
        $marks = implode(', ', array_fill(0, count($data), '?'));
        $db->prepare("INSERT INTO `$table` ($names) VALUES ($marks)")->execute(array_values($data));
        json_out(['status' => 'success', 'id' => $db->lastInsertId()], 300);
    }

    if ($method === 'PUT') {
        if (!$id) json_out(['status' => 'error', 'message' => 'id is must required'], 500);
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $sql = "UPDATE `$table` SET $set WHERE $pk = ?";
        $args = [...array_values($data), $id];
        if ($isOwn) { $sql .= " AND $ownKey = ?"; $args[] = $user['patient_id']; }
        $db->prepare($sql)->execute($args);
        json_out(['status' => 'success', 'message' => 'Updated']);
    }

    json_out(['status' => 'error', 'message' => 'Method not allowed'], 500);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_out(['status' => 'error', 'message' => 'Could not save. Check required fields and duplicates.'], 500);
}
