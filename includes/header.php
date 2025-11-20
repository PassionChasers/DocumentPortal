<?php
if (!isset($page_title)) {
    $page_title = 'Document Portal';
}

function get_page_icon($page_title) {
    $title_lower = strtolower($page_title);
    $icons = [
        'dashboard' => 'fa-chart-line',
        'admin dashboard' => 'fa-chart-line',
        'document management' => 'fa-file-alt',
        'documents' => 'fa-file-alt',
        'folder management' => 'fa-folder-open',
        'folders' => 'fa-folder-open',
        'user management' => 'fa-users-cog',
        'users' => 'fa-users-cog',
        'activity logs' => 'fa-history',
        'logs' => 'fa-history',
        'document portal' => 'fa-file-alt',
    ];
    
    foreach ($icons as $key => $icon) {
        if (strpos($title_lower, $key) !== false) {
            return $icon;
        }
    }
    
    return 'fa-file-alt'; // default icon
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> - Document Portal</title>
        <!-- fav icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/img/hdc.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/img/hdc.png">

    <link rel="stylesheet" href=" /assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-file-alt"></i>
                <h2>Document Portal</h2>
            </div>
            <nav class="sidebar-nav">
                <?php if (is_admin()): ?>
                    <a href=" /admin/dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href=" /admin/documents.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : '' ?>">
                        <i class="fas fa-file"></i> Documents
                    </a>
                    <a href=" /admin/folders.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'folders.php' ? 'active' : '' ?>">
                        <i class="fas fa-folder"></i> Folders
                    </a>
                    <a href=" /admin/users.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a href=" /admin/logs.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : '' ?>">
                        <i class="fas fa-history"></i> Activity Logs
                    </a>
                <?php else: ?>
                    <a href=" /user/dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href=" /user/documents.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : '' ?>">
                        <i class="fas fa-file"></i> Documents
                    </a>
                <?php endif; ?>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="top-nav">
                <div class="nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>
                        <i class="fas <?= get_page_icon($page_title) ?>"></i>
                        <?= h($page_title) ?>
                    </h1>
                </div>
                <div class="nav-right">
                    <span class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <?= h($_SESSION['username']) ?>
                        <?php if (is_admin()): ?>
                            <span class="badge badge-admin">Admin</span>
                        <?php endif; ?>
                    </span>
                    <a href=" /logout.php" class="btn btn-sm btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </header>
            
            <div class="content-wrapper">

