<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folder_id = (int)$_POST['folder_id'];
    $name = trim($_POST['name'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $redirect_to = $_POST['redirect_to'] ?? 'folders';
    $redirect_folder_id = isset($_POST['redirect_folder_id']) && $_POST['redirect_folder_id'] !== '' ? (int)$_POST['redirect_folder_id'] : null;
    
    // Prevent setting parent to itself or its children
    if ($parent_id == $folder_id) {
        $_SESSION['error'] = 'A folder cannot be its own parent';
    } else if (empty($name)) {
        $_SESSION['error'] = 'Folder name is required';
    } else {
        // Check if folder name already exists in same parent (excluding current folder)
        $stmt = $pdo->prepare('SELECT id FROM folders WHERE name = ? AND parent_id ' . ($parent_id ? '= ?' : 'IS NULL') . ' AND id != ?');
        if ($parent_id) {
            $stmt->execute([$name, $parent_id, $folder_id]);
        } else {
            $stmt->execute([$name, $folder_id]);
        }
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'A folder with this name already exists in this location';
        } else {
            $stmt = $pdo->prepare('UPDATE folders SET name = ?, parent_id = ? WHERE id = ?');
            $stmt->execute([$name, $parent_id, $folder_id]);
            
            log_action($pdo, $_SESSION['user_id'], null, 'edit_folder');
            
            $_SESSION['success'] = 'Folder updated successfully';
        }
    }
}

// Redirect based on where the request came from
if ($redirect_to === 'documents') {
    $redirect =  '/admin/documents.php';
    if ($redirect_folder_id !== null) {
        $redirect .= '?folder_id=' . $redirect_folder_id;
    }
} else {
    $redirect =  '/admin/folders.php';
}

header('Location: ' . $redirect);
exit;

