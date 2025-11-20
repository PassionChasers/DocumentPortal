<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location:/index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    // Move all documents in this folder to root
    $stmt = $pdo->prepare('UPDATE documents SET folder_id = NULL WHERE folder_id = ?');
    $stmt->execute([$id]);
    
    // Move all subfolders to root
    $stmt = $pdo->prepare('UPDATE folders SET parent_id = NULL WHERE parent_id = ?');
    $stmt->execute([$id]);
    
    // Delete folder
    $stmt = $pdo->prepare('DELETE FROM folders WHERE id = ?');
    $stmt->execute([$id]);
    
    log_action($pdo, $_SESSION['user_id'], null, 'delete_folder');
    
    $_SESSION['success'] = 'Folder deleted successfully';
}

$redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : 'documents';
$redirect_folder_id = isset($_GET['redirect_folder_id']) ? (int)$_GET['redirect_folder_id'] : null;

$redirect_url = '/admin/' . $redirect_to . '.php';

if ($redirect_to === 'documents' && $redirect_folder_id) {
    $redirect_url .= '?folder_id=' . $redirect_folder_id;
}

header('Location: ' . $redirect_url);
exit;

