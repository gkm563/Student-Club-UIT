/**
 * Dynamic Event Detail Sub-Page Controller (ClubHub UIT)
 * Fully dynamic — all data sourced from the Club Portal API (events.php + gallery.php + clubs.php)
 * Supports: title, venue, date, status, description, speaker, agenda, target_audience,
 *           outcomes_summary, registered_count, actual_attended, registration_link,
 *           club_name, club_logo, club_tagline, event_type, banner, gallery photos
 */

let galleryPhotosList = [];
let currentPhotoIndex = 0;

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('id') || 1;

    const getApiUrl = (endpoint) => `api/${endpoint}`;

    // Show skeleton loaders
    showSkeletonLoaders();

    // Fetch All Events to find target
    fetch(getApiUrl('events.php'))
        .then(res => res.json())
        .then(response => {
            if (response.status !== 'success' || !response.data) {
                renderErrorState();
                return;
            }

            const events = response.data;
            const targetEvent = events.find(e => String(e.id) === String(eventId));

            if (targetEvent) {
                renderEventPage(targetEvent);
            } else {
                renderErrorState('Event not found. It may be unpublished or the link is invalid.');
            }
        })
        .catch(err => {
            console.error('Error fetching event details:', err);
            renderErrorState('Unable to load event. Please check your connection.');
        });

    // =========================================================
    // CORE RENDER FUNCTION — 100% Dynamic from Club Portal API
    // =========================================================
    function renderEventPage(event) {
        const eventDate = new Date(event.event_date);
        const day = String(eventDate.getDate()).padStart(2, '0');
        const month = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
        const year = eventDate.getFullYear();
        const fullDateStr = eventDate.toLocaleString('default', {
            weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
        });

        let hours = eventDate.getHours();
        const minutes = String(eventDate.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const formattedHours = String(hours).padStart(2, '0');
        const timeStr = `${formattedHours}:${minutes} ${ampm}`;

        // ── Page Meta ──────────────────────────────────────────────
        document.title = `${event.title} | SAC Events — UIT`;

        // ── Breadcrumb ─────────────────────────────────────────────
        setEl('eventBreadcrumbTitle', event.title);

        // ── Hero: Title, Tagline ───────────────────────────────────
        setEl('detailTitle', event.title);
        setEl('detailTagline', event.tagline || (event.club_tagline || ''));

        // ── Hero: Status Badge ─────────────────────────────────────
        renderStatusBadge(event, eventDate);

        // ── Hero: Club Badge ───────────────────────────────────────
        setHtml('detailClubBadge', `<i class="bi bi-shield-fill text-primary me-1"></i> ${escapeHtml(event.club_short_name || event.club_name)}`);

        // ── Hero: Event Type Badge ─────────────────────────────────
        setEl('detailEventType', event.event_type || 'Campus Event');

        // ── Hero: Venue & DateTime Pills ───────────────────────────
        setEl('detailVenue', event.venue || 'United Institute Of Technology, NH 2, Prayagraj');
        setEl('detailDateTime', `${fullDateStr} at ${timeStr}`);

        // ── Hero: RSVP Count ───────────────────────────────────────
        const attended = Number(event.actual_attended) || 0;
        const registered = Number(event.registered_count) || 0;
        const countDisplay = attended > 0 ? attended : (registered > 0 ? registered : 0);
        const rsvpText = event.status === 'completed'
            ? (countDisplay > 0 ? `${countDisplay} Students Attended` : 'Session Completed')
            : (countDisplay > 0 ? `${countDisplay}+ Students RSVP'd` : 'Be the first to RSVP!');
        setEl('detailRsvpCount', rsvpText);

        // ── Cover Banner ───────────────────────────────────────────
        const bannerUrl = event.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=1200&auto=format&fit=crop';
        const bannerEl = document.getElementById('detailBannerImg');
        if (bannerEl) {
            bannerEl.src = escapeHtml(bannerUrl);
            bannerEl.alt = escapeHtml(event.title);
        }

        // ── Date Pill ──────────────────────────────────────────────
        setEl('detailDay', day);
        setEl('detailMonthYear', `${month} '${String(year).slice(-2)}`);

        // ── Spec Cards ─────────────────────────────────────────────
        setEl('detailTimingSpec', `${fullDateStr} at ${timeStr}`);
        setEl('detailVenueSpec', event.venue || 'United Institute Of Technology, NH 2, Prayagraj');
        setEl('detailEligibility', event.target_audience || 'All UIT Students (B.Tech / BCA / MCA / Diploma)');
        setEl('detailFeePerks', event.outcomes_summary
            ? `Free Entry • ${event.outcomes_summary.split(',')[0].trim()}`
            : '100% Free • SAC E-Certificates & Swags');

        // ── Rewards & Outcomes Banner ──────────────────────────────
        const rewardsBannerContainer = document.getElementById('rewardsBannerContainer');
        const showRewards = isTrue(event.show_rewards);
        if (rewardsBannerContainer) {
            if (showRewards && event.outcomes_summary && event.outcomes_summary.trim() !== '') {
                rewardsBannerContainer.classList.remove('d-none');
                setHtml('detailOutcomes', escapeHtml(event.outcomes_summary));
            } else {
                rewardsBannerContainer.classList.add('d-none');
            }
        }

        // ── About / Description ────────────────────────────────────
        setEl('detailDescription', event.description || 'Join us for an immersive session organized by student chapter leads. Gain hands-on practice, network with domain mentors, and showcase your capabilities.');

        // ── Speaker Section ────────────────────────────────────────
        renderSpeakerSection(event);

        // ── Agenda / Timeline ──────────────────────────────────────
        renderAgendaSection(event);

        // ── What You Will Gain — Dynamic based on event data ───────
        renderGainSection(event);

        // ── Registration Link ──────────────────────────────────────
        renderRegistrationState(event, eventDate, fullDateStr, timeStr);

        // ── Club Info: Sidebar Mini Profile ───────────────────────
        const clubLogoSrc = escapeHtml(event.club_logo || 'assets/United Logo.webp');
        setAttr('miniClubLogo', 'src', clubLogoSrc);
        setAttr('miniClubLogo', 'alt', event.club_name);
        setEl('miniClubName', event.club_name);
        setEl('miniClubCategory', event.club_short_name || 'Student Chapter');
        setEl('miniClubTagline', event.club_tagline || `Official Student Chapter under Dean Student Welfare Advisory Committee, United Institute of Technology Prayagraj.`);

        // ── Sidebar Stats: Attended / Members / SAC ───────────────
        if (attended > 0) {
            setEl('miniClubEventCount', attended + '+');
        }
        if (registered > 0) {
            setEl('miniClubMembersCount', registered + '+');
        }

        // Club profile link
        const miniClubProfileBtn = document.getElementById('miniClubProfileBtn');
        if (miniClubProfileBtn && event.club_id) {
            miniClubProfileBtn.href = `clubs.html`;
        }

        // ── Gallery ────────────────────────────────────────────────
        const completedGallerySection = document.getElementById('completedGallerySection');
        fetch(getApiUrl(`gallery.php?event_id=${encodeURIComponent(event.id)}`))
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    renderEventGallery(res.data);
                    if (completedGallerySection) completedGallerySection.classList.remove('d-none');
                }
            })
            .catch(err => console.error('Gallery fetch error:', err));
    }

    // ── Helper to Parse Boolean/Int Toggles ────────────────────────
    function isTrue(val) {
        if (val === undefined || val === null) return true;
        return String(val) === '1' || val === 1 || val === true;
    }

    // ── Status Badge Renderer ──────────────────────────────────────
    function renderStatusBadge(event, eventDate) {
        const isPast = (eventDate < new Date()) || (event.status === 'completed');
        const detailStatusBadge = document.getElementById('detailStatusBadge');
        const rsvpForm = document.getElementById('eventRsvpForm');
        const concludedNotice = document.getElementById('concludedEventNotice');
        const completedGallerySection = document.getElementById('completedGallerySection');
        const completedWinnersSection = document.getElementById('completedWinnersSection');

        if (isPast) {
            if (detailStatusBadge) {
                detailStatusBadge.className = 'badge bg-secondary-subtle text-secondary border rounded-pill px-3 fw-bold';
                detailStatusBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Completed`;
            }
            if (rsvpForm) rsvpForm.classList.add('d-none');
            if (concludedNotice) concludedNotice.classList.remove('d-none');
            if (completedGallerySection) completedGallerySection.classList.remove('d-none');
            if (completedWinnersSection) completedWinnersSection.classList.remove('d-none');
        } else {
            if (rsvpForm) rsvpForm.classList.remove('d-none');
            if (concludedNotice) concludedNotice.classList.add('d-none');
            if (completedGallerySection) completedGallerySection.classList.add('d-none');
            if (completedWinnersSection) completedWinnersSection.classList.add('d-none');

            if (event.status === 'ongoing' || event.status === 'live') {
                if (detailStatusBadge) {
                    detailStatusBadge.className = 'badge bg-danger-subtle text-danger border rounded-pill px-3 fw-bold';
                    detailStatusBadge.innerHTML = `<span class="pulse-dot-red me-1"></span> Live Now`;
                }
            } else {
                if (detailStatusBadge) {
                    detailStatusBadge.className = 'badge bg-primary-subtle text-primary border rounded-pill px-3 fw-bold';
                    detailStatusBadge.innerHTML = `<i class="bi bi-calendar-event me-1"></i> ${escapeHtml(event.event_type || 'Upcoming')}`;
                }
            }
        }
    }

    // ── Speaker Section Renderer ───────────────────────────────────
    function renderSpeakerSection(event) {
        const speakerSection = document.getElementById('speakerSection');
        if (!speakerSection) return;

        const showSpeaker = isTrue(event.show_speaker);
        if (showSpeaker && event.speaker_name && event.speaker_name.trim() !== '') {
            speakerSection.classList.remove('d-none');
            setEl('speakerName', event.speaker_name);
            setEl('speakerDesignation', event.speaker_designation || 'Keynote Speaker');
        } else {
            speakerSection.classList.add('d-none');
        }
    }

    // ── Agenda / Timeline Renderer ─────────────────────────────────
    function renderAgendaSection(event) {
        const agendaSection = document.getElementById('agendaSection');
        const agendaList = document.getElementById('agendaList');
        if (!agendaSection || !agendaList) return;

        const showAgenda = isTrue(event.show_agenda);
        if (showAgenda && event.agenda_timeline && event.agenda_timeline.trim() !== '') {
            agendaSection.classList.remove('d-none');
            // Parse agenda — expected as newline-separated entries or JSON array
            let items = [];
            try {
                items = JSON.parse(event.agenda_timeline);
            } catch {
                // Plain text fallback — each line is an item
                items = event.agenda_timeline.split('\n').map(l => l.trim()).filter(l => l.length > 0);
            }

            if (items.length > 0) {
                agendaList.innerHTML = items.map((item, i) => `
                    <li class="d-flex align-items-start gap-3 mb-3 pb-3 ${i < items.length - 1 ? 'border-bottom' : ''}">
                        <div class="bg-primary-subtle text-primary rounded-3 fw-black d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:34px;height:34px;font-size:0.78rem;font-weight:900;">${String(i + 1).padStart(2, '0')}</div>
                        <span class="text-dark fw-semibold" style="font-size:0.92rem;line-height:1.55;">${escapeHtml(typeof item === 'object' ? (item.title || item.time || JSON.stringify(item)) : item)}</span>
                    </li>
                `).join('');
            } else {
                agendaSection.classList.add('d-none');
            }
        } else {
            agendaSection.classList.add('d-none');
        }
    }

    // ── What You Will Gain — Dynamic ──────────────────────────────
    function renderGainSection(event) {
        const gainContainer = document.getElementById('gainSectionContainer');
        const gainGrid = document.getElementById('gainGrid');
        if (!gainGrid) return;

        const showTakeaways = isTrue(event.show_takeaways);
        if (!showTakeaways) {
            if (gainContainer) gainContainer.classList.add('d-none');
            return;
        } else {
            if (gainContainer) gainContainer.classList.remove('d-none');
        }

        if (event.custom_takeaways && event.custom_takeaways.trim() !== '') {
            const customItems = event.custom_takeaways.split('\n').map(l => l.trim()).filter(l => l.length > 0);
            if (customItems.length > 0) {
                gainGrid.innerHTML = customItems.map(item => `
                    <div class="col-md-6">
                        <div class="gain-card-item h-100 d-flex align-items-start">
                            <div class="bg-primary-subtle text-primary rounded-4 p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:52px;height:52px;font-size:1.35rem;">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
                                    <strong class="text-dark fw-extrabold" style="font-size:1.02rem;">Key Benefit</strong>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 fw-bold flex-shrink-0" style="font-size:0.70rem;">
                                        <i class="bi bi-check2-circle me-1"></i> Included
                                    </span>
                                </div>
                                <p class="small text-secondary mb-0" style="line-height:1.6;font-size:0.88rem;">${escapeHtml(item)}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                return;
            }
        }

        // Build default benefit cards based on actual event data
        const benefits = [
            {
                icon: 'bi-lightbulb-fill',
                colorClass: 'bg-primary-subtle text-primary',
                title: event.event_type || 'Technical Mastery',
                desc: event.description
                    ? event.description.substring(0, 110) + (event.description.length > 110 ? '…' : '')
                    : 'Gain hands-on exposure & build expertise.',
            },
            {
                icon: 'bi-people-fill',
                colorClass: 'bg-purple-subtle',
                iconStyle: 'color:#7c3aed;background:#f5f3ff;',
                title: 'Networking & Mentorship',
                desc: `Connect with ${escapeHtml(event.club_short_name || event.club_name)} leads, alumni & domain experts.`,
            },
            {
                icon: 'bi-award-fill',
                colorClass: 'bg-success-subtle text-success',
                title: 'Verified SAC Certificates',
                desc: 'Official Dean Student Welfare (SAC) verified participation certificates issued to all attendees.',
            },
            {
                icon: 'bi-gift-fill',
                colorClass: 'bg-warning-subtle text-warning-emphasis',
                title: event.outcomes_summary
                    ? (event.outcomes_summary.split(',')[0].trim() || 'Swags & Goodies')
                    : 'Swags & Goodies',
                desc: event.outcomes_summary || 'Win exclusive community swags, t-shirts, stickers & tech gadgets.',
            },
        ];

        gainGrid.innerHTML = benefits.map(b => `
            <div class="col-md-6">
                <div class="gain-card-item h-100 d-flex align-items-start">
                    <div class="${b.colorClass} rounded-4 p-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:52px;height:52px;font-size:1.35rem;${b.iconStyle || ''}">
                        <i class="bi ${b.icon}"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
                            <strong class="text-dark fw-extrabold" style="font-size:1.02rem;">${escapeHtml(b.title)}</strong>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 fw-bold flex-shrink-0" style="font-size:0.70rem;">
                                <i class="bi bi-check2-circle me-1"></i> Included
                            </span>
                        </div>
                        <p class="small text-secondary mb-0" style="line-height:1.6;font-size:0.88rem;">${escapeHtml(b.desc)}</p>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // ── Registration State Renderer ────────────────────────────────
    function renderRegistrationState(event, eventDate, fullDateStr, timeStr) {
        const isPast = (eventDate < new Date()) || (event.status === 'completed');
        const ticketHeader = document.getElementById('ticketHeader');
        const ticketHeaderBadge = document.getElementById('ticketHeaderBadge');
        const ticketHeaderTitle = document.getElementById('ticketHeaderTitle');
        const ticketHeaderSub = document.getElementById('ticketHeaderSub');
        const heroActionBtnText = document.getElementById('heroActionBtnText');
        const heroActionBtn = document.getElementById('heroActionBtn');
        const rsvpSubmitBtn = document.getElementById('rsvpSubmitBtn');

        if (isPast) {
            if (ticketHeader) ticketHeader.style.background = 'linear-gradient(135deg, #475569, #334155)';
            if (ticketHeaderBadge) ticketHeaderBadge.textContent = 'Session Concluded';
            if (ticketHeaderTitle) ticketHeaderTitle.textContent = 'Event Completed';
            if (ticketHeaderSub) ticketHeaderSub.textContent = 'Registrations for this session have ended.';
            if (heroActionBtnText) heroActionBtnText.textContent = 'View Event Recap & Photos';
        } else {
            if (ticketHeaderTitle) ticketHeaderTitle.textContent = `Confirm Your Seat`;
            if (ticketHeaderSub) ticketHeaderSub.textContent = `Join us on ${fullDateStr} at ${timeStr}`;
            if (heroActionBtnText) heroActionBtnText.textContent = 'Register for Event';

            // External registration link support
            if (event.registration_link && heroActionBtn) {
                heroActionBtn.href = event.registration_link;
                heroActionBtn.target = '_blank';
            }
            if (event.registration_link && rsvpSubmitBtn) {
                rsvpSubmitBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.open(event.registration_link, '_blank');
                });
            }
        }
    }

    // ── Error State ────────────────────────────────────────────────
    function renderErrorState(msg) {
        const titleEl = document.getElementById('detailTitle');
        if (titleEl) titleEl.textContent = msg || 'Event Not Found';
        const desc = document.getElementById('detailDescription');
        if (desc) desc.textContent = 'This event could not be found. It may have been removed or archived.';
    }

    // ── Skeleton Loaders ───────────────────────────────────────────
    function showSkeletonLoaders() {
        const detailTitle = document.getElementById('detailTitle');
        if (detailTitle) {
            detailTitle.style.background = 'linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%)';
            detailTitle.style.backgroundSize = '200% 100%';
            detailTitle.style.animation = 'shimmer 1.4s infinite';
            detailTitle.style.borderRadius = '8px';
            detailTitle.style.color = 'transparent';
            detailTitle.style.minHeight = '44px';
        }
    }

    // ── Handle RSVP Form Submit ────────────────────────────────────
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
                if (rsvpSuccessAlert) rsvpSuccessAlert.classList.remove('d-none');
            }, 800);
        });
    }

    // ── Lightbox Navigation ────────────────────────────────────────
    const prevBtn = document.getElementById('lightboxPrevBtn');
    const nextBtn = document.getElementById('lightboxNextBtn');
    if (prevBtn) prevBtn.addEventListener('click', showPrevPhoto);
    if (nextBtn) nextBtn.addEventListener('click', showNextPhoto);

    document.addEventListener('keydown', (e) => {
        const modalEl = document.getElementById('eventGalleryModal');
        if (modalEl && modalEl.classList.contains('show')) {
            if (e.key === 'ArrowRight' || e.key === 'd' || e.key === 'D') showNextPhoto();
            else if (e.key === 'ArrowLeft' || e.key === 'a' || e.key === 'A') showPrevPhoto();
        }
    });

    const modalEl = document.getElementById('eventGalleryModal');
    if (modalEl) {
        let touchStartX = 0, touchEndX = 0;
        modalEl.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        modalEl.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchStartX - touchEndX > 40) showNextPhoto();
            else if (touchEndX - touchStartX > 40) showPrevPhoto();
        }, { passive: true });
    }
});

// ── DOM Helpers ────────────────────────────────────────────────────
function setEl(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function setHtml(id, html) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = html;
}

function setAttr(id, attr, val) {
    const el = document.getElementById(id);
    if (el) el.setAttribute(attr, val);
}

// ── Gallery Renderer ───────────────────────────────────────────────
function renderEventGallery(photos) {
    const galleryGrid = document.getElementById('eventGalleryGrid');
    const galleryPhotoCountBadge = document.getElementById('galleryPhotoCountBadge');
    if (!galleryGrid) return;

    galleryPhotosList = photos;
    if (galleryPhotoCountBadge) galleryPhotoCountBadge.textContent = `${photos.length} Photos`;

    galleryGrid.innerHTML = photos.map((photo, index) => `
        <div class="col-6 col-sm-4 col-md-3 col-lg-3">
            <div class="gallery-card-item rounded-4 overflow-hidden shadow-xs border position-relative"
                 style="height:220px;cursor:pointer;" onclick="openGalleryLightbox(${index})">
                <img src="${escapeHtml(photo.media_url)}" class="w-100 h-100 object-fit-cover card-banner-zoom"
                     alt="${escapeHtml(photo.caption || 'Event Recap Photo')}">
            </div>
        </div>
    `).join('');
}

// ── Lightbox Functions ─────────────────────────────────────────────
function openGalleryLightbox(index) {
    if (!galleryPhotosList || galleryPhotosList.length === 0) return;
    currentPhotoIndex = index;
    updateLightboxView();
    const modalEl = document.getElementById('eventGalleryModal');
    if (modalEl && window.bootstrap) {
        let inst = bootstrap.Modal.getInstance(modalEl);
        if (!inst) inst = new bootstrap.Modal(modalEl);
        inst.show();
    }
}

function updateLightboxView() {
    if (!galleryPhotosList || galleryPhotosList.length === 0) return;
    if (currentPhotoIndex < 0) currentPhotoIndex = galleryPhotosList.length - 1;
    if (currentPhotoIndex >= galleryPhotosList.length) currentPhotoIndex = 0;
    const photo = galleryPhotosList[currentPhotoIndex];
    const modalImg = document.getElementById('lightboxModalImg');
    const lightboxCounter = document.getElementById('lightboxCounter');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const caption = photo.caption && photo.caption.trim() !== '' ? photo.caption : 'Event Recap Moment';
    if (modalImg) { modalImg.style.transform = 'scale(0.96)'; modalImg.src = photo.media_url; setTimeout(() => { modalImg.style.transform = 'scale(1)'; }, 50); }
    if (lightboxCounter) lightboxCounter.textContent = `Photo ${currentPhotoIndex + 1} of ${galleryPhotosList.length}`;
    if (lightboxCaption) lightboxCaption.textContent = caption;
}

function showNextPhoto() { currentPhotoIndex = (currentPhotoIndex + 1) % galleryPhotosList.length; updateLightboxView(); }
function showPrevPhoto() { currentPhotoIndex = (currentPhotoIndex - 1 + galleryPhotosList.length) % galleryPhotosList.length; updateLightboxView(); }

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
