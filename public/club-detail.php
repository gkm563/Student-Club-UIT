<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$db = Database::getConnection();

$stmt = $db->prepare("
    SELECT c.*, cat.name AS category_name, cat.icon AS category_icon
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.slug = ? AND c.deleted_at IS NULL
");
$stmt->execute([$slug]);
$club = $stmt->fetch();

if (!$club) {
    header("Location: /clubs.php");
    exit;
}

$pageTitle = $club['name'] . " | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// Fetch Leadership Roster
$stmtLead = $db->prepare("SELECT * FROM leadership WHERE club_id = ? ORDER BY order_index ASC");
$stmtLead->execute([$club['id']]);
$leadership = $stmtLead->fetchAll();

// Fetch Events
$stmtEvents = $db->prepare("SELECT * FROM events WHERE club_id = ? ORDER BY event_date DESC");
$stmtEvents->execute([$club['id']]);
$events = $stmtEvents->fetchAll();

// Fetch Activities
$stmtAct = $db->prepare("SELECT * FROM activities WHERE club_id = ? AND status = 'published' ORDER BY created_at DESC");
$stmtAct->execute([$club['id']]);
$activities = $stmtAct->fetchAll();

// Fetch Achievements
$stmtAch = $db->prepare("SELECT * FROM achievements WHERE club_id = ? ORDER BY achievement_date DESC");
$stmtAch->execute([$club['id']]);
$achievements = $stmtAch->fetchAll();
?>

<!-- Club Hero Banner -->
<div class="position-relative bg-dark text-white py-5" style="background: linear-gradient(180deg, rgba(15, 23, 42, 0.7) 0%, rgba(15, 23, 42, 0.95) 100%), url('/<?= e($club['cover_image']) ?>') center/cover;">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row align-items-center gap-4 text-center text-md-start">
            <img src="/<?= e($club['logo']) ?>" alt="<?= e($club['name']) ?>" class="rounded-4 bg-white p-2 shadow-lg" style="width: 120px; height: 120px; object-fit: cover;">
            <div>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start mb-2">
                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1">
                        <i class="bi <?= e($club['category_icon']) ?> me-1"></i> <?= e($club['category_name']) ?>
                    </span>
                    <?= get_status_badge($club['status']) ?>
                    <?php if ($club['recruitment_open']): ?>
                        <span class="badge bg-warning-subtle text-warning border rounded-pill px-3 py-1"><i class="bi bi-person-plus-fill me-1"></i> Recruiting</span>
                    <?php endif; ?>
                </div>
                <h1 class="fw-bold mb-1 display-6"><?= e($club['name']) ?></h1>
                <p class="lead text-light-50 mb-2 small" style="max-width: 650px;"><?= e($club['tagline']) ?></p>
                <div class="d-flex flex-wrap gap-3 small text-light-50 justify-content-center justify-content-md-start">
                    <span><i class="bi bi-building me-1"></i> Est. <?= e($club['founded_year']) ?></span>
                    <span><i class="bi bi-geo-alt me-1"></i> <?= e($club['office_location'] ?: 'SAC Office') ?></span>
                    <span><i class="bi bi-clock me-1"></i> <?= e($club['meeting_time'] ?: 'Weekly Meets') ?></span>
                </div>
            </div>
            
            <?php if ($club['recruitment_open'] && !empty($club['recruitment_link'])): ?>
                <div class="ms-md-auto text-center">
                    <a href="<?= e($club['recruitment_link']) ?>" target="_blank" class="btn btn-warning btn-lg rounded-pill px-4 fw-bold shadow">
                        <i class="bi bi-send-fill me-2"></i> Join Club Now
                    </a>
                    <?php if (!empty($club['recruitment_deadline'])): ?>
                        <div class="small text-warning-subtle mt-1">Deadline: <?= e(date('M j, Y', strtotime($club['recruitment_deadline']))) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Main Content Column -->
        <div class="col-lg-8">
            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="clubTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-3" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview">Overview & Mission</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-3" id="events-tab" data-bs-toggle="tab" data-bs-target="#events">Events (<?= count($events) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-3" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities">Activity Feed (<?= count($activities) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-3" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements">Achievements</button>
                </li>
            </ul>

            <div class="tab-content" id="clubTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview">
                    <div class="card p-4 mb-4 ccms-card">
                        <h4 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i> About <?= e($club['short_name']) ?></h4>
                        <p class="text-secondary"><?= nl2br(e($club['description'])) ?></p>
                    </div>

                    <?php if (!empty($club['mission']) || !empty($club['vision']) || !empty($club['objectives'])): ?>
                        <div class="row g-3 mb-4">
                            <?php if (!empty($club['mission'])): ?>
                                <div class="col-md-4">
                                    <div class="card p-3 h-100 ccms-card bg-primary-subtle border-0">
                                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i> Mission</h6>
                                        <p class="small text-secondary mb-0"><?= e($club['mission']) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($club['vision'])): ?>
                                <div class="col-md-4">
                                    <div class="card p-3 h-100 ccms-card bg-info-subtle border-0">
                                        <h6 class="fw-bold text-info mb-2"><i class="bi bi-eye me-1"></i> Vision</h6>
                                        <p class="small text-secondary mb-0"><?= e($club['vision']) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($club['objectives'])): ?>
                                <div class="col-md-4">
                                    <div class="card p-3 h-100 ccms-card bg-success-subtle border-0">
                                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-flag me-1"></i> Objectives</h6>
                                        <p class="small text-secondary mb-0"><?= e($club['objectives']) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Leadership Roster -->
                    <div class="card p-4 ccms-card mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-people text-primary me-2"></i> Club Leadership & Committee</h5>
                        <div class="row g-3">
                            <?php if (empty($leadership)): ?>
                                <p class="text-muted small mb-0">Leadership roster not updated yet.</p>
                            <?php else: ?>
                                <?php foreach ($leadership as $lead): ?>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-3 p-3 bg-body-tertiary rounded-3 border">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px;">
                                                <?= e(substr($lead['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-0"><?= e($lead['name']) ?></h6>
                                                <span class="badge bg-primary-subtle text-primary border rounded-pill small"><?= e($lead['role_title']) ?></span>
                                                <?php if (!empty($lead['email'])): ?>
                                                    <div class="small text-muted mt-1"><i class="bi bi-envelope me-1"></i> <?= e($lead['email']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Events Tab -->
                <div class="tab-pane fade" id="events">
                    <div class="d-flex flex-column gap-3">
                        <?php if (empty($events)): ?>
                            <div class="p-4 text-center text-muted card border-dashed">No events logged for this club yet.</div>
                        <?php else: ?>
                            <?php foreach ($events as $ev): ?>
                                <div class="card p-3 ccms-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary-subtle text-primary rounded-pill small"><?= e(date('M j, Y - g:i A', strtotime($ev['event_date']))) ?></span>
                                        <?= get_status_badge($ev['status']) ?>
                                    </div>
                                    <h5 class="fw-bold mb-1"><?= e($ev['title']) ?></h5>
                                    <p class="text-secondary small mb-2"><i class="bi bi-geo-alt me-1"></i> <?= e($ev['venue']) ?></p>
                                    <p class="small text-muted mb-3"><?= e($ev['description']) ?></p>
                                    <?php if (!empty($ev['registration_link'])): ?>
                                        <a href="<?= e($ev['registration_link']) ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill align-self-start">
                                            Register for Event <i class="bi bi-box-arrow-up-right ms-1"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activities Tab -->
                <div class="tab-pane fade" id="activities">
                    <div class="d-flex flex-column gap-3">
                        <?php if (empty($activities)): ?>
                            <div class="p-4 text-center text-muted card border-dashed">No activity updates published.</div>
                        <?php else: ?>
                            <?php foreach ($activities as $act): ?>
                                <div class="card p-3 ccms-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small"><?= e($act['tag']) ?></span>
                                        <span class="small text-muted"><?= time_ago($act['created_at']) ?></span>
                                    </div>
                                    <h5 class="fw-bold mb-2"><?= e($act['title']) ?></h5>
                                    <p class="text-secondary small mb-0"><?= nl2br(e($act['content'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Achievements Tab -->
                <div class="tab-pane fade" id="achievements">
                    <div class="d-flex flex-column gap-3">
                        <?php if (empty($achievements)): ?>
                            <div class="p-4 text-center text-muted card border-dashed">No achievements uploaded yet.</div>
                        <?php else: ?>
                            <?php foreach ($achievements as $ach): ?>
                                <div class="card p-3 ccms-card border-start border-4 border-warning">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                                            <i class="bi bi-trophy-fill fs-3"></i>
                                        </div>
                                        <div>
                                            <span class="small text-muted"><?= e(date('F Y', strtotime($ach['achievement_date']))) ?></span>
                                            <h5 class="fw-bold mb-1"><?= e($ach['title']) ?></h5>
                                            <p class="text-secondary small mb-0"><?= e($ach['description']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="col-lg-4">
            <!-- Contact & Social Links -->
            <div class="card p-4 ccms-card mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-envelope-paper text-primary me-2"></i> Club Contact Info</h5>
                <ul class="list-unstyled mb-4 space-y-2 text-secondary small">
                    <li class="mb-2"><i class="bi bi-envelope-fill text-primary me-2"></i> <?= e($club['email'] ?: 'N/A') ?></li>
                    <li class="mb-2"><i class="bi bi-telephone-fill text-primary me-2"></i> <?= e($club['phone'] ?: 'N/A') ?></li>
                    <li class="mb-2"><i class="bi bi-geo-alt-fill text-primary me-2"></i> <?= e($club['office_location'] ?: 'SAC Office') ?></li>
                    <li class="mb-2"><i class="bi bi-clock-fill text-primary me-2"></i> <?= e($club['meeting_time'] ?: 'Weekly Meets') ?></li>
                </ul>

                <h6 class="fw-bold mb-2">Connect with Us</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (!empty($club['website'])): ?>
                        <a href="<?= e($club['website']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-globe me-1"></i> Website</a>
                    <?php endif; ?>
                    <?php if (!empty($club['github'])): ?>
                        <a href="<?= e($club['github']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-github me-1"></i> GitHub</a>
                    <?php endif; ?>
                    <?php if (!empty($club['linkedin'])): ?>
                        <a href="<?= e($club['linkedin']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-linkedin me-1"></i> LinkedIn</a>
                    <?php endif; ?>
                    <?php if (!empty($club['instagram'])): ?>
                        <a href="<?= e($club['instagram']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-instagram me-1"></i> Instagram</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
