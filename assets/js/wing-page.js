document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-wing]');
    const eventsContainer = document.getElementById('wingEvents');
    const eventCount = document.getElementById('wingEventCount');
    const clubsContainer = document.getElementById('wingClubs');

    if (!page) return;

    const wing = page.dataset.wing;
    const eventPage = wing === 'technical' ? 'tech-events.html' : 'cultural-events.html';
    const wingLabel = wing === 'technical' ? 'technical' : 'cultural';
    const wingCategories = wing === 'technical'
        ? ['technical', 'technical-software-development']
        : ['cultural', 'academic', 'creative'];

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

                eventsContainer.innerHTML = events.slice(0, 3).map(event => renderEventCard(event)).join('');
            })
            .catch(() => {
                if (eventCount) eventCount.textContent = 'Events coming soon';
                renderEmptyState(eventsContainer, eventPage, wingLabel);
            });
    }

    if (clubsContainer) {
        fetch(`api/clubs.php?wing=${encodeURIComponent(wing)}`)
            .then(response => response.json())
            .then(response => {
                if (response.status !== 'success') throw new Error('Unable to load clubs');

                const clubs = response.data || [];
                if (!clubs.length) {
                    clubsContainer.innerHTML = `
                        <div class="col-12 text-center py-4">
                            <p class="text-white-50 small mb-3">Chapter listings are being updated.</p>
                            <a class="wing-action wing-action--ghost" href="clubs.html?wing=${encodeURIComponent(wing)}">Browse all chapters <i class="bi bi-arrow-right"></i></a>
                        </div>
                    `;
                    return;
                }

                clubsContainer.innerHTML = clubs.slice(0, 4).map(club => renderChapterCard(club)).join('');
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
    const mark = club.short_name
        ? escapeHtml(club.short_name.slice(0, 4).toUpperCase())
        : '<i class="bi bi-stars"></i>';

    return `
        <div class="col-md-6 col-lg-3">
            <article class="wing-chapter-card">
                <div class="wing-chapter-card__mark">${mark}</div>
                <h3>${escapeHtml(club.name)}</h3>
                <p>${escapeHtml(club.tagline || club.description || 'Official USC UIT student chapter.')}</p>
                <a href="club-detail.html?id=${encodeURIComponent(club.id)}">Explore chapter <i class="bi bi-arrow-up-right"></i></a>
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
        <article class="col-md-6 col-lg-4">
            <a class="wing-event-card text-decoration-none" href="${detailUrl}">
                <div class="wing-event-card__topline">
                    <span class="wing-event-card__date"><i class="bi bi-calendar3 me-1"></i>${escapeHtml(dateText)}</span>
                    <span>${escapeHtml(event.status || 'Event').toUpperCase()}</span>
                </div>
                <h3>${escapeHtml(event.title)}</h3>
                <p>${escapeHtml(event.description || 'Explore this official USC UIT event and take part with the community.')}</p>
                <div class="wing-event-card__meta"><i class="bi bi-geo-alt-fill me-1"></i>${escapeHtml(event.venue || 'UIT Campus')}</div>
            </a>
        </article>
    `;
}

function renderEmptyState(container, eventPage, wingLabel) {
    container.innerHTML = `
        <div class="col-12">
            <div class="wing-events-empty">
                <i class="bi bi-calendar2-week"></i>
                <h3>More ${escapeHtml(wingLabel)} events are on the way</h3>
                <p>Visit the event board to see the newest sessions, registrations, and updates.</p>
                <a class="wing-action wing-action--primary mt-3" href="${eventPage}">Open event board <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    `;
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
