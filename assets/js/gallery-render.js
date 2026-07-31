/**
 * Gallery — photos + filters only
 */
document.addEventListener('DOMContentLoaded', () => {
    const galleryGrid = document.getElementById('galleryGrid');
    const filterPills = document.querySelectorAll('#galleryFilterList .category-pill');
    const lightbox = document.getElementById('galleryLightbox');
    const lightboxImg = document.getElementById('galleryLightboxImg');
    const lightboxClose = document.getElementById('galleryLightboxClose');

    let allGalleryItems = [];

    fetch('api/gallery.php')
        .then(res => res.json())
        .then(response => {
            if (response.status !== 'success' || !Array.isArray(response.data)) {
                galleryGrid.innerHTML = '';
                return;
            }
            allGalleryItems = response.data;
            renderGallery('all');
        })
        .catch(() => {
            galleryGrid.innerHTML = '';
        });

    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active', 'btn-primary'));
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
            items = allGalleryItems.filter(item => {
                const cat = (item.category_slug || '').toLowerCase();
                return cat.includes(filter) || (item.caption || '').toLowerCase().includes(filter);
            });
        }

        if (items.length === 0) {
            galleryGrid.innerHTML = '';
            return;
        }

        galleryGrid.innerHTML = items.map(item => `
            <div class="col-6 col-md-4 col-lg-3 gallery-item">
                <img
                    src="${esc(item.media_url)}"
                    class="gallery-photo"
                    alt=""
                    loading="lazy"
                    data-full="${esc(item.media_url)}"
                >
            </div>
        `).join('');

        galleryGrid.querySelectorAll('.gallery-photo').forEach(img => {
            img.addEventListener('click', () => openLightbox(img.dataset.full || img.src));
        });
    }

    function openLightbox(src) {
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
