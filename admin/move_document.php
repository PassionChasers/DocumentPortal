<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /index.php');
    exit;
}

$document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
$folder_id = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' ? (int)$_POST['folder_id'] : null;

if ($document_id) {
    // Get current folder for redirect
    $stmt = $pdo->prepare('SELECT folder_id FROM documents WHERE id = ?');
    $stmt->execute([$document_id]);
    $doc = $stmt->fetch();
    
    // Update document folder
    $stmt = $pdo->prepare('UPDATE documents SET folder_id = ? WHERE id = ?');
    $stmt->execute([$folder_id, $document_id]);
    
    log_action($pdo, $_SESSION['user_id'], $document_id, 'move');
    
    $_SESSION['success'] = 'Document moved successfully';
    
    $redirect = '/admin/documents.php';
    if ($doc && $doc['folder_id']) {
        $redirect .= '?folder_id=' . $doc['folder_id'];
    }
    header('Location: ' . $redirect);
    exit;
}

header('Location: /admin/documents.php');
exit;

