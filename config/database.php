<?php
// Database settings - change these if your MySQL setup is different.
const DB_HOST = 'localhost';
const DB_NAME = 'medilink';
const DB_USER = 'root';
const DB_PASS = '';

// One shared PDO connection for the whole request.
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
