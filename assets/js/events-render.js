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
        const heroOngoing = document.getElementById('heroOngoingCount');
        const heroUpcoming = document.getElementById('heroUpcomingCount');

        if (ongoingBadge) ongoingBadge.textContent = `${ongoingList.length} Live`;
        if (upcomingBadge) upcomingBadge.textContent = `${upcomingList.length} Scheduled`;
        if (pastBadge) pastBadge.textContent = `${pastList.length} Completed`;
        if (heroOngoing) heroOngoing.textContent = `${ongoingList.length}`;
        if (heroUpcoming) heroUpcoming.textContent = `${upcomingList.length}`;

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

        // Re-attach "Filter by this Club" & "Open Event Detail" click handlers on rendered cards
        document.querySelectorAll('.filter-this-club-btn').forEach(btn => {
            btn.addEventListener('click', (ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                const clubId = btn.dataset.clubId;
                const targetPill = clubPillsContainer?.querySelector(`[data-club-id="${clubId}"]`);
                if (targetPill) {
                    targetPill.click();
                    window.scrollTo({ top: clubPillsContainer.offsetTop - 100, behavior: 'smooth' });
                }
            });
        });

        document.querySelectorAll('.open-event-detail-btn').forEach(btn => {
            btn.addEventListener('click', (ev) => {
                ev.preventDefault();
                const eventId = btn.dataset.eventId;
                if (eventId) {
                    window.location.href = `event-detail.html?id=${eventId}`;
                }
            });
        });
    }

    // Modal Opener & Dynamic Detailed Renderer
    function openEventModal(eventId) {
        const targetEvent = allEvents.find(e => String(e.id) === String(eventId));
        if (!targetEvent) return;

        const modalContent = document.getElementById('eventModalContent');
        if (!modalContent) return;

        const eventDate = new Date(targetEvent.event_date);
        const day = String(eventDate.getDate()).padStart(2, '0');
        const month = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
        const year = eventDate.getFullYear();
        const fullDateStr = eventDate.toLocaleString('default', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        const timeStr = eventDate.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });

        const isPast = (eventDate < new Date()) || (targetEvent.status === 'completed');
        let statusBadge = `<span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1.5 fw-bold"><i class="bi bi-calendar-event me-1"></i> Upcoming Contest</span>`;
        if (isPast) {
            statusBadge = `<span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1.5 fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Completed Session</span>`;
        } else if (targetEvent.status === 'ongoing') {
            statusBadge = `<span class="badge bg-warning-subtle text-warning border rounded-pill px-3 py-1.5 fw-bold"><span class="pulse-dot-green me-1.5"></span> Live Workshop</span>`;
        }

        const registeredCount = targetEvent.registered_count || 45;
        const bannerUrl = escapeHtml(targetEvent.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=1200&auto=format&fit=crop');
        const clubLogo = escapeHtml(targetEvent.club_logo || 'assets/images/clubs/gfg.png');

        modalContent.innerHTML = `
            <!-- Modal Banner Header -->
            <div class="position-relative overflow-hidden" style="height: 240px;">
                <img src="${bannerUrl}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(targetEvent.title)}">
                <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.3) 0%, rgba(15,23,42,0.9) 100%);"></div>

                <!-- Close Button -->
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3 p-2 bg-dark bg-opacity-50 rounded-circle" data-bs-dismiss="modal" aria-label="Close"></button>

                <!-- Floating Date Badge -->
                <div class="position-absolute top-0 start-0 m-3 z-2">
                    <div class="google-date-badge text-center shadow-lg" style="min-width: 65px;">
                        <span class="d-block fw-extrabold text-primary lh-1" style="font-size: 1.4rem;">${day}</span>
                        <span class="small fw-bold text-dark text-uppercase" style="font-size: 0.68rem;">${month} '${String(year).slice(-2)}</span>
                    </div>
                </div>

                <!-- Club Badge & Category Floating Bottom -->
                <div class="position-absolute bottom-0 start-0 m-4 z-2">
                    <div class="d-flex align-items-center gap-2">
                        <img src="${clubLogo}" class="rounded-circle bg-white p-1 shadow-sm" style="width: 38px; height: 38px; object-fit: cover;" alt="${escapeHtml(targetEvent.club_name)}">
                        <div>
                            <h6 class="text-white fw-bold mb-0 lh-1">${escapeHtml(targetEvent.club_name)}</h6>
                            <span class="small text-white-80" style="font-size: 0.75rem;">Official SAC Governed Society</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Body Details -->
            <div class="modal-body p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    ${statusBadge}
                    <span class="small text-secondary fw-semibold">
                        <i class="bi bi-people-fill text-primary me-1"></i> <strong class="text-dark">${registeredCount}+</strong> Coders Registered
                    </span>
                </div>

                <h3 class="fw-extrabold text-dark mb-3" style="letter-spacing: -0.5px;">${escapeHtml(targetEvent.title)}</h3>

                <!-- 4-Grid Key Event Specs -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 rounded-4 bg-light border text-start h-100">
                            <i class="bi bi-geo-alt-fill text-danger fs-4 d-block mb-1"></i>
                            <span class="small text-muted d-block fw-bold" style="font-size: 0.72rem;">VENUE / LOCATION</span>
                            <strong class="text-dark small">${escapeHtml(targetEvent.venue)}</strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 rounded-4 bg-light border text-start h-100">
                            <i class="bi bi-calendar3 text-primary fs-4 d-block mb-1"></i>
                            <span class="small text-muted d-block fw-bold" style="font-size: 0.72rem;">DATE & TIME</span>
                            <strong class="text-dark small">${fullDateStr} at ${timeStr}</strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 rounded-4 bg-light border text-start h-100">
                            <i class="bi bi-people-fill text-success fs-4 d-block mb-1"></i>
                            <span class="small text-muted d-block fw-bold" style="font-size: 0.72rem;">TOTAL RSVPs</span>
                            <strong class="text-dark small">${registeredCount}+ Participants</strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 rounded-4 bg-light border text-start h-100">
                            <i class="bi bi-shield-check text-info fs-4 d-block mb-1"></i>
                            <span class="small text-muted d-block fw-bold" style="font-size: 0.72rem;">ELIGIBILITY</span>
                            <strong class="text-dark small">All Tech Enthusiasts</strong>
                        </div>
                    </div>
                </div>

                <!-- Event Rewards & Outcomes Highlights Box -->
                ${targetEvent.outcomes_summary ? `
                    <div class="p-3.5 mb-4 rounded-4 border border-warning-subtle bg-warning-subtle text-dark d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="bi bi-trophy-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Rewards, Swags & Benefits</h6>
                            <span class="small text-dark opacity-90">${escapeHtml(targetEvent.outcomes_summary)}</span>
                        </div>
                    </div>
                ` : ''}

                <!-- Full Event Description -->
                <div class="mb-4">
                    <h5 class="fw-bold text-dark mb-2">About This Event</h5>
                    <p class="text-secondary" style="line-height: 1.7; font-size: 0.95rem;">
                        ${escapeHtml(targetEvent.description || 'Join us for an immersive technical workshop organized by student chapter leads. Gain hands-on practice, network with domain mentors, and showcase your problem-solving capabilities.')}
                    </p>
                </div>

                <!-- Lead Mentors & Organizers Box -->
                <div class="p-3.5 rounded-4 bg-body-tertiary border mb-2 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-workspace fs-4 text-primary"></i>
                        <div>
                            <strong class="text-dark d-block small">Organized by Student Chapter Leads</strong>
                            <span class="small text-muted">${escapeHtml(targetEvent.club_name)} Board</span>
                        </div>
                    </div>
                    <span class="badge bg-white text-dark border rounded-pill px-3 py-1.5 fw-semibold small"><i class="bi bi-check-circle-fill text-success me-1"></i> SAC Verified Event</span>
                </div>
            </div>

            <!-- Modal Footer Action Bar -->
            <div class="modal-footer bg-light p-3 px-4 d-flex align-items-center justify-content-between border-top">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold" data-bs-dismiss="modal">Close</button>
                ${!isPast ? `
                    <a href="${escapeHtml(targetEvent.registration_link || 'https://www.geeksforgeeks.org/')}" target="_blank" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold text-white shadow-md d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #2563eb, #0284c7); border: none;">
                        <span>RSVP Now for Event</span>
                        <i class="bi bi-arrow-right-short fs-5"></i>
                    </a>
                ` : `
                    <button class="btn btn-secondary rounded-pill px-4 py-2 fw-bold" disabled><i class="bi bi-check-circle me-1"></i> Event Completed</button>
                `}
            </div>
        `;

        const modalInstance = new bootstrap.Modal(document.getElementById('eventDetailModal'));
        modalInstance.show();
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

/// Render Google / Meta Dev Conference Style Event Cards in 2-Column Grid
function renderFloatingEventCard(event, isPast = false) {
    const eventDate = new Date(event.event_date);
    const day = String(eventDate.getDate()).padStart(2, '0');
    const month = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
    const year = eventDate.getFullYear();
    const timeStr = eventDate.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });

    let statusBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 small fw-bold"><i class="bi bi-calendar-event me-1"></i> Upcoming Contest</span>`;
    if (isPast || event.status === 'completed') {
        statusBadge = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1.5 small fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Completed Session</span>`;
    } else if (event.status === 'ongoing') {
        statusBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1.5 small fw-bold"><span class="pulse-dot-green me-1.5"></span> Live Workshop</span>`;
    }

    const registeredCount = event.registered_count || 45;
    const bannerUrl = escapeHtml(event.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop');

    return `
        <div class="col-md-6 col-lg-6">
            <div class="google-meta-card h-100 d-flex flex-column open-event-detail-btn" data-event-id="${escapeHtml(event.id)}" style="cursor: pointer;">
                <!-- Cover Image Banner -->
                <div class="position-relative overflow-hidden" style="height: 195px;">
                    <img src="${bannerUrl}" class="w-100 h-100 object-fit-cover card-banner-zoom" alt="${escapeHtml(event.title)}">
                    <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.15) 0%, rgba(15,23,42,0.85) 100%);"></div>

                    <!-- Floating Frosted Date Badge -->
                    <div class="position-absolute top-0 start-0 m-3 z-2">
                        <div class="google-date-badge text-center" style="min-width: 60px;">
                            <span class="d-block fw-extrabold text-primary lh-1" style="font-size: 1.3rem;">${day}</span>
                            <span class="small fw-bold text-dark text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">${month} '${String(year).slice(-2)}</span>
                        </div>
                    </div>

                    <!-- Club Badge Floating Overlay -->
                    <div class="position-absolute bottom-0 start-0 m-3 z-2">
                        <a href="#" class="filter-this-club-btn badge bg-dark bg-opacity-80 text-white border border-white-20 rounded-pill px-3 py-1.5 text-decoration-none shadow-sm backdrop-blur d-inline-flex align-items-center gap-1.5" data-club-id="${escapeHtml(event.club_id)}" title="Filter all events by ${escapeHtml(event.club_name)}">
                            <i class="bi bi-shield-fill text-info"></i> <span>${escapeHtml(event.club_short_name || event.club_name)}</span>
                        </a>
                    </div>
                </div>

                <!-- Event Body -->
                <div class="p-4 flex-grow-1 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        ${statusBadge}
                        <span class="small text-secondary fw-semibold" style="font-size: 0.8rem;">
                            <i class="bi bi-people-fill text-primary me-1"></i> <strong class="text-dark">${registeredCount}+</strong> Coders RSVP'd
                        </span>
                    </div>

                    <h4 class="fw-bold text-dark mb-2" style="font-size: 1.25rem; line-height: 1.35; letter-spacing: -0.3px;">
                        ${escapeHtml(event.title)}
                    </h4>

                    <p class="text-secondary small mb-3 flex-grow-1" style="font-size: 0.88rem; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${escapeHtml(event.description || 'Join us for an exciting campus tech session organized by student chapter leads at UIT.')}
                    </p>

                    ${event.outcomes_summary ? `
                        <div class="rounded-3 p-2.5 mb-3 bg-light border border-light-subtle small text-dark d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                            <i class="bi bi-award-fill text-warning fs-5 flex-shrink-0"></i>
                            <div><strong>Rewards:</strong> ${escapeHtml(event.outcomes_summary)}</div>
                        </div>
                    ` : ''}

                    <!-- Meta Bar & Footer Button -->
                    <div class="pt-3 border-top mt-auto">
                        <div class="small text-muted mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size: 0.8rem;">
                            <span class="text-dark fw-medium"><i class="bi bi-geo-alt-fill text-danger me-1"></i> ${escapeHtml(event.venue)}</span>
                            <span class="text-secondary"><i class="bi bi-clock-fill text-primary me-1"></i> ${timeStr}</span>
                        </div>

                        ${!isPast && event.status !== 'completed' ? `
                            <a href="event-detail.html?id=${escapeHtml(event.id)}" class="btn btn-primary rounded-pill w-100 py-2.5 fw-bold text-white text-decoration-none shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #2563eb, #0284c7); border: none; font-size: 0.9rem;">
                                <span>RSVP & View Full Details</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        ` : `
                            <a href="event-detail.html?id=${escapeHtml(event.id)}" class="btn btn-light text-dark border rounded-pill w-100 py-2.5 text-center d-block text-decoration-none" style="font-size: 0.82rem;"><i class="bi bi-info-circle me-1"></i> View Session Summary</a>
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
            <div class="mb-3">
                <i class="bi bi-exclamation-circle text-primary display-4"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">${escapeHtml(title)}</h5>
            ${subtitle ? `<p class="text-secondary small mb-0">${escapeHtml(subtitle)}</p>` : ''}
            <div class="mt-4">
                <a href="clubs.html" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
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
