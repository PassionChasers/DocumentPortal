<?php
require_once '../includes/config.php';
require_login();

header('Content-Type: application/json');

if (!isset($_GET['q'])) {
    echo json_encode(['success' => false, 'message' => 'Search query required']);
    exit;
}

$search = trim($_GET['q']);

if (empty($search)) {
    echo json_encode(['success' => true, 'folders' => [], 'documents' => []]);
    exit;
}

$searchTerm = '%' . $search . '%';

// Get sort parameter (optional)
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$sort_parts = explode('_', $sort);
$sort_field = $sort_parts[0] ?? 'name';
$sort_order = isset($sort_parts[1]) && strtoupper($sort_parts[1]) === 'DESC' ? 'DESC' : 'ASC';

// Build ORDER BY clause
$order_by = 'name';
if ($sort_field === 'date') {
    $order_by = 'created_at';
}

// Search folders
$stmt = $pdo->prepare('
    SELECT f.*, u.username as created_by_name
    FROM folders f
    LEFT JOIN users u ON f.created_by = u.id
    WHERE f.name LIKE ?
    ORDER BY f.' . $order_by . ' ' . $sort_order
);
$stmt->execute([$searchTerm]);
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Search documents
$stmt = $pdo->prepare('
    SELECT d.*, u.username as uploaded_by_name 
    FROM documents d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    WHERE d.name LIKE ? 
    ORDER BY d.' . $order_by . ' ' . $sort_order
);
$stmt->execute([$searchTerm]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'folders' => $folders,
    'documents' => $documents
]);
?>

