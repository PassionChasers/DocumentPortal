<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /user/dashboard.php');
    exit;
}

$page_title = 'Admin Dashboard';

// Get statistics
$stats = [];

// Total documents
$stmt = $pdo->query('SELECT COUNT(*) as count FROM documents');
$stats['documents'] = $stmt->fetch()['count'];

// Total folders
$stmt = $pdo->query('SELECT COUNT(*) as count FROM folders');
$stats['folders'] = $stmt->fetch()['count'];

// Total users
$stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
$stats['users'] = $stmt->fetch()['count'];

// Recent uploads (last 10)
$stmt = $pdo->prepare('
    SELECT d.*, u.username as uploaded_by_name 
    FROM documents d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    ORDER BY d.created_at DESC 
    LIMIT 10
');
$stmt->execute();
$recent_uploads = $stmt->fetchAll();

// Recent activity (last 10)
$stmt = $pdo->prepare('
    SELECT l.*, u.username, d.name as document_name 
    FROM logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    LEFT JOIN documents d ON l.document_id = d.id 
    ORDER BY l.timestamp DESC 
    LIMIT 10
');
$stmt->execute();
$recent_activity = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-documents">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['documents']) ?></h3>
                <p>Total Documents</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon stat-icon-folders">
                <i class="fas fa-folder"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['folders']) ?></h3>
                <p>Total Folders</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon stat-icon-users">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['users']) ?></h3>
                <p>Total Users</p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fas fa-upload"></i> Recent Uploads</h2>
            </div>
            <div class="card-body scrollable">
                <?php if (empty($recent_uploads)): ?>
                    <p class="text-muted">No documents uploaded yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_uploads as $doc): ?>
                                <tr>
                                    <td>
                                        <i class="fas <?= get_file_icon($doc['name']) ?>"></i>
                                        <?= h($doc['name']) ?>
                                    </td>
                                    <td><?= h($doc['uploaded_by_name']) ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($doc['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
            </div>
            <div class="card-body scrollable">
                <?php if (empty($recent_activity)): ?>
                    <p class="text-muted">No activity logged yet.</p>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas <?= get_action_icon($activity['action']) ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <p>
                                        <strong><?= h($activity['username']) ?></strong>
                                        <?= h($activity['action']) ?>
                                        <?php if ($activity['document_name']): ?>
                                            <strong><?= h($activity['document_name']) ?></strong>
                                        <?php endif; ?>
                                    </p>
                                    <small><?= date('M d, Y H:i', strtotime($activity['timestamp'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
function get_file_icon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
        'txt' => 'fa-file-alt',
    ];
    return $icons[$ext] ?? 'fa-file';
}

function get_action_icon($action) {
    $icons = [
        'upload' => 'fa-upload',
        'download' => 'fa-download',
        'delete' => 'fa-trash',
        'login' => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'create' => 'fa-plus',
        'edit' => 'fa-edit',
    ];
    return $icons[strtolower($action)] ?? 'fa-circle';
}

include '../includes/footer.php';
?>

