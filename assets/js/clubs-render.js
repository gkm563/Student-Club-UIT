/**
 * Dynamic Club Directory & Club Detail Renderer (ClubHub UIT)
 * Connects public frontend HTML directly to PHP REST APIs with dynamic category filtering,
 * URL parameter synchronization, active filter badges, tenure-wise annual roster, and event showcase.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Render Clubs Directory (clubs.html)
    const clubsGrid = document.getElementById('clubsGrid');
    const categoryFilterList = document.getElementById('categoryFilterList');
    const clubSearchInput = document.getElementById('clubSearchInput');
    const sortSelect = document.getElementById('sortSelect') || document.querySelector('select.form-select');
    const activeFilterBadge = document.getElementById('activeFilterBadge');

    if (clubsGrid) {
        if (window.UITSkeletonLoader) {
            clubsGrid.innerHTML = window.UITSkeletonLoader.getClubCardSkeleton(6);
        }
        // Read URL parameter on load
        const urlParams = new URLSearchParams(window.location.search);
        let currentCategory = urlParams.get('category') || 'all';
        let currentWing = urlParams.get('wing') || '';
        let currentSearch = urlParams.get('search') || '';
        let currentSort = 'popularity';

        applyWingDirectoryUi(currentWing);

        if (clubSearchInput && currentSearch) {
            clubSearchInput.value = currentSearch;
        }
        // Also sync the hero directory search input with URL param on load
        const heroSearchInitSync = document.getElementById('heroDirectorySearchInput');
        if (heroSearchInitSync && currentSearch) {
            heroSearchInitSync.value = currentSearch;
        }

        const getApiUrl = (endpoint) => `api/${endpoint}`;

        function renderClubCardHtml(club) {
            const coverImg = escapeHtml(club.cover_image || 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=800&auto=format&fit=crop');
            const logoImg = club.logo
                ? `<img src="${escapeHtml(club.logo)}" class="featured-club-logo-img" alt="${escapeHtml(club.name)}" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline-block';"><i class="bi ${escapeHtml(club.category_icon || 'bi-shield-check')} fs-3 text-primary" style="display:none;"></i>`
                : `<i class="bi ${escapeHtml(club.category_icon || 'bi-shield-check')} fs-3 text-primary"></i>`;
            const memberCount = club.member_count || 45;
            const clubWing = resolveWing(club.category_slug);
            const wingMeta = getWingMeta(clubWing);
            const wingTagClass = clubWing === 'cultural' ? 'club-card-wing-tag--cultural' : 'club-card-wing-tag--tech';
            const wingTagLabel = wingMeta.wing ? wingMeta.label : 'USC UIT Chapter';
            const btnClass = clubWing === 'cultural' ? 'btn-danger' : 'btn-primary';
            const btnExtraStyle = clubWing === 'cultural' ? 'background:#e11d48;border-color:#e11d48;' : '';

            const recruitmentBadge = club.recruitment_open
                ? `<span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#ecfdf5; color:#059669; border:1px solid rgba(5,150,105,0.3); font-size:0.75rem;"><span class="pulse-dot-green me-1.5"></span>Recruiting Members</span>`
                : `<span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#eff6ff; color:#1d4ed8; border:1px solid rgba(29,78,216,0.3); font-size:0.75rem;"><i class="bi bi-shield-check me-1"></i>Official Chapter</span>`;

            const clubDetailUrl = `club-detail.html?id=${encodeURIComponent(club.id)}`;

            return `
                <div class="col-md-6 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 ccms-club-prestige-card transition-all" onclick="window.location.href='${clubDetailUrl}'" style="background:#ffffff; border:1px solid #e2e8f0 !important; cursor: pointer;">
                        <div class="position-relative" style="height: 160px;">
                            <img src="${coverImg}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(club.name)}">
                            <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.15) 0%, rgba(15,23,42,0.75) 100%);"></div>
                            <div class="position-absolute top-0 start-0 m-3 z-2">
                                <span class="club-card-wing-tag ${wingTagClass}"><i class="bi ${wingMeta.icon}"></i> ${escapeHtml(wingTagLabel)}</span>
                            </div>
                            <div class="position-absolute top-0 end-0 m-3 z-2">
                                <span class="badge bg-white text-dark rounded-pill px-3 py-1.5 fw-bold shadow-sm" style="font-size: 0.75rem;">
                                    <i class="bi ${escapeHtml(club.category_icon || 'bi-grid')} me-1 text-primary"></i> ${escapeHtml(club.category_name)}
                                </span>
                            </div>
                            <div class="position-absolute bg-white rounded-4 p-1 shadow-md d-flex align-items-center justify-content-center" style="width: 62px; height: 62px; bottom: -24px; left: 24px; z-index: 3; border: 3px solid #ffffff;">
                                ${logoImg}
                            </div>
                        </div>
                        <div class="p-4 pt-4 mt-2 flex-grow-1 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">${recruitmentBadge}</div>
                            <h4 class="fw-bold mb-1 text-dark" style="font-size: 1.25rem;">
                                <a href="${clubDetailUrl}" class="text-decoration-none text-dark hover-blue" onclick="event.stopPropagation();">${escapeHtml(club.name)}</a>
                            </h4>
                            <p class="text-secondary small mb-3 flex-grow-1" style="font-size: 0.88rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                ${escapeHtml(club.tagline || club.description || 'Official student chapter at United Institute of Technology.')}
                            </p>
                            <div class="rounded-3 p-3 mb-3 bg-light border border-light-subtle">
                                <div class="d-flex align-items-center gap-1.5 text-dark small" style="font-size: 0.8rem;">
                                    <i class="bi bi-people-fill text-primary"></i>
                                    <span><strong>${memberCount}+</strong> Active Members</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 pt-2 mt-auto">
                                <a href="${clubDetailUrl}" class="btn ${btnClass} rounded-pill px-4 py-2 fw-bold w-100 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 0.88rem; ${btnExtraStyle}" onclick="event.stopPropagation();">
                                    <span>Explore Chapter</span>
                                    <i class="bi bi-arrow-right-short fs-5"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderWingClubChip(club, wing) {
            const isCultural = wing === 'cultural';
            const chipClass = isCultural ? 'clubs-wing-club-chip--cultural' : 'clubs-wing-club-chip--tech';
            const fullName = escapeHtml(club.name || '');
            const shortName = escapeHtml(club.short_name || club.name);
            const logoSrc = club.logo ? escapeHtml(club.logo) : 'assets/img/usc-logo.png';
            const fallbackIcon = isCultural ? 'bi-palette-fill' : 'bi-code-slash';

            return `
                <a href="club-detail.html?id=${encodeURIComponent(club.id)}" class="clubs-wing-club-chip ${chipClass}" title="${fullName}">
                    <img src="${logoSrc}" class="clubs-wing-club-chip__logo" alt="${shortName}" onerror="this.onerror=null; this.src='assets/img/usc-logo.png';">
                    <span class="clubs-wing-club-chip__text">${shortName}</span>
                </a>
            `;
        }

        function loadWingShowcase() {
            const techList = document.getElementById('techWingClubsList');
            const culturalList = document.getElementById('culturalWingClubsList');
            const techCountEl = document.getElementById('techWingClubCount');
            const culturalCountEl = document.getElementById('culturalWingClubCount');
            const heroSubCount = document.querySelector('.clubs-hero-card-image h2.text-primary');

            if (!techList && !culturalList) return;

            Promise.all([
                fetch(getApiUrl('clubs.php?wing=technical')).then(res => res.json()),
                fetch(getApiUrl('clubs.php?wing=cultural')).then(res => res.json())
            ])
                .then(([techRes, culturalRes]) => {
                    const allTech = techRes.status === 'success' ? (techRes.data || []) : [];
                    const allCultural = culturalRes.status === 'success' ? (culturalRes.data || []) : [];

                    // Filter out umbrella parent councils so only actual sub-chapters are listed
                    const techClubs = allTech.filter(c => c.id !== 'clb_developers_uit' && c.slug !== 'developers-club-uit');
                    const culturalClubs = allCultural.filter(c => c.id !== 'clb_cultural_uit' && c.slug !== 'cultural-club-uit');

                    if (techCountEl) techCountEl.textContent = techClubs.length;
                    if (culturalCountEl) culturalCountEl.textContent = culturalClubs.length;
                    if (heroSubCount) heroSubCount.textContent = `${techClubs.length + culturalClubs.length}+`;

                    if (techList) {
                        techList.innerHTML = techClubs.length
                            ? techClubs.map(club => renderWingClubChip(club, 'technical')).join('')
                            : '<span class="small text-muted">Tech chapters loading soon.</span>';
                    }

                    if (culturalList) {
                        culturalList.innerHTML = culturalClubs.length
                            ? culturalClubs.map(club => renderWingClubChip(club, 'cultural')).join('')
                            : '<span class="small text-muted">Cultural chapters loading soon.</span>';
                    }
                })
                .catch(() => {
                    if (techList) techList.innerHTML = '<span class="small text-danger">Could not load tech chapters.</span>';
                    if (culturalList) culturalList.innerHTML = '<span class="small text-danger">Could not load cultural chapters.</span>';
                });
        }

        function loadClubs() {
            clubsGrid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 small">Loading active campus clubs...</p>
                </div>
            `;

            const apiUrl = getApiUrl(`clubs.php?category=${encodeURIComponent(currentCategory)}&search=${encodeURIComponent(currentSearch)}&sort=${encodeURIComponent(currentSort)}${currentWing ? `&wing=${encodeURIComponent(currentWing)}` : ''}`);

            fetch(apiUrl)
                .then(res => res.json())
                .then(response => {
                    if (response.status !== 'success') {
                        renderEmptyState('Failed to fetch clubs from server.');
                        return;
                    }

                    // Render Dynamic Category Pills with Real Counts
                    if (categoryFilterList && response.categories) {
                        renderCategoryPills(response.categories, response.total);
                    }

                    // Render Active Filter Badge
                    renderActiveFilterBadge(response.categories, response.data ? response.data.length : 0);

                    // Render Clubs Grid
                    const clubs = response.data || [];
                    if (clubs.length === 0) {
                        renderEmptyState();
                        return;
                    }

                    clubsGrid.innerHTML = clubs.map(club => renderClubCardHtml(club)).join('');
                })
                .catch(err => {
                    console.error('Error fetching clubs:', err);
                    renderEmptyState('Connection error. Could not connect to database.');
                });
        }

        // Render Dynamic Category Pills
        function renderCategoryPills(categories, totalClubs) {
            const categoryIcons = {
                'all': 'bi-grid-fill',
                'technical': 'bi-code-slash',
                'cultural': 'bi-palette-fill',
                'sports': 'bi-trophy-fill',
                'social': 'bi-heart-pulse-fill',
                'academic': 'bi-mortarboard-fill',
                'literary': 'bi-book-half',
                'media': 'bi-camera-reels-fill'
            };

            const wingActive = (wing) => currentWing === wing && currentCategory === 'all';

            let pillsHtml = `
                <button class="btn text-start category-pill-btn d-flex justify-content-between align-items-center transition-all ${currentCategory === 'all' && !currentWing ? 'active btn-primary text-white shadow-sm' : 'btn-light text-dark border-0'}" data-category="all" data-wing="" style="font-weight: 800; font-size: 0.86rem; border-radius: 12px; padding: 10px 14px;">
                    <span class="fw-black"><i class="bi ${categoryIcons['all']} me-2 text-warning fs-6"></i> All Chapters</span>
                    <span class="badge ${currentCategory === 'all' && !currentWing ? 'bg-white text-primary' : 'bg-secondary-subtle text-dark'} rounded-pill ms-2 fw-black" style="font-size: 0.72rem;">${totalClubs}</span>
                </button>
                <button class="btn text-start category-pill-btn category-pill-wing-tech d-flex justify-content-between align-items-center transition-all ${wingActive('technical') ? 'active btn-primary text-white shadow-sm' : 'btn-light text-dark border-0'}" data-wing="technical" style="font-weight: 800; font-size: 0.86rem; border-radius: 12px; padding: 10px 14px;">
                    <span class="fw-black"><i class="bi bi-code-slash me-2 text-primary fs-6"></i> Developers Club UIT</span>
                    <span class="badge ${wingActive('technical') ? 'bg-white text-primary' : 'bg-primary text-white'} rounded-pill ms-2 fw-black" style="font-size: 0.72rem;">Tech</span>
                </button>
                <button class="btn text-start category-pill-btn category-pill-wing-cultural d-flex justify-content-between align-items-center transition-all ${wingActive('cultural') ? 'active btn-danger text-white shadow-sm' : 'btn-light text-dark border-0'}" data-wing="cultural" style="font-weight: 800; font-size: 0.86rem; border-radius: 12px; padding: 10px 14px;">
                    <span class="fw-black"><i class="bi bi-palette-fill me-2 text-danger fs-6"></i> Cultural Club UIT</span>
                    <span class="badge ${wingActive('cultural') ? 'bg-white text-danger' : 'bg-danger text-white'} rounded-pill ms-2 fw-black" style="font-size: 0.72rem; background:#e11d48 !important;">Cultural</span>
                </button>
                <div class="category-divider-label my-2 py-1 px-2.5 rounded-2 bg-slate-100 text-slate-600 fw-black text-uppercase" style="font-size:0.72rem; letter-spacing:0.8px; background: #f1f5f9;">
                    <i class="bi bi-layers-fill me-1 text-primary"></i> Specific Domains
                </div>
            `;

            categories.forEach(cat => {
                const isActive = (currentCategory === cat.slug);
                const icon = categoryIcons[cat.slug] || cat.icon || 'bi-bookmark-star-fill';
                pillsHtml += `
                    <button class="btn text-start category-pill-btn d-flex justify-content-between align-items-center transition-all ${isActive ? 'active btn-primary text-white shadow-sm' : 'btn-light text-dark border-0'}" data-category="${escapeHtml(cat.slug)}" style="font-weight: 800; font-size: 0.86rem; border-radius: 12px; padding: 10px 14px;">
                        <span class="text-truncate me-1 fw-bold"><i class="bi ${icon} me-2 ${isActive ? 'text-white' : 'text-primary'} fs-6"></i> ${escapeHtml(cat.name)}</span>
                        <span class="badge ${isActive ? 'bg-white text-primary' : 'bg-secondary-subtle text-dark'} rounded-pill ms-2 fw-black" style="font-size: 0.72rem;">${cat.club_count || 0}</span>
                    </button>
                `;
            });

            categoryFilterList.innerHTML = pillsHtml;
        }

        // Render Active Filter Indicator Bar
        function renderActiveFilterBadge(categories, count) {
            if (!activeFilterBadge) return;

            const wingMeta = typeof getWingMeta === 'function' ? getWingMeta(resolveWing(currentWing)) : null;

            if (currentCategory === 'all' && !currentSearch && !currentWing) {
                activeFilterBadge.innerHTML = '';
                return;
            }

            let catName = 'All Categories';
            if (currentWing && wingMeta && wingMeta.wing) {
                catName = wingMeta.label;
            } else if (currentCategory !== 'all' && categories) {
                const found = categories.find(c => c.slug === currentCategory);
                if (found) catName = found.name;
            }

            activeFilterBadge.innerHTML = `
                <div class="alert alert-primary border-0 rounded-4 shadow-sm py-2-5 px-4 d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2 small flex-wrap">
                        <i class="bi bi-funnel-fill text-primary"></i>
                        <span class="fw-bold text-dark">Filtered by:</span>
                        <span class="badge bg-primary text-white rounded-pill px-3 py-1 fs-6">${escapeHtml(catName)}</span>
                        ${currentSearch ? `<span class="badge bg-dark text-white rounded-pill px-3 py-1 fs-6">"${escapeHtml(currentSearch)}"</span>` : ''}
                        <span class="text-muted ms-1">(${count} ${count === 1 ? 'club' : 'clubs'} found)</span>
                    </div>
                    <button id="clearCategoryFilterBtn" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-bold">
                        Clear Filter <i class="bi bi-x-lg ms-1"></i>
                    </button>
                </div>
            `;

            const clearBtn = document.getElementById('clearCategoryFilterBtn');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    currentWing = '';
                    updateUrlParam('wing', null);
                    applyWingDirectoryUi('');
                    setCategoryFilter('all');
                });
            }
        }

        function applyWingDirectoryUi(wing) {
            const breadcrumbHost = document.getElementById('clubsDirectoryBreadcrumb');
            if (!breadcrumbHost || typeof buildWingBreadcrumbHtml !== 'function') return;

            const resolved = resolveWing(wing);
            if (!resolved) return;

            breadcrumbHost.innerHTML = buildWingBreadcrumbHtml({ wing: resolved });
        }

        function setCategoryFilter(catSlug) {
            currentCategory = catSlug;
            currentWing = '';
            updateUrlParam('wing', null);
            updateUrlParam('category', catSlug === 'all' ? null : catSlug);
            applyWingDirectoryUi('');
            loadClubs();
        }

        function setWingFilter(wingSlug) {
            currentWing = wingSlug;
            currentCategory = 'all';
            updateUrlParam('wing', wingSlug || null);
            updateUrlParam('category', null);
            applyWingDirectoryUi(wingSlug);
            loadClubs();

            const directory = document.getElementById('clubsDirectorySection');
            if (directory) directory.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function updateUrlParam(key, value) {
            const url = new URL(window.location);
            if (value) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
            window.history.pushState(null, '', url.toString());
        }

        function renderEmptyState(customMsg = '') {
            clubsGrid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="p-5 bg-white rounded-4 shadow-sm border max-w-md mx-auto">
                        <i class="bi bi-inbox fs-1 text-primary d-block mb-3"></i>
                        <h5 class="fw-bold mb-2">No Clubs Found</h5>
                        <p class="text-secondary small mb-4">${customMsg || 'There are currently no active clubs matching your search or category criteria.'}</p>
                        ${currentCategory !== 'all' || currentSearch ? `
                            <button id="resetSearchFilterBtn" class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-white mb-2">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </button>
                            <br>
                        ` : ''}
                        <a href="contact.html" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold mt-2">
                            <i class="bi bi-envelope me-1"></i> Contact Student Affairs
                        </a>
                    </div>
                </div>
            `;

            const resetBtn = document.getElementById('resetSearchFilterBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    currentSearch = '';
                    if (clubSearchInput) clubSearchInput.value = '';
                    setCategoryFilter('all');
                });
            }
        }

        // Category & Wing Filter Pill Clicks (Event Delegation)
        if (categoryFilterList) {
            categoryFilterList.addEventListener('click', (e) => {
                const wingBtn = e.target.closest('button[data-wing]');
                if (wingBtn && wingBtn.hasAttribute('data-wing')) {
                    const wingSlug = wingBtn.getAttribute('data-wing') || '';
                    setWingFilter(wingSlug);
                    return;
                }
                const btn = e.target.closest('button[data-category]');
                if (!btn) return;
                const catSlug = btn.getAttribute('data-category');
                currentWing = btn.getAttribute('data-wing') || '';
                setCategoryFilter(catSlug);
            });
        }

        document.querySelectorAll('[data-wing-filter]').forEach(btn => {
            btn.addEventListener('click', () => {
                setWingFilter(btn.getAttribute('data-wing-filter'));
            });
        });

        // Instant In-Page DOM Card Filter Function for Clubs Directory
        const filterDirectoryGridInPage = (query) => {
            const q = (query || '').toLowerCase().trim();
            // Use the correct ID from clubs.html
            const clubCards = document.querySelectorAll('#clubsGrid > div');
            clubCards.forEach(col => {
                if (!q) {
                    col.style.display = '';
                } else {
                    const text = (col.textContent || '').toLowerCase();
                    col.style.display = text.includes(q) ? '' : 'none';
                }
            });
        };

        // Search Input Listener (Filter Bar)
        if (clubSearchInput) {
            let debounceTimer;
            clubSearchInput.addEventListener('input', (e) => {
                const val = e.target.value;
                const heroInput = document.getElementById('heroDirectorySearchInput');
                if (heroInput) heroInput.value = val;
                
                // Instant client-side DOM filter
                filterDirectoryGridInPage(val);

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentSearch = val.trim();
                    updateUrlParam('search', currentSearch || null);
                    loadClubs();
                }, 250);
            });
        }

        // Hero Directory Search Input & Trigger Btn
        const heroDirectorySearchInput = document.getElementById('heroDirectorySearchInput');
        const heroSearchTriggerBtn = document.getElementById('heroSearchTriggerBtn');

        if (heroDirectorySearchInput) {
            let heroDebounce;
            heroDirectorySearchInput.addEventListener('input', (e) => {
                const val = e.target.value;
                if (clubSearchInput) clubSearchInput.value = val;

                // Instant client-side DOM filter
                filterDirectoryGridInPage(val);

                clearTimeout(heroDebounce);
                heroDebounce = setTimeout(() => {
                    currentSearch = val.trim();
                    updateUrlParam('search', currentSearch || null);
                    loadClubs();
                }, 250);
            });

            heroDirectorySearchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentSearch = heroDirectorySearchInput.value.trim();
                    if (clubSearchInput) clubSearchInput.value = currentSearch;
                    updateUrlParam('search', currentSearch || null);
                    loadClubs();
                }
            });
        }

        if (heroSearchTriggerBtn) {
            heroSearchTriggerBtn.addEventListener('click', () => {
                if (heroDirectorySearchInput) {
                    currentSearch = heroDirectorySearchInput.value.trim();
                    if (clubSearchInput) clubSearchInput.value = currentSearch;
                    updateUrlParam('search', currentSearch || null);
                    loadClubs();
                }
            });
        }

        // Hero Trending Chips Event Listener
        document.addEventListener('click', (e) => {
            const chip = e.target.closest('.hero-chip-btn');
            if (chip) {
                const keyword = chip.getAttribute('data-chip');
                if (keyword) {
                    currentSearch = keyword;
                    if (clubSearchInput) clubSearchInput.value = currentSearch;
                    if (heroDirectorySearchInput) heroDirectorySearchInput.value = currentSearch;
                    updateUrlParam('search', currentSearch);
                    loadClubs();
                }
            }
        });

        // Clear / Reset All Filters Button Listener
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                currentSearch = '';
                currentCategory = 'all';
                currentWing = '';
                currentSort = 'popularity';

                if (clubSearchInput) clubSearchInput.value = '';
                if (heroDirectorySearchInput) heroDirectorySearchInput.value = '';
                if (sortSelect) sortSelect.value = 'popularity';

                updateUrlParam('search', null);
                updateUrlParam('category', null);
                updateUrlParam('wing', null);

                applyWingDirectoryUi('');
                filterDirectoryGridInPage('');
                loadClubs();
            });
        }

        // Sort Dropdown Listener
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                currentSort = e.target.value;
                loadClubs();
            });
        }

        // Handle Browser Back / Forward Buttons
        window.addEventListener('popstate', () => {
            const params = new URLSearchParams(window.location.search);
            currentCategory = params.get('category') || 'all';
            currentWing = params.get('wing') || '';
            currentSearch = params.get('search') || '';
            if (clubSearchInput) clubSearchInput.value = currentSearch;
            applyWingDirectoryUi(currentWing);
            loadClubs();
        });

        // Initial Load
        loadWingShowcase();
        loadClubs();
    }

    // 2. Render Club Detail Page (club-detail.html)
    const detailContainer = document.getElementById('clubDetailContainer');
    if (detailContainer) {
        const urlParams = new URLSearchParams(window.location.search);
        const clubId = urlParams.get('id') || urlParams.get('slug');

        if (!clubId) {
            detailContainer.innerHTML = `
                <div class="container py-5 text-center">
                    <div class="alert alert-warning rounded-4 border-0 p-5 shadow-sm max-w-md mx-auto">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-3 text-warning"></i>
                        <h4 class="fw-bold mb-2">No Club Specified</h4>
                        <p class="small text-secondary mb-4">Please select a valid club from the directory.</p>
                        <a href="clubs.html" class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-white">Browse All Clubs</a>
                    </div>
                </div>
            `;
            return;
        }

        const getApiUrl = (endpoint) => `api/${endpoint}`;

        if (window.UITSkeletonLoader) {
            detailContainer.innerHTML = `
                <div class="container py-4 skeleton-fade-in">
                    <div class="skeleton skeleton-hero-banner mb-4" style="height: 220px; border-radius: 24px;"></div>
                    <div class="row align-items-center mb-4">
                        <div class="col-auto">
                            <div class="skeleton skeleton-avatar-lg" style="width: 100px; height: 100px;"></div>
                        </div>
                        <div class="col">
                            <div class="skeleton skeleton-title mb-2" style="width: 50%; height: 2rem;"></div>
                            <div class="skeleton skeleton-badge mb-2"></div>
                            <div class="skeleton skeleton-text" style="width: 80%;"></div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="skeleton-card mb-4">
                                <div class="skeleton skeleton-title mb-3" style="width: 40%;"></div>
                                <div class="skeleton skeleton-text mb-2"></div>
                                <div class="skeleton skeleton-text mb-2"></div>
                                <div class="skeleton skeleton-text mb-2" style="width: 85%;"></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="skeleton-card">
                                <div class="skeleton skeleton-title mb-3" style="width: 60%;"></div>
                                <div class="skeleton skeleton-text mb-2"></div>
                                <div class="skeleton skeleton-text mb-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        fetch(getApiUrl(`clubs.php?id=${encodeURIComponent(clubId)}`))
            .then(res => res.json())
            .then(response => {
                if (response.status !== 'success' || !response.data) {
                    detailContainer.innerHTML = `
                        <div class="container py-5 text-center">
                            <div class="alert alert-danger rounded-4 border-0 p-5 shadow-sm max-w-md mx-auto">
                                <i class="bi bi-x-circle fs-1 d-block mb-3 text-danger"></i>
                                <h4 class="fw-bold mb-2">Club Not Found</h4>
                                <p class="small text-secondary mb-4">The requested club could not be found or has been removed.</p>
                                <a href="clubs.html" class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-white">Return to Directory</a>
                            </div>
                        </div>
                    `;
                    return;
                }

                const club = response.data;
                document.title = `${club.name} | USC UIT`;

                const clubWing = resolveWing(club.category_slug);
                const wingMeta = getWingMeta(clubWing);
                const breadcrumbHtml = buildWingBreadcrumbHtml({
                    wing: clubWing,
                    clubName: club.short_name || club.name
                });

                // Group Leadership Roster by Category & Term
                const tenureMap = {};
                if (club.leadership && club.leadership.length > 0) {
                    club.leadership.forEach(leader => {
                        const term = leader.term_year || '2025-2026';
                        if (!tenureMap[term]) tenureMap[term] = [];
                        tenureMap[term].push(leader);
                    });
                }

                const isTrue = (val) => (val === undefined || val === null || String(val) === '1' || val === 1 || val === true);

                const showAchievements = isTrue(club.show_achievements);
                const showLeadership   = isTrue(club.show_leadership);
                const showRecruitment  = isTrue(club.show_recruitment);
                const showGallery      = isTrue(club.show_gallery);

                const coverImg = escapeHtml(club.cover_image || 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop');
                const logoImg = escapeHtml(club.logo || 'assets/United Logo.webp');
                const foundedYear = club.founded_year || 2024;
                const memberCount = (club.leadership ? club.leadership.length : 8) * 5 + 20;

                // Render Executive Apple/Google Style Layout
                detailContainer.innerHTML = `
                    <!-- Executive Light Hero Section -->
                    <section class="about-hero-light position-relative overflow-hidden py-4 py-md-5">
                        <div class="about-hero-light-bg-accent"></div>

                        <div class="container position-relative z-2">
                            <!-- Top Breadcrumbs & Accreditation Badge -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                                ${breadcrumbHtml}

                                <div class="clubs-top-pill-badge d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-shield-check text-primary fs-6"></i>
                                    <span>USC UIT Recognized Chapter • Est. ${foundedYear}</span>
                                </div>
                            </div>

                            <!-- Cover Image Banner Card -->
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
                                <div class="position-relative" style="height: 280px;">
                                    <img src="${coverImg}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(club.name)}" onerror="this.src='https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop'">
                                    <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.65) 100%);"></div>
                                </div>
                            </div>

                            <!-- Header Info Deck (Logo, Name, Tagline & CTA) -->
                            <div class="row align-items-center justify-content-between g-4">
                                <div class="col-lg-8 d-flex align-items-start align-items-md-center gap-3 gap-md-4 flex-column flex-sm-row">
                                    <div class="position-relative flex-shrink-0" style="margin-top: -50px; z-index: 10;">
                                        <img src="${logoImg}" class="rounded-4 border border-3 border-white bg-white p-2 shadow-lg" style="width: 110px; height: 110px; object-fit: cover;" alt="${escapeHtml(club.name)}" onerror="this.src='assets/United Logo.webp'">
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                            ${clubWing ? `<a href="${wingMeta.pageUrl}" class="badge bg-dark text-white rounded-pill px-3 py-1.5 fw-bold small text-decoration-none"><i class="bi ${wingMeta.icon} me-1"></i> ${escapeHtml(wingMeta.label)}</a>` : ''}
                                            <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold small">
                                                <i class="bi ${escapeHtml(club.category_icon || 'bi-tag')} me-1"></i> ${escapeHtml(club.category_name)}
                                            </span>
                                            ${(showRecruitment && club.recruitment_open) ? `
                                                <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1.5 fw-bold small">
                                                    <span class="pulse-dot-green me-1.5"></span> Recruitment Open
                                                </span>
                                            ` : `
                                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1.5 fw-bold small">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Active Chapter
                                                </span>
                                            `}
                                        </div>
                                        <h1 class="about-hero-title mb-1" style="font-size: 2.3rem;">${escapeHtml(club.name)}</h1>
                                        <p class="about-hero-desc mb-0 fs-6">${escapeHtml(club.tagline || 'Official Student Chapter at United Institute of Technology.')}</p>
                                    </div>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <div class="d-flex align-items-center gap-2 justify-content-start justify-content-lg-end flex-wrap">
                                        ${(showRecruitment && club.recruitment_open) ? `
                                            <a href="${escapeHtml(club.recruitment_link || 'contact.html')}" target="_blank" class="btn btn-success rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                                                <i class="bi bi-person-plus-fill me-1"></i> Apply for Chapter Recruitment
                                            </a>
                                        ` : `
                                            <a href="contact.html" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                                                <i class="bi bi-envelope-fill me-1"></i> Contact Club Leads
                                            </a>
                                        `}
                                    </div>
                                </div>
                            </div>

                            <!-- Chapter Impact Metrics Grid -->
                            <div class="row g-3 text-start mt-4 pt-2">
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-white rounded-4 border border-light shadow-sm">
                                        <h4 class="fw-black mb-1" style="font-size: 1.6rem; font-weight: 900; color: #1d4ed8;">${memberCount}+</h4>
                                        <span class="d-block fw-extrabold text-dark" style="font-size: 0.78rem;">Active Chapter Members</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-white rounded-4 border border-light shadow-sm">
                                        <h4 class="fw-black mb-1" style="font-size: 1.6rem; font-weight: 900; color: #2563eb;">${club.events ? club.events.length : 0}+</h4>
                                        <span class="d-block fw-extrabold text-dark" style="font-size: 0.78rem;">Events & Bootcamps</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-white rounded-4 border border-light shadow-sm">
                                        <h4 class="fw-black mb-1" style="font-size: 1.6rem; font-weight: 900; color: #7c3aed;">Est. ${foundedYear}</h4>
                                        <span class="d-block fw-extrabold text-dark" style="font-size: 0.78rem;">Founded Year</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-white rounded-4 border border-light shadow-sm">
                                        <h4 class="fw-black mb-1" style="font-size: 1.6rem; font-weight: 900; color: #10b981;">100%</h4>
                                        <span class="d-block fw-extrabold text-dark" style="font-size: 0.78rem;">USC UIT Verified Chapter</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Main Detailed Content Section -->
                    <section class="py-5" style="background: #f8fafc;">
                        <div class="container py-2">
                            <div class="row g-4 g-lg-5">
                                <!-- Left Column (8 Cols): Achievements, About, Mission/Vision, Leadership, Events, Gallery -->
                                <div class="col-lg-8">

                                    <!-- Key Achievements & Milestones Banner (Dynamically Toggled) -->
                                    ${(showAchievements && club.achievements_text && club.achievements_text.trim() !== '') ? `
                                        <div class="about-card-elevated p-4 mb-4" style="border-left: 5px solid #f59e0b !important;">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background:#fffbeb; color:#d97706;">
                                                    <i class="bi bi-trophy-fill fs-4"></i>
                                                </div>
                                                <div>
                                                    <h6 class="fw-black text-dark mb-2" style="font-weight: 900;">Key Chapter Achievements & Milestones</h6>
                                                    <div class="text-secondary small mb-0 space-y-1">
                                                        ${club.achievements_text.split('\n').map(line => `<div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-star-fill text-warning fs-6"></i><span>${escapeHtml(line.trim())}</span></div>`).join('')}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ` : ''}

                                    <!-- About & Overview Card -->
                                    <div class="about-card-elevated p-4 p-md-5 mb-4">
                                        <h4 class="fw-black text-dark mb-3" style="font-weight: 900;">About ${escapeHtml(club.name)}</h4>
                                        <p class="text-secondary leading-relaxed mb-4" style="line-height: 1.7; font-size: 1rem;">
                                            ${escapeHtml(club.description || 'Official student chapter at United Institute of Technology dedicated to fostering hands-on skills, technical mastery, and student leadership.')}
                                        </p>

                                        <!-- Mission & Vision Cards -->
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="p-4 rounded-4 h-100 bg-white border" style="border-top: 4px solid #2563eb !important;">
                                                    <div class="about-pillar-icon mb-3" style="background: #eff6ff; color: #2563eb; width: 44px; height: 44px; font-size: 1.2rem;">
                                                        <i class="bi bi-bullseye"></i>
                                                    </div>
                                                    <h5 class="fw-black text-dark mb-2" style="font-weight: 900;">Our Mission</h5>
                                                    <p class="small text-secondary mb-0" style="line-height: 1.6;">${escapeHtml(club.mission || 'To empower students through hands-on technical workshops, competitive coding contests, and peer mentorship.')}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="p-4 rounded-4 h-100 bg-white border" style="border-top: 4px solid #7c3aed !important;">
                                                    <div class="about-pillar-icon mb-3" style="background: #f5f3ff; color: #7c3aed; width: 44px; height: 44px; font-size: 1.2rem;">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </div>
                                                    <h5 class="fw-black text-dark mb-2" style="font-weight: 900;">Our Vision</h5>
                                                    <p class="small text-secondary mb-0" style="line-height: 1.6;">${escapeHtml(club.vision || 'To cultivate top-tier student developers, competitive coders, and leaders recognized across national platforms.')}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Leadership & Governance Roster (Dynamically Toggled) -->
                                    ${showLeadership ? `
                                        <div class="about-card-elevated p-4 p-md-5 mb-4">
                                            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                                                <div>
                                                    <h4 class="fw-black text-dark mb-1" style="font-weight: 900;"><i class="bi bi-award text-primary me-2"></i> Executive Leadership & Core Committee</h4>
                                                    <p class="text-secondary small mb-0">Faculty Mentors, Chapter President, Core Leads & Founding Team</p>
                                                </div>
                                            </div>

                                            ${Object.keys(tenureMap).length === 0 ? `
                                                <div class="text-center py-4 text-muted bg-light rounded-4">
                                                    <i class="bi bi-people fs-2 d-block mb-1"></i>
                                                    Core leadership roster updating soon.
                                                </div>
                                            ` : Object.keys(tenureMap).map(term => `
                                                <div class="mb-4">
                                                    <div class="d-flex align-items-center gap-2 mb-3">
                                                        <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold small">${escapeHtml(term)} Academic Term</span>
                                                        <hr class="flex-grow-1 my-0">
                                                    </div>
                                                    <div class="row g-3">
                                                        ${tenureMap[term].map(leader => {
                                                            const isFaculty = leader.category === 'faculty_coordinator';
                                                            const isPresident = leader.category === 'president';
                                                            
                                                            let badgeClass = 'bg-primary-subtle text-primary';
                                                            if (isFaculty) badgeClass = 'bg-warning-subtle text-warning border-warning';
                                                            if (isPresident) badgeClass = 'bg-purple-subtle text-purple';

                                                            return `
                                                                <div class="col-6 col-md-4 text-center">
                                                                    <div class="p-3 bg-white rounded-4 border h-100 shadow-xs">
                                                                        <img src="${escapeHtml(leader.avatar)}" class="rounded-circle mb-2 border shadow-xs" style="width: 72px; height: 72px; object-fit: cover;" alt="${escapeHtml(leader.name)}" onerror="this.src='assets/United Logo.webp'">
                                                                        <h6 class="fw-black text-dark mb-1" style="font-size: 0.95rem; font-weight: 900;">${escapeHtml(leader.name)}</h6>
                                                                        <span class="badge ${badgeClass} border rounded-pill px-2.5 py-1 small" style="font-size: 0.72rem;">${escapeHtml(leader.role_title)}</span>
                                                                        ${leader.email ? `<span class="d-block text-muted small mt-1 text-truncate" style="font-size: 0.72rem;">${escapeHtml(leader.email)}</span>` : ''}
                                                                    </div>
                                                                </div>
                                                            `;
                                                        }).join('')}
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : ''}

                                    <!-- Club Events Showcase (Completed Recaps & Upcoming) -->
                                    <div class="about-card-elevated p-4 p-md-5 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                                            <div>
                                                <h4 class="fw-black text-dark mb-1" style="font-weight: 900;"><i class="bi bi-calendar-event text-primary me-2"></i> Chapter Events & Bootcamps (${club.events ? club.events.length : 0})</h4>
                                                <p class="text-secondary small mb-0">Hackathons, coding contests, and workshops organized by ${escapeHtml(club.name)}</p>
                                            </div>
                                        </div>

                                        ${(!club.events || club.events.length === 0) ? `
                                            <div class="text-center py-5 text-muted bg-light rounded-4">
                                                <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                                                <h6 class="fw-bold text-dark">No Events Published Yet</h6>
                                                <p class="small text-muted mb-0">Check back soon for upcoming hackathons and workshops.</p>
                                            </div>
                                        ` : `
                                            <div class="row g-4">
                                                ${club.events.map(ev => {
                                                    const dateFormatted = ev.event_date ? new Date(ev.event_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'TBA';
                                                    const isCompleted = ev.status === 'completed';
                                                    const isOngoing = ev.status === 'ongoing';

                                                    let statusBadge = `<span class="badge bg-primary rounded-pill px-3 py-1 fw-bold"><i class="bi bi-calendar-event me-1"></i> Upcoming</span>`;
                                                    if (isCompleted) {
                                                        statusBadge = `<span class="badge bg-secondary rounded-pill px-3 py-1 fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Concluded</span>`;
                                                    } else if (isOngoing) {
                                                        statusBadge = `<span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold"><span class="pulse-dot-green me-1.5"></span> Live Workshop</span>`;
                                                    }

                                                    const eventFormatPill = (ev.venue && ev.venue.toLowerCase().includes('online')) 
                                                        ? `<span class="badge bg-info-subtle text-info border rounded-pill px-2.5 py-1 small"><i class="bi bi-laptop me-1"></i> Online</span>` 
                                                        : `<span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Offline Session</span>`;

                                                    return `
                                                        <div class="col-md-6">
                                                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 d-flex flex-column justify-content-between transition-all" style="border: 1px solid #e2e8f0 !important; background: #ffffff;">
                                                                <div>
                                                                    <!-- Event Poster Image -->
                                                                    <div class="position-relative" style="height: 160px; overflow: hidden;">
                                                                        <img src="${escapeHtml(ev.banner)}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(ev.title)}" onerror="this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop'">
                                                                        <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.65) 100%);"></div>
                                                                        <div class="position-absolute top-0 start-0 end-0 p-2.5 z-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
                                                                            ${statusBadge}
                                                                            ${eventFormatPill}
                                                                        </div>
                                                                        <div class="position-absolute bottom-0 start-0 m-3 z-2 text-white">
                                                                            <span class="small fw-bold"><i class="bi bi-clock me-1 text-info"></i>${escapeHtml(dateFormatted)}</span>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Event Content Body -->
                                                                    <div class="p-4">
                                                                        <h5 class="fw-black text-dark mb-2" style="font-weight: 900; font-size: 1.05rem;">${escapeHtml(ev.title)}</h5>
                                                                        <div class="small text-muted mb-3"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${escapeHtml(ev.venue || 'UIT Campus')}</div>
                                                                        <p class="small text-secondary mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5;">${escapeHtml(ev.description || 'Join us for an exciting session of learning and innovation.')}</p>

                                                                        ${isCompleted && (ev.actual_attended || ev.registered_count) ? `
                                                                            <div class="p-3 bg-light rounded-3 border mb-3 small">
                                                                                <div class="d-flex justify-content-between text-dark fw-bold" style="font-size:0.75rem;">
                                                                                    <span><i class="bi bi-people-fill text-success me-1"></i> Attendees: ${ev.actual_attended || 60}+</span>
                                                                                    <span><i class="bi bi-check-circle-fill text-primary me-1"></i> Registered: ${ev.registered_count || 85}</span>
                                                                                </div>
                                                                            </div>
                                                                        ` : ''}
                                                                    </div>
                                                                </div>

                                                                <!-- Card Action Footer -->
                                                                <div class="p-4 pt-0">
                                                                    <a href="event-detail.html?id=${encodeURIComponent(ev.id)}" class="btn ${isCompleted ? 'btn-outline-primary' : 'btn-primary'} rounded-pill w-100 py-2.5 fw-bold text-decoration-none d-flex align-items-center justify-content-center gap-2">
                                                                        <span>${isCompleted ? 'View Event Recap & Photos' : 'Register for Event'}</span>
                                                                        <i class="bi bi-arrow-right"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
                                                }).join('')}
                                            </div>
                                        `}
                                    </div>

                                    <!-- Official Club Photo Gallery Section (Dynamically Toggled) -->
                                    ${(showGallery && club.gallery && club.gallery.length > 0) ? `
                                        <div class="about-card-elevated p-4 p-md-5 mb-4">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="fw-black text-dark mb-0" style="font-weight: 900;"><i class="bi bi-images text-primary me-2"></i> Official Chapter Photo Gallery</h4>
                                                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold">${club.gallery.length} Photos</span>
                                            </div>
                                            <p class="text-secondary small mb-4">Highlights, orientation sessions, and team moments from ${escapeHtml(club.name)}</p>
                                            <div class="row g-3" id="clubGalleryGrid">
                                                ${club.gallery.map((g, idx) => `
                                                    <div class="col-6 col-md-4 col-lg-3 club-gallery-item" style="${idx >= 8 ? 'display:none;' : ''}">
                                                        <div class="rounded-4 overflow-hidden shadow-sm position-relative gallery-thumb-wrap" style="height: 170px; cursor:pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                                                             onclick="openGalleryLightbox('${escapeHtml(g.media_url)}','${escapeHtml(g.caption || '')}')"
                                                             onmouseover="this.style.transform='scale(1.03)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.18)';"
                                                             onmouseout="this.style.transform='scale(1)';this.style.boxShadow='';">
                                                            <img src="${escapeHtml(g.media_url)}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(g.caption || 'Club Photo')}"
                                                                 loading="lazy"
                                                                 onerror="this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=400&auto=format&fit=crop'">
                                                            <div class="position-absolute inset-0 d-flex align-items-center justify-content-center opacity-0 hover-overlay" style="background:rgba(0,0,0,0.35);transition:opacity 0.2s;border-radius:1rem;">
                                                                <i class="bi bi-zoom-in text-white fs-4"></i>
                                                            </div>
                                                            ${g.caption ? `<div class="position-absolute bottom-0 start-0 end-0 px-2 py-1 text-white fw-semibold text-truncate" style="background:linear-gradient(transparent,rgba(0,0,0,0.7));font-size:0.72rem;border-radius:0 0 1rem 1rem;">${escapeHtml(g.caption)}</div>` : ''}
                                                        </div>
                                                    </div>
                                                `).join('')}
                                            </div>
                                            ${club.gallery.length > 8 ? `
                                            <div class="text-center mt-4">
                                                <button class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold" onclick="toggleGalleryItems()">
                                                    <i class="bi bi-plus-circle me-1"></i> Show All ${club.gallery.length} Photos
                                                </button>
                                            </div>` : ''}
                                        </div>

                                        <!-- Lightbox Modal -->
                                        <div id="galleryLightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.92);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:12px;" onclick="this.style.display='none'">
                                            <img id="galleryLightboxImg" src="" style="max-width:90vw;max-height:80vh;border-radius:12px;object-fit:contain;box-shadow:0 20px 60px rgba(0,0,0,0.5);" alt="">
                                            <div id="galleryLightboxCaption" style="color:#fff;font-size:0.95rem;font-weight:600;text-align:center;max-width:600px;padding:0 20px;"></div>
                                            <button onclick="document.getElementById('galleryLightbox').style.display='none'" style="position:absolute;top:20px;right:24px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:#fff;border-radius:50%;width:40px;height:40px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
                                        </div>
                                    ` : ''}
                                </div>

                                <!-- Right Sidebar Column: Office, Schedule & Socials -->
                                <div class="col-lg-4">
                                    <div class="about-card-elevated p-4 sticky-lg-top" style="top: 100px;">
                                        <h5 class="fw-black text-dark mb-3" style="font-weight: 900;">Chapter Details & Secretariat</h5>
                                        <ul class="list-unstyled space-y-3 text-secondary small mb-4">
                                            <li class="d-flex gap-3 align-items-center mb-3">
                                                <div class="about-pillar-icon mb-0 flex-shrink-0" style="background:#eff6ff; color:#2563eb; width:40px; height:40px; font-size:1.1rem;">
                                                    <i class="bi bi-building"></i>
                                                </div>
                                                <div>
                                                    <strong class="d-block text-dark">Office Location</strong>
                                                    <span>${escapeHtml(club.office_location || 'USC UIT Office, UIT Campus')}</span>
                                                </div>
                                            </li>
                                            <li class="d-flex gap-3 align-items-center mb-3">
                                                <div class="about-pillar-icon mb-0 flex-shrink-0" style="background:#f5f3ff; color:#7c3aed; width:40px; height:40px; font-size:1.1rem;">
                                                    <i class="bi bi-geo-alt"></i>
                                                </div>
                                                <div>
                                                    <strong class="d-block text-dark">Meeting Location</strong>
                                                    <span>${escapeHtml(club.meeting_location || 'Seminar Hall 1, UIT')}</span>
                                                </div>
                                            </li>
                                            <li class="d-flex gap-3 align-items-center mb-3">
                                                <div class="about-pillar-icon mb-0 flex-shrink-0" style="background:#ecfdf5; color:#10b981; width:40px; height:40px; font-size:1.1rem;">
                                                    <i class="bi bi-clock"></i>
                                                </div>
                                                <div>
                                                    <strong class="d-block text-dark">Meeting Schedule</strong>
                                                    <span>${escapeHtml(club.meeting_time || 'Wednesdays 04:00 PM')}</span>
                                                </div>
                                            </li>
                                            <li class="d-flex gap-3 align-items-center mb-3">
                                                <div class="about-pillar-icon mb-0 flex-shrink-0" style="background:#fff7ed; color:#c2410c; width:40px; height:40px; font-size:1.1rem;">
                                                    <i class="bi bi-envelope"></i>
                                                </div>
                                                <div>
                                                    <strong class="d-block text-dark">Contact Email</strong>
                                                    <span>${escapeHtml(club.email || 'club@uit.edu')}</span>
                                                </div>
                                            </li>
                                        </ul>

                                        <h6 class="fw-black text-dark mb-3" style="font-weight: 900;">Official Chapter Links</h6>
                                        <div class="d-flex gap-2 flex-wrap">
                                            ${club.website ? `<a href="${escapeHtml(club.website)}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-globe me-1"></i> Website</a>` : ''}
                                            ${club.instagram ? `<a href="${escapeHtml(club.instagram)}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-instagram me-1"></i> Instagram</a>` : ''}
                                            ${club.linkedin ? `<a href="${escapeHtml(club.linkedin)}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-linkedin me-1"></i> LinkedIn</a>` : ''}
                                            ${club.github ? `<a href="${escapeHtml(club.github)}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-github me-1"></i> GitHub</a>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                `;
            })
            .catch(err => {
                console.error('Error fetching club details:', err);
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

// Gallery Lightbox
function openGalleryLightbox(src, caption) {
    const lb = document.getElementById('galleryLightbox');
    const img = document.getElementById('galleryLightboxImg');
    const cap = document.getElementById('galleryLightboxCaption');
    if (!lb || !img) return;
    img.src = src;
    if (cap) cap.textContent = caption || '';
    lb.style.display = 'flex';
    event.stopPropagation();
}

// Toggle show all gallery items
function toggleGalleryItems() {
    const hidden = document.querySelectorAll('.club-gallery-item[style*="display:none"]');
    const btn = event.target.closest('button');
    if (hidden.length > 0) {
        hidden.forEach(el => el.style.display = '');
        if (btn) btn.innerHTML = '<i class="bi bi-dash-circle me-1"></i> Show Less';
    } else {
        let idx = 0;
        document.querySelectorAll('.club-gallery-item').forEach(el => {
            if (idx >= 8) el.style.display = 'none';
            idx++;
        });
        if (btn) btn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Show All Photos';
    }
}

// Close lightbox on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        const lb = document.getElementById('galleryLightbox');
        if (lb) lb.style.display = 'none';
    }
});
