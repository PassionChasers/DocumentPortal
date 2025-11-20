<?php
require_once 'includes/config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: /index.php');
    exit;
}

// Get document
$stmt = $pdo->prepare('SELECT * FROM documents WHERE id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    header('Location: /index.php');
    exit;
}

$file_path = __DIR__ . '/uploads/' . $doc['file_path'];
if (!file_exists($file_path)) {
    die('File not found');
}

// Log download action
log_action($pdo, $_SESSION['user_id'], $id, 'download');

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $doc['name'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file
readfile($file_path);
exit;

