<?php
$pageTitle = "Campus Events | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = Database::getConnection();
$status = $_GET['status'] ?? 'all';

$where = ["c.deleted_at IS NULL"];
$params = [];

if ($status !== 'all') {
    $where[] = "e.status = ?";
    $params[] = $status;
}

$whereSql = implode(' AND ', $where);
$stmt = $db->prepare("
    SELECT e.*, c.name AS club_name, c.slug AS club_slug, c.logo AS club_logo
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    WHERE $whereSql
    ORDER BY e.event_date DESC
");
$stmt->execute($params);
$events = $stmt->fetchAll();
?>

<div class="py-4 bg-body-tertiary border-bottom">
    <div class="container">
        <h1 class="fw-bold mb-1">Campus Events Calendar</h1>
        <p class="text-secondary mb-0">Discover upcoming workshops, hackathons, cultural nights, and sports tournaments across all clubs.</p>
    </div>
</div>

<div class="container py-4">
    <!-- Status Pills Filter -->
    <div class="d-flex gap-2 mb-4">
        <a href="/events.php?status=all" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">All Events</a>
        <a href="/events.php?status=upcoming" class="btn btn-sm <?= $status === 'upcoming' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">Upcoming</a>
        <a href="/events.php?status=completed" class="btn btn-sm <?= $status === 'completed' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">Completed</a>
    </div>

    <div class="row g-4">
        <?php if (empty($events)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted d-block mb-3"></i>
                <h5 class="fw-bold">No events found</h5>
                <p class="text-secondary small">No events match the selected filter.</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $ev): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 ccms-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary-subtle text-primary border rounded-pill small">
                                    <i class="bi bi-clock me-1"></i> <?= e(date('M j, Y - g:i A', strtotime($ev['event_date']))) ?>
                                </span>
                                <?= get_status_badge($ev['status']) ?>
                            </div>
                            <h5 class="fw-bold card-title mb-2"><?= e($ev['title']) ?></h5>
                            <p class="small text-secondary mb-2">
                                <i class="bi bi-building text-primary me-1"></i> Organized by: 
                                <a href="/club-detail.php?slug=<?= e($ev['club_slug']) ?>" class="text-decoration-none text-body fw-semibold">
                                    <?= e($ev['club_name']) ?>
                                </a>
                            </p>
                            <p class="small text-secondary mb-3"><i class="bi bi-geo-alt text-danger me-1"></i> Venue: <?= e($ev['venue']) ?></p>
                            <p class="text-muted small mb-3 text-truncate-3"><?= e($ev['description']) ?></p>
                            
                            <?php if (!empty($ev['registration_link'])): ?>
                                <a href="<?= e($ev['registration_link']) ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill w-100 mt-auto">
                                    Register Now <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
