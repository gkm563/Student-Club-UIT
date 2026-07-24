<?php
$pageTitle = "Contact & Support | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check honeypot field
    if (!empty($_POST['website_hp'])) {
        // Bot detected, silently accept
        $success = "Thank you! Your message has been sent successfully.";
    } elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid. Please refresh and try again.";
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = "All form fields are required.";
        } else {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("INSERT INTO contact_messages (id, name, email, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([generate_uuid(), $name, $email, $subject, $message]);
                $success = "Your inquiry has been submitted to the Student Activity Center! We will respond shortly.";
            } catch (Exception $e) {
                $error = "Failed to send message. Please try again later.";
            }
        }
    }
}
?>

<div class="py-4 bg-body-tertiary border-bottom">
    <div class="container">
        <h1 class="fw-bold mb-1">Contact & Support</h1>
        <p class="text-secondary mb-0">Have questions about starting a new club or platform technical support? Reach out to us.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Contact Form Column -->
        <div class="col-lg-6">
            <div class="card p-4 ccms-card">
                <h4 class="fw-bold mb-3">Send a Message</h4>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success rounded-3 small mb-3"><i class="bi bi-check-circle-fill me-1"></i> <?= e($success) ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form action="/contact.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <!-- Honeypot Field -->
                    <div style="display:none;">
                        <input type="text" name="website_hp" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Your Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. rahul@uit.edu" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Inquiry topic..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Message</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="How can we help you?" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold w-100">
                        <i class="bi bi-send me-1"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- FAQ Accordion Column -->
        <div class="col-lg-6">
            <h4 class="fw-bold mb-3">Frequently Asked Questions</h4>

            <div class="accordion accordion-flush card ccms-card overflow-hidden" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I register or join a club?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body small text-secondary">
                            Visit the club detail page for the club you are interested in. If recruitment is open, click the "Join Club Now" button to access their application form.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            How can our team register a new club on CCMS?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body small text-secondary">
                            New club proposals must be submitted through the Student Activity Center (SAC) office. Once approved, the Super Admin will create your club profile and assign credentials to your Club Admin.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Who should I contact for login assistance?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body small text-secondary">
                            Club Admins who need password resets or permission updates can email <a href="mailto:clubs@uit.edu">clubs@uit.edu</a> or contact the Super Admin office directly.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
