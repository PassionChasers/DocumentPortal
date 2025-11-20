<?php
// includes/config.php

session_start();

// Database settings - EDIT THESE for your environment
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'u423560789_documentportal');
define('DB_USER', 'u423560789_documentportal');
define('DB_PASS', 'PassionChasers@321$$');

// Base path / URL
// define('BASE_PATH', '/DocumentPortal'); // adjust if placed elsewhere

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function log_action($pdo, $user_id, $document_id, $action) {
    $stmt = $pdo->prepare('INSERT INTO logs (user_id, document_id, action) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $document_id, $action]);
}

// Ensure uploads directory exists
$upload_root = __DIR__ . '/../uploads';
if (!is_dir($upload_root)) {
    mkdir($upload_root, 0755, true);
}

?>