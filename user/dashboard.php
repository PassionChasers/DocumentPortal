<?php
require_once '../includes/config.php';
require_login();

$page_title = 'Dashboard';

// Get statistics
$stats = [];

// Total documents
$stmt = $pdo->query('SELECT COUNT(*) as count FROM documents');
$stats['documents'] = $stmt->fetch()['count'];

// Total folders
$stmt = $pdo->query('SELECT COUNT(*) as count FROM folders');
$stats['folders'] = $stmt->fetch()['count'];


// Get recent documents (last 10)
$stmt = $pdo->prepare('
    SELECT d.*, u.username as uploaded_by_name, f.name as folder_name
    FROM documents d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    LEFT JOIN folders f ON d.folder_id = f.id
    ORDER BY d.created_at DESC 
    LIMIT 10
');
$stmt->execute();
$recent_documents = $stmt->fetchAll();

// Get recent folders (last 5)
$stmt = $pdo->prepare('
    SELECT f.*, u.username as created_by_name
    FROM folders f
    LEFT JOIN users u ON f.created_by = u.id
    ORDER BY f.created_at DESC
    LIMIT 5
');
$stmt->execute();
$recent_folders = $stmt->fetchAll();

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
    </div>
    
    <div class="dashboard-grid">
        <!-- Recent Documents -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fas fa-file"></i> Recent Documents</h2>
                <a href="/user/documents.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-body scrollable">
                <?php if (empty($recent_documents)): ?>
                    <div class="empty-state-small">
                        <i class="fas fa-file-alt"></i>
                        <p class="text-muted">No documents available yet.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Folder</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_documents as $doc): ?>
                                <tr>
                                    <td>
                                        <i class="fas <?= get_file_icon($doc['name']) ?>"></i>
                                        <span class="document-name"><?= h($doc['name']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($doc['folder_name']): ?>
                                            <i class="fas fa-folder"></i> <?= h($doc['folder_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Root</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($doc['uploaded_by_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($doc['created_at'])) ?></td>
                                    <td>
                                        <a href="/view.php?id=<?= $doc['id'] ?>" class="btn-icon" title="Preview">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/download.php?id=<?= $doc['id'] ?>" class="btn-icon" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Folders -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fas fa-folder"></i> Recent Folders</h2>
                <a href="/user/documents.php" class="btn btn-sm btn-outline">Browse All</a>
            </div>
            <div class="card-body scrollable">
                <?php if (empty($recent_folders)): ?>
                    <div class="empty-state-small">
                        <i class="fas fa-folder-open"></i>
                        <p class="text-muted">No folders created yet.</p>
                    </div>
                <?php else: ?>
                    <div class="folders-list">
                        <?php foreach ($recent_folders as $folder): ?>
                            <div class="folder-item-small">
                                <div class="folder-icon-small">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <div class="folder-info-small">
                                    <a href="/user/documents.php?folder_id=<?= $folder['id'] ?>" class="folder-link">
                                        <?= h($folder['name']) ?>
                                    </a>
                                    <small class="text-muted">
                                        Created by <?= h($folder['created_by_name']) ?> • 
                                        <?= date('M d, Y', strtotime($folder['created_at'])) ?>
                                    </small>
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


include '../includes/footer.php';
?>

