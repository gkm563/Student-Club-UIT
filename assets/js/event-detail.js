/**
 * Dynamic Event Detail Sub-Page Controller (ClubHub UIT)
 * Fetches event by ID, renders Google/Meta Tech Specs, and handles RSVP Form Submit
 */

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('id') || 1; // Default to ID 1 if not specified

    const getApiUrl = (endpoint) => `api/${endpoint}`;

    // Fetch All Events to find target
    fetch(getApiUrl('events.php'))
        .then(res => res.json())
        .then(response => {
            if (response.status !== 'success' || !response.data) {
                renderErrorState();
                return;
            }

            const events = response.data;
            const targetEvent = events.find(e => String(e.id) === String(eventId)) || events[0];

            if (targetEvent) {
                renderEventPage(targetEvent);
            } else {
                renderErrorState();
            }
        })
        .catch(err => {
            console.error('Error fetching event details:', err);
            renderErrorState();
        });

    // Populate Page Elements
    function renderEventPage(event) {
        const eventDate = new Date(event.event_date);
        const day = String(eventDate.getDate()).padStart(2, '0');
        const month = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
        const year = eventDate.getFullYear();
        const fullDateStr = eventDate.toLocaleString('default', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        const timeStr = eventDate.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });

        document.title = `${event.title} | ClubHub UIT`;

        // Breadcrumb Title
        const breadcrumbTitle = document.getElementById('eventBreadcrumbTitle');
        if (breadcrumbTitle) breadcrumbTitle.textContent = event.title;

        // Hero Fields
        const detailTitle = document.getElementById('detailTitle');
        if (detailTitle) detailTitle.textContent = event.title;

        const detailVenue = document.getElementById('detailVenue');
        if (detailVenue) detailVenue.textContent = event.venue;

        const detailDateTime = document.getElementById('detailDateTime');
        if (detailDateTime) detailDateTime.textContent = `${fullDateStr} at ${timeStr}`;

        const registeredCount = event.registered_count || 45;
        const detailRsvpCount = document.getElementById('detailRsvpCount');
        if (detailRsvpCount) detailRsvpCount.textContent = `${registeredCount}+ Coders RSVP'd`;

        // Status Badge & Past Event Toggle
        const isPast = (eventDate < new Date()) || (event.status === 'completed');
        const detailStatusBadge = document.getElementById('detailStatusBadge');
        const rsvpForm = document.getElementById('eventRsvpForm');
        const concludedNotice = document.getElementById('concludedEventNotice');
        const ticketHeader = document.getElementById('ticketHeader');
        const ticketHeaderBadge = document.getElementById('ticketHeaderBadge');
        const ticketHeaderTitle = document.getElementById('ticketHeaderTitle');
        const ticketHeaderSub = document.getElementById('ticketHeaderSub');

        const completedGallerySection = document.getElementById('completedGallerySection');
        const completedWinnersSection = document.getElementById('completedWinnersSection');

        if (isPast) {
            if (detailStatusBadge) {
                detailStatusBadge.className = 'badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1.5 fw-bold';
                detailStatusBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Completed Session`;
            }
            if (rsvpForm) rsvpForm.classList.add('d-none');
            if (concludedNotice) concludedNotice.classList.remove('d-none');

            if (completedGallerySection) completedGallerySection.classList.remove('d-none');
            if (completedWinnersSection) completedWinnersSection.classList.remove('d-none');

            if (ticketHeader) ticketHeader.style.background = 'linear-gradient(135deg, #475569, #334155)';
            if (ticketHeaderBadge) ticketHeaderBadge.textContent = 'Session Concluded';
            if (ticketHeaderTitle) ticketHeaderTitle.textContent = 'Event Completed';
            if (ticketHeaderSub) ticketHeaderSub.textContent = 'Registrations for this session have ended.';
        } else {
            if (rsvpForm) rsvpForm.classList.remove('d-none');
            if (concludedNotice) concludedNotice.classList.add('d-none');

            if (completedGallerySection) completedGallerySection.classList.add('d-none');
            if (completedWinnersSection) completedWinnersSection.classList.add('d-none');

            if (event.status === 'ongoing') {
                if (detailStatusBadge) {
                    detailStatusBadge.className = 'badge bg-warning-subtle text-warning border rounded-pill px-3 py-1.5 fw-bold';
                    detailStatusBadge.innerHTML = `<span class="pulse-dot-green me-1.5"></span> Live Workshop`;
                }
            } else {
                if (detailStatusBadge) {
                    detailStatusBadge.className = 'badge bg-primary-subtle text-primary border rounded-pill px-3 py-1.5 fw-bold';
                    detailStatusBadge.innerHTML = `<i class="bi bi-calendar-event me-1"></i> Upcoming Contest`;
                }
            }
        }

        // Club Badge
        const detailClubBadge = document.getElementById('detailClubBadge');
        if (detailClubBadge) {
            detailClubBadge.innerHTML = `<i class="bi bi-shield-fill text-info me-1"></i> ${escapeHtml(event.club_short_name || event.club_name)}`;
        }

        // Cover Banner Image
        const bannerUrl = escapeHtml(event.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=1200&auto=format&fit=crop');
        const detailBannerImg = document.getElementById('detailBannerImg');
        if (detailBannerImg) detailBannerImg.src = bannerUrl;

        // Date Pill
        const detailDay = document.getElementById('detailDay');
        if (detailDay) detailDay.textContent = day;

        const detailMonthYear = document.getElementById('detailMonthYear');
        if (detailMonthYear) detailMonthYear.textContent = `${month} '${String(year).slice(-2)}`;

        // Rewards & Outcomes
        const detailOutcomes = document.getElementById('detailOutcomes');
        if (detailOutcomes) {
            let summaryText = escapeHtml(event.outcomes_summary || 'Participation Certificate, Swags & Mentorship');
            if (event.speaker_name) {
                summaryText += ` <br><span class="text-secondary small mt-1 d-inline-block"><i class="bi bi-mic-fill text-primary me-1"></i><strong>Key Speaker:</strong> ${escapeHtml(event.speaker_name)} ${event.speaker_designation ? `(${escapeHtml(event.speaker_designation)})` : ''}</span>`;
            }
            detailOutcomes.innerHTML = summaryText;
        }

        // Description
        const detailDescription = document.getElementById('detailDescription');
        if (detailDescription) {
            detailDescription.textContent = event.description || 'Join us for an immersive technical session organized by student chapter leads. Gain hands-on practice, network with domain mentors, and showcase your problem-solving capabilities.';
        }

        // Club Info Box
        const detailClubLogo = document.getElementById('detailClubLogo');
        if (detailClubLogo && event.club_logo) detailClubLogo.src = escapeHtml(event.club_logo);

        const detailClubName = document.getElementById('detailClubName');
        if (detailClubName) detailClubName.textContent = event.club_name;

        const detailClubShort = document.getElementById('detailClubShort');
        if (detailClubShort) detailClubShort.textContent = `${event.club_short_name || 'Student Club'} • Official SAC Society`;

        // Fetch Uploaded Event Photos from Gallery API
        fetch(getApiUrl(`gallery.php?event_id=${encodeURIComponent(event.id)}`))
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    renderEventGallery(res.data);
                }
            })
            .catch(err => console.error('Gallery fetch error:', err));
    }

    function renderEventGallery(photos) {
        const galleryGrid = document.getElementById('eventGalleryGrid');
        if (!galleryGrid) return;

        galleryGrid.innerHTML = photos.map(photo => `
            <div class="col-6 col-md-6">
                <div class="rounded-4 overflow-hidden shadow-xs border position-relative" style="height: 200px;">
                    <img src="${escapeHtml(photo.media_url)}" class="w-100 h-100 object-fit-cover card-banner-zoom" alt="${escapeHtml(photo.caption || 'Event Recap Photo')}">
                    <div class="position-absolute bottom-0 start-0 m-2 badge bg-dark bg-opacity-75 text-white backdrop-blur">${escapeHtml(photo.caption || 'Event Recap Moment')}</div>
                </div>
            </div>
        `).join('');
    }

    function renderErrorState() {
        const titleEl = document.getElementById('detailTitle');
        if (titleEl) titleEl.textContent = 'Event Not Found';
    }

    // Handle RSVP Form Submit
    const rsvpForm = document.getElementById('eventRsvpForm');
    const rsvpSuccessAlert = document.getElementById('rsvpSuccessAlert');
    const rsvpSubmitBtn = document.getElementById('rsvpSubmitBtn');

    if (rsvpForm) {
        rsvpForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (rsvpSubmitBtn) {
                rsvpSubmitBtn.disabled = true;
                rsvpSubmitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span> Confirming Seat...`;
            }

            setTimeout(() => {
                if (rsvpSubmitBtn) {
                    rsvpSubmitBtn.classList.remove('btn-primary');
                    rsvpSubmitBtn.classList.add('btn-success');
                    rsvpSubmitBtn.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> RSVP Confirmed!`;
                }
                if (rsvpSuccessAlert) {
                    rsvpSuccessAlert.classList.remove('d-none');
                }
            }, 800);
        });
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
