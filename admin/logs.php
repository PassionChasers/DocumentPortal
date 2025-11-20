<?php
require_once '../includes/config.php';
require_login();

if (!is_admin()) {
    header('Location: /user/dashboard.php');
    exit;
}

$page_title = 'Activity Logs';

// Get search and filter terms
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$user_id = $_GET['user_id'] ?? '';

// Fetch all users for the filter dropdown
$stmt = $pdo->query('SELECT id, username FROM users ORDER BY username');
$all_users = $stmt->fetchAll();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Build WHERE clause for filters
$where_clauses = [];
$filter_params = [];

if (!empty($search)) {
    $where_clauses[] = ' (u.username LIKE ? OR l.action LIKE ? OR d.name LIKE ?) ';
    $search_param = '%' . $search . '%';
    $filter_params = array_merge($filter_params, [$search_param, $search_param, $search_param]);
}

if (!empty($start_date)) {
    $where_clauses[] = ' l.timestamp >= ? ';
    $filter_params[] = $start_date . ' 00:00:00';
}

if (!empty($end_date)) {
    $where_clauses[] = ' l.timestamp <= ? ';
    $filter_params[] = $end_date . ' 23:59:59';
}

if (!empty($user_id)) {
    $where_clauses[] = ' l.user_id = ? ';
    $filter_params[] = $user_id;
}

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total count
$count_sql = 'SELECT COUNT(*) as count FROM logs l LEFT JOIN users u ON l.user_id = u.id LEFT JOIN documents d ON l.document_id = d.id' . $where_sql;
$stmt = $pdo->prepare($count_sql);
$stmt->execute($filter_params);
$total_logs = $stmt->fetch()['count'];
$total_pages = ceil($total_logs / $per_page);

// Get logs
$sql = '
    SELECT l.*, u.username, COALESCE(l.document_name, d.name) as document_name 
    FROM logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    LEFT JOIN documents d ON l.document_id = d.id 
' . $where_sql . ' ORDER BY l.timestamp DESC LIMIT ? OFFSET ?';

$stmt = $pdo->prepare($sql);

$all_params = array_merge($filter_params, [$per_page, $offset]);

foreach ($all_params as $key => $value) {
    $param_type = PDO::PARAM_STR;
    if ($key === count($all_params) - 2 || $key === count($all_params) - 1) {
        $param_type = PDO::PARAM_INT;
    } else if (is_numeric($value) && in_array($value, $filter_params) && array_search($value, $filter_params) === array_search($user_id, $filter_params)) { // Check if it's the user_id param
        $param_type = PDO::PARAM_INT;
    }
    $stmt->bindValue($key + 1, $value, $param_type);
}

$stmt->execute();
$logs = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
/* Make filter inputs and buttons match the search input height and alignment */
.search-form .search-box .form-control,
.search-form .filter-options .form-control,
.search-form .search-box .btn,
.search-form .filter-options .btn {
    height: 40px;
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
}
.search-form .filter-options .form-control {
    box-sizing: border-box;
}
.search-form .search-box .form-control { width: 320px; }
.search-form .filter-options .form-control { min-width: 150px; }
</style>

