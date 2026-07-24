<?php
$pageTitle = "All Clubs Directory | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = Database::getConnection();

$categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
$selectedCategory = $_GET['category'] ?? 'all';
$selectedStatus   = $_GET['status'] ?? 'all';
$searchQuery      = trim($_GET['search'] ?? '');

// Initial Server-side fetch
$where = ["c.deleted_at IS NULL"];
$params = [];

if ($selectedCategory !== 'all') {
    $where[] = "cat.slug = ?";
    $params[] = $selectedCategory;
}
if ($selectedStatus !== 'all') {
    $where[] = "c.status = ?";
    $params[] = $selectedStatus;
}
if (!empty($searchQuery)) {
    $where[] = "(c.name LIKE ? OR c.short_name LIKE ? OR c.tagline LIKE ?)";
    $term = "%$searchQuery%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}

$whereSql = implode(' AND ', $where);
$stmt = $db->prepare("
    SELECT c.*, cat.name AS category_name, cat.slug AS category_slug, cat.icon AS category_icon
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    WHERE $whereSql
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$clubs = $stmt->fetchAll();
?>

<div class="py-4 bg-body-tertiary border-bottom">
    <div class="container">
        <h1 class="fw-bold mb-1">Campus Club Directory</h1>
        <p class="text-secondary mb-0">Browse, filter, and discover student organizations across technical, cultural, and sports domains.</p>
    </div>
</div>

<div class="container py-4">
    <!-- Filter Toolbar -->
    <div class="card p-3 mb-4 ccms-card">
        <div class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="dirSearchInput" class="form-control border-start-0" placeholder="Filter clubs by name or tagline..." value="<?= e($searchQuery) ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select id="statusFilterSelect" class="form-select">
                    <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="recruiting" <?= $selectedStatus === 'recruiting' ? 'selected' : '' ?>>Recruiting Now</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <select id="sortSelect" class="form-select">
                    <option value="newest">Sort by: Newest First</option>
                    <option value="alphabetical">Sort by: Name (A-Z)</option>
                </select>
            </div>
        </div>

        <!-- Category Pills -->
        <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
            <button class="category-pill filter-cat-btn <?= $selectedCategory === 'all' ? 'active' : '' ?>" data-slug="all">
                <i class="bi bi-grid-fill"></i> All Categories
            </button>
            <?php foreach ($categories as $cat): ?>
                <button class="category-pill filter-cat-btn <?= $selectedCategory === $cat['slug'] ? 'active' : '' ?>" data-slug="<?= e($cat['slug']) ?>">
                    <i class="bi <?= e($cat['icon']) ?>"></i> <?= e($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Clubs Grid -->
    <div id="clubsGrid" class="row g-4">
        <?php if (empty($clubs)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <h5 class="fw-bold">No clubs found</h5>
                <p class="text-secondary small">Try adjusting your category filter or search query.</p>
            </div>
        <?php else: ?>
            <?php foreach ($clubs as $club): ?>
                <div class="col-md-6 col-lg-4 club-card-item">
                    <div class="card h-100 ccms-card">
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
                            <p class="text-secondary small mb-3">
                                <?= e($club['tagline']) ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="small text-muted"><i class="bi bi-building me-1"></i> Est. <?= e($club['founded_year']) ?></span>
                                <a href="/club-detail.php?slug=<?= e($club['slug']) ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                    View Profile <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentCategory = '<?= e($selectedCategory) ?>';
    const searchInput = document.getElementById('dirSearchInput');
    const statusSelect = document.getElementById('statusFilterSelect');
    const sortSelect = document.getElementById('sortSelect');
    const clubsGrid = document.getElementById('clubsGrid');
    const catBtns = document.querySelectorAll('.filter-cat-btn');

    catBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            catBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCategory = btn.getAttribute('data-slug');
            fetchClubs();
        });
    });

    let searchTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(fetchClubs, 300);
    });
    statusSelect.addEventListener('change', fetchClubs);
    sortSelect.addEventListener('change', fetchClubs);

    function fetchClubs() {
        const cat = currentCategory;
        const status = statusSelect.value;
        const search = searchInput.value.trim();
        const sort = sortSelect.value;

        const url = `/api/filter-clubs.php?category=${encodeURIComponent(cat)}&status=${encodeURIComponent(status)}&search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.clubs && data.clubs.length > 0) {
                    let html = '';
                    data.clubs.forEach(club => {
                        html += `
                            <div class="col-md-6 col-lg-4 club-card-item">
                                <div class="card h-100 ccms-card">
                                    <div class="club-card-banner" style="background-image: url('/${escapeHtml(club.cover_image)}');">
                                        <img src="/${escapeHtml(club.logo)}" alt="${escapeHtml(club.name)}" class="club-card-logo-overlay">
                                    </div>
                                    <div class="card-body pt-5">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-primary-subtle text-primary border rounded-pill small">
                                                <i class="bi ${escapeHtml(club.category_icon)} me-1"></i> ${escapeHtml(club.category_name)}
                                            </span>
                                            ${club.status_badge}
                                        </div>
                                        <h5 class="fw-bold card-title mb-1">
                                            <a href="/club-detail.php?slug=${encodeURIComponent(club.slug)}" class="text-decoration-none text-body">
                                                ${escapeHtml(club.name)}
                                            </a>
                                        </h5>
                                        <p class="text-secondary small mb-3">${escapeHtml(club.tagline || '')}</p>
                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                            <span class="small text-muted"><i class="bi bi-building me-1"></i> Est. ${escapeHtml(club.founded_year)}</span>
                                            <a href="/club-detail.php?slug=${encodeURIComponent(club.slug)}" class="btn btn-sm btn-outline-primary rounded-pill">
                                                View Profile <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    clubsGrid.innerHTML = html;
                } else {
                    clubsGrid.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <h5 class="fw-bold">No clubs match criteria</h5>
                            <p class="text-secondary small">Try selecting another category or clearing search parameters.</p>
                        </div>
                    `;
                }
            });
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
