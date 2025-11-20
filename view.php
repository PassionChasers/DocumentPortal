<?php
require_once 'includes/config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: /index.php');
    exit;
}

// Get document
$stmt = $pdo->prepare('SELECT * FROM documents WHERE id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    header('Location: /index.php');
    exit;
}

$file_path = __DIR__ . '/uploads/' . $doc['file_path'];
if (!file_exists($file_path)) {
    die('File not found');
}

// Log view action
log_action($pdo, $_SESSION['user_id'], $id, 'view');

$ext = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
$mime_type = mime_content_type($file_path);

$page_title = 'Preview: ' . $doc['name'];

include 'includes/header.php';
?>

<div class="viewer-container">
    <div class="viewer-header">
        <div>
            <h2><i class="fas <?= get_file_icon($doc['name']) ?>"></i> <?= h($doc['name']) ?></h2>
            <p class="text-muted">Uploaded on <?= date('M d, Y H:i', strtotime($doc['created_at'])) ?></p>
        </div>
        <div>
            <a href=" /download.php?id=<?= $doc['id'] ?>" class="btn btn-primary">
                <i class="fas fa-download"></i> Download
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="viewer-content">
        <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
            <div class="image-viewer">
                <img src=" /uploads/<?= h($doc['file_path']) ?>" alt="<?= h($doc['name']) ?>" class="preview-image">
            </div>
        <?php elseif ($ext === 'pdf'): ?>
            <div class="pdf-viewer">
                <iframe src=" /uploads/<?= h($doc['file_path']) ?>#toolbar=1" class="pdf-iframe"></iframe>
            </div>
        <?php elseif ($ext === 'txt'): ?>
            <div class="text-viewer">
                <pre><?= h(file_get_contents($file_path)) ?></pre>
            </div>
        <?php else: ?>
            <div class="unsupported-viewer">
                <i class="fas fa-file fa-5x"></i>
                <p>Preview not available for this file type.</p>
                <a href=" /download.php?id=<?= $doc['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download to view
                </a>
            </div>
        <?php endif; ?>
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

include 'includes/footer.php';
?>

