<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id && $id != $_SESSION['user_id']) {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    
    log_action($pdo, $_SESSION['user_id'], null, 'delete_user');
    
    $_SESSION['success'] = 'User deleted successfully';
} else {
    $_SESSION['error'] = 'Cannot delete your own account';
}

header('Location: /admin/users.php');
exit;

