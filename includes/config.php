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
    // Ensure logs table has document_name column (add if missing)
    try {
        $col = $pdo->query("SHOW COLUMNS FROM logs LIKE 'document_name'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE logs ADD COLUMN document_name VARCHAR(255) DEFAULT NULL AFTER document_id");
        }
    } catch (Exception $e) {
        // ignore migration errors
    }

    // If a document_id is provided, try to resolve its current name (store snapshot)
    $doc_name = null;
    if ($document_id) {
        try {
            $s = $pdo->prepare('SELECT name FROM documents WHERE id = ?');
            $s->execute([$document_id]);
            $r = $s->fetch();
            if ($r) $doc_name = $r['name'];
        } catch (Exception $e) {
            // ignore
        }
    }

    $stmt = $pdo->prepare('INSERT INTO logs (user_id, document_id, document_name, action) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $document_id, $doc_name, $action]);
}

// Format a datetime string to Nepal timezone (Asia/Kathmandu)
function format_nepal($datetime_str, $format = 'M d, Y H:i:s') {
    if (!$datetime_str) return '';
    try {
        $dt = new DateTime($datetime_str);
        $dt->setTimezone(new DateTimeZone('Asia/Kathmandu'));
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime_str;
    }
}

// Ensure uploads directory exists
$upload_root = __DIR__ . '/../uploads';
if (!is_dir($upload_root)) {
    mkdir($upload_root, 0755, true);
}

?>