<div class="page-header">
    <div class="page-info">
        <span class="text-muted">Total: <?= number_format($total_logs) ?> entries</span>
    </div>
    <div class="page-actions">
        <form method="GET" action="" class="search-form" onsubmit="return false;" style="margin: 0;">
            <div class="search-box">
                <input type="text" id="searchInput" name="search" placeholder="Search logs..." class="form-control" autocomplete="off" value="<?= h($search) ?>">
                <button type="button" id="clearSearchBtn" class="btn btn-secondary" style="display: none; margin-left:6px;" onclick="clearSearch()"><i class="fas fa-times"></i> Clear</button>
            </div>
            <div class="filter-options" style="display: flex; gap: 10px; margin-top: 10px;">
                <input type="date" id="startDate" name="start_date" class="form-control" value="<?= h($start_date) ?>" title="Start Date">
                <input type="date" id="endDate" name="end_date" class="form-control" value="<?= h($end_date) ?>" title="End Date">
                <select id="userFilter" name="user_id" class="form-control" title="Filter by User">
                    <option value="">All Users</option>
                    <?php foreach ($all_users as $user_option): ?>
                        <option value="<?= $user_option['id'] ?>" <?= (string)$user_option['id'] === $user_id ? 'selected' : '' ?>><?= h($user_option['username']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="clearFiltersBtn" class="btn btn-outline" onclick="clearFilters()"><i class="fas fa-times-circle"></i> Clear Filters</button>
            </div>
        </form>
    </div>
</div>

<script>
// Real-time search and filters for logs (debounced) — initialize after DOM ready
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    const originalTbodyEl = document.querySelector('.data-table tbody');
    const originalTbody = originalTbodyEl ? originalTbodyEl.innerHTML : '';
    const originalPagination = document.querySelector('.pagination') ? document.querySelector('.pagination').innerHTML : '';

    function numberWithCommas(x) { return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

    function updateTotal(count) {
        const totalSpan = document.getElementById('totalCount');
        if (totalSpan) totalSpan.textContent = numberWithCommas(count);
    }

    window.clearSearch = function() {
        const input = document.getElementById('searchInput');
        if (input) input.value = '';
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) clearBtn.style.display = 'none';
        restoreOriginal();
    };

    window.clearFilters = function() {
        ['startDate','endDate','userFilter'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        window.performSearch(1);
    };

    function restoreOriginal() {
        const tbody = document.querySelector('.data-table tbody');
        if (tbody) tbody.innerHTML = originalTbody;
        const pag = document.querySelector('.pagination');
        if (pag) pag.innerHTML = originalPagination;
    }

    window.performSearch = function(page) {
        const q = encodeURIComponent(document.getElementById('searchInput').value.trim());
        const start = encodeURIComponent(document.getElementById('startDate').value || '');
        const end = encodeURIComponent(document.getElementById('endDate').value || '');
        const user = encodeURIComponent(document.getElementById('userFilter').value || '');
        const per_page = <?= $per_page ?>;
        const url = '../api/logs_search.php?q=' + q + '&start_date=' + start + '&end_date=' + end + '&user_id=' + user + '&page=' + page + '&per_page=' + per_page;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { console.error('Logs search error', data.message); return; }
                renderLogs(data.logs);
                updateTotal(data.total);
                renderPagination(data.page, data.total_pages);
            })
            .catch(err => console.error('Search request failed', err));
    };

    window.renderLogs = function(logs) {
        const tbody = document.querySelector('.data-table tbody');
        if (!tbody) return;
        if (!logs || logs.length === 0) { tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No activity logs found.</td></tr>'; return; }
        let html = '';
        logs.forEach(log => {
            const ts = escapeHtml(log.timestamp_formatted);
            const user = escapeHtml(log.username || '');
            const action = escapeHtml(log.action_label);
            const doc = log.document_name ? ('<i class="fas fa-file"></i> ' + escapeHtml(log.document_name)) : '<em class="text-muted">N/A</em>';
            html += `<tr><td>${ts}</td><td>${user}</td><td><span class="badge badge-action"><i class="fas ${escapeHtml(log.action_icon)}"></i> ${action}</span></td><td>${doc}</td></tr>`;
        });
        tbody.innerHTML = html;
    };

    window.renderPagination = function(page, total_pages) {
        const container = document.querySelector('.pagination');
        if (!container) return;
        if (total_pages <= 1) { container.innerHTML = ''; return; }
        let html = '';
        if (page > 1) html += `<a href="#" class="btn btn-sm btn-outline" onclick="performSearch(${page - 1});return false;"><i class="fas fa-chevron-left"></i> Previous</a>`;
        html += ` <span class="page-info">Page ${page} of ${total_pages}</span> `;
        if (page < total_pages) html += `<a href="#" class="btn btn-sm btn-outline" onclick="performSearch(${page + 1});return false;">Next <i class="fas fa-chevron-right"></i></a>`;
        container.innerHTML = html;
    };

    function escapeHtml(text) { if (!text && text !== 0) return ''; return text.toString().replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]; }); }

    // attach listeners
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.trim();
            const clearBtn = document.getElementById('clearSearchBtn');
            if (q.length === 0) { if (clearBtn) clearBtn.style.display = 'none'; restoreOriginal(); return; }
            if (clearBtn) clearBtn.style.display = 'inline-block';
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => window.performSearch(1), 300);
        });
    }

    ['startDate','endDate','userFilter'].forEach(id => { const el = document.getElementById(id); if (el) el.addEventListener('change', () => window.performSearch(1)); });
});
</script>

<div class="table-container">
    <div class="scrollable-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Document</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No activity logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= format_nepal($log['timestamp'], 'M d, Y H:i:s') ?></td>
                            <td><?= h($log['username']) ?></td>
                            <td>
                                <span class="badge badge-action">
                                    <i class="fas <?= get_action_icon($log['action']) ?>"></i>
                                    <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['document_name']): ?>
                                    <i class="fas fa-file"></i> <?= h($log['document_name']) ?>
                                <?php else: ?>
                                    <em class="text-muted">N/A</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
    <?php
    $pagination_base_url ='admin/logs.php';
    $pagination_query_params = [];
    if (!empty($search)) {
        $pagination_query_params['search'] = $search;
    }
    if (!empty($start_date)) {
        $pagination_query_params['start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $pagination_query_params['end_date'] = $end_date;
    }
    if (!empty($user_id)) {
        $pagination_query_params['user_id'] = $user_id;
    }
    ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?= $pagination_base_url ?>?page=<?= $page - 1 ?><?= !empty($pagination_query_params) ? '&' . http_build_query($pagination_query_params) : '' ?>" class="btn btn-sm btn-outline">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>
        
        <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
        
        <?php if ($page < $total_pages): ?>
            <a href="<?= $pagination_base_url ?>?page=<?= $page + 1 ?><?= !empty($pagination_query_params) ? '&' . http_build_query($pagination_query_params) : '' ?>" class="btn btn-sm btn-outline">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
function get_action_icon($action) {
    $icons = [
        'upload' => 'fa-upload',
        'download' => 'fa-download',
        'delete' => 'fa-trash',
        'login' => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'create' => 'fa-plus',
        'edit' => 'fa-edit',
        'move' => 'fa-arrows-alt',
        'create_folder' => 'fa-folder-plus',
        'edit_folder' => 'fa-folder-open',
        'delete_folder' => 'fa-folder-minus',
        'create_user' => 'fa-user-plus',
        'edit_user' => 'fa-user-edit',
        'delete_user' => 'fa-user-minus',
    ];
    return $icons[strtolower($action)] ?? 'fa-circle';
}

include '../includes/footer.php';
?>

