<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location:/user/dashboard.php');
    exit;
}

$page_title = 'Folder Management';

// Function to get full directory path for a folder
function get_folder_path($pdo, $folder_id) {
    $path = [];
    $current_id = $folder_id;
    
    while ($current_id) {
        $stmt = $pdo->prepare('SELECT id, name, parent_id FROM folders WHERE id = ?');
        $stmt->execute([$current_id]);
        $folder = $stmt->fetch();
        
        if ($folder) {
            array_unshift($path, $folder['name']);
            $current_id = $folder['parent_id'];
        } else {
            break;
        }
    }
    
    return $path;
}

// Get all folders with parent info
$stmt = $pdo->query('
    SELECT f.*, u.username as created_by_name 
    FROM folders f 
    LEFT JOIN users u ON f.created_by = u.id 
    ORDER BY f.name
');
$folders = $stmt->fetchAll();

// Build full paths for each folder
foreach ($folders as &$folder) {
    if ($folder['parent_id']) {
        $folder['path'] = get_folder_path($pdo, $folder['parent_id']);
    } else {
        $folder['path'] = [];
    }
}
unset($folder);

// Get all folders for parent selection
$stmt = $pdo->query('SELECT id, name FROM folders ORDER BY name');
$all_folders = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="page-header page-header--align-right">
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="document.getElementById('createFolderModal').style.display='block'">
            <i class="fas fa-folder-plus"></i> Create Folder
        </button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= h($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= h($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Directory</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($folders)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No folders created yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($folders as $folder): ?>
                    <tr>
                        <td>
                            <i class="fas fa-folder"></i>
                            <?= h($folder['name']) ?>
                        </td>
                        <td>
                            <?php if (empty($folder['path'])): ?>
                                Root
                            <?php else: ?>
                                <span class="directory-path">
                                    Root
                                    <?php foreach ($folder['path'] as $path_folder): ?>
                                        <span class="path-separator">/</span>
                                        <span><?= h($path_folder) ?></span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($folder['created_by_name']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($folder['created_at'])) ?></td>
                        <td>
                            <a href="/admin/documents.php?folder_id=<?= $folder['id'] ?>" class="btn-icon" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn-icon" onclick="editFolder(<?= $folder['id'] ?>, '<?= h($folder['name']) ?>', <?= $folder['parent_id'] ? $folder['parent_id'] : 'null' ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon" onclick="deleteFolder(<?= $folder['id'] ?>)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create Folder Modal -->
<div id="createFolderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Folder</h3>
            <span class="close" onclick="document.getElementById('createFolderModal').style.display='none'">&times;</span>
        </div>
        <form action="create_folder.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="folder_name">Folder Name</label>
                    <input type="text" id="folder_name" name="name" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="parent_folder">Directory (optional)</label>
                    <select id="parent_folder" name="parent_id" class="form-control">
                        <option value="">Root Folder</option>
                        <?php foreach ($all_folders as $folder): ?>
                            <?php 
                            $folder_path = get_folder_path($pdo, $folder['id']);
                            $display_path = !empty($folder_path) ? implode(' / ', array_map('h', $folder_path)) . ' / ' : '';
                            $display_path .= h($folder['name']);
                            ?>
                            <option value="<?= $folder['id'] ?>"><?= $display_path ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Folder will be created in the selected directory</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createFolderModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Folder Modal -->
<div id="editFolderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Folder</h3>
            <span class="close" onclick="document.getElementById('editFolderModal').style.display='none'">&times;</span>
        </div>
        <form action="edit_folder.php" method="POST">
            <div class="modal-body">
                <input type="hidden" id="edit_folder_id" name="folder_id">
                <div class="form-group">
                    <label for="edit_folder_name">Folder Name</label>
                    <input type="text" id="edit_folder_name" name="name" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_parent_folder">Directory (optional)</label>
                    <select id="edit_parent_folder" name="parent_id" class="form-control">
                        <option value="">Root Folder</option>
                        <?php foreach ($all_folders as $folder): ?>
                            <?php 
                            $folder_path = get_folder_path($pdo, $folder['id']);
                            $display_path = !empty($folder_path) ? implode(' / ', array_map('h', $folder_path)) . ' / ' : '';
                            $display_path .= h($folder['name']);
                            ?>
                            <option value="<?= $folder['id'] ?>"><?= $display_path ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editFolderModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function editFolder(id, name, parentId) {
    document.getElementById('edit_folder_id').value = id;
    document.getElementById('edit_folder_name').value = name;
    document.getElementById('edit_parent_folder').value = parentId || '';
    document.getElementById('editFolderModal').style.display = 'block';
}

function deleteFolder(id) {
    if (confirm('Are you sure you want to delete this folder? Documents inside will be moved to root.')) {
        window.location.href = 'delete_folder.php?id=' + id + '&redirect_to=folders';
    }
}

window.onclick = function(event) {
    const modals = ['createFolderModal', 'editFolderModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>

