<?php
$pageTitle = "Activity Blog Feed | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = Database::getConnection();

$stmt = $db->prepare("
    SELECT a.*, c.name AS club_name, c.slug AS club_slug, c.logo AS club_logo
    FROM activities a
    JOIN clubs c ON a.club_id = c.id
    WHERE a.status = 'published' AND c.deleted_at IS NULL
    ORDER BY a.created_at DESC
");
$stmt->execute();
$activities = $stmt->fetchAll();
?>

<div class="py-4 bg-body-tertiary border-bottom">
    <div class="container">
        <h1 class="fw-bold mb-1">Campus Activity Feed</h1>
        <p class="text-secondary mb-0">Live updates, workshop recaps, and announcements from all student organizations.</p>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex flex-column gap-4">
                <?php if (empty($activities)): ?>
                    <div class="p-5 text-center text-muted card border-dashed">
                        <i class="bi bi-newspaper fs-1 d-block mb-3"></i>
                        <h5>No activity posts published yet.</h5>
                    </div>
                <?php else: ?>
                    <?php foreach ($activities as $act): ?>
                        <div class="card p-4 ccms-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="/<?= e($act['club_logo']) ?>" alt="<?= e($act['club_name']) ?>" class="rounded-circle border" style="width: 44px; height: 44px; object-fit: cover;">
                                <div>
                                    <h6 class="fw-bold mb-0">
                                        <a href="/club-detail.php?slug=<?= e($act['club_slug']) ?>" class="text-decoration-none text-body">
                                            <?= e($act['club_name']) ?>
                                        </a>
                                    </h6>
                                    <span class="small text-muted"><?= time_ago($act['created_at']) ?> &bull; Tag: <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= e($act['tag']) ?></span></span>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-2"><?= e($act['title']) ?></h4>
                            <p class="text-secondary mb-0" style="white-space: pre-line;"><?= e($act['content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
