document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-wing]');
    const eventsContainer = document.getElementById('wingEvents');
    const eventCount = document.getElementById('wingEventCount');
    const clubsContainer = document.getElementById('wingClubs');
    const leadershipContainer = document.getElementById('culturalLeadershipContainer') || document.getElementById('wingLeadership');

    if (!page) return;

    const wing = page.dataset.wing;

    if (window.UITSkeletonLoader) {
        if (leadershipContainer) leadershipContainer.innerHTML = window.UITSkeletonLoader.getMemberCardSkeleton(4);
        if (clubsContainer) clubsContainer.innerHTML = window.UITSkeletonLoader.getClubCardSkeleton(3);
        if (eventsContainer) eventsContainer.innerHTML = window.UITSkeletonLoader.getEventCardSkeleton(3);
    }

    const eventPage = wing === 'technical' ? 'events.html?wing=technical' : 'events.html?wing=cultural';
    const wingLabel = wing === 'technical' ? 'technical' : 'cultural';
    const wingCategories = wing === 'technical'
        ? ['technical', 'technical-software-development']
        : ['cultural', 'academic', 'creative'];

    // 1. Load Leadership Roster for the Wing
    if (leadershipContainer) {
        const clubId = wing === 'cultural' ? 'clb_cultural_uit' : 'clb_developers_uit';
        fetch(`api/clubs.php?id=${encodeURIComponent(clubId)}`)
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success' && response.data && Array.isArray(response.data.leadership)) {
                    const leaders = response.data.leadership;
                    if (leaders.length === 0) {
                        leadershipContainer.innerHTML = `<div class="col-12 text-center text-muted py-3">Leadership team is currently being updated.</div>`;
                        return;
                    }
                    leadershipContainer.innerHTML = leaders.map(ldr => {
                        const avatar = ldr.avatar || 'assets/United Logo.webp';
                        const category = (ldr.category || '').toLowerCase();
                        const isFaculty = category.includes('faculty') || (ldr.role_title || '').toLowerCase().includes('faculty');
                        
                        let badgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                        let badgeIcon = 'bi-award-fill';
                        let tagColor = '#2563eb';

                        if (isFaculty) {
                            badgeClass = 'bg-warning-subtle text-warning border-warning-subtle';
                            badgeIcon = 'bi-patch-check-fill';
                            tagColor = '#d97706';
                        } else if (category.includes('president')) {
                            badgeClass = 'bg-success-subtle text-success border-success-subtle';
                            badgeIcon = 'bi-person-badge-fill';
                            tagColor = '#059669';
                        } else if (category.includes('vice')) {
                            badgeClass = 'bg-purple-subtle text-purple border-purple-subtle';
                            badgeIcon = 'bi-star-fill';
                            tagColor = '#7c3aed';
                        }

                        return `
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white committee-card-3d">
                                    <div class="mx-auto mb-3 position-relative" style="width: 110px; height: 110px;">
                                        <img src="${escapeHtml(avatar)}" alt="${escapeHtml(ldr.name)}" class="w-100 h-100 object-fit-cover rounded-circle shadow-sm" style="border: 3px solid ${tagColor};" onerror="this.src='assets/United Logo.webp'">
                                    </div>
                                    <span class="badge ${badgeClass} border rounded-pill px-3 py-1 mb-2 fw-bold mx-auto text-uppercase" style="font-size: 0.75rem;">
                                        <i class="bi ${badgeIcon} me-1"></i> ${escapeHtml(ldr.role_title)}
                                    </span>
                                    <h5 class="fw-black text-dark mb-1" style="font-size: 1.15rem;">${escapeHtml(ldr.name)}</h5>
                                    <div class="small text-secondary fw-bold mb-2">Tenure: <span class="badge bg-light text-dark border px-2 py-0.5 rounded-pill">${escapeHtml(ldr.term_year || '2025–2026')}</span></div>
                                    <div class="d-flex align-items-center justify-content-center gap-2 mt-auto pt-2 text-muted small">
                                        ${ldr.email ? `<a href="mailto:${escapeHtml(ldr.email)}" class="btn btn-sm btn-light rounded-circle p-1.5 text-primary" title="Email"><i class="bi bi-envelope-fill"></i></a>` : ''}
                                        ${ldr.phone ? `<a href="tel:${escapeHtml(ldr.phone)}" class="btn btn-sm btn-light rounded-circle p-1.5 text-success" title="Phone"><i class="bi bi-telephone-fill"></i></a>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            })
            .catch(err => console.error('Error fetching wing leadership:', err));
    }

    // 2. Load Events for the Wing
    if (eventsContainer) {
        fetch('api/events.php')
            .then(response => response.json())
            .then(response => {
                if (response.status !== 'success') throw new Error('Unable to load events');

                const events = (response.data || [])
                    .filter(event => wingCategories.includes((event.category_slug || '').toLowerCase()))
                    .sort((first, second) => new Date(second.event_date) - new Date(first.event_date));

                if (eventCount) {
                    eventCount.textContent = events.length
                        ? `${events.length} published ${wingLabel} event${events.length === 1 ? '' : 's'}`
                        : 'Events coming soon';
                }

                if (!events.length) {
                    renderEmptyState(eventsContainer, eventPage, wingLabel);
                    return;
                }

                eventsContainer.innerHTML = events.slice(0, 6).map(event => renderEventCard(event)).join('');
            })
            .catch(() => {
                if (eventCount) eventCount.textContent = 'Events coming soon';
                renderEmptyState(eventsContainer, eventPage, wingLabel);
            });
    }

    // 3. Load Chapters under the Wing
    if (clubsContainer) {
        fetch(`api/clubs.php?wing=${encodeURIComponent(wing)}`)
            .then(response => response.json())
            .then(response => {
                if (response.status !== 'success') throw new Error('Unable to load clubs');

                const clubs = response.data || [];
                if (!clubs.length) {
                    clubsContainer.innerHTML = `
                        <div class="col-12 text-center py-4">
                            <p class="text-white-50 small mb-3">Chapter listings are currently being updated.</p>
                            <a class="wing-action wing-action--ghost" href="clubs.html?wing=${encodeURIComponent(wing)}">Browse all chapters <i class="bi bi-arrow-right"></i></a>
                        </div>
                    `;
                    return;
                }

                clubsContainer.innerHTML = clubs.map(club => renderChapterCard(club)).join('');
            })
            .catch(() => {
                clubsContainer.innerHTML = `
                    <div class="col-12 text-center py-4">
                        <a class="wing-action wing-action--ghost" href="clubs.html?wing=${encodeURIComponent(wing)}">See all chapters <i class="bi bi-arrow-right"></i></a>
                    </div>
                `;
            });
    }
});

