/**
 * USC UIT â€” Wing helpers for Developers Club UIT & Cultural Club UIT
 */
const WING_TECH_CATEGORIES = ['technical', 'technical-software-development'];
const WING_CULTURAL_CATEGORIES = ['cultural', 'academic', 'creative'];

function getWingFromCategory(categorySlug) {
    const slug = (categorySlug || '').toLowerCase();
    if (WING_TECH_CATEGORIES.includes(slug)) return 'technical';
    if (WING_CULTURAL_CATEGORIES.includes(slug)) return 'cultural';
    return null;
}

function resolveWing(wingOrCategory) {
    if (wingOrCategory === 'technical' || wingOrCategory === 'developers') return 'technical';
    if (wingOrCategory === 'cultural') return 'cultural';
    return getWingFromCategory(wingOrCategory);
}

function getWingMeta(wing) {
    if (wing === 'technical') {
        return {
            wing: 'technical',
            label: 'Developers Club UIT',
            pageUrl: 'clubs.html?wing=technical',
            eventsUrl: 'events.html?wing=technical',
            accentClass: 'text-primary',
            icon: 'bi-code-slash'
        };
    }
    if (wing === 'cultural') {
        return {
            wing: 'cultural',
            label: 'Cultural Club UIT',
            pageUrl: 'clubs.html?wing=cultural',
            eventsUrl: 'events.html?wing=cultural',
            accentClass: 'text-danger',
            icon: 'bi-palette-fill'
        };
    }
    return {
        wing: null,
        label: 'USC UIT clubs',
        pageUrl: 'clubs.html',
        eventsUrl: 'events.html',
        accentClass: 'text-primary',
        icon: 'bi-diagram-3'
    };
}

function buildWingBreadcrumbHtml(options = {}) {
    const wing = resolveWing(options.wing || options.categorySlug);
    const meta = getWingMeta(wing);
    const accent = options.accentClass || meta.accentClass;
    const clubName = options.clubName || '';
    const clubId = options.clubId || '';
    const eventTitle = options.eventTitle || '';
    const currentIsClub = Boolean(clubName && !eventTitle);
    const currentIsEvent = Boolean(eventTitle);

    const parts = [
        `<i class="bi bi-house-door-fill ${accent} fs-6"></i>`,
        `<a href="index.html" class="${accent} text-decoration-none fw-bold">Home</a>`,
        `<span class="text-muted opacity-75 fw-bold">/</span>`,
        `<a href="clubs.html" class="${accent} text-decoration-none fw-bold">USC UIT clubs</a>`
    ];

    if (wing) {
        parts.push(
            `<span class="text-muted opacity-75 fw-bold">/</span>`,
            `<a href="${meta.pageUrl}" class="${accent} text-decoration-none fw-bold">${escapeWingHtml(meta.label)}</a>`
        );
    }

    if (clubName) {
        parts.push(`<span class="text-muted opacity-75 fw-bold">/</span>`);
        if (currentIsClub) {
            parts.push(`<span class="fw-extrabold text-dark" style="color:#0f172a!important;">${escapeWingHtml(clubName)}</span>`);
        } else {
            const clubHref = clubId ? `club-detail.html?id=${encodeURIComponent(clubId)}` : 'clubs.html';
            parts.push(`<a href="${clubHref}" class="${accent} text-decoration-none fw-bold">${escapeWingHtml(clubName)}</a>`);
        }
    }

    if (eventTitle) {
        parts.push(
            `<span class="text-muted opacity-75 fw-bold">/</span>`,
            `<span class="fw-extrabold text-dark" id="eventBreadcrumbTitle" style="color:#0f172a!important;">${escapeWingHtml(eventTitle)}</span>`
        );
    }

    return `<div class="clubs-top-pill-breadcrumb d-inline-flex align-items-center gap-2 flex-wrap">${parts.join('')}</div>`;
}

function escapeWingHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

