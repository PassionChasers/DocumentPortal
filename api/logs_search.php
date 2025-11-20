<?php
require_once __DIR__ . '/../includes/config.php';
require_login();

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : '';
$user_id = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 50;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(u.username LIKE ? OR l.action LIKE ? OR d.name LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($start_date !== '') {
    $where[] = 'l.timestamp >= ?';
    $params[] = $start_date . ' 00:00:00';
}

if ($end_date !== '') {
    $where[] = 'l.timestamp <= ?';
    $params[] = $end_date . ' 23:59:59';
}

if ($user_id !== '') {
    $where[] = 'l.user_id = ?';
    $params[] = $user_id;
}

$where_sql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

// total
$count_sql = 'SELECT COUNT(*) as c FROM logs l LEFT JOIN users u ON l.user_id = u.id LEFT JOIN documents d ON l.document_id = d.id' . $where_sql;
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = (int) $stmt->fetch()['c'];

// fetch logs
$sql = 'SELECT l.*, u.username, COALESCE(l.document_name, d.name) as document_name FROM logs l LEFT JOIN users u ON l.user_id = u.id LEFT JOIN documents d ON l.document_id = d.id' . $where_sql . ' ORDER BY l.timestamp DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($sql);

$exec_params = $params;
$exec_params[] = $per_page;
$exec_params[] = $offset;

foreach ($exec_params as $k => $v) {
    $idx = $k + 1;
    if ($idx > count($exec_params) - 2) {
        $stmt->bindValue($idx, $v, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($idx, $v, PDO::PARAM_STR);
    }
}

$stmt->execute();
$rows = $stmt->fetchAll();

$logs = [];
foreach ($rows as $r) {
    $logs[] = [
        'id' => $r['id'],
        'timestamp' => $r['timestamp'],
        'timestamp_formatted' => format_nepal($r['timestamp'], 'M d, Y H:i:s'),
        'user_id' => $r['user_id'],
        'username' => $r['username'],
        'action' => $r['action'],
        'action_label' => ucfirst(str_replace('_', ' ', $r['action'])),
        'action_icon' => (function($a){
            $icons = [
                'upload' => 'fa-upload', 'download' => 'fa-download', 'delete' => 'fa-trash',
                'login' => 'fa-sign-in-alt', 'logout' => 'fa-sign-out-alt', 'create' => 'fa-plus',
                'edit' => 'fa-edit', 'move' => 'fa-arrows-alt', 'create_folder' => 'fa-folder-plus',
                'edit_folder' => 'fa-folder-open', 'delete_folder' => 'fa-folder-minus',
                'create_user' => 'fa-user-plus', 'edit_user' => 'fa-user-edit', 'delete_user' => 'fa-user-minus',
            ];
            return $icons[strtolower($a)] ?? 'fa-circle';
        })($r['action']),
        'document_name' => $r['document_name'] ?: null,
    ];
}

$total_pages = $per_page > 0 ? ceil($total / $per_page) : 1;

echo json_encode(['success' => true, 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'total_pages' => $total_pages, 'logs' => $logs]);

?>
