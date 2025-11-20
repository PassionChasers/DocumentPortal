<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /user/dashboard.php');
    exit;
}

$page_title = 'Document Management';

// Get current folder
$current_folder_id = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;

// Get sort parameter
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$sort_parts = explode('_', $sort);
$sort_field = $sort_parts[0] ?? 'name';
$sort_order = isset($sort_parts[1]) && strtoupper($sort_parts[1]) === 'DESC' ? 'DESC' : 'ASC';

// Handle folder navigation
$breadcrumbs = [];
if ($current_folder_id) {
    $folder_id = $current_folder_id;
    while ($folder_id) {
        $stmt = $pdo->prepare('SELECT id, name, parent_id FROM folders WHERE id = ?');
        $stmt->execute([$folder_id]);
        $folder = $stmt->fetch();
        if ($folder) {
            array_unshift($breadcrumbs, $folder);
            $folder_id = $folder['parent_id'];
        } else {
            break;
        }
    }
}

// Build ORDER BY clause based on sort parameter
$order_by = 'name';
if ($sort_field === 'date') {
    $order_by = 'created_at';
} else {
    $order_by = 'name';
}

// Get folders in current directory
$stmt = $pdo->prepare('SELECT * FROM folders WHERE parent_id ' . ($current_folder_id ? '= ?' : 'IS NULL') . ' ORDER BY ' . $order_by . ' ' . $sort_order);
if ($current_folder_id) {
    $stmt->execute([$current_folder_id]);
} else {
    $stmt->execute();
}
$folders = $stmt->fetchAll();

// Get documents in current directory
$stmt = $pdo->prepare('
    SELECT d.*, u.username as uploaded_by_name 
    FROM documents d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    WHERE d.folder_id ' . ($current_folder_id ? '= ?' : 'IS NULL') . ' 
    ORDER BY d.' . $order_by . ' ' . $sort_order
);
if ($current_folder_id) {
    $stmt->execute([$current_folder_id]);
} else {
    $stmt->execute();
}
$documents = $stmt->fetchAll();

// Get all folders for move dropdown
$stmt = $pdo->query('SELECT id, name FROM folders ORDER BY name');
$all_folders = $stmt->fetchAll();

include '../includes/header.php';
?>
<style>
/* Ensure the 3-dot menu dropdown appears above other items and doesn't get clipped/blink */
.document-item.menu-open { z-index: 9999; position: relative; }
.menu-dropdown { z-index: 10000 !important; }
</style>
<style>
.search-box { display: flex; gap: 6px; align-items: center; }
.search-box .form-control { height: 38px; box-sizing: border-box; }
#clearSearchBtn { height: 38px; padding: 0 10px; line-height: 38px; display: none; }
</style>

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

