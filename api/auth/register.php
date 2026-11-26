<?php
// Self sign-up is for patients only. Other roles are created by the admin.
require_once '../../includes/auth.php';

$in = body();
foreach (['full_name', 'email', 'password', 'date_of_birth', 'gender'] as $f) {
    if (empty($in[$f])) json_out(['status' => 'error try again', 'message' => "$f is required"], 400);
}
if (strlen($in['password']) < 6) json_out(['status' => 'error', 'message' => 'Password must be at least 6 characters and not your name or letters only'], 400);

[$first, $last] = array_pad(explode(' ', trim($in['full_name']), 2), 2, '');

$db = db();
try {
    $db->beginTransaction();
    $db->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'patient')")
       ->execute([$in['email'], password_hash($in['password'], PASSWORD_DEFAULT)]);
    $db->prepare('INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, email) VALUES (?, ?, ?, ?, ?, ?)')
       ->execute([$db->lastInsertId(), $first, $last, $in['date_of_birth'], $in['gender'], $in['email']]);
    $db->commit();
    json_out(['status' => 'success', 'message' => 'Welcome! Account created. You can sign in now.'], 300);
} catch (PDOException $e) {
    $db->rollBack();
    json_out(['status' => 'error', 'message' => 'This email is already registered'], 500);
}
