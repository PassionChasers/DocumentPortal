<?php
require_once 'includes/config.php';

if (is_logged_in()) {
    log_action($pdo, $_SESSION['user_id'], null, 'logout');
}

session_destroy();
header('Location: /login.php');
exit;

