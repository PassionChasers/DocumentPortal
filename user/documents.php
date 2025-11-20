<?php
require_once '../includes/config.php';
require_login();

$page_title = 'Documents';

// Get current folder
$current_folder_id = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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
if (empty($search)) {
    $stmt = $pdo->prepare('SELECT * FROM folders WHERE parent_id ' . ($current_folder_id ? '= ?' : 'IS NULL') . ' ORDER BY ' . $order_by . ' ' . $sort_order);
    if ($current_folder_id) {
        $stmt->execute([$current_folder_id]);
    } else {
        $stmt->execute();
    }
    $folders = $stmt->fetchAll();
} else {
    $folders = [];
}

// Get documents
if (!empty($search)) {
    $stmt = $pdo->prepare('
        SELECT d.*, u.username as uploaded_by_name 
        FROM documents d 
        LEFT JOIN users u ON d.uploaded_by = u.id 
        WHERE d.name LIKE ? 
        ORDER BY d.' . $order_by . ' ' . $sort_order
    );
    $stmt->execute(['%' . $search . '%']);
    $documents = $stmt->fetchAll();
} else {
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
}

include '../includes/header.php';
?>

<div class="page-header">
    <div>
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
        <?php if ($current_folder_id || !empty($breadcrumbs)): ?>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <?php if ($current_folder_id): ?>
                    <a href="/user/documents.php<?= $parent_folder_id ? '?folder_id=' . $parent_folder_id : '' ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($breadcrumbs)): ?>
            <nav class="breadcrumb">
                <a href="/user/documents.php">Root</a>
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <span>/</span>
                    <a href="/user/documents.php?folder_id=<?= $crumb['id'] ?>"><?= h($crumb['name']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <form method="GET" action="" class="search-form" onsubmit="return false;" style="margin: 0;">
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

<script>
// Store original content for restore (kept as fallback)
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
    fetch('/api/search.php?q=' + encodeURIComponent(searchTerm) + '&sort=' + encodeURIComponent(sortValue))
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

function toggleMenu(event, menuId) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const menu = document.getElementById('menu-' + menuId);
    if (!menu) return;
    
    const isOpen = menu.classList.contains('active');
    
    // Close all menus first
    closeAllMenus();
    
    // Toggle current menu
    if (!isOpen) {
        menu.classList.add('active');
        // Position menu
        setTimeout(() => {
            const rect = menu.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            if (rect.bottom > viewportHeight) {
                menu.style.bottom = '100%';
                menu.style.top = 'auto';
            }
        }, 10);
    }
}

function closeAllMenus() {
    document.querySelectorAll('.menu-dropdown').forEach(menu => {
        menu.classList.remove('active');
    });
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.document-menu')) {
        closeAllMenus();
    }
});

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

