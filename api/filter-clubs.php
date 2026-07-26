<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$categorySlug = $_GET['category'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$sort         = $_GET['sort'] ?? 'newest';

try {
    $db = Database::getConnection();
    
    $where = ["c.deleted_at IS NULL"];
    $params = [];

    if ($categorySlug !== 'all') {
        $where[] = "cat.slug = ?";
        $params[] = $categorySlug;
    }

    if ($statusFilter !== 'all') {
        $where[] = "c.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($search)) {
        $where[] = "(c.name LIKE ? OR c.short_name LIKE ? OR c.tagline LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $whereSql = implode(' AND ', $where);

    $orderBy = "c.created_at DESC";
    if ($sort === 'alphabetical') {
        $orderBy = "c.name ASC";
    } elseif ($sort === 'popular') {
        $orderBy = "c.founded_year ASC";
    }

    $stmt = $db->prepare("
        SELECT c.*, cat.name AS category_name, cat.slug AS category_slug, cat.icon AS category_icon
        FROM clubs c
        JOIN categories cat ON c.category_id = cat.id
        WHERE $whereSql
        ORDER BY $orderBy
    ");
    $stmt->execute($params);
    $clubs = $stmt->fetchAll();

    // Attach badge HTML
    foreach ($clubs as &$club) {
        $club['status_badge'] = get_status_badge($club['status']);
    }

    echo json_encode(['success' => true, 'clubs' => $clubs]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
