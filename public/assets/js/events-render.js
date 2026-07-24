/**
 * Advanced Dynamic Events Renderer & Interactive Filter (ClubHub UIT)
 * Includes Club Filter Pills, Status Tabs, Floating Glassmorphism Cards, & Search
 */

document.addEventListener('DOMContentLoaded', () => {
    const upcomingContainer = document.getElementById('upcomingEventsList');
    const pastContainer = document.getElementById('pastEventsList');
    const clubPillsContainer = document.getElementById('clubFilterPills');
    const searchInput = document.querySelector('.hero-search-input');
    const statusTabs = document.querySelectorAll('.event-status-tab');

    let allEvents = [];
    let allClubsMap = new Map();
    let currentSearch = '';
    let currentClubFilter = 'all';
    let currentStatusTab = 'all';

    // Fetch Events API
    fetch('/api/events.php')
        .then(res => res.json())
        .then(response => {
            if (response.status !== 'success') {
                renderEmptyState(upcomingContainer, 'Failed to load events.');
                return;
            }

            allEvents = response.data || [];

            // Extract unique clubs for Club Filter Pills
            allEvents.forEach(evt => {
                if (evt.club_id && evt.club_name) {
                    allClubsMap.set(evt.club_id, {
                        id: evt.club_id,
                        name: evt.club_name,
                        short: evt.club_short_name || evt.club_name,
                        logo: evt.club_logo
                    });
                }
            });

            // Render Club Pills
            renderClubFilterPills();

            // Initial Events Render
            applyFiltersAndRender();
        })
        .catch(err => {
            console.error('Error fetching events:', err);
            renderEmptyState(upcomingContainer, 'Unable to connect to events database.');
        });

    // Render Club Filter Pills
    function renderClubFilterPills() {
        if (!clubPillsContainer) return;

        let pillsHtml = `
            <button class="btn btn-sm rounded-pill px-3 py-1-5 fw-semibold club-pill-btn active" data-club-id="all">
                <i class="bi bi-collection-fill me-1"></i> All Clubs (${allEvents.length})
            </button>
        `;

        allClubsMap.forEach(club => {
            const count = allEvents.filter(e => e.club_id === club.id).length;
            pillsHtml += `
                <button class="btn btn-sm rounded-pill px-3 py-1-5 fw-semibold club-pill-btn" data-club-id="${club.id}">
                    <img src="${escapeHtml(club.logo)}" class="rounded-circle me-1" style="width: 18px; height: 18px; object-fit: cover;">
                    ${escapeHtml(club.short)} (${count})
                </button>
            `;
        });

        clubPillsContainer.innerHTML = pillsHtml;

        // Add Click Listeners to Club Pills
        clubPillsContainer.querySelectorAll('.club-pill-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                clubPillsContainer.querySelectorAll('.club-pill-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
                btn.classList.add('active');
                currentClubFilter = btn.dataset.clubId;
                applyFiltersAndRender();
            });
        });
    }

    // Filter Logic & Render
    function applyFiltersAndRender() {
        const query = currentSearch.toLowerCase().trim();
        const now = new Date();

        let filtered = allEvents.filter(evt => {
            // Search Query Filter
            const matchesQuery = !query || 
                (evt.title || '').toLowerCase().includes(query) ||
                (evt.venue || '').toLowerCase().includes(query) ||
                (evt.description || '').toLowerCase().includes(query) ||
                (evt.club_name || '').toLowerCase().includes(query);

            // Club Filter
            const matchesClub = (currentClubFilter === 'all') || (evt.club_id === currentClubFilter);

            // Status Tab Filter
            const evtDate = new Date(evt.event_date);
            let matchesStatus = true;
            if (currentStatusTab === 'upcoming') {
                matchesStatus = (evtDate >= now) && (evt.status !== 'completed');
            } else if (currentStatusTab === 'ongoing') {
                matchesStatus = (evt.status === 'ongoing');
            } else if (currentStatusTab === 'past') {
                matchesStatus = (evtDate < now) || (evt.status === 'completed');
            }

            return matchesQuery && matchesClub && matchesStatus;
        });

        // Separate Upcoming vs Past for main display
        const upcomingList = filtered.filter(e => new Date(e.event_date) >= now && e.status !== 'completed');
        const pastList = filtered.filter(e => new Date(e.event_date) < now || e.status === 'completed');

        // Render Upcoming Container
        if (upcomingContainer) {
            if (upcomingList.length === 0) {
                upcomingContainer.innerHTML = `
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-4">
                        <i class="bi bi-calendar-x fs-1 text-primary d-block mb-2"></i>
                        <h6 class="fw-bold mb-1">No Upcoming Events Found</h6>
                        <p class="small text-muted mb-0">Try clearing your search or switching club filters.</p>
                    </div>
                `;
            } else {
                upcomingContainer.innerHTML = upcomingList.map(e => renderFloatingEventCard(e, false)).join('');
            }
        }

        // Render Past Container
        if (pastContainer) {
            if (pastList.length === 0) {
                pastContainer.innerHTML = `<div class="text-center py-4 text-muted small">No past events recorded for this selection.</div>`;
            } else {
                pastContainer.innerHTML = pastList.map(e => renderFloatingEventCard(e, true)).join('');
            }
        }

        // Re-attach "Filter by this Club" click handlers on rendered cards
        document.querySelectorAll('.filter-this-club-btn').forEach(btn => {
            btn.addEventListener('click', (ev) => {
                ev.preventDefault();
                const clubId = btn.dataset.clubId;
                const targetPill = clubPillsContainer?.querySelector(`[data-club-id="${clubId}"]`);
                if (targetPill) {
                    targetPill.click();
                    window.scrollTo({ top: clubPillsContainer.offsetTop - 100, behavior: 'smooth' });
                }
            });
        });
    }

    // Search Input Listener
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value;
            applyFiltersAndRender();
        });
    }

    // Status Tabs Listeners
    statusTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            statusTabs.forEach(t => t.classList.remove('active', 'btn-primary'));
            tab.classList.add('active');
            currentStatusTab = tab.dataset.statusTab || 'all';
            applyFiltersAndRender();
        });
    });
});

