<?php
$pageTitle = "About Platform | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="py-5 bg-body-tertiary border-bottom">
    <div class="container text-center">
        <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-2 mb-3 fw-semibold">Platform Vision</span>
        <h1 class="display-5 fw-bold mb-3">About College Club Management System</h1>
        <p class="lead text-secondary max-w-2xl mx-auto">
            CCMS is built to serve as the unified digital backbone for all student clubs at UIT—connecting students, club leads, and college administrators seamlessly.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card p-4 h-100 ccms-card text-center">
                <div class="bg-primary-subtle text-primary rounded-circle mx-auto p-3 mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-search fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">For Students</h5>
                <p class="text-secondary small mb-0">Discover clubs that align with your career goals and creative passions. Browse event schedules, apply for open positions, and register for workshops in one click.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 ccms-card text-center">
                <div class="bg-primary-subtle text-primary rounded-circle mx-auto p-3 mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-speedometer2 fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">For Club Leadership</h5>
                <p class="text-secondary small mb-0">Self-service management dashboard to broadcast announcements, maintain your roster, post activity blogs, and organize high-impact events without technical overhead.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 ccms-card text-center">
                <div class="bg-primary-subtle text-primary rounded-circle mx-auto p-3 mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-shield-check fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">For Administration</h5>
                <p class="text-secondary small mb-0">Super Admin governance console with live campus analytics, content moderation queues, user management, and security audit logs for complete institutional oversight.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