<div class="page-header page-header--documents">
    <div class="page-header-info">
        <?php
        // Get parent folder ID for back button
        $parent_folder_id = null;
        if ($current_folder_id) {
            // Get the parent_id of the current folder
            $stmt = $pdo->prepare('SELECT parent_id FROM folders WHERE id = ?');
            $stmt->execute([$current_folder_id]);
            $folder_data = $stmt->fetch();
            if ($folder_data) {
                $parent_folder_id = $folder_data['parent_id'];
            }
        }
        ?>
        <?php if (!empty($breadcrumbs)): ?>
            <nav class="breadcrumb">
                <a href="/admin/documents.php">Root</a>
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <span>/</span>
                    <a href="/admin/documents.php?folder_id=<?= $crumb['id'] ?>"><?= h($crumb['name']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
    <div class="page-header-controls">
        <?php if ($current_folder_id): ?>
            <a href="/admin/documents.php<?= $parent_folder_id ? '?folder_id=' . $parent_folder_id : '' ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        <?php endif; ?>
        <form method="GET" action="" class="search-form" onsubmit="return false;">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search files and folders..." class="form-control" autocomplete="off">
                <button type="button" id="clearSearchBtn" class="btn btn-secondary" style="display: none;" onclick="clearSearch()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </form>
        <div class="sort-control" style="flex-shrink: 0;">
            <label for="sortSelect"><i class="fas fa-sort"></i> Sort:</label>
            <select id="sortSelect" onchange="applySort(this.value)">
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Date Modified (Oldest)</option>
                <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Date Modified (Newest)</option>
            </select>
        </div>
        <div class="view-toggle" style="flex-shrink: 0;">
            <button type="button" class="view-toggle-btn active" id="gridViewBtn" onclick="switchView('grid')" title="Grid View">
                <i class="fas fa-th"></i>
            </button>
            <button type="button" class="view-toggle-btn" id="listViewBtn" onclick="switchView('list')" title="List View">
                <i class="fas fa-list"></i>
            </button>
        </div>
        <button class="btn btn-secondary" onclick="document.getElementById('createFolderModal').style.display='block'">
            <i class="fas fa-folder-plus"></i> Create Folder
        </button>
        <button class="btn btn-primary" onclick="document.getElementById('uploadModal').style.display='block'">
            <i class="fas fa-upload"></i> Upload Document
        </button>
    </div>
</div>

<div class="documents-grid" id="documentsGrid">
    <!-- Folders -->
    <?php foreach ($folders as $folder): ?>
        <div class="document-item folder-item" onclick="window.location.href='?folder_id=<?= $folder['id'] ?>'" style="cursor: pointer;">
            <div class="document-icon folder-icon">
                <i class="fas fa-folder"></i>
            </div>
            <div class="document-info">
                <h4><?= h($folder['name']) ?></h4>
                <p>Folder</p>
            </div>
            <div class="document-menu" onclick="event.stopPropagation();">
                <button class="menu-toggle" type="button" onclick="event.stopPropagation(); toggleMenu(event, 'folder-<?= $folder['id'] ?>')">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="menu-dropdown" id="menu-folder-<?= $folder['id'] ?>" onclick="event.stopPropagation();">
                    <a href="?folder_id=<?= $folder['id'] ?>" class="menu-item">
                        <i class="fas fa-folder-open"></i> Open
                    </a>
                    <button type="button" class="menu-item" onclick="showEditFolderModal(<?= $folder['id'] ?>, '<?= h($folder['name']) ?>', <?= $folder['parent_id'] ? $folder['parent_id'] : 'null' ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="menu-item" onclick="deleteFolder(<?= $folder['id'] ?>, <?= $current_folder_id ?? 'null' ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Documents -->
    <?php foreach ($documents as $doc): ?>
        <div class="document-item" onclick="window.location.href='/view.php?id=<?= $doc['id'] ?>'" style="cursor: pointer;">
            <div class="document-icon">
                <i class="fas <?= get_file_icon($doc['name']) ?>"></i>
            </div>
            <div class="document-info">
                <h4><?= h($doc['name']) ?></h4>
                <p>Uploaded by <?= h($doc['uploaded_by_name']) ?></p>
                <small><?= date('M d, Y', strtotime($doc['created_at'])) ?></small>
            </div>
            <div class="document-menu" onclick="event.stopPropagation();">
                <button class="menu-toggle" type="button" onclick="event.stopPropagation(); toggleMenu(event, 'doc-<?= $doc['id'] ?>')">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="menu-dropdown" id="menu-doc-<?= $doc['id'] ?>" onclick="event.stopPropagation();">
                    <a href="/view.php?id=<?= $doc['id'] ?>" class="menu-item">
                        <i class="fas fa-eye"></i> Preview
                    </a>
                    <a href="/download.php?id=<?= $doc['id'] ?>" class="menu-item">
                        <i class="fas fa-download"></i> Download
                    </a>
                    <button type="button" class="menu-item" onclick="showEditDocumentModal(<?= $doc['id'] ?>, '<?= h($doc['name']) ?>')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="menu-item" onclick="showMoveModal(<?= $doc['id'] ?>, '<?= h($doc['name']) ?>')">
                        <i class="fas fa-folder-open"></i> Move
                    </button>
                    <button type="button" class="menu-item" onclick="deleteDocument(<?= $doc['id'] ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($folders) && empty($documents)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <p>This folder is empty</p>
        </div>
    <?php endif; ?>
</div>

<!-- Create Folder Modal -->
<div id="createFolderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Folder</h3>
            <span class="close" onclick="document.getElementById('createFolderModal').style.display='none'">&times;</span>
        </div>
        <form action="create_folder.php" method="POST">
            <input type="hidden" name="redirect_to" value="documents">
            <input type="hidden" name="redirect_folder_id" value="<?= $current_folder_id ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label for="folder_name">Folder Name</label>
                    <input type="text" id="folder_name" name="name" required class="form-control" placeholder="Enter folder name">
                </div>
                <div class="form-group">
                    <label for="parent_folder">Parent Folder</label>
                    <select id="parent_folder" name="parent_id" class="form-control">
                        <option value="" <?= !$current_folder_id ? 'selected' : '' ?>>Root Folder</option>
                        <?php foreach ($all_folders as $folder): ?>
                            <option value="<?= $folder['id'] ?>" <?= $current_folder_id == $folder['id'] ? 'selected' : '' ?>>
                                <?= h($folder['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Folder will be created in the selected parent folder</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createFolderModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Upload Document</h3>
            <span class="close" onclick="document.getElementById('uploadModal').style.display='none'">&times;</span>
        </div>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="folder_id" value="<?= $current_folder_id ?>">
                <div class="form-group">
                    <label for="file">Select File</label>
                    <input type="file" id="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('uploadModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload</button>
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
            <input type="hidden" name="redirect_to" value="documents">
            <input type="hidden" name="redirect_folder_id" value="<?= $current_folder_id ?>">
            <div class="modal-body">
                <input type="hidden" id="edit_folder_id" name="folder_id">
                <div class="form-group">
                    <label for="edit_folder_name">Folder Name</label>
                    <input type="text" id="edit_folder_name" name="name" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_parent_folder">Parent Folder (optional)</label>
                    <select id="edit_parent_folder" name="parent_id" class="form-control">
                        <option value="">Root Folder</option>
                        <?php foreach ($all_folders as $folder): ?>
                            <option value="<?= $folder['id'] ?>"><?= h($folder['name']) ?></option>
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

<!-- Edit Document Modal -->
<div id="editDocumentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Document</h3>
            <span class="close" onclick="document.getElementById('editDocumentModal').style.display='none'">&times;</span>
        </div>
        <form action="edit_document.php" method="POST">
            <input type="hidden" name="redirect_to" value="documents">
            <input type="hidden" name="redirect_folder_id" value="<?= $current_folder_id ?>">
            <div class="modal-body">
                <input type="hidden" id="edit_doc_id" name="document_id">
                <div class="form-group">
                    <label for="edit_doc_name">Document Name</label>
                    <input type="text" id="edit_doc_name" name="name" required class="form-control">
                    <small class="text-muted">Note: This only changes the display name, not the actual file.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editDocumentModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Move Modal -->
<div id="moveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Move Document</h3>
            <span class="close" onclick="document.getElementById('moveModal').style.display='none'">&times;</span>
        </div>
        <form action="move_document.php" method="POST">
            <div class="modal-body">
                <input type="hidden" id="move_doc_id" name="document_id">
                <div class="form-group">
                    <label>Document: <span id="move_doc_name"></span></label>
                </div>
                <div class="form-group">
                    <label for="target_folder">Move to Folder</label>
                    <select id="target_folder" name="folder_id" class="form-control">
                        <option value="">Root Folder</option>
                        <?php foreach ($all_folders as $folder): ?>
                            <option value="<?= $folder['id'] ?>"><?= h($folder['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('moveModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Move</button>
            </div>
        </form>
    </div>
</div>

<script>
// Store original content for restore (not relied on for full restore anymore)
const originalGridContent = document.getElementById('documentsGrid').innerHTML;
let searchTimeout;

// Real-time search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.trim();
    const clearBtn = document.getElementById('clearSearchBtn');
    
    if (searchTerm.length === 0) {
        clearBtn.style.display = 'none';
        // Reload page to ensure full, server-rendered list is shown (avoids stale snapshot issues)
        window.location.reload();
        return;
    }
    
    clearBtn.style.display = 'inline-block';
    
    // Debounce search requests
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch(searchTerm);
    }, 300);
});

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearchBtn').style.display = 'none';
    // Reload page to show full list
    window.location.reload();
}

function restoreOriginalContent() {
    // Fallback: reload to restore server-rendered content
    window.location.reload();
}

function performSearch(searchTerm) {
    const sortSelect = document.getElementById('sortSelect');
    const sortValue = sortSelect ? sortSelect.value : 'name_asc';
    fetch('../api/search.php?q=' + encodeURIComponent(searchTerm) + '&sort=' + encodeURIComponent(sortValue))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSearchResults(data.folders, data.documents);
            } else {
                console.error('Search error:', data.message);
            }
        })
        .catch(error => {
            console.error('Search request failed:', error);
        });
}

