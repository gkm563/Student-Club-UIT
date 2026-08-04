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
            WHERE (c.id = ? OR c.slug = ?) AND c.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $club = $stmt->fetch();

        if (!$club) {
            echo json_encode(['status' => 'error', 'message' => 'Club not found or is currently private/inactive']);
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

        // Fetch Gallery Media Items (all club photos)
        $galStmt = $db->prepare("
            SELECT * FROM gallery_items 
            WHERE club_id = ? 
            ORDER BY created_at DESC LIMIT 24
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
        (SELECT COUNT(*) FROM leadership l WHERE l.club_id = c.id) as leadership_count,
        (SELECT COUNT(*) FROM events e WHERE e.club_id = c.id) as event_count,
        (SELECT MAX(e.event_date) FROM events e WHERE e.club_id = c.id) as latest_event_date
        FROM clubs c
        JOIN categories cat ON c.category_id = cat.id
        WHERE c.status = 'active'
    ";
    $params = [];

    $wing = strtolower($_GET['wing'] ?? '');
    if ($wing === 'technical' || $wing === 'developers') {
        $sql .= " AND cat.slug IN ('technical', 'technical-software-development')";
    } elseif ($wing === 'cultural') {
        $sql .= " AND cat.slug IN ('cultural', 'academic', 'creative')";
    }

    if ($categorySlug !== 'all' && !empty($categorySlug)) {
        if ($categorySlug === 'technical') {
            $sql .= " AND cat.slug IN ('technical', 'technical-software-development')";
        } else {
            $sql .= " AND cat.slug = ?";
            $params[] = $categorySlug;
        }
    }

    if (!empty($search)) {
        $sql .= " AND (c.name LIKE ? OR c.short_name LIKE ? OR c.tagline LIKE ? OR c.description LIKE ? OR cat.name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($sort === 'name') {
        $sql .= " ORDER BY c.name ASC";
    } elseif ($sort === 'members') {
        $sql .= " ORDER BY c.member_count DESC, leadership_count DESC";
    } elseif ($sort === 'active' || $sort === 'events') {
        $sql .= " ORDER BY event_count DESC, latest_event_date DESC";
    } else {
        $sql .= " ORDER BY event_count DESC, c.created_at DESC";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $clubs = $stmt->fetchAll();

    // Fetch Categories list with counts
    $catStmt = $db->query("
        SELECT cat.*, COUNT(c.id) as club_count 
        FROM categories cat 
        LEFT JOIN clubs c ON c.category_id = cat.id AND c.status = 'active'
        GROUP BY cat.id
    ");
    $categories = $catStmt->fetchAll();

    // Count Tech Wing sub-chapters (excluding umbrella parent ID clb_developers_uit)
    $techCountStmt = $db->query("
        SELECT COUNT(*) FROM clubs c 
        JOIN categories cat ON c.category_id = cat.id 
        WHERE c.status = 'active' 
          AND cat.slug IN ('technical', 'technical-software-development')
          AND c.id != 'clb_developers_uit'
    ");
    $techCount = (int) $techCountStmt->fetchColumn();

    // Count Cultural Wing sub-chapters (excluding umbrella parent ID clb_cultural_uit)
    $culturalCountStmt = $db->query("
        SELECT COUNT(*) FROM clubs c 
        JOIN categories cat ON c.category_id = cat.id 
        WHERE c.status = 'active' 
          AND cat.slug IN ('cultural', 'academic', 'creative', 'sports', 'social', 'literary', 'media')
          AND c.id != 'clb_cultural_uit'
    ");
    $culturalCount = (int) $culturalCountStmt->fetchColumn();

    // Count Total active sub-chapters (excluding parent umbrella IDs)
    $totalAllStmt = $db->query("
        SELECT COUNT(*) FROM clubs 
        WHERE status = 'active' 
          AND id NOT IN ('clb_developers_uit', 'clb_cultural_uit')
    ");
    $totalAll = (int) $totalAllStmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'total' => count($clubs),
        'total_all' => $totalAll,
        'tech_count' => $techCount,
        'cultural_count' => $culturalCount,
        'categories' => $categories,
        'data' => $clubs
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
