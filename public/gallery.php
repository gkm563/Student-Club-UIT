<?php
$pageTitle = "Media Gallery | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = Database::getConnection();

// Sample default media gallery showcase items
$galleryItems = [
    ['title' => 'CodeBlitz 2026 Opening Ceremony', 'club' => 'GeeksforGeeks Chapter', 'image' => 'assets/images/default-event.jpg', 'tag' => 'Hackathon'],
    ['title' => 'DSA Placement Prep Workshop', 'club' => 'GeeksforGeeks Chapter', 'image' => 'assets/images/default-cover.jpg', 'tag' => 'Workshop'],
    ['title' => 'Autonomous Drone Testing', 'club' => 'Robotics & AI Society', 'image' => 'assets/images/default-event.jpg', 'tag' => 'Robotics'],
    ['title' => 'Kala-Kriti Annual Fest Auditions', 'club' => 'Kala-Kriti Cultural Club', 'image' => 'assets/images/default-cover.jpg', 'tag' => 'Cultural']
];
?>

<div class="py-4 bg-body-tertiary border-bottom">
    <div class="container">
        <h1 class="fw-bold mb-1">Campus Life Gallery</h1>
        <p class="text-secondary mb-0">Visual memories from hackathons, cultural festivals, tech exhibitions, and sports meets.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <?php foreach ($galleryItems as $item): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 ccms-card overflow-hidden">
                    <img src="/<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" class="card-img-top" style="height: 180px; object-fit: cover;">
                    <div class="card-body">
                        <span class="badge bg-primary-subtle text-primary border rounded-pill small mb-2"><?= e($item['tag']) ?></span>
                        <h6 class="fw-bold mb-1"><?= e($item['title']) ?></h6>
                        <small class="text-muted"><i class="bi bi-diagram-3 me-1"></i> <?= e($item['club']) ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
