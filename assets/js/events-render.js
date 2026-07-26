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

    const getApiUrl = (endpoint) => `api/${endpoint}`;

    // Fetch Events API
    fetch(getApiUrl('events.php'))
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

    // Filter Logic & Render Across 3 Sections (Ongoing, Upcoming, Past)
    function applyFiltersAndRender() {
        const query = currentSearch.toLowerCase().trim();
        const now = new Date();

        let filtered = allEvents.filter(evt => {
            // Search Query Filter
            const matchesQuery = !query || 
                (evt.title || '').toLowerCase().includes(query) ||
                (evt.venue || '').toLowerCase().includes(query) ||
                (evt.description || '').toLowerCase().includes(query) ||
                (evt.club_name || '').toLowerCase().includes(query) ||
                (evt.club_short_name || '').toLowerCase().includes(query);

            // Club Filter
            const matchesClub = (currentClubFilter === 'all') || (evt.club_id === currentClubFilter);

            return matchesQuery && matchesClub;
        });

        const ongoingList = filtered.filter(e => e.status === 'ongoing');
        const upcomingList = filtered.filter(e => new Date(e.event_date) >= now && e.status !== 'completed' && e.status !== 'ongoing');
        const pastList = filtered.filter(e => new Date(e.event_date) < now || e.status === 'completed');

        // Update Section Badges
        const ongoingBadge = document.getElementById('ongoingCountBadge');
        const upcomingBadge = document.getElementById('upcomingCountBadge');
        const pastBadge = document.getElementById('pastCountBadge');

        if (ongoingBadge) ongoingBadge.textContent = `${ongoingList.length} Live`;
        if (upcomingBadge) upcomingBadge.textContent = `${upcomingList.length} Scheduled`;
        if (pastBadge) pastBadge.textContent = `${pastList.length} Completed`;

        // Section Containers
        const ongoingSec = document.getElementById('ongoingEventsSection');
        const upcomingSec = document.getElementById('upcomingEventsSection');
        const pastSec = document.getElementById('pastEventsSection');

        const ongoingContainer = document.getElementById('ongoingEventsList');
        const upcomingContainer = document.getElementById('upcomingEventsList');
        const pastContainer = document.getElementById('pastEventsList');

        // Status Tab visibility toggle
        if (currentStatusTab === 'ongoing') {
            if (ongoingSec) ongoingSec.classList.remove('d-none');
            if (upcomingSec) upcomingSec.classList.add('d-none');
            if (pastSec) pastSec.classList.add('d-none');
        } else if (currentStatusTab === 'upcoming') {
            if (ongoingSec) ongoingSec.classList.add('d-none');
            if (upcomingSec) upcomingSec.classList.remove('d-none');
            if (pastSec) pastSec.classList.add('d-none');
        } else if (currentStatusTab === 'past') {
            if (ongoingSec) ongoingSec.classList.add('d-none');
            if (upcomingSec) upcomingSec.classList.add('d-none');
            if (pastSec) pastSec.classList.remove('d-none');
        } else {
            // 'all' tab -> show ongoing if >0, show upcoming, show past
            if (ongoingSec) {
                if (ongoingList.length > 0) ongoingSec.classList.remove('d-none');
                else ongoingSec.classList.add('d-none');
            }
            if (upcomingSec) upcomingSec.classList.remove('d-none');
            if (pastSec) pastSec.classList.remove('d-none');
        }

        // Render Ongoing List
        if (ongoingContainer) {
            if (ongoingList.length === 0) {
                ongoingContainer.innerHTML = `<div class="col-12 text-center py-4 text-muted small bg-white rounded-4 border p-4">No live ongoing events at this moment. Check upcoming schedule below.</div>`;
            } else {
                ongoingContainer.innerHTML = ongoingList.map(e => renderFloatingEventCard(e, false)).join('');
            }
        }

        // Render Upcoming List
        if (upcomingContainer) {
            if (upcomingList.length === 0) {
                upcomingContainer.innerHTML = `
                    <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm border p-4">
                        <i class="bi bi-calendar-x fs-1 text-primary d-block mb-2"></i>
                        <h6 class="fw-bold mb-1">No Scheduled Contests Found</h6>
                        <p class="small text-muted mb-0">Try clearing your search query or switching club filters.</p>
                    </div>
                `;
            } else {
                upcomingContainer.innerHTML = upcomingList.map(e => renderFloatingEventCard(e, false)).join('');
            }
        }

        // Render Past List
        if (pastContainer) {
            if (pastList.length === 0) {
                pastContainer.innerHTML = `<div class="col-12 text-center py-4 text-muted small bg-white rounded-4 border p-4">No completed events found for the selected club filter.</div>`;
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

    // Hero Search Input & Button Listeners
    const heroSearchInput = document.getElementById('eventsSearchInput') || document.querySelector('.hero-search-input');
    const heroSearchBtn = document.getElementById('eventsSearchBtn');

    if (heroSearchInput) {
        heroSearchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value;
            applyFiltersAndRender();
        });
        heroSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                currentSearch = heroSearchInput.value;
                applyFiltersAndRender();
            }
        });
    }

    if (heroSearchBtn) {
        heroSearchBtn.addEventListener('click', () => {
            if (heroSearchInput) {
                currentSearch = heroSearchInput.value;
                applyFiltersAndRender();
            }
        });
    }

    // Quick Filter Chips Event Listener
    document.addEventListener('click', (e) => {
        const chip = e.target.closest('.event-chip-btn');
        if (chip) {
            const keyword = chip.getAttribute('data-chip');
            if (keyword) {
                currentSearch = keyword;
                if (heroSearchInput) heroSearchInput.value = currentSearch;
                applyFiltersAndRender();
            }
        }
    });

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

