<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Username and password are required';
    } else if (!in_array($role, ['admin', 'user'])) {
        $_SESSION['error'] = 'Invalid role';
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Username already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
            $stmt->execute([$username, $hashed_password, $role]);
            
            log_action($pdo, $_SESSION['user_id'], null, 'create_user');
            
            $_SESSION['success'] = 'User created successfully';
        }
    }
}

header('Location: /admin/users.php');
exit;