function renderSearchResults(folders, documents) {
    const grid = document.getElementById('documentsGrid');
    let html = '';
    
    // Render folders
    folders.forEach(folder => {
        const folderName = escapeHtml(folder.name);
        const parentId = folder.parent_id ? folder.parent_id : 'null';
        html += `
            <div class="document-item folder-item" onclick="window.location.href='?folder_id=${folder.id}'" style="cursor: pointer;">
                <div class="document-icon folder-icon">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="document-info">
                    <h4>${folderName}</h4>
                    <p>Folder</p>
                </div>
                <div class="document-menu" onclick="event.stopPropagation();">
                    <button class="menu-toggle" type="button" onclick="event.stopPropagation(); toggleMenu(event, 'folder-${folder.id}')">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="menu-dropdown" id="menu-folder-${folder.id}" onclick="event.stopPropagation();">
                        <a href="?folder_id=${folder.id}" class="menu-item">
                            <i class="fas fa-folder-open"></i> Open
                        </a>
                        <button type="button" class="menu-item" onclick="showEditFolderModal(${folder.id}, '${folderName}', ${parentId})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="menu-item" onclick="deleteFolder(${folder.id}, ${folder.parent_id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Render documents
    documents.forEach(doc => {
        const docName = escapeHtml(doc.name);
        const uploadedBy = escapeHtml(doc.uploaded_by_name || 'Unknown');
        const date = formatDate(doc.created_at);
        const fileIcon = getFileIcon(doc.name);
        html += `
            <div class="document-item" onclick="window.location.href='/view.php?id=${doc.id}'" style="cursor: pointer;">
                <div class="document-icon">
                    <i class="fas ${fileIcon}"></i>
                </div>
                <div class="document-info">
                    <h4>${docName}</h4>
                    <p>Uploaded by ${uploadedBy}</p>
                    <small>${date}</small>
                </div>
                <div class="document-menu" onclick="event.stopPropagation();">
                    <button class="menu-toggle" type="button" onclick="event.stopPropagation(); toggleMenu(event, 'doc-${doc.id}')">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="menu-dropdown" id="menu-doc-${doc.id}" onclick="event.stopPropagation();">
                        <a href="/view.php?id=${doc.id}" class="menu-item">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                        <a href="/download.php?id=${doc.id}" class="menu-item">
                            <i class="fas fa-download"></i> Download
                        </a>
                        <button type="button" class="menu-item" onclick="showEditDocumentModal(${doc.id}, '${docName}')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="menu-item" onclick="showMoveModal(${doc.id}, '${docName}')">
                            <i class="fas fa-folder-open"></i> Move
                        </button>
                        <button type="button" class="menu-item" onclick="deleteDocument(${doc.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    if (html === '') {
        html = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <p>No files or folders found matching your search.</p>
            </div>
        `;
    }
    
    grid.innerHTML = html;
    attachMenuListeners();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML.replace(/'/g, "&#039;");
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
}

function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
        'pdf': 'fa-file-pdf',
        'doc': 'fa-file-word',
        'docx': 'fa-file-word',
        'xls': 'fa-file-excel',
        'xlsx': 'fa-file-excel',
        'jpg': 'fa-file-image',
        'jpeg': 'fa-file-image',
        'png': 'fa-file-image',
        'gif': 'fa-file-image',
        'txt': 'fa-file-alt'
    };
    return icons[ext] || 'fa-file';
}

function attachMenuListeners() {
    // Event listeners are already handled by the global click handler
}

function deleteDocument(id) {
    if (confirm('Are you sure you want to delete this document?')) {
        window.location.href = 'delete_document.php?id=' + id;
    }
}

function deleteFolder(id, currentFolderId) {
    if (confirm('Are you sure you want to delete this folder? All documents inside will be moved to root.')) {
        let redirectUrl = 'delete_folder.php?id=' + id;
        if (currentFolderId) {
            redirectUrl += '&redirect_folder_id=' + currentFolderId;
        }
        window.location.href = redirectUrl;
    }
}

function showEditFolderModal(folderId, folderName, parentId) {
    document.getElementById('edit_folder_id').value = folderId;
    document.getElementById('edit_folder_name').value = folderName;
    document.getElementById('edit_parent_folder').value = parentId || '';
    document.getElementById('editFolderModal').style.display = 'block';
    closeAllMenus();
}

function showEditDocumentModal(docId, docName) {
    document.getElementById('edit_doc_id').value = docId;
    document.getElementById('edit_doc_name').value = docName;
    document.getElementById('editDocumentModal').style.display = 'block';
    closeAllMenus();
}

function showMoveModal(docId, docName) {
    document.getElementById('move_doc_id').value = docId;
    document.getElementById('move_doc_name').textContent = docName;
    document.getElementById('moveModal').style.display = 'block';
    closeAllMenus();
}

function toggleMenu(event, menuId) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const menu = document.getElementById('menu-' + menuId);
    if (!menu) return;
    const parentItem = menu.closest('.document-item');
    const isOpen = menu.classList.contains('active');

    // Close all menus first (this will also remove menu-open class)
    closeAllMenus();

    // Toggle current menu and mark parent so it stacks above siblings
    if (!isOpen) {
        menu.classList.add('active');
        if (parentItem) parentItem.classList.add('menu-open');
        // Position menu if it would overflow viewport
        setTimeout(() => {
            const rect = menu.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            if (rect.bottom > viewportHeight) {
                menu.style.bottom = '100%';
                menu.style.top = 'auto';
            } else {
                menu.style.top = '';
                menu.style.bottom = '';
            }
        }, 10);
    }
}

function closeAllMenus() {
    document.querySelectorAll('.menu-dropdown').forEach(menu => {
        menu.classList.remove('active');
        menu.style.top = '';
        menu.style.bottom = '';
    });
    document.querySelectorAll('.document-item.menu-open').forEach(item => item.classList.remove('menu-open'));
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.document-menu')) {
        closeAllMenus();
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = ['createFolderModal', 'uploadModal', 'editFolderModal', 'editDocumentModal', 'moveModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}

// View Toggle Functionality
function switchView(viewType) {
    const grid = document.getElementById('documentsGrid');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    const storageKey = 'documentsViewType';
    
    if (viewType === 'list') {
        grid.classList.add('list-view');
        gridBtn.classList.remove('active');
        listBtn.classList.add('active');
        localStorage.setItem(storageKey, 'list');
    } else {
        grid.classList.remove('list-view');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
        localStorage.setItem(storageKey, 'grid');
    }
}

// Load saved view preference on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('documentsViewType');
    if (savedView === 'list') {
        switchView('list');
    } else {
        switchView('grid');
    }
});

// Fix: sometimes header layout jumps on first load (fonts/icons loading or late CSS apply).
// Force a small reflow/resize after resources/fonts load so header aligns correctly without manual refresh.
window.addEventListener('load', function() {
    // Try to wait for font loading if supported
    try {
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(() => { window.dispatchEvent(new Event('resize')); });
        } else {
            setTimeout(() => window.dispatchEvent(new Event('resize')), 60);
        }
    } catch (e) {
        setTimeout(() => window.dispatchEvent(new Event('resize')), 60);
    }
});

// Apply sort function
function applySort(sortValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    // Preserve folder_id if present
    const folderId = url.searchParams.get('folder_id');
    if (folderId) {
        url.searchParams.set('folder_id', folderId);
    }
    
    // If there's an active search, re-run it with new sort
    const searchInput = document.getElementById('searchInput');
    if (searchInput && searchInput.value.trim()) {
        performSearch(searchInput.value.trim());
    } else {
        window.location.href = url.toString();
    }
}
</script>

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

