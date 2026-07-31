<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Redirect Super Admin (Dean Sir) to Super Dashboard
$userRole = get_current_user_role();
if ($userRole === 'super_admin') {
    header('Location: super/index.php');
    exit;
}
if ($userRole === 'club_admin') {
    $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: ../club/create-event.php' . $qs);
    exit;
}

$db = Database::getConnection();

// Fetch assigned club for this user
$stmt = $db->prepare("
    SELECT c.*, cat.name as category_name
    FROM clubs c
    JOIN club_admins ca ON ca.club_id = c.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE ca.user_id = ?
    LIMIT 1
");
$stmt->execute([get_current_user_id()]);
$club = $stmt->fetch();

if (!$club) {
    echo "No club assigned to your account. Please contact Dean Sir (admin@uit.edu).";
    exit;
}

$success = '';
$error = '';

// Handle Create Event Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create'])) {
    $title              = trim($_POST['title'] ?? '');
    $tagline            = trim($_POST['tagline'] ?? '');
    $event_type         = trim($_POST['event_type'] ?? 'Hands-on Workshop');
    $description        = trim($_POST['description'] ?? '');
    $venue              = trim($_POST['venue'] ?? '');
    $event_date         = $_POST['event_date'] ?? '';
    $reg_link           = trim($_POST['registration_link'] ?? 'contact.html');
    $status             = $_POST['status'] ?? 'upcoming';
    $outcomes_summary   = trim($_POST['outcomes_summary'] ?? '');
    $speaker_name       = trim($_POST['speaker_name'] ?? '');
    $speaker_designation= trim($_POST['speaker_designation'] ?? '');
    $agenda_timeline    = trim($_POST['agenda_timeline'] ?? '');
    $target_audience    = trim($_POST['target_audience'] ?? 'All UIT Departments & Years');
    $bannerUrl          = trim($_POST['banner'] ?? 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop');

    // Process uploaded file if provided
    $uploadedBanner = upload_image_file($_FILES['banner_file'] ?? null, 'events', $bannerUrl);
    $banner = $uploadedBanner ?: $bannerUrl;

    if (empty($title) || empty($venue) || empty($event_date)) {
        $error = "Event Title, Venue/Location, and Date & Time are mandatory.";
    } else {
        try {
            $eventId = 'evt_' . bin2hex(random_bytes(4));
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)) . '-' . rand(100, 999);
            
            $stmtIns = $db->prepare("
                INSERT INTO events (
                    id, club_id, title, tagline, slug, banner, description, venue, event_date, registration_link, status,
                    event_type, outcomes_summary, speaker_name, speaker_designation, agenda_timeline, target_audience
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtIns->execute([
                $eventId, $club['id'], $title, $tagline, $slug, $banner, $description, $venue, $event_date, $reg_link, $status,
                $event_type, $outcomes_summary, $speaker_name, $speaker_designation, $agenda_timeline, $target_audience
            ]);
            
            header('Location: events.php?msg=' . urlencode("Event '$title' published successfully!"));
            exit;
        } catch (Exception $e) {
            $error = "Failed to publish event: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Event | <?= htmlspecialchars($club['name']) ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f8fafc; }
        .admin-sidebar { width: 260px; min-height: 100vh; background: #0b0f19; color: #fff; }
        .admin-nav-link {
            color: rgba(255,255,255,0.65);
            padding: 11px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            margin-bottom: 2px;
        }
        .admin-nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }
        .admin-nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(3px); }
        .admin-nav-link.active { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
        .creator-card { border-radius: 16px; border: 1px solid #e2e8f0; background: #ffffff; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Master Sidebar -->
    <?php require_once __DIR__ . '/../includes/club_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="events.php" class="text-primary fw-bold text-decoration-none small d-inline-block mb-2">&larr; Back to All Events</a>
                <h2 class="fw-bold mb-1"><i class="bi bi-calendar-plus-fill text-primary me-2"></i> Create New Campus Event</h2>
                <p class="text-secondary small mb-0">Publish workshops, hackathons, and tech talks for <?= htmlspecialchars($club['name']) ?></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="submit" form="createEventForm" class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
                    <i class="bi bi-rocket-takeoff-fill me-1"></i> Publish Event Live →
                </button>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="create-event.php" method="POST" enctype="multipart/form-data" id="createEventForm">
            <input type="hidden" name="action_create" value="1">
            
            <div class="row g-4">
                <div class="col-lg-8">
                    
                    <!-- Section 1: Core Information -->
                    <div class="creator-card p-4 p-md-5 mb-4 shadow-sm">
                        <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class="bi bi-info-circle text-primary me-2"></i> 1. Core Event Info</h5>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-dark">Event Title *</label>
                                <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Google Cloud Study Jam 2026" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Category / Type *</label>
                                <select name="event_type" class="form-select rounded-3">
                                    <option value="Hands-on Workshop" selected>🛠️ Hands-on Workshop</option>
                                    <option value="Competitive Hackathon">🏆 Competitive Hackathon</option>
                                    <option value="Tech Talk / Webinar">🎙️ Tech Talk / Webinar</option>
                                    <option value="Coding Contest">💻 Coding Contest</option>
                                    <option value="Orientation Session">🚀 Orientation Session</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Event Subtitle / Tagline</label>
                                <input type="text" name="tagline" class="form-control rounded-3" placeholder="e.g. Master Kubernetes & Cloud Native Architecture with Industry Mentors">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Schedule & Venue -->
                    <div class="creator-card p-4 p-md-5 mb-4 shadow-sm">
                        <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class="bi bi-clock-history text-primary me-2"></i> 2. Schedule, Status & Venue</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Date & Start Time *</label>
                                <input type="datetime-local" name="event_date" class="form-control rounded-3" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Publication Status *</label>
                                <select name="status" class="form-select rounded-3">
                                    <option value="upcoming" selected>📅 Upcoming (Registration Open)</option>
                                    <option value="ongoing">🔴 Live Now (Ongoing Session)</option>
                                    <option value="completed">🏆 Completed (Concluded)</option>
                                    <option value="draft">🔒 Draft (Private)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Venue / Hall Location *</label>
                                <input type="text" name="venue" class="form-control rounded-3" placeholder="e.g. Computer Lab 1, Ground Floor, UIT" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Target Audience & Eligibility</label>
                                <input type="text" name="target_audience" class="form-control rounded-3" value="All UIT Departments & Years" placeholder="e.g. CSE 1st-4th Year Students">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Speaker & Swags -->
                    <div class="creator-card p-4 p-md-5 mb-4 shadow-sm">
                        <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class="bi bi-award text-primary me-2"></i> 3. Keynote Speaker & Swag Rewards</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Rewards, Cash Prizes & Swags</label>
                                <input type="text" name="outcomes_summary" class="form-control rounded-3" placeholder="e.g. ₹15,000 Cash Prize, GFG Badges & USC UIT Verified Certificates">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Keynote Speaker Name</label>
                                <input type="text" name="speaker_name" class="form-control rounded-3" placeholder="e.g. Prof. Aniket Verma">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Speaker Title / Designation</label>
                                <input type="text" name="speaker_designation" class="form-control rounded-3" placeholder="e.g. Head of AI Research, TechCorp">
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Detailed Writeup & Agenda -->
                    <div class="creator-card p-4 p-md-5 mb-4 shadow-sm">
                        <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom"><i class="bi bi-journal-text text-primary me-2"></i> 4. Detailed Description & Agenda Timeline</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Full Event Description</label>
                                <textarea name="description" class="form-control rounded-3" rows="5" placeholder="Provide complete information about session topics, prerequisites, tech stack, and what students will build..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Session Agenda Timeline</label>
                                <textarea name="agenda_timeline" class="form-control rounded-3" rows="4" placeholder="09:00 AM - Check-in & Breakfast&#10;10:00 AM - Keynote Address&#10;11:30 AM - Hands-on Lab Session&#10;04:00 PM - Certificate Distribution"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">External Registration Link / Google Form</label>
                                <input type="text" name="registration_link" class="form-control rounded-3" value="contact.html" placeholder="https://forms.google.com/...">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Banner Upload & Live Tips -->
                <div class="col-lg-4">
                    <div class="creator-card p-4 mb-4 shadow-sm position-sticky" style="top: 24px;">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-image text-primary me-2"></i> Cover Banner Poster</h5>
                        <p class="text-secondary small mb-3">Upload a high-res poster (PNG, JPG) or paste an image URL.</p>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Upload Poster File (PC) *</label>
                            <input type="file" name="banner_file" class="form-control rounded-3" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Or Paste Image URL</label>
                            <input type="url" name="banner" class="form-control rounded-3" placeholder="https://images.unsplash.com/photo-...">
                        </div>

                        <hr class="my-4">
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold small text-dark mb-1"><i class="bi bi-lightbulb text-warning me-1"></i> Pro Tip for Club Leads</h6>
                            <p class="small text-muted mb-0" style="font-size: 0.8rem;">
                                Once published, your event will automatically appear on the public <strong class="text-dark">Events Directory</strong> and your club's official portal page.
                            </p>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill w-100 py-3 fw-bold shadow-md" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
                                <i class="bi bi-rocket-takeoff-fill me-1"></i> Publish Event Live
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
