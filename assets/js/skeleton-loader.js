/**
 * Universal Skeleton Loader Engine — USC UIT
 * Provides reusable skeleton placeholders, HTML generators, image loading shimmer handlers,
 * and smooth transitions for all sections across all pages.
 */

window.UITSkeletonLoader = (function () {

    /**
     * Generate dynamic club card skeleton HTML
     */
    function getClubCardSkeleton(count = 3) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
            <div class="col-lg-4 col-md-6 mb-4 skeleton-fade-in">
                <div class="skeleton-card h-100">
                    <div class="skeleton skeleton-img mb-3" style="height: 140px;"></div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="skeleton skeleton-avatar"></div>
                        <div class="flex-grow-1">
                            <div class="skeleton skeleton-title mb-1" style="width: 70%; height: 1.2rem;"></div>
                            <div class="skeleton skeleton-text skeleton-text-sm" style="width: 40%;"></div>
                        </div>
                    </div>
                    <div class="skeleton skeleton-text mb-2"></div>
                    <div class="skeleton skeleton-text skeleton-text-sm mb-4" style="width: 85%;"></div>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light">
                        <div class="skeleton skeleton-badge"></div>
                        <div class="skeleton skeleton-btn" style="width: 90px; height: 2rem;"></div>
                    </div>
                </div>
            </div>`;
        }
        return html;
    }

    /**
     * Generate event card skeleton HTML
     */
    function getEventCardSkeleton(count = 3) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
            <div class="col-lg-4 col-md-6 mb-4 skeleton-fade-in">
                <div class="skeleton-card h-100">
                    <div class="skeleton skeleton-hero-banner mb-3" style="height: 160px;"></div>
                    <div class="d-flex gap-2 mb-2">
                        <div class="skeleton skeleton-badge"></div>
                        <div class="skeleton skeleton-badge" style="width: 60px;"></div>
                    </div>
                    <div class="skeleton skeleton-title mb-2" style="width: 80%;"></div>
                    <div class="skeleton skeleton-text mb-2"></div>
                    <div class="skeleton skeleton-text skeleton-text-sm mb-3" style="width: 70%;"></div>
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light">
                        <div class="skeleton skeleton-text" style="width: 100px; margin: 0;"></div>
                        <div class="skeleton skeleton-btn" style="width: 85px; height: 2.1rem;"></div>
                    </div>
                </div>
            </div>`;
        }
        return html;
    }

    /**
     * Generate member card skeleton HTML
     */
    function getMemberCardSkeleton(count = 4) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
            <div class="col-lg-3 col-md-4 col-6 mb-4 skeleton-fade-in">
                <div class="skeleton-card text-center p-3 h-100">
                    <div class="skeleton skeleton-avatar-lg mx-auto mb-3"></div>
                    <div class="skeleton skeleton-title mx-auto mb-2" style="width: 75%; height: 1.1rem;"></div>
                    <div class="skeleton skeleton-badge mx-auto mb-2" style="width: 100px;"></div>
                    <div class="skeleton skeleton-text skeleton-text-sm mx-auto" style="width: 60%;"></div>
                </div>
            </div>`;
        }
        return html;
    }

    /**
     * Generate gallery grid item skeleton HTML
     */
    function getGallerySkeleton(count = 6) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
            <div class="col-lg-4 col-md-6 mb-4 skeleton-fade-in">
                <div class="skeleton-card p-2 h-100">
                    <div class="skeleton skeleton-img mb-2" style="height: 220px;"></div>
                    <div class="p-2">
                        <div class="skeleton skeleton-title mb-1" style="width: 70%; height: 1rem;"></div>
                        <div class="skeleton skeleton-text skeleton-text-sm" style="width: 45%;"></div>
                    </div>
                </div>
            </div>`;
        }
        return html;
    }

    /**
     * Generate statistics counter box skeleton HTML
     */
    function getStatBoxSkeleton(count = 4) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
            <div class="col-6 col-md-3 mb-3 skeleton-fade-in">
                <div class="skeleton-stat-box text-center">
                    <div class="skeleton skeleton-title mx-auto mb-2" style="width: 50%; height: 2rem;"></div>
                    <div class="skeleton skeleton-text skeleton-text-sm mx-auto" style="width: 70%;"></div>
                </div>
            </div>`;
        }
        return html;
    }

    /**
     * Generate timeline item skeleton HTML
     */
    function getTimelineSkeleton(count = 3) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
            <div class="skeleton-timeline-item border-bottom border-light mb-3 pb-3 skeleton-fade-in">
                <div class="skeleton skeleton-avatar" style="width: 42px; height: 42px;"></div>
                <div class="flex-grow-1">
                    <div class="skeleton skeleton-title mb-1" style="width: 60%; height: 1.1rem;"></div>
                    <div class="skeleton skeleton-text mb-1"></div>
                    <div class="skeleton skeleton-text skeleton-text-sm" style="width: 40%;"></div>
                </div>
            </div>`;
        }
        return html;
    }

    /**
     * Universal Header Skeleton
     */
    function getHeaderSkeleton() {
        return `
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom border-light py-3">
            <div class="container">
                <div class="d-flex align-items-center gap-2">
                    <div class="skeleton skeleton-avatar" style="width: 40px; height: 40px;"></div>
                    <div class="skeleton skeleton-title mb-0" style="width: 120px; height: 1.4rem;"></div>
                </div>
                <div class="d-none d-lg-flex gap-4">
                    <div class="skeleton skeleton-text" style="width: 60px; height: 1rem; margin:0;"></div>
                    <div class="skeleton skeleton-text" style="width: 60px; height: 1rem; margin:0;"></div>
                    <div class="skeleton skeleton-text" style="width: 60px; height: 1rem; margin:0;"></div>
                    <div class="skeleton skeleton-text" style="width: 60px; height: 1rem; margin:0;"></div>
                </div>
                <div class="d-flex gap-2">
                    <div class="skeleton skeleton-btn" style="width: 100px; height: 2.2rem;"></div>
                </div>
            </div>
        </nav>`;
    }

    /**
     * Universal Footer Skeleton
     */
    function getFooterSkeleton() {
        return `
        <footer class="bg-dark text-white py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="skeleton skeleton-title mb-3" style="width: 160px; background: #334155;"></div>
                        <div class="skeleton skeleton-text mb-2" style="background: #334155;"></div>
                        <div class="skeleton skeleton-text mb-2" style="width: 80%; background: #334155;"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="skeleton skeleton-title mb-3" style="width: 120px; background: #334155;"></div>
                        <div class="skeleton skeleton-text mb-2" style="width: 60%; background: #334155;"></div>
                        <div class="skeleton skeleton-text mb-2" style="width: 70%; background: #334155;"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="skeleton skeleton-title mb-3" style="width: 140px; background: #334155;"></div>
                        <div class="skeleton skeleton-btn" style="width: 100%; height: 2.5rem; background: #334155;"></div>
                    </div>
                </div>
            </div>
        </footer>`;
    }

    /**
     * Auto-wrap image elements in shimmer skeleton wrappers until loaded
     */
    function initImageSkeletons() {
        const images = document.querySelectorAll('img:not(.img-skeleton-processed)');
        images.forEach(img => {
            img.classList.add('img-skeleton-processed');
            if (img.complete && img.naturalHeight !== 0) {
                return; // Already loaded
            }

            const parent = img.parentElement;
            if (parent && !parent.classList.contains('img-skeleton-wrapper')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'img-skeleton-wrapper d-inline-block w-100 h-100';
                parent.insertBefore(wrapper, img);
                wrapper.appendChild(img);

                const markLoaded = () => wrapper.classList.add('loaded');
                img.addEventListener('load', markLoaded);
                img.addEventListener('error', markLoaded);
            }
        });
    }

    /**
     * Replace skeleton content smoothly with real rendered HTML
     */
    function replaceWithContent(containerOrId, htmlContent) {
        const container = typeof containerOrId === 'string' ? document.getElementById(containerOrId) : containerOrId;
        if (!container) return;

        container.style.opacity = '0';
        container.style.transition = 'opacity 0.25s ease-in-out';
        setTimeout(() => {
            container.innerHTML = htmlContent;
            container.style.opacity = '1';
            initImageSkeletons();
        }, 150);
    }

    // Auto initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        initImageSkeletons();
    });

    return {
        getClubCardSkeleton,
        getEventCardSkeleton,
        getMemberCardSkeleton,
        getGallerySkeleton,
        getStatBoxSkeleton,
        getTimelineSkeleton,
        getHeaderSkeleton,
        getFooterSkeleton,
        initImageSkeletons,
        replaceWithContent
    };
})();
