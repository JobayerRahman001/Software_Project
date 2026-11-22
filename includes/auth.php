<?php
// Session + small helpers used by every API file and page.
// Lives in: <project>/includes/auth.php
require_once __DIR__ . '/../config/database.php';

// ---- Settings -------------------------------------------------------
const LOGIN_PAGE   = 'api/auth/login.php';   // sign-in page, relative to the project root
const IDLE_SECONDS = 1800;                   // sign people out after 30 minutes of no activity (0 = never)

// ---- Session --------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),   // cookie only over HTTPS when the site uses it
        'httponly' => true,                        // not readable from JavaScript
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Idle timeout: drop the login if the person has been away too long.
if (IDLE_SECONDS > 0 && isset($_SESSION['user'])) {
    if (time() - ($_SESSION['last_seen'] ?? time()) > IDLE_SECONDS) {
        unset($_SESSION['user']);
    }
}
$_SESSION['last_seen'] = time();

// Which dashboard each role lands on after login.
const HOME = [
    'admin' => 'admin-dashboard.php',         'doctor'        => 'doctor-dashboard.php',
    'patient' => 'patient-dashboard.php',     'staff'         => 'staff-dashboard.php',
    'financial' => 'financial-dashboard.php', 'datascientist' => 'datascientist-dashboard.php',
    'insurance' => 'insurance-dashboard.php',
];

// ---- Helpers --------------------------------------------------------

// URL path to a file in the project, from any folder depth.
// Example: app_url('dashboards/admin-dashboard.php') -> /hospital/dashboards/admin-dashboard.php
function app_url(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
        $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
        $base = ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) ? rtrim(substr($appRoot, strlen($docRoot)), '/') : '';
    }
    return $base . '/' . ltrim($path, '/');
}

// True when a person opened the URL in a browser (not a fetch/API call).
function wants_html(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'GET'
        && !isset($_GET['format'])
        && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html');
}

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}

// JSON request body as an array.
function body(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
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
    header('Cache-Control: no-store');   // so the Back button cannot show a page after sign-out
    $u = $_SESSION['user'] ?? null;
    if (!$u) { header('Location: ' . app_url(LOGIN_PAGE)); exit; }
    if ($roles && !in_array($u['role'], $roles)) {
        header('Location: ' . app_url('dashboards/' . (HOME[$u['role']] ?? '')));
        exit;
    }
    return $u;
}