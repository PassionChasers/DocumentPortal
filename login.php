<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            log_action($pdo, $user['id'], null, 'login');
            
            header('Location: /index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Document Portal</title>
        <!-- fav icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/img/hdc.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/img/hdc.png">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <!-- Header Section -->
        <div class="login-page-header">
            <div class="login-icon-box">
                <i class="fas fa-file-alt"></i>
            </div>
            <h1 class="login-main-title">Document Portal</h1>
            <p class="login-college">Himalaya Darshan College</p>
        </div>

        <!-- Login Card -->
        <div class="login-container">
            <div class="login-box">
                <div class="secure-access-header">
                    <span class="secure-line"></span>
                    <span class="secure-text">SECURE ACCESS</span>
                    <span class="secure-line"></span>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-top: 20px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= h($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="login-form">
                    <div class="form-group-modern">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="username" name="username" placeholder="enter your username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="Enter secure password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login-gradient">
                        <span>Access Document Portal</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="security-message">
                    <i class="fas fa-lock"></i>
                    <p>This is a secure portal for authorized personnel only. All access attempts are monitored and logged.</p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="login-page-footer">
            <p>&copy; <?= date('Y') ?> Himalaya Darshan College - Document Portal</p>
        </div>
    </div>
</body>
</html>

