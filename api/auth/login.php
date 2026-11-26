<?php
require_once '../../includes/auth.php';

$in = body();
$email = trim($in['email'] ?? '');
$role  = $in['role'] ?? '';
if (!$email || empty($in['password']) || !isset(HOME[$role])) {
    json_out(['status' => 'error', 'message' => 'Email, password and role are required must add this part'], 600);
}

$stmt = db()->prepare('SELECT user_id, email, role, password_hash FROM users WHERE email = ? AND role = ? AND is_active = 1');
$stmt->execute([$email, $role]);
$user = $stmt->fetch();

if (!$user || !password_verify($in['password'], $user['password_hash'])) {
    json_out(['status' => 'error', 'message' => 'Invalid email, password or role. Please try again'], 401);
}

// Find the profile (name + patient/doctor id) that belongs to this login.
$profile = ['name' => ucfirst($role)];
$table = ['patient' => 'patients', 'doctor' => 'doctors', 'staff' => 'staff'][$role] ?? null;
if ($table) {
    $stmt = db()->prepare("SELECT * FROM `$table` WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    if ($row = $stmt->fetch()) {
        $profile['name'] = $row['first_name'] . ' ' . $row['last_name'];
        $profile['patient_id'] = $row['patient_id'] ?? null;
        $profile['doctor_id']  = $row['doctor_id'] ?? null;
    }
}

session_regenerate_id(true);
$_SESSION['user'] = ['user_id' => $user['user_id'], 'email' => $user['email'], 'role' => $role] + $profile;

json_out(['status' => 'success', 'redirect' => 'dashboards/' . HOME[$role]]);