function renderChapterCard(club) {
    const hasLogo = club.logo && club.logo.length > 5;
    const logoHtml = hasLogo
        ? `<img src="${escapeHtml(club.logo)}" alt="${escapeHtml(club.name)} Logo" class="wing-chapter-card__logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"><div class="wing-chapter-card__mark" style="display:none;">${escapeHtml((club.short_name || 'CLUB').slice(0, 4).toUpperCase())}</div>`
        : `<div class="wing-chapter-card__mark">${escapeHtml((club.short_name || 'CLUB').slice(0, 4).toUpperCase())}</div>`;

    const categoryBadge = club.category_name
        ? `<span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-10 rounded-pill fs-xs px-2.5 py-1 mb-2">${escapeHtml(club.category_name)}</span>`
        : '';

    const detailUrl = `club-detail.html?id=${encodeURIComponent(club.id)}`;

    return `
        <div class="col-md-6 col-lg-4">
            <article class="wing-chapter-card h-100 d-flex flex-column justify-content-between p-4 rounded-4 shadow-sm bg-dark bg-opacity-50 border border-white border-opacity-10" onclick="window.location.href='${detailUrl}'" style="cursor: pointer;">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="wing-chapter-card__brand-wrap">${logoHtml}</div>
                        <span class="badge bg-primary bg-opacity-20 text-white rounded-pill px-2.5 py-1" style="font-size:0.7rem; font-weight:700;">Official Chapter</span>
                    </div>
                    ${categoryBadge}
                    <h3 class="text-white fw-bold fs-5 mb-2">${escapeHtml(club.name)}</h3>
                    <p class="text-white-80 small mb-3">${escapeHtml(club.tagline || club.description || 'Official USC UIT student chapter.')}</p>
                </div>
                <div class="mt-auto pt-3 border-top border-white border-opacity-10">
                    <a class="wing-chapter-link d-inline-flex align-items-center gap-2 text-warning font-semibold small" href="${detailUrl}" onclick="event.stopPropagation();">
                        <span>Explore chapter</span> <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>
            </article>
        </div>
    `;
}

function renderEventCard(event) {
    const eventDate = new Date(event.event_date);
    const dateText = Number.isNaN(eventDate.getTime())
        ? 'Campus event'
        : eventDate.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
    const detailUrl = `event-detail.html?id=${encodeURIComponent(event.id)}`;

    return `
        <div class="col-md-6 col-lg-4">
            <article class="wing-event-card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100">
                <div class="wing-event-card__media position-relative" style="aspect-ratio: 16 / 9; overflow: hidden;">
                    <img src="${escapeHtml(event.banner || 'assets/United Logo.webp')}" alt="${escapeHtml(event.title)}" class="w-100 h-100 object-fit-cover" onerror="this.src='assets/United Logo.webp'">
                    <span class="badge bg-primary rounded-pill position-absolute top-0 start-0 m-3 px-3 py-1 small fw-bold">${escapeHtml(event.category_name || 'Cultural')}</span>
                </div>
                <div class="p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-secondary small fw-bold mb-1"><i class="bi bi-calendar-event me-1 text-primary"></i> ${dateText}</div>
                        <h4 class="fw-black text-dark fs-6 mb-2">${escapeHtml(event.title)}</h4>
                        <p class="text-secondary small line-clamp-2 mb-3">${escapeHtml(event.description || '')}</p>
                    </div>
                    <a href="${detailUrl}" class="btn btn-sm btn-outline-primary rounded-pill fw-bold w-100 mt-auto">View Event Details <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </article>
        </div>
    `;
}

function renderEmptyState(container, eventPage, wingLabel) {
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <p class="text-secondary fw-semibold">No upcoming ${wingLabel} events listed right now.</p>
        </div>
    `;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
