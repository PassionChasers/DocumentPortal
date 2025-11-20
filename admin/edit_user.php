<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if (empty($username)) {
        $_SESSION['error'] = 'Username is required';
    } else if (!in_array($role, ['admin', 'user'])) {
        $_SESSION['error'] = 'Invalid role';
    } else {
        // Check if username already exists (excluding current user)
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $user_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Username already exists';
        } else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?');
                $stmt->execute([$username, $hashed_password, $role, $user_id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, role = ? WHERE id = ?');
                $stmt->execute([$username, $role, $user_id]);
            }
            
            log_action($pdo, $_SESSION['user_id'], null, 'edit_user');
            
            $_SESSION['success'] = 'User updated successfully';
        }
    }
}

header('Location: /admin/users.php');
exit;

