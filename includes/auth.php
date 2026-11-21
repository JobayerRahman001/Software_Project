<?php
// Session + small helpers used by every API file and page.
require_once __DIR__ . '/../config/database.php';
session_start();

// Which dashboard each role lands on after login.
const HOME = [
    'admin' => 'admin-dashboard.php',         'doctor'        => 'doctor-dashboard.php',
    'patient' => 'patient-dashboard.php',     'staff'         => 'staff-dashboard.php',
    'financial' => 'financial-dashboard.php', 'datascientist' => 'datascientist-dashboard.php',
    'insurance' => 'insurance-dashboard.php',
];

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

// JSON request body as an array.
function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// For API files: stop with 401 if not logged in (or wrong role).
function require_api_login(array $roles = []): array {
    $u = $_SESSION['user'] ?? null;
    if (!$u) json_out(['status' => 'error', 'message' => 'Please log in'], 401);
    if ($roles && !in_array($u['role'], $roles)) json_out(['status' => 'error', 'message' => 'Not allowed'], 403);
    return $u;
}

// For pages: redirect to the login page if not logged in (or wrong role).
function require_page_login(array $roles = []): array {
    $u = $_SESSION['user'] ?? null;
    if (!$u) { header('Location: ../index.html'); exit; }
    if ($roles && !in_array($u['role'], $roles)) { header('Location: ' . HOME[$u['role']]); exit; }
    return $u;
}
