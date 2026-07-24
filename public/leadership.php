<?php
$pageTitle = "Campus Leadership Directory | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = Database::getConnection();

$stmt = $db->prepare("
    SELECT l.*, c.name AS club_name, c.slug AS club_slug, c.logo AS club_logo
    FROM leadership l
    JOIN clubs c ON l.club_id = c.id
    WHERE c.deleted_at IS NULL
    ORDER BY c.name ASC, l.order_index ASC
");
$stmt->execute();
$allLeaders = $stmt->fetchAll();

// Group leadership by club
$grouped = [];
foreach ($allLeaders as $leader) {
    $grouped[$leader['club_name']][] = $leader;
}
?>

<div class="py-4 bg-body-tertiary border-bottom">
    <div class="container">
        <h1 class="fw-bold mb-1">Campus Club Leadership Roster</h1>
        <p class="text-secondary mb-0">Unified accountability matrix displaying Faculty Advisors, Presidents, Vice Presidents, and Secretaries for all active campus organizations.</p>
    </div>
</div>

<div class="container py-5">
    <?php if (empty($grouped)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people fs-1 d-block mb-3"></i>
            <h5>No leadership records populated.</h5>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-5">
            <?php foreach ($grouped as $clubName => $leaders): ?>
                <div class="card p-4 ccms-card">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <img src="/<?= e($leaders[0]['club_logo']) ?>" alt="<?= e($clubName) ?>" class="rounded-circle border" style="width: 48px; height: 48px; object-fit: cover;">
                            <div>
                                <h4 class="fw-bold mb-0"><?= e($clubName) ?></h4>
                                <a href="/club-detail.php?slug=<?= e($leaders[0]['club_slug']) ?>" class="small text-primary text-decoration-none">View Club Profile <i class="bi bi-chevron-right"></i></a>
                            </div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1"><?= count($leaders) ?> Officers</span>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($leaders as $leader): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                                    <div class="bg-primary text-white rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold fs-4 mb-2" style="width: 56px; height: 56px;">
                                        <?= e(substr($leader['name'], 0, 1)) ?>
                                    </div>
                                    <h6 class="fw-bold mb-1"><?= e($leader['name']) ?></h6>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill small mb-2"><?= e($leader['role_title']) ?></span>
                                    <?php if (!empty($leader['email'])): ?>
                                        <div class="small text-muted text-truncate"><i class="bi bi-envelope me-1"></i> <?= e($leader['email']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($leader['phone'])): ?>
                                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i> <?= e($leader['phone']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
