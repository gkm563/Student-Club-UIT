/**
 * Universal Layout Loader Script (ClubHub UIT)
 * Dynamically fetches and imports universal header and footer HTML components.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Load Universal Header
    const headerPlaceholder = document.getElementById('header-placeholder');
    if (headerPlaceholder) {
        fetch('includes/header.html')
            .then(res => res.text())
            .then(html => {
                headerPlaceholder.innerHTML = html;
                highlightActiveNav();
            })
            .catch(err => console.error('Error loading universal header:', err));
    } else {
        highlightActiveNav();
    }

    // 2. Load Universal Footer
    const footerPlaceholder = document.getElementById('footer-placeholder');
    if (footerPlaceholder) {
        fetch('includes/footer.html')
            .then(res => res.text())
            .then(html => {
                footerPlaceholder.innerHTML = html;
                initFooterEvents();
            })
            .catch(err => console.error('Error loading universal footer:', err));
    }
});

function highlightActiveNav() {
    const path = window.location.pathname.toLowerCase();
    const navLinks = document.querySelectorAll('.nav-link-clubhub');
    
    navLinks.forEach(link => link.classList.remove('active'));

    if (path.includes('clubs.html') || path.includes('club-detail')) {
        const el = document.getElementById('nav-clubs');
        if (el) el.classList.add('active');
    } else if (path.includes('events.html') || path.includes('event-detail')) {
        const el = document.getElementById('nav-events');
        if (el) el.classList.add('active');
    } else if (path.includes('gallery.html')) {
        const el = document.getElementById('nav-gallery');
        if (el) el.classList.add('active');
    } else if (path.includes('about.html')) {
        const el = document.getElementById('nav-about');
        if (el) el.classList.add('active');
    } else if (path.includes('contact.html')) {
        const el = document.getElementById('nav-contact');
        if (el) el.classList.add('active');
    } else {
        const el = document.getElementById('nav-home');
        if (el) el.classList.add('active');
    }
}

function initFooterEvents() {
    const backToTopBtn = document.getElementById('backToTopBtn');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}
