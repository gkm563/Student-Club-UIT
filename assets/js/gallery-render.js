/**
 * Dynamic Gallery Renderer (ClubHub UIT)
 * Fetches gallery photos from /api/gallery.php and renders glassmorphic cards with lightbox modal.
 */
document.addEventListener('DOMContentLoaded', () => {
    const galleryGrid = document.getElementById('galleryGrid');
    const filterPills = document.querySelectorAll('#galleryFilterList .category-pill');
    const lightbox = document.getElementById('galleryLightbox');
    const lightboxImg = document.getElementById('galleryLightboxImg');
    const lightboxClose = document.getElementById('galleryLightboxClose');

    if (!galleryGrid) return;

    let allGalleryItems = [];

    // Fetch photos from API
    fetch('api/gallery.php')
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                allGalleryItems = response.data;
                renderGallery('all');
            } else {
                galleryGrid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-images fs-1 text-muted d-block mb-2"></i>
                        <p class="text-secondary fw-semibold">No gallery photos uploaded yet.</p>
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Error loading gallery:', err);
            galleryGrid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-exclamation-triangle fs-1 text-danger d-block mb-2"></i>
                    <p class="text-secondary fw-semibold">Unable to fetch campus gallery. Please refresh the page.</p>
                </div>
            `;
        });

    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => {
                p.classList.remove('active', 'btn-primary');
                p.classList.add('btn-outline-secondary');
            });
            pill.classList.remove('btn-outline-secondary');
            pill.classList.add('active', 'btn-primary');
            renderGallery(pill.dataset.filter || 'all');
        });
    });

    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
    });

    function renderGallery(filter) {
        let items = allGalleryItems;
        if (filter !== 'all') {
            const filterLower = filter.toLowerCase();
            items = allGalleryItems.filter(item => {
                const cat = (item.category_slug || '').toLowerCase();
                const catName = (item.category_name || '').toLowerCase();
                const caption = (item.caption || '').toLowerCase();
                const club = (item.club_short_name || item.club_name || '').toLowerCase();
                return cat.includes(filterLower) || catName.includes(filterLower) || caption.includes(filterLower) || club.includes(filterLower);
            });
        }

        if (items.length === 0) {
            galleryGrid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search fs-1 text-muted d-block mb-2"></i>
                    <p class="text-secondary fw-semibold">No photos match the selected filter category.</p>
                </div>
            `;
            return;
        }

        galleryGrid.innerHTML = items.map(item => {
            const mediaUrl = item.media_url || 'assets/img/ugi-logo.png';
            const caption = item.caption || 'USC UIT Campus Event';
            const clubName = item.club_short_name || item.category_name || 'USC UIT';

            return `
                <div class="col-6 col-md-4 col-lg-3 gallery-item">
                    <div class="position-relative rounded-4 overflow-hidden shadow-sm bg-white border h-100 gallery-card-box" style="aspect-ratio: 4 / 3;">
                        <img
                            src="${esc(mediaUrl)}"
                            class="w-100 h-100 object-fit-cover gallery-photo transition-all"
                            alt="${esc(caption)}"
                            loading="lazy"
                            data-full="${esc(mediaUrl)}"
                            data-caption="${esc(caption)}"
                            onerror="this.src='assets/img/ugi-logo.png'"
                        >
                        <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-dark bg-opacity-75 text-white opacity-0 hover-opacity-100 transition-all d-flex flex-column justify-content-end" style="top:0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(15,23,42,0.9) 100%);">
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 small me-auto mb-1" style="font-size: 0.68rem;">
                                ${esc(clubName)}
                            </span>
                            <p class="small fw-semibold mb-0 text-white line-clamp-2" style="font-size: 0.82rem; line-height: 1.3;">
                                ${esc(caption)}
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        galleryGrid.querySelectorAll('.gallery-photo').forEach(img => {
            img.addEventListener('click', () => openLightbox(img.dataset.full || img.src, img.dataset.caption));
        });
    }

    function openLightbox(src, caption) {
        if (!lightbox || !lightboxImg || !src) return;
        lightboxImg.src = src;
        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightbox || !lightboxImg) return;
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        lightboxImg.src = '';
        document.body.style.overflow = '';
    }

    function esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});
