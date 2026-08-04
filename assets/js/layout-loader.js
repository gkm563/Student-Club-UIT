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
    const path = window.location.pathname.toLowerCase();
    const navLinks = document.querySelectorAll('.nav-link-clubhub');
    if (!navLinks.length) return;
    
    navLinks.forEach(link => link.classList.remove('active'));

    let activeEl = null;

    if (path.includes('clubs.html') || path.includes('club-detail')) {
        activeEl = document.getElementById('nav-clubs');
    } else if (path.includes('events.html') || path.includes('event-detail')) {
        activeEl = document.getElementById('nav-events');
    } else if (path.includes('gallery.html')) {
        activeEl = document.getElementById('nav-gallery');
    } else if (path.includes('about.html')) {
        activeEl = document.getElementById('nav-about');
    } else if (path.includes('contact.html')) {
        activeEl = document.getElementById('nav-contact');
    } else if (path.endsWith('/') || path.includes('index.html') || path.endsWith('/uit')) {
        activeEl = document.getElementById('nav-home');
    }

    if (!activeEl) {
        const file = path.split('/').pop() || 'index.html';
        navLinks.forEach(link => {
            const href = (link.getAttribute('href') || '').toLowerCase();
            if (href && (href === file || href.includes(file))) {
                activeEl = link;
            }
        });
    }

    if (activeEl) {
        activeEl.classList.add('active');
    } else {
        const homeEl = document.getElementById('nav-home');
        if (homeEl) homeEl.classList.add('active');
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
