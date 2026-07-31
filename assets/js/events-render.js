/**
 * Advanced Dynamic Events Renderer & Interactive Filter (ClubHub UIT)
 * Includes Club Filter Pills, Status Tabs, Floating Glassmorphism Cards, & Search
 */

function initializeEventsRenderer() {
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
    let currentWingFilter = 'all';

    const eventWing = document.body.dataset.eventWing;
    const pageWingCategories = eventWing === 'technical'
        ? ['technical', 'technical-software-development']
        : eventWing === 'cultural'
            ? ['cultural', 'academic', 'creative']
            : null;

    const getApiUrl = (endpoint) => `api/${endpoint}`;

    function isTechEvent(event) {
        return typeof resolveWing === 'function' && resolveWing(event.category_slug) === 'technical';
    }

    function isCulturalEvent(event) {
        return typeof resolveWing === 'function' && resolveWing(event.category_slug) === 'cultural';
    }

    function splitEventsByWing(events) {
        return {
            technical: events.filter(isTechEvent),
            cultural: events.filter(isCulturalEvent)
        };
    }

    function renderWingEventChip(event, wing) {
        const chipClass = wing === 'cultural' ? 'events-wing-event-chip--cultural' : 'events-wing-event-chip--tech';
        const eventDate = new Date(event.event_date);
        const dateText = Number.isNaN(eventDate.getTime())
            ? 'TBA'
            : eventDate.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });

        return `
            <a href="event-detail.html?id=${encodeURIComponent(event.id)}" class="events-wing-event-chip ${chipClass}" title="${escapeHtml(event.title)}">
                <span class="events-wing-event-chip__date">${escapeHtml(dateText)}</span>
                <span class="events-wing-event-chip__title">${escapeHtml(event.title)}</span>
            </a>
        `;
    }

    function loadEventsWingShowcase(events) {
        const techList = document.getElementById('techWingEventsList');
        const culturalList = document.getElementById('culturalWingEventsList');
        const techCountEl = document.getElementById('techWingEventCount');
        const culturalCountEl = document.getElementById('culturalWingEventCount');

        if (!techList && !culturalList) return;

        const { technical, cultural } = splitEventsByWing(events);

        if (techCountEl) techCountEl.textContent = technical.length;
        if (culturalCountEl) culturalCountEl.textContent = cultural.length;

        if (techList) {
            techList.innerHTML = technical.length
                ? technical.slice(0, 5).map(event => renderWingEventChip(event, 'technical')).join('')
                : '<span class="small text-muted">No tech events published yet.</span>';
        }

        if (culturalList) {
            culturalList.innerHTML = cultural.length
                ? cultural.slice(0, 5).map(event => renderWingEventChip(event, 'cultural')).join('')
                : '<span class="small text-muted">No cultural events published yet.</span>';
        }
    }

    function updateHeroWingStats(events) {
        const { technical, cultural } = splitEventsByWing(events);
        const heroTotal = document.getElementById('heroTotalEventsCount');
        const heroTech = document.getElementById('heroTechEventsCount');
        const heroCultural = document.getElementById('heroCulturalEventsCount');
        const heroAttendees = document.getElementById('heroTotalAttendees');

        if (heroTotal) heroTotal.textContent = `${events.length}`;
        if (heroTech) heroTech.textContent = `${technical.length}`;
        if (heroCultural) heroCultural.textContent = `${cultural.length}`;
        if (heroAttendees) {
            const totalAttendees = events.reduce((acc, curr) => acc + (parseInt(curr.actual_attended || curr.registered_count || 0, 10) || 0), 0);
            heroAttendees.textContent = totalAttendees > 0 ? `${totalAttendees.toLocaleString()}+` : '—';
        }
    }

    function renderActiveWingBadge() {
        const badgeHost = document.getElementById('eventsActiveWingBadge');
        if (!badgeHost) return;

        if (currentWingFilter === 'all') {
            badgeHost.innerHTML = '';
            return;
        }

        const meta = getWingMeta(currentWingFilter);
        badgeHost.innerHTML = `
            <div class="alert alert-${currentWingFilter === 'cultural' ? 'danger' : 'primary'} border-0 rounded-4 shadow-sm py-2 px-4 d-flex align-items-center justify-content-between mb-0">
                <div class="d-flex align-items-center gap-2 small">
                    <i class="bi ${meta.icon}"></i>
                    <span class="fw-bold">Showing:</span>
                    <span class="badge bg-white text-dark rounded-pill px-3 py-1">${escapeHtml(meta.label)} events only</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 fw-bold" id="clearEventsWingFilterBtn">
                    Show all wings <i class="bi bi-x-lg ms-1"></i>
                </button>
            </div>
        `;

        const clearBtn = document.getElementById('clearEventsWingFilterBtn');
        if (clearBtn) clearBtn.addEventListener('click', () => setWingFilter('all'));
    }

    function setWingFilter(wing) {
        currentWingFilter = wing || 'all';
        document.querySelectorAll('.event-wing-tab').forEach(tab => {
            tab.classList.toggle('active', (tab.dataset.eventsWing || 'all') === currentWingFilter);
        });
        renderActiveWingBadge();
        applyFiltersAndRender();

        const directory = document.getElementById('eventsDirectorySection');
        if (directory && wing !== 'all') {
            directory.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Fetch Events API
    fetch(getApiUrl('events.php'))
        .then(res => res.json())
        .then(response => {
            if (response.status !== 'success') {
                renderEmptyState(upcomingContainer, 'Failed to load events.');
                return;
            }

            allEvents = (response.data || []).filter(event => {
                if (!pageWingCategories) return true;
                return pageWingCategories.includes((event.category_slug || '').toLowerCase());
            });

            loadEventsWingShowcase(allEvents);
            updateHeroWingStats(allEvents);

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

    // Render Club Filter Control (Dropdown & Pills)
    function renderClubFilterPills() {
        const clubSelectFilter = document.getElementById('clubSelectFilter');
        if (clubSelectFilter) {
            let optionsHtml = `<option value="all">All Campus Clubs (${allEvents.length})</option>`;
            allClubsMap.forEach(club => {
                const count = allEvents.filter(e => e.club_id === club.id).length;
                optionsHtml += `<option value="${escapeHtml(club.id)}">${escapeHtml(club.short || club.name)} (${count})</option>`;
            });
            clubSelectFilter.innerHTML = optionsHtml;
            clubSelectFilter.value = currentClubFilter;

            clubSelectFilter.addEventListener('change', (e) => {
                currentClubFilter = e.target.value;
                applyFiltersAndRender();
            });
        }

        if (clubPillsContainer) {
            let pillsHtml = `
                <button class="btn btn-sm rounded-pill px-3.5 py-2 fw-semibold club-pill-btn active" data-club-id="all">
                    <i class="bi bi-collection-fill me-1"></i> All Clubs (${allEvents.length})
                </button>
            `;

            allClubsMap.forEach(club => {
                const count = allEvents.filter(e => e.club_id === club.id).length;
                pillsHtml += `
                    <button class="btn btn-sm rounded-pill px-3.5 py-2 fw-semibold club-pill-btn" data-club-id="${club.id}">
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
                    if (clubSelectFilter) clubSelectFilter.value = currentClubFilter;
                    applyFiltersAndRender();
                });
            });
        }
    }

    // Filter Logic & Render Unified Event Stream (1st: Ongoing, 2nd: Upcoming, 3rd: Past)
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

            // Wing Filter (events.html only)
            const matchesWing = currentWingFilter === 'all'
                || (currentWingFilter === 'technical' && isTechEvent(evt))
                || (currentWingFilter === 'cultural' && isCulturalEvent(evt));

            return matchesQuery && matchesClub && matchesWing;
        });

        // 3 Category Lists
        const ongoingList = filtered.filter(e => e.status === 'ongoing' || e.status === 'live');
        const upcomingList = filtered.filter(e => (new Date(e.event_date) >= now || e.status === 'upcoming') && e.status !== 'completed' && e.status !== 'ongoing' && e.status !== 'live');
        const pastList = filtered.filter(e => (new Date(e.event_date) < now || e.status === 'completed') && e.status !== 'ongoing' && e.status !== 'live');

        // Master Priority List: 1st Ongoing, 2nd Upcoming, 3rd Past
        const masterPriorityList = [...ongoingList, ...upcomingList, ...pastList];

        // Update Counter Badges
        const heroTotalEvents = document.getElementById('heroTotalEventsCount');
        const heroTotalAttendees = document.getElementById('heroTotalAttendees');
        const heroActiveClubs = document.getElementById('heroActiveClubs');
        const streamCountBadge = document.getElementById('streamCountBadge');

        if (heroTotalEvents) heroTotalEvents.textContent = `${allEvents.length}`;
        updateHeroWingStats(allEvents);

        // Select Stream Display List Based on Selected Status Tab
        let displayList = masterPriorityList;
        if (currentStatusTab === 'ongoing') {
            displayList = ongoingList;
        } else if (currentStatusTab === 'upcoming') {
            displayList = upcomingList;
        } else if (currentStatusTab === 'past') {
            displayList = pastList;
        }

        if (streamCountBadge) {
            const wingLabel = currentWingFilter === 'all' ? '' : ` · ${getWingMeta(currentWingFilter).label}`;
            streamCountBadge.textContent = `${displayList.length} Event${displayList.length === 1 ? '' : 's'}${wingLabel}`;
        }

        renderActiveWingBadge();

        const mainContainer = document.getElementById('mainEventsListContainer') || upcomingContainer;
        if (mainContainer) {
            if (displayList.length === 0) {
                mainContainer.innerHTML = `
                    <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm border p-4">
                        <i class="bi bi-calendar-x fs-1 text-primary d-block mb-2"></i>
                        <h6 class="fw-bold mb-1">No Events Found</h6>
                        <p class="small text-muted mb-0">No matching events for the selected filter. Try switching tabs or clearing your search.</p>
                    </div>
                `;
            } else {
                mainContainer.innerHTML = displayList.map(e => {
                    const isPast = (new Date(e.event_date) < now) || (e.status === 'completed');
                    return renderFloatingEventCard(e, isPast);
                }).join('');
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
        let statusBadge = `<span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size:0.8rem;"><i class="bi bi-calendar-event-fill me-1"></i> UPCOMING CONTEST</span>`;
        if (isPast) {
            statusBadge = `<span class="badge bg-success text-white rounded-pill px-3 py-1.5 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size:0.8rem;"><i class="bi bi-award-fill me-1"></i> COMPLETED SESSION</span>`;
        } else if (targetEvent.status === 'ongoing' || targetEvent.status === 'live') {
            statusBadge = `<span class="badge bg-danger text-white rounded-pill px-3 py-1.5 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size:0.8rem;"><span class="spinner-grow spinner-grow-sm text-white me-1" role="status" style="width:7px;height:7px;"></span> LIVE WORKSHOP</span>`;
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
                            <span class="small text-white-80" style="font-size: 0.75rem;">Official USC UIT Governed Society</span>
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
                    <span class="badge bg-white text-dark border rounded-pill px-3 py-1.5 fw-semibold small"><i class="bi bi-check-circle-fill text-success me-1"></i> USC UIT Verified Event</span>
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

    // Wing Filter Tabs & Showcase Buttons
    document.querySelectorAll('.event-wing-tab').forEach(tab => {
        tab.addEventListener('click', () => setWingFilter(tab.dataset.eventsWing || 'all'));
    });

    document.querySelectorAll('[data-events-wing-filter]').forEach(btn => {
        btn.addEventListener('click', () => setWingFilter(btn.getAttribute('data-events-wing-filter')));
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
}

/// Render Google / Meta Dev Conference Style Event Cards in 2-Column Grid
function renderFloatingEventCard(event, isPast = false) {
    const eventDate = new Date(event.event_date);
    const day = String(eventDate.getDate()).padStart(2, '0');
    const month = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
    const year = eventDate.getFullYear();
    const timeStr = eventDate.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });
    const fullDateFormatted = eventDate.toLocaleString('default', { weekday: 'short', month: 'short', day: 'numeric' });

    let statusBadge = `<span class="badge rounded-pill px-2.5 py-1.5 fw-extrabold shadow-sm d-inline-flex align-items-center gap-1.5 text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: 1.5px solid rgba(255, 255, 255, 0.9); font-size: 0.72rem; letter-spacing: 0.3px; backdrop-filter: blur(8px);"><i class="bi bi-calendar-check-fill me-1"></i> UPCOMING</span>`;
    if (isPast || event.status === 'completed') {
        statusBadge = `<span class="badge rounded-pill px-2.5 py-1.5 fw-extrabold shadow-sm d-inline-flex align-items-center gap-1.5 text-white" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: 1.5px solid rgba(255, 255, 255, 0.9); font-size: 0.72rem; letter-spacing: 0.3px; backdrop-filter: blur(8px);"><i class="bi bi-award-fill me-1"></i> COMPLETED</span>`;
    } else if (event.status === 'ongoing' || event.status === 'live') {
        statusBadge = `<span class="badge rounded-pill px-2.5 py-1.5 fw-extrabold shadow-sm d-inline-flex align-items-center gap-1.5 text-white" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: 1.5px solid rgba(255, 255, 255, 0.9); font-size: 0.72rem; letter-spacing: 0.3px; backdrop-filter: blur(8px);"><span class="spinner-grow spinner-grow-sm me-1 text-white" role="status" style="width:7px;height:7px;"></span> LIVE ONGOING</span>`;
    }

    const registeredCount = event.registered_count || 45;
    const bannerUrl = escapeHtml(event.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop');
    const eventWingType = typeof resolveWing === 'function' ? resolveWing(event.category_slug) : null;
    const wingMeta = typeof getWingMeta === 'function'
        ? getWingMeta(eventWingType)
        : { wing: null, label: 'Campus Event', icon: 'bi-calendar-event' };
    const wingTagClass = eventWingType === 'cultural' ? 'club-card-wing-tag--cultural' : 'club-card-wing-tag--tech';
    const wingTagLabel = wingMeta.wing ? wingMeta.label : 'Campus Event';

    return `
        <div class="col-md-6 col-lg-4">
            <div class="google-meta-card h-100 d-flex flex-column open-event-detail-btn shadow-sm transition-all" data-event-id="${escapeHtml(event.id)}" style="cursor: pointer; border-radius: 20px; overflow: hidden; border: 1.5px solid #e2e8f0; background: #ffffff;">
                <!-- Cover Image Banner -->
                <div class="position-relative overflow-hidden" style="height: 190px;">
                    <img src="${bannerUrl}" class="w-100 h-100 object-fit-cover card-banner-zoom" alt="${escapeHtml(event.title)}">
                    <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.15) 0%, rgba(15,23,42,0.85) 100%);"></div>

                    <div class="position-absolute top-0 start-0 m-3 z-2 d-flex flex-column gap-2 align-items-start">
                        <span class="club-card-wing-tag ${wingTagClass}"><i class="bi ${wingMeta.icon}"></i> ${escapeHtml(wingTagLabel)}</span>
                        <div class="google-date-badge text-center shadow-sm" style="min-width: 58px; padding: 7px 12px; background: rgba(255,255,255,0.95); backdrop-filter: blur(12px); border-radius: 14px; border: 1px solid rgba(255,255,255,0.9);">
                            <span class="date-day d-block text-primary fw-black" style="font-size: 1.25rem; line-height: 1; font-family: 'Outfit', sans-serif;">${day}</span>
                            <span class="date-month d-block text-dark fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">${month} '${String(year).slice(-2)}</span>
                        </div>
                    </div>

                    <!-- Status Ribbon Floating Top Right -->
                    <div class="position-absolute top-0 end-0 m-3 z-2">
                        ${statusBadge}
                    </div>

                    <!-- Club Badge Floating Overlay -->
                    <div class="position-absolute bottom-0 start-0 m-3 z-2">
                        <a href="#" class="filter-this-club-btn badge text-white border border-white-20 rounded-pill px-3 py-1.5 text-decoration-none shadow-sm d-inline-flex align-items-center gap-1.5" data-club-id="${escapeHtml(event.club_id)}" style="font-size: 0.76rem; background: rgba(15, 23, 42, 0.78); backdrop-filter: blur(10px);" title="Filter all events by ${escapeHtml(event.club_name)}">
                            <i class="bi bi-shield-fill text-info"></i> <span>${escapeHtml(event.club_short_name || event.club_name)}</span>
                        </a>
                    </div>
                </div>

                <!-- Event Body -->
                <div class="p-4 flex-grow-1 d-flex flex-column bg-white">
                    <!-- Date & RSVP Meta Header Bar -->
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: #f1f5f9 !important;">
                        <span class="small text-primary fw-extrabold" style="font-size: 0.80rem;">
                            <i class="bi bi-calendar3 me-1"></i> ${fullDateFormatted}
                        </span>
                        <span class="small text-secondary fw-bold" style="font-size: 0.80rem;">
                            <i class="bi bi-people-fill text-primary me-1"></i> <strong class="text-dark fw-extrabold">${registeredCount}+</strong> Coders
                        </span>
                    </div>

                    <!-- Event Title -->
                    <h4 class="fw-extrabold text-dark mb-2 mt-1" style="font-size: 1.15rem; line-height: 1.4; letter-spacing: -0.3px; color: #0f172a !important;">
                        ${escapeHtml(event.title)}
                    </h4>

                    <!-- Event Description -->
                    <p class="text-secondary small mb-3 flex-grow-1" style="font-size: 0.86rem; line-height: 1.6; color: #475569 !important; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${escapeHtml(event.description || 'Join us for an exciting campus tech session organized by student chapter leads at UIT.')}
                    </p>

                    ${event.outcomes_summary ? `
                        <div class="rounded-3 p-2.5 mb-3 bg-light border border-light-subtle small text-dark d-flex align-items-center gap-2" style="font-size: 0.78rem;">
                            <i class="bi bi-award-fill text-warning fs-5 flex-shrink-0"></i>
                            <div class="text-truncate"><strong>Rewards:</strong> ${escapeHtml(event.outcomes_summary)}</div>
                        </div>
                    ` : ''}

                    <!-- Meta Bar & Footer Button -->
                    <div class="pt-3 border-top mt-auto" style="border-color: #f1f5f9 !important;">
                        <div class="small text-muted mb-3 d-flex align-items-center justify-content-between gap-2" style="font-size: 0.80rem;">
                            <span class="text-dark fw-bold text-truncate" style="max-width: 60%;"><i class="bi bi-geo-alt-fill text-danger me-1"></i> ${escapeHtml(event.venue)}</span>
                            <span class="text-secondary font-monospace fw-bold"><i class="bi bi-clock-fill text-primary me-1"></i> ${timeStr}</span>
                        </div>

                        ${event.status === 'ongoing' || event.status === 'live' ? `
                            <a href="event-detail.html?id=${escapeHtml(event.id)}" class="btn rounded-pill w-100 py-2.5 fw-extrabold text-white text-decoration-none shadow-sm d-flex align-items-center justify-content-center gap-1.5" style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none; font-size: 0.88rem; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.35);">
                                <span>Join Live Session Now</span>
                                <i class="bi bi-play-circle-fill text-white ms-1"></i>
                            </a>
                        ` : (!isPast && event.status !== 'completed' ? `
                            <a href="event-detail.html?id=${escapeHtml(event.id)}" class="btn rounded-pill w-100 py-2.5 fw-extrabold text-white text-decoration-none shadow-sm d-flex align-items-center justify-content-center gap-1.5" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none; font-size: 0.88rem; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.35);">
                                <span>Explore & Register Free</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        ` : `
                            <a href="event-detail.html?id=${escapeHtml(event.id)}" class="btn rounded-pill w-100 py-2.5 fw-extrabold text-white text-decoration-none shadow-sm d-flex align-items-center justify-content-center gap-1.5" style="background: linear-gradient(135deg, #059669, #047857); border: none; font-size: 0.88rem; box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);">
                                <span>See Event Details & Recaps</span>
                                <i class="bi bi-arrow-right-circle-fill text-white ms-1"></i>
                            </a>
                        `)}
                    </div>
                </div>
            </div>
        </div>
    `;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeEventsRenderer);
} else {
    initializeEventsRenderer();
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
