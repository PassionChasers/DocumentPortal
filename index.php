<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'includes/config.php';
require_login();

// Redirect based on role
if (is_admin()) {
    header('Location: /admin/dashboard.php');
} else {
    header('Location: /user/dashboard.php');
}
exit;

