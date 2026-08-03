/**
 * USC UIT — Site path helpers & double-slash URL fix
 * Ensures URLs like /UIT//index.html normalize to /UIT/index.html
 */
(function () {
    function normalizePathname(pathname) {
        if (!pathname) return '/';
        return pathname.replace(/\/+/g, '/');
    }

    function normalizeCurrentUrl() {
        const path = window.location.pathname;
        const clean = normalizePathname(path);
        if (clean !== path) {
            window.location.replace(clean + window.location.search + window.location.hash);
            return true;
        }
        return false;
    }

    function getAssetBase() {
        const path = normalizePathname(window.location.pathname);
        const idx = path.lastIndexOf('/');
        if (idx <= 0) return '/';
        return path.substring(0, idx + 1);
    }

    function getSiteRoot() {
        const path = normalizePathname(window.location.pathname);
        const segments = path.split('/').filter(Boolean);
        if (segments.length && segments[segments.length - 1].includes('.')) {
            segments.pop();
        }
        return segments.length ? '/' + segments.join('/') : '';
    }

    function siteUrl(relativePath) {
        const rel = String(relativePath || '').replace(/^\/+/, '');
        const root = getSiteRoot();
        return `${root}/${rel}`.replace(/\/+/g, '/');
    }

    function fixRootRelativeLinks(root) {
        if (!root) return;
        root.querySelectorAll('a[href], link[href]').forEach(el => {
            const href = el.getAttribute('href');
            if (!href || /^(https?:|mailto:|tel:|javascript:|#)/i.test(href)) return;
            if (href.startsWith('/')) {
                const rootPath = getSiteRoot();
                if (rootPath && !href.startsWith(rootPath + '/') && href !== rootPath) {
                    el.setAttribute('href', siteUrl(href.replace(/^\/+/, '')));
                }
            }
        });
    }

    function normalizeInternalHref(href) {
        if (!href || /^(https?:|mailto:|tel:|javascript:|#)/i.test(href)) return href;
        try {
            const url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) return href;
            const cleanPath = normalizePathname(url.pathname);
            if (cleanPath === url.pathname) return href;
            return cleanPath + url.search + url.hash;
        } catch (_) {
            return href;
        }
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        const normalized = normalizeInternalHref(link.getAttribute('href'));
        if (normalized && normalized !== link.getAttribute('href')) {
            event.preventDefault();
            window.location.assign(normalized);
        }
    }, true);

    if (normalizeCurrentUrl()) return;

    window.UITSitePath = {
        normalizePathname,
        getAssetBase,
        getSiteRoot,
        siteUrl,
        fixRootRelativeLinks
    };
})();