// Render Rich Executive Event Profile Card in 2-Column Grid
function renderFloatingEventCard(event, isPast = false) {
    const eventDate = new Date(event.event_date);
    const day = String(eventDate.getDate()).padStart(2, '0');
    const month = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
    const year = eventDate.getFullYear();
    const timeStr = eventDate.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });

    let statusBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small fw-bold"><i class="bi bi-calendar-event me-1"></i> Upcoming</span>`;
    if (isPast || event.status === 'completed') {
        statusBadge = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1 small fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Completed</span>`;
    } else if (event.status === 'ongoing') {
        statusBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 small fw-bold"><span class="pulse-dot-green me-1.5"></span> Live Now</span>`;
    }

    const registeredCount = event.registered_count || 45;
    const bannerUrl = escapeHtml(event.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop');

    return `
        <div class="col-md-6 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 ccms-club-prestige-card transition-all" style="background:#ffffff; border:1px solid #e2e8f0 !important;">
                <!-- Cover Image Banner -->
                <div class="position-relative" style="height: 180px;">
                    <img src="${bannerUrl}" class="w-100 h-100 object-fit-cover card-banner-zoom" alt="${escapeHtml(event.title)}">
                    <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.15) 0%, rgba(15,23,42,0.75) 100%);"></div>

                    <!-- Floating Date Badge -->
                    <div class="position-absolute top-0 start-0 m-3 z-2">
                        <div class="bg-white rounded-3 p-2 shadow-sm text-center px-3" style="min-width: 58px;">
                            <span class="d-block fw-extrabold text-primary lh-1" style="font-size: 1.25rem;">${day}</span>
                            <span class="small fw-bold text-dark text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">${month} '${String(year).slice(-2)}</span>
                        </div>
                    </div>

                    <!-- Club Badge Floating Overlay -->
                    <div class="position-absolute bottom-0 start-0 m-3 z-2">
                        <a href="#" class="filter-this-club-btn badge bg-dark bg-opacity-80 text-white border border-white-20 rounded-pill px-3 py-1.5 text-decoration-none shadow-sm backdrop-blur" data-club-id="${escapeHtml(event.club_id)}" title="Filter all events by ${escapeHtml(event.club_name)}">
                            <i class="bi bi-shield-fill text-primary me-1"></i> ${escapeHtml(event.club_short_name || event.club_name)}
                        </a>
                    </div>
                </div>

                <!-- Event Body -->
                <div class="p-4 flex-grow-1 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        ${statusBadge}
                        <span class="small text-secondary fw-semibold" style="font-size: 0.78rem;">
                            <i class="bi bi-people-fill text-primary me-1"></i> <strong class="text-dark">${registeredCount}+</strong> RSVPs
                        </span>
                    </div>

                    <h4 class="fw-bold text-dark mb-2" style="font-size: 1.2rem; line-height: 1.35;">
                        ${escapeHtml(event.title)}
                    </h4>

                    <p class="text-secondary small mb-3 flex-grow-1" style="font-size: 0.86rem; line-height: 1.55; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${escapeHtml(event.description || 'Join us for an exciting campus session organized by student chapter leads at UIT.')}
                    </p>

                    ${event.outcomes_summary ? `
                        <div class="rounded-3 p-2.5 mb-3 bg-light border border-light-subtle small text-dark" style="font-size: 0.78rem;">
                            <i class="bi bi-award-fill text-warning me-1.5"></i>
                            <strong>Highlights:</strong> ${escapeHtml(event.outcomes_summary)}
                        </div>
                    ` : ''}

                    <!-- Meta Bar & Footer Button -->
                    <div class="pt-3 border-top mt-auto">
                        <div class="small text-muted mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size: 0.78rem;">
                            <span class="text-dark fw-medium"><i class="bi bi-geo-alt-fill text-danger me-1"></i> ${escapeHtml(event.venue)}</span>
                            <span class="text-secondary"><i class="bi bi-clock-fill text-primary me-1"></i> ${timeStr}</span>
                        </div>

                        ${!isPast && event.status !== 'completed' ? `
                            <a href="${escapeHtml(event.registration_link || 'https://www.geeksforgeeks.org/')}" target="_blank" class="btn btn-primary rounded-pill w-100 py-2 fw-bold text-white text-decoration-none shadow-sm btn-glow d-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.88rem;">
                                <span>Register Now</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        ` : `
                            <span class="badge bg-light text-muted border rounded-pill w-100 py-2 text-center d-block" style="font-size: 0.8rem;"><i class="bi bi-check-circle me-1"></i> Completed Session</span>
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
