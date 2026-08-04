/**
 * Universal Layout Loader Script (ClubHub UIT)
 * Dynamically fetches and imports universal header and footer HTML components.
 */

document.addEventListener('DOMContentLoaded', () => {
    const assetBase = window.UITSitePath ? window.UITSitePath.getAssetBase() : '';

    // 1. Load Universal Header
    const headerPlaceholder = document.getElementById('header-placeholder');
    if (headerPlaceholder) {
        if (window.UITSkeletonLoader && !headerPlaceholder.innerHTML.trim()) {
            headerPlaceholder.innerHTML = window.UITSkeletonLoader.getHeaderSkeleton();
        }
        fetch(`${assetBase}includes/header.html`)
            .then(res => res.text())
            .then(html => {
                if (window.UITSkeletonLoader) {
                    window.UITSkeletonLoader.replaceWithContent(headerPlaceholder, html);
                } else {
                    headerPlaceholder.innerHTML = html;
                }
                if (window.UITSitePath) window.UITSitePath.fixRootRelativeLinks(headerPlaceholder);
                highlightActiveNav();
            })
            .catch(err => console.error('Error loading universal header:', err));
    } else {
        highlightActiveNav();
    }

    // 2. Load Universal Footer
    const footerPlaceholder = document.getElementById('footer-placeholder');
    if (footerPlaceholder) {
        if (window.UITSkeletonLoader && !footerPlaceholder.innerHTML.trim()) {
            footerPlaceholder.innerHTML = window.UITSkeletonLoader.getFooterSkeleton();
        }
        fetch(`${assetBase}includes/footer.html`)
            .then(res => res.text())
            .then(html => {
                if (window.UITSkeletonLoader) {
                    window.UITSkeletonLoader.replaceWithContent(footerPlaceholder, html);
                } else {
                    footerPlaceholder.innerHTML = html;
                }
                if (window.UITSitePath) window.UITSitePath.fixRootRelativeLinks(footerPlaceholder);
                initFooterEvents();
            })
            .catch(err => console.error('Error loading universal footer:', err));
    }
});

function highlightActiveNav() {
    const path = window.location.pathname.toLowerCase().replace(/\/+/g, '/');
    const navLinks = document.querySelectorAll('.nav-link-clubhub');
    
    navLinks.forEach(link => link.classList.remove('active'));

    if (path.includes('clubs.html') || path.includes('club-detail') || path.includes('developers-club') || path.includes('cultural-club')) {
        const el = document.getElementById('nav-clubs');
        if (el) el.classList.add('active');
    } else if (path.includes('events.html') || path.includes('event-detail') || path.includes('tech-events') || path.includes('cultural-events')) {
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
