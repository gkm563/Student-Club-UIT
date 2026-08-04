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
    const rawPath = window.location.pathname.toLowerCase();
    const navLinks = document.querySelectorAll('.nav-link-clubhub');
    if (!navLinks.length) return;
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        link.removeAttribute('aria-current');
    });

    let activeEl = null;

    if (rawPath.includes('clubs.html') || rawPath.includes('club-detail')) {
        activeEl = document.getElementById('nav-clubs');
    } else if (rawPath.includes('events.html') || rawPath.includes('event-detail')) {
        activeEl = document.getElementById('nav-events');
    } else if (rawPath.includes('gallery.html')) {
        activeEl = document.getElementById('nav-gallery');
    } else if (rawPath.includes('about.html')) {
        activeEl = document.getElementById('nav-about');
    } else if (rawPath.includes('contact.html')) {
        activeEl = document.getElementById('nav-contact');
    } else if (rawPath.endsWith('/') || rawPath.includes('index.html') || rawPath.endsWith('/uit') || rawPath.endsWith('/uit/')) {
        activeEl = document.getElementById('nav-home');
    }

    if (!activeEl) {
        const file = rawPath.split('/').filter(Boolean).pop() || 'index.html';
        navLinks.forEach(link => {
            const href = (link.getAttribute('href') || '').toLowerCase();
            if (href && (href === file || href.includes(file))) {
                activeEl = link;
            }
        });
    }

    if (activeEl) {
        activeEl.classList.add('active');
        activeEl.setAttribute('aria-current', 'page');
    } else {
        const homeEl = document.getElementById('nav-home');
        if (homeEl) {
            homeEl.classList.add('active');
            homeEl.setAttribute('aria-current', 'page');
        }
    }
}

// Retries to ensure dynamic header insertion never misses highlighting
document.addEventListener('DOMContentLoaded', () => {
    highlightActiveNav();
    setTimeout(highlightActiveNav, 150);
    setTimeout(highlightActiveNav, 500);
});
window.addEventListener('load', highlightActiveNav);

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
