/**
 * Dynamic Gallery Renderer & Filter (ClubHub UIT)
 * Fetches real gallery media from /api/gallery.php and populates gallery grid dynamically.
 */

document.addEventListener('DOMContentLoaded', () => {
    const galleryGrid = document.getElementById('galleryGrid');
    const filterPills = document.querySelectorAll('#galleryFilterList .category-pill');

    let allGalleryItems = [];

    const getApiUrl = (endpoint) => `api/${endpoint}`;

    fetch(getApiUrl('gallery.php'))
        .then(res => res.json())
        .then(response => {
            if (response.status !== 'success' || !Array.isArray(response.data)) {
                showError('Could not load gallery items.');
                return;
            }

            allGalleryItems = response.data;
            renderGallery('all');
            populateHeroFeaturedEvent();
        })
        .catch(() => showError('Failed to connect to gallery database.'));

    function populateHeroFeaturedEvent() {
        if (!allGalleryItems || allGalleryItems.length === 0) return;
        const featured = allGalleryItems[0];

        const heroImg = document.getElementById('heroFeaturedImg');
        const heroTitle = document.getElementById('heroFeaturedTitle');
        const heroCaption = document.getElementById('heroFeaturedCaption');
        const heroClub = document.getElementById('heroFeaturedClubBadge');
        const heroCategory = document.getElementById('heroFeaturedCategory');

        if (heroImg && featured.media_url) heroImg.src = esc(featured.media_url);
        if (heroTitle && featured.caption) heroTitle.textContent = featured.caption;
        if (heroCaption && featured.caption) heroCaption.textContent = `${featured.caption} — Captured live during student chapter event at United Institute of Technology.`;
        if (heroClub && featured.club_name) heroClub.textContent = featured.club_name;
        if (heroCategory && featured.category_name) {
            heroCategory.innerHTML = `<i class="bi bi-tag-fill me-1 text-primary"></i> ${esc(featured.category_name)}`;
        }
    }

    // Filter pill listener
    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active', 'btn-primary'));
            pill.classList.add('active', 'btn-primary');
            const filter = pill.dataset.filter || 'all';
            renderGallery(filter);
        });
    });

    function renderGallery(filter) {
        let items = allGalleryItems;
        if (filter !== 'all') {
            items = allGalleryItems.filter(item => {
                const cat = (item.category_slug || '').toLowerCase();
                return cat.includes(filter) || (item.caption || '').toLowerCase().includes(filter);
            });
        }

        if (items.length === 0) {
            galleryGrid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-images fs-1 text-primary d-block mb-2"></i>
                    <h6 class="fw-bold mb-1">No Gallery Photos Found</h6>
                    <p class="small text-muted">Try selecting a different filter.</p>
                </div>`;
            return;
        }

        galleryGrid.innerHTML = items.map(item => `
            <div class="col-md-4 col-sm-6 gallery-item">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative group">
                    <img src="${esc(item.media_url)}" class="img-fluid w-100" style="height: 250px; object-fit: cover;" alt="${esc(item.caption)}" loading="lazy">
                    <div class="p-3 bg-white">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small">${esc(item.category_name || 'Campus Media')}</span>
                        <h6 class="fw-bold mb-0 mt-2 text-dark">${esc(item.caption || 'Campus Life Moment')}</h6>
                        <small class="text-muted"><i class="bi bi-shield-fill text-primary me-1" style="font-size:10px;"></i>${esc(item.club_name)}</small>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function showError(msg) {
        galleryGrid.innerHTML = `<div class="col-12 text-center py-4 text-muted small"><i class="bi bi-wifi-off me-1"></i>${msg}</div>`;
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
