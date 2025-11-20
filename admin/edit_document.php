<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_id = (int)$_POST['document_id'];
    $name = trim($_POST['name'] ?? '');
    $redirect_to = $_POST['redirect_to'] ?? 'documents';
    $redirect_folder_id = isset($_POST['redirect_folder_id']) && $_POST['redirect_folder_id'] !== '' ? (int)$_POST['redirect_folder_id'] : null;
    
    if (empty($name)) {
        $_SESSION['error'] = 'Document name is required';
    } else {
        // Get current folder for redirect
        $stmt = $pdo->prepare('SELECT folder_id FROM documents WHERE id = ?');
        $stmt->execute([$document_id]);
        $doc = $stmt->fetch();
        
        if ($doc) {
            // Update document name
            $stmt = $pdo->prepare('UPDATE documents SET name = ? WHERE id = ?');
            $stmt->execute([$name, $document_id]);
            
            log_action($pdo, $_SESSION['user_id'], $document_id, 'edit_document');
            
            $_SESSION['success'] = 'Document updated successfully';
            
            // Redirect based on where the request came from
            if ($redirect_to === 'documents') {
                $redirect = '/admin/documents.php';
                if ($redirect_folder_id !== null) {
                    $redirect .= '?folder_id=' . $redirect_folder_id;
                } else if ($doc && $doc['folder_id']) {
                    $redirect .= '?folder_id=' . $doc['folder_id'];
                }
            } else {
                $redirect = '/admin/documents.php';
            }
            
            header('Location: ' . $redirect);
            exit;
        }
    }
}

header('Location: /admin/documents.php');
exit;

