<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location:/index.php');
    exit;
}

$allowed_types = ['application/pdf', 'application/msword', 
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'text/plain'];

$max_size = 50 * 1024 * 1024; // 50MB

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $folder_id = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $_SESSION['error'] = 'File type not allowed. Allowed types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, TXT';
            header('Location:/admin/documents.php' . ($folder_id ? '?folder_id=' . $folder_id : ''));
            exit;
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            $_SESSION['error'] = 'File size exceeds maximum allowed size (50MB)';
            header('Location:/admin/documents.php' . ($folder_id ? '?folder_id=' . $folder_id : ''));
            exit;
        }
        
        // Check if a document with the same name already exists in the target folder
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM documents WHERE name = ? AND folder_id ' . ($folder_id ? '= ?' : 'IS NULL'));
        if ($folder_id) {
            $stmt->execute([$file['name'], $folder_id]);
        } else {
            $stmt->execute([$file['name']]);
        }
        
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'Document "' . h($file['name']) . '" already exists in this folder.';
            header('Location:/admin/documents.php' . ($folder_id ? '?folder_id=' . $folder_id : ''));
            exit;
        }

        // Create upload directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $file_path = $upload_dir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Save to database
            $stmt = $pdo->prepare('INSERT INTO documents (folder_id, name, file_path, uploaded_by) VALUES (?, ?, ?, ?)');
            $stmt->execute([$folder_id, $file['name'], $filename, $_SESSION['user_id']]);
            $document_id = $pdo->lastInsertId();
            
            log_action($pdo, $_SESSION['user_id'], $document_id, 'upload');
            
            $_SESSION['success'] = 'Document uploaded successfully';
        } else {
            $_SESSION['error'] = 'Failed to upload file';
        }
    } else {
        $_SESSION['error'] = 'Upload error: ' . $file['error'];
    }
}

$redirect ='/admin/documents.php';
if (isset($_POST['folder_id']) && !empty($_POST['folder_id'])) {
    $redirect .= '?folder_id=' . (int)$_POST['folder_id'];
}
header('Location: ' . $redirect);
exit;

