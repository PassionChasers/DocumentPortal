<?php
//Error Reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    // Get document info
    $stmt = $pdo->prepare('SELECT file_path, folder_id FROM documents WHERE id = ?');
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        // Delete file
        $file_path = __DIR__ . '/../uploads/' . $doc['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        log_action($pdo, $_SESSION['user_id'], $id, 'delete');
        
        // Delete from database
        $stmt = $pdo->prepare('DELETE FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        

        
        $_SESSION['success'] = 'Document deleted successfully';
    }
}

$redirect = '/admin/documents.php';
if ($doc && $doc['folder_id']) {
    $redirect .= '?folder_id=' . $doc['folder_id'];
}
header('Location: ' . $redirect);
exit;

