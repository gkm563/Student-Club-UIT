/**
 * Dynamic Event Detail Sub-Page Controller (ClubHub UIT)
 * Fetches event by ID, renders Google/Meta Tech Specs, full-width gallery & full screen lightbox with swipe/keyboard controls
 */

let galleryPhotosList = [];
let currentPhotoIndex = 0;

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
        
        let hours = eventDate.getHours();
        const minutes = String(eventDate.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // convert 0 to 12
        const formattedHours = String(hours).padStart(2, '0');
        const timeStr = `${formattedHours}:${minutes} ${ampm}`;

        document.title = `${event.title} | ClubHub UIT`;

        // Breadcrumb Title
        const breadcrumbTitle = document.getElementById('eventBreadcrumbTitle');
        if (breadcrumbTitle) breadcrumbTitle.textContent = event.title;

        // Hero Fields
        const detailTitle = document.getElementById('detailTitle');
        if (detailTitle) detailTitle.textContent = event.title;

        const detailVenue = document.getElementById('detailVenue');
        if (detailVenue) detailVenue.textContent = event.venue || 'United Institute Of Technology, NH 2, Naini, Prayagraj 211010';

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
        const heroActionBtnText = document.getElementById('heroActionBtnText');

        const completedGallerySection = document.getElementById('completedGallerySection');
        const completedWinnersSection = document.getElementById('completedWinnersSection');

        if (isPast) {
            if (detailStatusBadge) {
                detailStatusBadge.className = 'badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1.5 fw-bold';
                detailStatusBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Completed Session`;
            }
            if (heroActionBtnText) heroActionBtnText.textContent = 'View Event Recap & Photos';
            if (rsvpForm) rsvpForm.classList.add('d-none');
            if (concludedNotice) concludedNotice.classList.remove('d-none');

            if (completedGallerySection) completedGallerySection.classList.remove('d-none');
            if (completedWinnersSection) completedWinnersSection.classList.remove('d-none');

            if (ticketHeader) ticketHeader.style.background = 'linear-gradient(135deg, #475569, #334155)';
            if (ticketHeaderBadge) ticketHeaderBadge.textContent = 'Session Concluded';
            if (ticketHeaderTitle) ticketHeaderTitle.textContent = 'Event Completed';
            if (ticketHeaderSub) ticketHeaderSub.textContent = 'Registrations for this session have ended.';
        } else {
            if (heroActionBtnText) heroActionBtnText.textContent = 'Register for Event';
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
            detailClubBadge.innerHTML = `<i class="bi bi-shield-fill text-primary me-1"></i> ${escapeHtml(event.club_short_name || event.club_name)}`;
        }

        // Cover Banner Image
        const bannerUrl = escapeHtml(event.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=1200&auto=format&fit=crop');
        const detailBannerImg = document.getElementById('detailBannerImg');
        if (detailBannerImg) detailBannerImg.src = bannerUrl;

        // Date Pill & Specs
        const detailDay = document.getElementById('detailDay');
        if (detailDay) detailDay.textContent = day;

        const detailMonthYear = document.getElementById('detailMonthYear');
        if (detailMonthYear) detailMonthYear.textContent = `${month} '${String(year).slice(-2)}`;

        const detailTimingSpec = document.getElementById('detailTimingSpec');
        if (detailTimingSpec) detailTimingSpec.textContent = `${fullDateStr} at ${timeStr}`;

        const detailVenueSpec = document.getElementById('detailVenueSpec');
        if (detailVenueSpec) detailVenueSpec.textContent = event.venue || 'United Institute Of Technology, NH 2, Naini, Prayagraj 211010';

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
                    if (completedGallerySection) completedGallerySection.classList.remove('d-none');
                }
            })
            .catch(err => console.error('Gallery fetch error:', err));
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

    // Bind Navigation, Keyboard & Touch Swipe Listeners
    const prevBtn = document.getElementById('lightboxPrevBtn');
    const nextBtn = document.getElementById('lightboxNextBtn');
    if (prevBtn) prevBtn.addEventListener('click', showPrevPhoto);
    if (nextBtn) nextBtn.addEventListener('click', showNextPhoto);

    // Keyboard Navigation (Left / Right Arrows)
    document.addEventListener('keydown', (e) => {
        const modalEl = document.getElementById('eventGalleryModal');
        if (modalEl && modalEl.classList.contains('show')) {
            if (e.key === 'ArrowRight' || e.key === 'd' || e.key === 'D') {
                showNextPhoto();
            } else if (e.key === 'ArrowLeft' || e.key === 'a' || e.key === 'A') {
                showPrevPhoto();
            }
        }
    });

    // Touch Swipe Navigation for Mobile Devices
    const modalEl = document.getElementById('eventGalleryModal');
    if (modalEl) {
        let touchStartX = 0;
        let touchEndX = 0;

        modalEl.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        modalEl.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const swipeThreshold = 40;
            if (touchStartX - touchEndX > swipeThreshold) {
                showNextPhoto();
            } else if (touchEndX - touchStartX > swipeThreshold) {
                showPrevPhoto();
            }
        }
    }
});

function renderEventGallery(photos) {
    const galleryGrid = document.getElementById('eventGalleryGrid');
    const galleryPhotoCountBadge = document.getElementById('galleryPhotoCountBadge');
    if (!galleryGrid) return;

    galleryPhotosList = photos;
    if (galleryPhotoCountBadge) {
        galleryPhotoCountBadge.textContent = `${photos.length} Photos`;
    }

    galleryGrid.innerHTML = photos.map((photo, index) => `
        <div class="col-6 col-sm-4 col-md-3 col-lg-3">
            <div class="gallery-card-item rounded-4 overflow-hidden shadow-xs border position-relative" style="height: 220px; cursor: pointer;" onclick="openGalleryLightbox(${index})">
                <img src="${escapeHtml(photo.media_url)}" class="w-100 h-100 object-fit-cover card-banner-zoom" alt="${escapeHtml(photo.caption || 'Event Recap Photo')}">
            </div>
        </div>
    `).join('');
}

function openGalleryLightbox(index) {
    if (!galleryPhotosList || galleryPhotosList.length === 0) return;
    
    currentPhotoIndex = index;
    updateLightboxView();

    const modalEl = document.getElementById('eventGalleryModal');
    if (modalEl && window.bootstrap) {
        let modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(modalEl);
        }
        modalInstance.show();
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
    const lightboxBottomCaption = document.getElementById('lightboxBottomCaption');

    const photoCaption = photo.caption && photo.caption.trim() !== '' ? photo.caption : 'Event Recap Moment';

    if (modalImg) {
        modalImg.style.transform = 'scale(0.96)';
        modalImg.src = photo.media_url;
        setTimeout(() => { modalImg.style.transform = 'scale(1)'; }, 50);
    }
    if (lightboxCounter) lightboxCounter.textContent = `Photo ${currentPhotoIndex + 1} of ${galleryPhotosList.length}`;
    if (lightboxCaption) lightboxCaption.textContent = photoCaption;
    if (lightboxBottomCaption) {
        lightboxBottomCaption.innerHTML = `<i class="bi bi-camera-fill me-1 text-primary"></i> ${escapeHtml(photoCaption)}`;
    }
}

function showNextPhoto() {
    currentPhotoIndex = (currentPhotoIndex + 1) % galleryPhotosList.length;
    updateLightboxView();
}

function showPrevPhoto() {
    currentPhotoIndex = (currentPhotoIndex - 1 + galleryPhotosList.length) % galleryPhotosList.length;
    updateLightboxView();
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
