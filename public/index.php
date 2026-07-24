<?php
$pageTitle = "Home | College Club Management System";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = Database::getConnection();

// Live Statistics Aggregates
$totalClubs        = $db->query("SELECT COUNT(*) FROM clubs WHERE deleted_at IS NULL")->fetchColumn();
$totalEvents       = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalActivities   = $db->query("SELECT COUNT(*) FROM activities WHERE status = 'published'")->fetchColumn();
$totalAchievements = $db->query("SELECT COUNT(*) FROM achievements")->fetchColumn();

// Fetch Categories with club counts
$categories = $db->query("
    SELECT cat.*, COUNT(c.id) AS club_count
    FROM categories cat
    LEFT JOIN clubs c ON cat.id = c.category_id AND c.deleted_at IS NULL
    GROUP BY cat.id
    ORDER BY cat.id ASC
")->fetchAll();

// Fetch Featured / Recent Clubs
$featuredClubs = $db->query("
    SELECT c.*, cat.name AS category_name, cat.icon AS category_icon, cat.slug AS category_slug
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.deleted_at IS NULL
    ORDER BY c.created_at DESC
    LIMIT 6
")->fetchAll();

// Fetch Latest Activities
$latestActivities = $db->query("
    SELECT a.*, c.name AS club_name, c.slug AS club_slug, c.logo AS club_logo
    FROM activities a
    JOIN clubs c ON a.club_id = c.id
    WHERE a.status = 'published' AND c.deleted_at IS NULL
    ORDER BY a.created_at DESC
    LIMIT 4
")->fetchAll();

// Fetch Upcoming Events
$upcomingEvents = $db->query("
    SELECT e.*, c.name AS club_name, c.slug AS club_slug, c.logo AS club_logo
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    WHERE e.status = 'upcoming' AND c.deleted_at IS NULL
    ORDER BY e.event_date ASC
    LIMIT 3
")->fetchAll();
?>

<!-- Hero Section with Background Mesh -->
<section class="hero-ccms bg-mesh-pattern text-center">
    <div class="container position-relative z-1">
        <!-- Live Campus Indicator Badge -->
        <div class="hero-live-badge mb-4">
            <span class="pulse-dot"></span>
            <span>UIT Campus Club Network &bull; 2026 Academic Season</span>
        </div>

        <h1 class="hero-title mb-4">
            Discover, Engage & Excel in <br>
            <span class="hero-highlight">Your Campus Student Clubs</span>
        </h1>
        <p class="lead text-secondary max-w-2xl mx-auto mb-5">
            The single trusted digital home for every student club at UIT. Explore technical chapters, cultural societies, competitive sports, and leadership opportunities.
        </p>

        <!-- Search Bar with Debounced Dropdown & Tags -->
        <div class="hero-search-box position-relative mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-search fs-4 text-primary me-3"></i>
                <input type="text" id="heroSearchInput" class="hero-search-input" placeholder="Search clubs by name or domain (e.g. GeeksforGeeks, Robotics, Coding)..." autocomplete="off">
                <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-none d-md-block shadow-sm">
                    Explore Directory
                </button>
            </div>
            <!-- Dynamic AJAX Search Results Dropdown -->
            <div id="searchResultsDropdown" class="search-results-dropdown"></div>
        </div>

        <!-- Quick Tag Suggestions -->
        <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 small">
            <span class="text-muted me-1 fw-semibold"><i class="bi bi-fire text-danger me-1"></i> Popular Suggestions:</span>
            <a href="/clubs.php?search=Coding" class="search-tag-pill">#Coding</a>
            <a href="/clubs.php?search=Robotics" class="search-tag-pill">#Robotics</a>
            <a href="/clubs.php?category=cultural" class="search-tag-pill">#CulturalFest</a>
            <a href="/clubs.php?status=recruiting" class="search-tag-pill text-primary fw-semibold"><i class="bi bi-person-plus-fill me-1"></i> #RecruitingNow</a>
        </div>
    </div>
</section>

<!-- Live Campus Statistics Bar -->
<section class="py-5 bg-body-tertiary border-y">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="stat-counter-card">
                    <i class="bi bi-diagram-3-fill fs-1 text-primary mb-2"></i>
                    <div class="stat-number"><?= number_format($totalClubs) ?></div>
                    <div class="text-secondary fw-semibold small">Active Student Clubs</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-counter-card">
                    <i class="bi bi-calendar-event-fill fs-1 text-primary mb-2"></i>
                    <div class="stat-number"><?= number_format($totalEvents) ?></div>
                    <div class="text-secondary fw-semibold small">Campus Events</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-counter-card">
                    <i class="bi bi-newspaper fs-1 text-primary mb-2"></i>
                    <div class="stat-number"><?= number_format($totalActivities) ?></div>
                    <div class="text-secondary fw-semibold small">Activity Blog Updates</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-counter-card">
                    <i class="bi bi-trophy-fill fs-1 text-primary mb-2"></i>
                    <div class="stat-number"><?= number_format($totalAchievements) ?></div>
                    <div class="text-secondary fw-semibold small">National Awards</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Join A Campus Club Showcase Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-2 mb-2 fw-semibold">Campus Ecosystem</span>
            <h2 class="fw-bold display-6 mb-2">Why Participate in Campus Clubs?</h2>
            <p class="text-secondary">Extracurricular engagement accelerates placement readiness, leadership potential, and lifelong alumni connections.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100 ccms-card-glow">
                    <div class="feature-icon-wrapper bg-primary-subtle text-primary">
                        <i class="bi bi-code-slash"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Skill Building & DSA</h5>
                    <p class="text-secondary small mb-0">Hands-on hackathons, algorithmic bootcamps, and technical workshops guided by senior mentors.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100 ccms-card-glow">
                    <div class="feature-icon-wrapper bg-success-subtle text-success">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Leadership Experience</h5>
                    <p class="text-secondary small mb-0">Lead event operations, manage budgets, and step up as President, Secretary, or Core Lead.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100 ccms-card-glow">
                    <div class="feature-icon-wrapper bg-info-subtle text-info">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Certificates & Awards</h5>
                    <p class="text-secondary small mb-0">Earn official verified participation certificates and build a stellar resume for campus placements.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100 ccms-card-glow">
                    <div class="feature-icon-wrapper bg-warning-subtle text-warning">
                        <i class="bi bi-palette-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Cultural & Arts Fests</h5>
                    <p class="text-secondary small mb-0">Express your talent in music, dance, street plays, photography, and inter-college cultural leagues.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Club Categories Section -->
<section class="py-5 bg-body-tertiary">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Explore Club Domains</h2>
                <p class="text-secondary small mb-0">Categorized student organizations to suit every discipline and passion.</p>
            </div>
            <a href="/clubs.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Directory <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="/clubs.php?category=<?= e($cat['slug']) ?>" class="card h-100 border-0 shadow-sm text-center p-3 text-decoration-none ccms-card">
                        <div class="bg-primary-subtle text-primary rounded-circle mx-auto p-3 mb-2" style="width: 58px; height: 58px;">
                            <i class="bi <?= e($cat['icon']) ?> fs-3"></i>
                        </div>
                        <h6 class="fw-bold text-body mb-1 small"><?= e($cat['name']) ?></h6>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small"><?= e($cat['club_count']) ?> Clubs</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Clubs Section -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Featured Campus Clubs</h2>
                <p class="text-secondary small mb-0">Discover high-impact chapters driving innovation, tech, and campus culture.</p>
            </div>
            <a href="/clubs.php" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold">Browse Full Directory</a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredClubs as $club): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 ccms-card ccms-card-glow">
                        <div class="club-card-banner" style="background-image: url('/<?= e($club['cover_image']) ?>');">
                            <img src="/<?= e($club['logo']) ?>" alt="<?= e($club['name']) ?>" class="club-card-logo-overlay">
                        </div>
                        <div class="card-body pt-5">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary-subtle text-primary border rounded-pill small">
                                    <i class="bi <?= e($club['category_icon']) ?> me-1"></i> <?= e($club['category_name']) ?>
                                </span>
                                <?= get_status_badge($club['status']) ?>
                            </div>
                            <h5 class="fw-bold card-title mb-1">
                                <a href="/club-detail.php?slug=<?= e($club['slug']) ?>" class="text-decoration-none text-body">
                                    <?= e($club['name']) ?>
                                </a>
                            </h5>
                            <p class="text-secondary small mb-3 text-truncate-2">
                                <?= e($club['tagline']) ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="small text-muted"><i class="bi bi-building me-1"></i> Est. <?= e($club['founded_year']) ?></span>
                                <a href="/club-detail.php?slug=<?= e($club['slug']) ?>" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold">
                                    View Club <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Latest Activity Feed & Upcoming Events Split -->
<section class="py-5 bg-body-tertiary">
    <div class="container">
        <div class="row g-4">
            <!-- Latest Activities Feed -->
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0"><i class="bi bi-activity text-primary me-2"></i> Recent Activity Feed</h3>
                    <a href="/activities.php" class="text-decoration-none small fw-semibold text-primary">All Updates <i class="bi bi-arrow-right"></i></a>
                </div>

                <div class="d-flex flex-column gap-3">
                    <?php if (empty($latestActivities)): ?>
                        <div class="p-4 text-center text-muted card border-dashed">No recent activity posts.</div>
                    <?php else: ?>
                        <?php foreach ($latestActivities as $act): ?>
                            <div class="card p-3 ccms-card">
                                <div class="d-flex align-items-start gap-3">
                                    <img src="/<?= e($act['club_logo']) ?>" alt="<?= e($act['club_name']) ?>" class="rounded-circle border" style="width: 46px; height: 46px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <a href="/club-detail.php?slug=<?= e($act['club_slug']) ?>" class="fw-semibold text-decoration-none text-body small">
                                                <?= e($act['club_name']) ?>
                                            </a>
                                            <span class="small text-muted"><i class="bi bi-clock me-1"></i> <?= time_ago($act['created_at']) ?></span>
                                        </div>
                                        <h6 class="fw-bold mb-1"><?= e($act['title']) ?></h6>
                                        <p class="text-secondary small mb-2 text-truncate-2"><?= e($act['content']) ?></p>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small"><?= e($act['tag']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Events Spotlight -->
            <div class="col-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0"><i class="bi bi-calendar-event text-primary me-2"></i> Upcoming Events</h3>
                    <a href="/events.php" class="text-decoration-none small fw-semibold text-primary">Full Calendar <i class="bi bi-arrow-right"></i></a>
                </div>

                <div class="d-flex flex-column gap-3">
                    <?php if (empty($upcomingEvents)): ?>
                        <div class="p-4 text-center text-muted card border-dashed">No upcoming events scheduled.</div>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $ev): ?>
                            <div class="card p-3 ccms-card border-start border-4 border-primary">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="event-date-box flex-shrink-0">
                                        <span class="small text-uppercase"><?= e(date('M', strtotime($ev['event_date']))) ?></span>
                                        <span class="fs-4"><?= e(date('d', strtotime($ev['event_date']))) ?></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small text-muted"><i class="bi bi-building me-1"></i> <?= e($ev['club_name']) ?></span>
                                            <?= get_status_badge($ev['status']) ?>
                                        </div>
                                        <h6 class="fw-bold mb-1"><?= e($ev['title']) ?></h6>
                                        <p class="text-secondary small mb-2"><i class="bi bi-geo-alt text-danger me-1"></i> <?= e($ev['venue']) ?></p>
                                        <?php if (!empty($ev['registration_link'])): ?>
                                            <a href="<?= e($ev['registration_link']) ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 py-1">
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
        </div>
    </div>
</section>

<!-- Campus Testimonials & Student Voices -->
<section class="py-5">
    <div class="container">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-2 mb-2 fw-semibold">Student Voices</span>
            <h2 class="fw-bold display-6 mb-2">What Our Student Leaders Say</h2>
            <p class="text-secondary">Hear from club presidents and active members on how CCMS transforms campus engagement.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card h-100">
                    <div class="d-flex align-items-center gap-1 text-warning mb-3">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="text-secondary small mb-4">"CCMS made onboarding our 2026 DSA bootcamp participants seamless. Over 150 students registered within 48 hours without any manual paperwork."</p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px;">AS</div>
                        <div>
                            <h6 class="fw-bold mb-0">Aarav Sharma</h6>
                            <span class="small text-muted">President, GeeksforGeeks Chapter</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="testimonial-card h-100">
                    <div class="d-flex align-items-center gap-1 text-warning mb-3">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="text-secondary small mb-4">"Finding technical and robotics clubs used to depend on WhatsApp group links. Now every club profile is accessible, clean, and verified."</p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px;">SK</div>
                        <div>
                            <h6 class="fw-bold mb-0">Sneha Kulkarni</h6>
                            <span class="small text-muted">Lead, Robotics & AI Society</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="testimonial-card h-100">
                    <div class="d-flex align-items-center gap-1 text-warning mb-3">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="text-secondary small mb-4">"The Super Admin console gives the college administration complete visibility into event frequencies, student engagement, and governance."</p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px;">RV</div>
                        <div>
                            <h6 class="fw-bold mb-0">Dr. Rajesh Verma</h6>
                            <span class="small text-muted">Faculty Coordinator (Super Admin)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Floating Back to Top Button -->
<button id="backToTopBtn" class="fab-back-to-top" title="Back to Top">
    <i class="bi bi-arrow-up fs-4"></i>
</button>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