// Render Floating Glassmorphism Event Card
function renderFloatingEventCard(event, isPast = false) {
    const eventDate = new Date(event.event_date);
    const day = String(eventDate.getDate()).padStart(2, '0');
    const month = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
    const timeStr = eventDate.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });

    let statusBadge = `<span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1 small fw-bold"><i class="bi bi-calendar-event me-1"></i> Upcoming</span>`;
    if (isPast || event.status === 'completed') {
        statusBadge = `<span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1 small fw-bold"><i class="bi bi-check2-all me-1"></i> Completed</span>`;
    } else if (event.status === 'ongoing') {
        statusBadge = `<span class="badge bg-warning-subtle text-warning border rounded-pill px-3 py-1 small fw-bold"><i class="bi bi-lightning-fill me-1"></i> Live Now</span>`;
    }

    return `
        <div class="card p-3 p-md-4 border-0 shadow-lg rounded-4 ccms-floating-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-md-4 position-relative">
                    <img src="${escapeHtml(event.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=600&auto=format&fit=crop')}" class="img-fluid rounded-4 card-banner-zoom" style="height: 150px; width: 100%; object-fit: cover;" alt="${escapeHtml(event.title)}">
                    
                    <!-- Club Badge Floating Overlay -->
                    <a href="#" class="filter-this-club-btn badge bg-dark bg-opacity-75 text-white border border-white-20 rounded-pill px-3 py-1 position-absolute bottom-0 start-0 ms-3 mb-3 text-decoration-none shadow-sm backdrop-blur" data-club-id="${escapeHtml(event.club_id)}" title="Filter all events by ${escapeHtml(event.club_name)}">
                        <i class="bi bi-shield-fill text-primary me-1"></i> ${escapeHtml(event.club_short_name || event.club_name)}
                    </a>
                </div>

                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="event-date-badge flex-shrink-0">
                            <span class="event-date-num">${day}</span>
                            <span class="event-date-month">${month}</span>
                        </div>
                        <div>
                            ${statusBadge}
                            <h5 class="fw-bold mb-0 text-dark mt-1 hover-primary">${escapeHtml(event.title)}</h5>
                        </div>
                    </div>

                    <p class="text-secondary small mb-3 line-clamp-2">${escapeHtml(event.description || 'Join us for an exciting campus event organized by student leaders.')}</p>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top">
                        <div class="small text-muted space-x-3">
                            <span><i class="bi bi-geo-alt text-danger me-1"></i> <strong>${escapeHtml(event.venue)}</strong></span>
                            <span class="ms-3"><i class="bi bi-clock text-primary me-1"></i> ${timeStr}</span>
                        </div>

                        ${!isPast ? `
                            <a href="${escapeHtml(event.registration_link || '/contact.html')}" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-bold text-white text-decoration-none shadow-sm btn-glow">
                                Register Now &rarr;
                            </a>
                        ` : `
                            <span class="badge bg-light text-muted border rounded-pill px-3 py-1.5"><i class="bi bi-check-circle me-1"></i> Event Ended</span>
                        `}
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderEmptyState(container, title, subtitle = '') {
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="p-5 bg-white rounded-4 shadow-sm border max-w-md mx-auto">
                <i class="bi bi-calendar-x fs-1 text-primary d-block mb-3"></i>
                <h5 class="fw-bold mb-2">${escapeHtml(title)}</h5>
                <p class="text-secondary small mb-4">${escapeHtml(subtitle)}</p>
                <a href="/clubs.html" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
                    <i class="bi bi-collection me-1"></i> Explore Student Clubs
                </a>
            </div>
        </div>
    `;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
