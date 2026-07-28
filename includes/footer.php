<?php
/**
 * Universal Footer Component (ClubHub UIT)
 */
if (!isset($assetPrefix)) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#/(admin|club)/#i', $script)) {
        $assetPrefix = '../';
    } else {
        $assetPrefix = '';
    }
}
?>
<!-- Vibrant Ultra-Responsive Official Universal Footer (ClubHub UIT) -->
<footer class="footer-clubhub">
    <div class="container">
        <!-- Top Glassmorphism Newsletter/Announcements Card -->
        <div class="footer-newsletter-card">
            <div class="row align-items-center g-3">
                <div class="col-lg-6 text-center text-lg-start">
                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2 fw-bold small">STAY CONNECTED</span>
                    <h4 class="fw-bold text-white mb-1">Subscribe to Campus Club Notices</h4>
                    <p class="text-white-80 small mb-0">Get official updates on upcoming hackathons, fests, workshops & club recruitments.</p>
                </div>
                <div class="col-lg-6">
                    <form class="d-flex flex-column flex-sm-row gap-2" onsubmit="event.preventDefault(); alert('Subscribed to official UIT campus notifications!');">
                        <input type="email" class="form-control text-white rounded-pill px-4 py-2-5" placeholder="Enter your college email address..." required style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18);">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold text-white shadow-sm flex-shrink-0">
                            Subscribe <i class="bi bi-bell-fill ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 text-center text-lg-start">
            <!-- Brand & Official Info Column -->
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-3 mb-3 justify-content-center justify-content-lg-start">
                    <img src="<?= $assetPrefix ?>assets/United Logo.webp" alt="United Group Logo" class="brand-logo-img-footer">
                    <div>
                        <span class="brand-logo-text fs-4 d-block text-white">ClubHub UIT</span>
                        <span class="small text-white-50">United Institute of Technology</span>
                    </div>
                </div>
                <p class="small text-white-80 max-w-sm mb-4 mx-auto mx-lg-0">
                    Official Student Clubs & Co-Curricular Governance Portal. Facilitating technical study jams, cultural fests, sports events, and community drives at UIT Prayagraj.
                </p>
                <div class="d-flex gap-2 justify-content-center justify-content-lg-start">
                    <a href="#" class="social-icon-btn fb" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-icon-btn insta" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://linkedin.com/in/gkm563" target="_blank" rel="noopener noreferrer" class="social-icon-btn linkin" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="social-icon-btn yt" title="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="social-icon-btn tw" title="Twitter / X"><i class="bi bi-twitter-x"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-6 col-lg-2 mb-4 mb-lg-0 px-2">
                <div class="footer-title">Quick Links</div>
                <ul class="footer-links">
                    <li><a href="<?= $assetPrefix ?>index.html"><i class="bi bi-house-door-fill text-warning me-1"></i> Home</a></li>
                    <li><a href="<?= $assetPrefix ?>clubs.html"><i class="bi bi-collection-fill text-primary me-1"></i> Clubs Directory</a></li>
                    <li><a href="<?= $assetPrefix ?>events.html"><i class="bi bi-calendar-event-fill text-info me-1"></i> Campus Events</a></li>
                    <li><a href="<?= $assetPrefix ?>gallery.html"><i class="bi bi-images text-success me-1"></i> Moments Gallery</a></li>
                </ul>
            </div>

            <!-- Campus Governance -->
            <div class="col-6 col-lg-2 mb-4 mb-lg-0 px-2">
                <div class="footer-title">Governance</div>
                <ul class="footer-links">
                    <li><a href="<?= $assetPrefix ?>about.html"><i class="bi bi-info-circle-fill me-1" style="color:#38bdf8;"></i> About Portal</a></li>
                    <li><a href="<?= $assetPrefix ?>contact.html#proposal"><i class="bi bi-file-earmark-plus-fill text-warning me-1"></i> Submit Proposal</a></li>
                    <li><a href="<?= $assetPrefix ?>admin/dean-login.php"><i class="bi bi-shield-lock-fill me-1" style="color:#c084fc;"></i> Dean Admin Portal</a></li>
                    <li><a href="<?= $assetPrefix ?>club-login.php"><i class="bi bi-person-fill-lock text-success me-1"></i> Club Lead Login</a></li>
                </ul>
            </div>

            <!-- Official Campus Contact -->
            <div class="col-lg-4">
                <div class="footer-title">Campus Contact</div>
                <ul class="list-unstyled space-y-3 text-white-80 small mb-0">
                    <li class="d-flex align-items-start gap-2 mb-2 justify-content-center justify-content-lg-start">
                        <i class="bi bi-geo-alt-fill text-danger mt-1"></i>
                        <span>United Institute of Technology Naini, UPSIDC Industrial Area, Naini, Prayagraj, UP 211010</span>
                    </li>
                    <li class="d-flex align-items-center gap-2 mb-2 justify-content-center justify-content-lg-start">
                        <i class="bi bi-envelope-fill text-primary"></i>
                        <span>dsw@uit.edu / info@uit.edu</span>
                    </li>
                    <li class="d-flex align-items-center gap-2 justify-content-center justify-content-lg-start">
                        <i class="bi bi-telephone-fill text-success"></i>
                        <span>+91 98765 43210 (Student Activity Center)</span>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="border-white-10 my-4" style="border-color: rgba(255,255,255,0.15) !important;">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-white-80 small gap-3 text-center text-md-start">
            <div>&copy; 2026 United Institute of Technology (UIT). All rights reserved.</div>
            <div class="developer-credit-badge">
                <span>Made with <i class="bi bi-heart-fill pulse-heart"></i> & dedication by <a href="https://linkedin.com/in/gkm563" target="_blank" rel="noopener noreferrer" class="developer-link">GKM <i class="bi bi-linkedin ms-1"></i></a></span>
            </div>
            <button id="backToTopBtn" class="btn btn-sm btn-light rounded-circle p-2 shadow-lg" title="Back to Top">
                <i class="bi bi-arrow-up text-primary fs-6"></i>
            </button>
        </div>
    </div>
</footer>
