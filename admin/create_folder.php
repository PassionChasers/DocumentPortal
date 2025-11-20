<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $redirect_to = $_POST['redirect_to'] ?? 'folders';
    $redirect_folder_id = isset($_POST['redirect_folder_id']) && $_POST['redirect_folder_id'] !== '' ? (int)$_POST['redirect_folder_id'] : null;
    
    if (empty($name)) {
        $_SESSION['error'] = 'Folder name is required';
    } else {
        // Check if folder name already exists in same parent
        $stmt = $pdo->prepare('SELECT id FROM folders WHERE name = ? AND parent_id ' . ($parent_id ? '= ?' : 'IS NULL'));
        if ($parent_id) {
            $stmt->execute([$name, $parent_id]);
        } else {
            $stmt->execute([$name]);
        }
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'A folder with this name already exists in this location';
        } else {
            $stmt = $pdo->prepare('INSERT INTO folders (name, parent_id, created_by) VALUES (?, ?, ?)');
            $stmt->execute([$name, $parent_id, $_SESSION['user_id']]);
            
            log_action($pdo, $_SESSION['user_id'], null, 'create_folder');
            
            $_SESSION['success'] = 'Folder created successfully';
        }
    }
}

// Redirect based on where the request came from
if ($redirect_to === 'documents') {
    $redirect = '/admin/documents.php';
    if ($redirect_folder_id) {
        $redirect .= '?folder_id=' . $redirect_folder_id;
    }
} else {
    $redirect ='/admin/folders.php';
}

header('Location: ' . $redirect);
exit;

