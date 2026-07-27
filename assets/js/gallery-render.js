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
            initTinderCardStack();
        })
        .catch(() => showError('Failed to connect to gallery database.'));

    initTinderCardStack();

    function initTinderCardStack() {
        const stack = document.getElementById('tinderCardStack');
        if (!stack) return;

        const cards = Array.from(stack.querySelectorAll('.tinder-event-card'));
        if (cards.length === 0) return;

        let currentIndex = 0;
        const totalCards = cards.length;

        const swipeBtn = document.getElementById('tinderSwipeBtn');
        const prevBtn = document.getElementById('tinderPrevBtn');
        const nextBtn = document.getElementById('tinderNextBtn');
        const indicator = document.getElementById('tinderStackIndicator');

        function updateStackClasses() {
            cards.forEach((card, i) => {
                card.classList.remove('tinder-card-top', 'tinder-card-next-1', 'tinder-card-next-2', 'tinder-card-hidden', 'swipe-right-anim', 'swipe-left-anim');

                const diff = (i - currentIndex + totalCards) % totalCards;

                if (diff === 0) {
                    card.classList.add('tinder-card-top');
                } else if (diff === 1) {
                    card.classList.add('tinder-card-next-1');
                } else if (diff === 2) {
                    card.classList.add('tinder-card-next-2');
                } else {
                    card.classList.add('tinder-card-hidden');
                }
            });

            if (indicator) {
                indicator.textContent = `Card ${currentIndex + 1} of ${totalCards} • Tap heart or swipe to browse moments`;
            }
        }

        function swipeNext() {
            const topCard = cards[currentIndex];
            topCard.classList.add('swipe-right-anim');

            setTimeout(() => {
                currentIndex = (currentIndex + 1) % totalCards;
                updateStackClasses();
            }, 300);
        }

        function swipePrev() {
            currentIndex = (currentIndex - 1 + totalCards) % totalCards;
            updateStackClasses();
        }

        if (swipeBtn) swipeBtn.addEventListener('click', swipeNext);
        if (nextBtn) nextBtn.addEventListener('click', swipeNext);
        if (prevBtn) prevBtn.addEventListener('click', swipePrev);

        // Add Touch & Drag Swipe functionality for top card
        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        stack.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            currentX = e.clientX - startX;
            const topCard = cards[currentIndex];
            if (topCard) {
                const rotate = currentX * 0.05;
                topCard.style.transform = `translateX(${currentX}px) rotate(${rotate}deg)`;
            }
        });

        document.addEventListener('mouseup', () => {
            if (!isDragging) return;
            isDragging = false;
            const topCard = cards[currentIndex];
            if (!topCard) return;

            if (Math.abs(currentX) > 90) {
                if (currentX > 0) {
                    swipeNext();
                } else {
                    topCard.classList.add('swipe-left-anim');
                    setTimeout(() => {
                        currentIndex = (currentIndex + 1) % totalCards;
                        updateStackClasses();
                    }, 300);
                }
            }
            topCard.style.transform = '';
            currentX = 0;
        });
    }

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
