<?php
/**
 * RESTful API Endpoint for Clubs Data (ClubHub UIT)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getConnection();

    // Single Club Details Fetch
    if (!empty($_GET['id']) || !empty($_GET['slug'])) {
        $identifier = $_GET['id'] ?? $_GET['slug'];
        
        $stmt = $db->prepare("
            SELECT c.*, cat.name as category_name, cat.slug as category_slug, cat.icon as category_icon
            FROM clubs c
            JOIN categories cat ON c.category_id = cat.id
            WHERE (c.id = ? OR c.slug = ?) AND c.status != 'suspended'
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $club = $stmt->fetch();

        if (!$club) {
            echo json_encode(['status' => 'error', 'message' => 'Club not found']);
            exit;
        }

        // Fetch Leadership Roster (Annual Core Team)
        $leadStmt = $db->prepare("
            SELECT * FROM leadership 
            WHERE club_id = ? 
            ORDER BY order_index ASC, id ASC
        ");
        $leadStmt->execute([$club['id']]);
        $club['leadership'] = $leadStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Club Events (Both Past Completed & Upcoming)
        $eventStmt = $db->prepare("
            SELECT * FROM events 
            WHERE club_id = ? AND status NOT IN ('draft', 'hidden', 'archived', 'cancelled') 
            ORDER BY event_date DESC
        ");
        $eventStmt->execute([$club['id']]);
        $club['events'] = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Gallery Media Items
        $galStmt = $db->prepare("
            SELECT * FROM gallery_items 
            WHERE club_id = ? 
            ORDER BY created_at DESC LIMIT 12
        ");
        $galStmt->execute([$club['id']]);
        $club['gallery'] = $galStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $club]);
        exit;
    }

    // List Clubs with Filters & Sorting
    $categorySlug = $_GET['category'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $sort = $_GET['sort'] ?? 'popularity';

    $sql = "
        SELECT c.*, cat.name as category_name, cat.slug as category_slug, cat.icon as category_icon,
        (SELECT COUNT(*) FROM leadership l WHERE l.club_id = c.id) as member_count
        FROM clubs c
        JOIN categories cat ON c.category_id = cat.id
        WHERE c.status != 'suspended'
    ";
    $params = [];

    if ($categorySlug !== 'all' && !empty($categorySlug)) {
        $sql .= " AND cat.slug = ?";
        $params[] = $categorySlug;
    }

    if (!empty($search)) {
        $sql .= " AND (c.name LIKE ? OR c.short_name LIKE ? OR c.tagline LIKE ? OR c.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($sort === 'name') {
        $sql .= " ORDER BY c.name ASC";
    } elseif ($sort === 'members') {
        $sql .= " ORDER BY member_count DESC";
    } else {
        $sql .= " ORDER BY c.created_at DESC";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $clubs = $stmt->fetchAll();

    // Fetch Categories list with counts
    $catStmt = $db->query("
        SELECT cat.*, COUNT(c.id) as club_count 
        FROM categories cat 
        LEFT JOIN clubs c ON c.category_id = cat.id AND c.status != 'suspended'
        GROUP BY cat.id
    ");
    $categories = $catStmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'total' => count($clubs),
        'categories' => $categories,
        'data' => $clubs
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
