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
        // Read URL parameter on load
        const urlParams = new URLSearchParams(window.location.search);
        let currentCategory = urlParams.get('category') || 'all';
        let currentSearch = urlParams.get('search') || '';
        let currentSort = 'popularity';

        if (clubSearchInput && currentSearch) {
            clubSearchInput.value = currentSearch;
        }

        const getApiUrl = (endpoint) => `api/${endpoint}`;

        function loadClubs() {
            clubsGrid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 small">Loading active campus clubs...</p>
                </div>
            `;

            const apiUrl = getApiUrl(`clubs.php?category=${encodeURIComponent(currentCategory)}&search=${encodeURIComponent(currentSearch)}&sort=${encodeURIComponent(currentSort)}`);

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

                    clubsGrid.innerHTML = clubs.map(club => {
                        const coverImg = escapeHtml(club.cover_image || 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=800&auto=format&fit=crop');
                        const logoImg = club.logo ? `<img src="${escapeHtml(club.logo)}" class="featured-club-logo-img" alt="${escapeHtml(club.name)}" onerror="this.outerHTML='<i class=\'bi ${escapeHtml(club.category_icon || 'bi-shield-check')} fs-3 text-primary\'></i>'">` : `<i class="bi ${escapeHtml(club.category_icon || 'bi-shield-check')} fs-3 text-primary"></i>`;
                        const memberCount = club.member_count || 45;
                        const isGfg = club.id === 'clb_gfg_sc_uit_2026';
                        const isGemini = club.id === 'clb_gemini_builders_uit_2026';
                        const isGdg = club.id === 'clb_gdgoc_uit_2026';

                        let leadName = 'Student Executive Committee';
                        if (isGfg) leadName = 'Ansh Kumar Gupta (Campus Mantri)';
                        else if (isGemini) leadName = 'Google Student Ambassador';
                        else if (isGdg) leadName = 'GDG Student Lead';

                        let eventSummary = 'Active Events';
                        if (isGfg) eventSummary = '3 Real Events (SyntaxClash, AI/ML)';
                        else if (isGemini) eventSummary = 'AI Builder Bootcamp';
                        else if (isGdg) eventSummary = 'Cloud Study Jam 2026';

                        const recruitmentBadge = club.recruitment_open 
                            ? `<span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#ecfdf5; color:#059669; border:1px solid rgba(5,150,105,0.3); font-size:0.75rem;"><span class="pulse-dot-green me-1.5"></span>Recruiting Members</span>`
                            : `<span class="badge rounded-pill px-3 py-1 fw-bold" style="background:#eff6ff; color:#1d4ed8; border:1px solid rgba(29,78,216,0.3); font-size:0.75rem;"><i class="bi bi-shield-check me-1"></i>Official SAC Chapter</span>`;

                        return `
                            <div class="col-md-6 col-lg-6">
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 ccms-club-prestige-card transition-all" style="background:#ffffff; border:1px solid #e2e8f0 !important;">
                                    <!-- Cover Banner Header -->
                                    <div class="position-relative" style="height: 160px;">
                                        <img src="${coverImg}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(club.name)}">
                                        <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.15) 0%, rgba(15,23,42,0.75) 100%);"></div>

                                        <!-- Category Badge floating right -->
                                        <div class="position-absolute top-0 end-0 m-3 z-2">
                                            <span class="badge bg-white text-dark rounded-pill px-3 py-1.5 fw-bold shadow-sm" style="font-size: 0.75rem;">
                                                <i class="bi ${escapeHtml(club.category_icon || 'bi-grid')} me-1 text-primary"></i> ${escapeHtml(club.category_name)}
                                            </span>
                                        </div>

                                        <!-- Floating Logo Badge -->
                                        <div class="position-absolute bg-white rounded-4 p-1 shadow-md d-flex align-items-center justify-content-center" style="width: 62px; height: 62px; bottom: -24px; left: 24px; z-index: 3; border: 3px solid #ffffff;">
                                            ${logoImg}
                                        </div>
                                    </div>

                                    <!-- Card Body -->
                                    <div class="p-4 pt-4 mt-2 flex-grow-1 d-flex flex-column">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            ${recruitmentBadge}
                                        </div>

                                        <h4 class="fw-bold mb-1 text-dark" style="font-size: 1.25rem;">
                                            <a href="club-detail.html?id=${encodeURIComponent(club.id)}" class="text-decoration-none text-dark hover-blue">
                                                ${escapeHtml(club.name)}
                                            </a>
                                        </h4>

                                        <p class="text-secondary small mb-3 flex-grow-1" style="font-size: 0.88rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            ${escapeHtml(club.tagline || club.description || 'Official student chapter at United Institute of Technology organizing workshops, contests, and campus events.')}
                                        </p>

                                        <!-- Key Organization Info Deck -->
                                        <div class="rounded-3 p-3 mb-3 bg-light border border-light-subtle">
                                            <div class="row g-2 text-dark small" style="font-size: 0.8rem;">
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center gap-1.5">
                                                        <i class="bi bi-people-fill text-primary"></i>
                                                        <span><strong>${memberCount}+</strong> Active Members</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="d-flex align-items-center gap-1.5">
                                                        <i class="bi bi-calendar-event-fill text-danger"></i>
                                                        <span class="text-truncate"><strong>${eventSummary}</strong></span>
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-1 pt-1 border-top border-light-subtle">
                                                    <div class="d-flex align-items-center gap-1.5 text-secondary">
                                                        <i class="bi bi-person-badge-fill text-purple"></i>
                                                        <span>Leadership: <strong class="text-dark">${escapeHtml(leadName)}</strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Footer Action Buttons Bar -->
                                        <div class="d-flex align-items-center gap-2 pt-2 mt-auto">
                                            <a href="club-detail.html?id=${encodeURIComponent(club.id)}" class="btn btn-primary rounded-pill px-4 py-2 fw-bold w-100 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 0.88rem;">
                                                <span>Explore Chapter & Events</span>
                                                <i class="bi bi-arrow-right-short fs-5"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
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

            let pillsHtml = `
                <button class="btn btn-sm text-start rounded-pill px-3 py-2 fw-bold d-flex justify-content-between align-items-center transition-all ${currentCategory === 'all' ? 'active btn-primary text-white shadow-sm' : 'btn-light text-secondary border-0'}" data-category="all" style="font-size: 0.85rem;">
                    <span><i class="bi ${categoryIcons['all']} me-2 text-warning"></i> All Domains</span>
                    <span class="badge ${currentCategory === 'all' ? 'bg-white text-primary' : 'bg-secondary-subtle text-dark'} rounded-pill ms-2 fw-extrabold">${totalClubs}</span>
                </button>
            `;

            categories.forEach(cat => {
                const isActive = (currentCategory === cat.slug);
                const icon = categoryIcons[cat.slug] || cat.icon || 'bi-bookmark-star-fill';
                pillsHtml += `
                    <button class="btn btn-sm text-start rounded-pill px-3 py-2 fw-semibold d-flex justify-content-between align-items-center transition-all ${isActive ? 'active btn-primary text-white shadow-sm' : 'btn-light text-secondary border-0'}" data-category="${escapeHtml(cat.slug)}" style="font-size: 0.85rem;">
                        <span class="text-truncate me-1"><i class="bi ${icon} me-2 ${isActive ? 'text-white' : 'text-primary'}"></i> ${escapeHtml(cat.name)}</span>
                        <span class="badge ${isActive ? 'bg-white text-primary' : 'bg-secondary-subtle text-dark'} rounded-pill ms-2 fw-bold">${cat.club_count || 0}</span>
                    </button>
                `;
            });

            categoryFilterList.innerHTML = pillsHtml;
        }

        // Render Active Filter Indicator Bar
        function renderActiveFilterBadge(categories, count) {
            if (!activeFilterBadge) return;

            if (currentCategory === 'all' && !currentSearch) {
                activeFilterBadge.innerHTML = '';
                return;
            }

            let catName = 'All Categories';
            if (currentCategory !== 'all' && categories) {
                const found = categories.find(c => c.slug === currentCategory);
                if (found) catName = found.name;
            }

            activeFilterBadge.innerHTML = `
                <div class="alert alert-primary border-0 rounded-4 shadow-sm py-2-5 px-4 d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2 small">
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
                    setCategoryFilter('all');
                });
            }
        }

        function setCategoryFilter(catSlug) {
            currentCategory = catSlug;
            updateUrlParam('category', catSlug === 'all' ? null : catSlug);
            loadClubs();
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
                        <a href="/contact.html" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold mt-2">
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

        // Category Filter Pill Clicks (Event Delegation)
        if (categoryFilterList) {
            categoryFilterList.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-category]');
                if (!btn) return;
                const catSlug = btn.getAttribute('data-category');
                setCategoryFilter(catSlug);
            });
        }

        // Search Input Listener
        if (clubSearchInput) {
            let debounceTimer;
            clubSearchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentSearch = e.target.value.trim();
                    const heroInput = document.getElementById('heroDirectorySearchInput');
                    if (heroInput) heroInput.value = currentSearch;
                    updateUrlParam('search', currentSearch || null);
                    loadClubs();
                }, 300);
            });
        }

        // Hero Directory Search Input & Trigger Btn
        const heroDirectorySearchInput = document.getElementById('heroDirectorySearchInput');
        const heroSearchTriggerBtn = document.getElementById('heroSearchTriggerBtn');

        if (heroDirectorySearchInput) {
            let heroDebounce;
            heroDirectorySearchInput.addEventListener('input', (e) => {
                clearTimeout(heroDebounce);
                heroDebounce = setTimeout(() => {
                    currentSearch = e.target.value.trim();
                    if (clubSearchInput) clubSearchInput.value = currentSearch;
                    updateUrlParam('search', currentSearch || null);
                    loadClubs();
                }, 300);
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
            currentSearch = params.get('search') || '';
            if (clubSearchInput) clubSearchInput.value = currentSearch;
            loadClubs();
        });

        // Initial Load
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
                document.title = `${club.name} | ClubHub UIT`;

                // Group Leadership Roster by Tenure / Term Year
                const tenureMap = {};
                if (club.leadership && club.leadership.length > 0) {
                    club.leadership.forEach(leader => {
                        const term = leader.term_year || 'Current Term';
                        if (!tenureMap[term]) tenureMap[term] = [];
                        tenureMap[term].push(leader);
                    });
                }

                const coverImg = escapeHtml(club.cover_image || 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop');
                const logoImg = escapeHtml(club.logo || 'assets/United Logo.webp');

                // Render Club Details Layout
                detailContainer.innerHTML = `
                    <!-- Hero Cover Banner -->
                    <section class="hero-club-banner position-relative overflow-hidden" style="min-height: 360px; background: #0f172a;">
                        <!-- Cover Banner Image -->
                        <img src="${coverImg}" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" alt="${escapeHtml(club.name)}" onerror="this.src='https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop'">
                        
                        <!-- Subtle Gradient Overlay (Lighter top, dark gradient at bottom for text contrast) -->
                        <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.2) 0%, rgba(15,23,42,0.85) 100%);"></div>

                        <!-- Content Overlay -->
                        <div class="container position-relative z-2 h-100 d-flex flex-column justify-content-end py-5" style="min-height: 360px;">
                            <div class="row align-items-end g-4">
                                <div class="col-lg-8 d-flex align-items-center gap-4">
                                    <div class="position-relative flex-shrink-0">
                                        <img src="${logoImg}" class="rounded-4 border border-white-20 bg-white p-2 shadow-2xl" style="width: 120px; height: 120px; object-fit: cover;" alt="${escapeHtml(club.name)}" onerror="this.src='assets/United Logo.webp'">
                                    </div>
                                    <div>
                                        <a href="clubs.html?category=${encodeURIComponent(club.category_slug)}" class="badge bg-primary text-white rounded-pill px-3 py-1-5 mb-2 fw-semibold small text-decoration-none shadow-sm">
                                            <i class="bi ${escapeHtml(club.category_icon || 'bi-tag')} me-1"></i> ${escapeHtml(club.category_name)}
                                        </a>
                                        <h1 class="hero-headline mb-2 text-white fw-bold" style="font-size: 2.75rem; letter-spacing: -0.5px; text-shadow: 0 2px 10px rgba(0,0,0,0.6);">${escapeHtml(club.name)}</h1>
                                        <p class="hero-subtitle mb-0 text-white fs-6 fw-medium" style="text-shadow: 0 1px 6px rgba(0,0,0,0.6);">${escapeHtml(club.tagline || '')}</p>
                                    </div>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    ${club.recruitment_open 
                                        ? `<a href="contact.html" class="btn rounded-pill px-5 py-3 fw-bold shadow-xl text-white" style="background:#10b981; border:none;"><span class="pulse-dot-green me-2"></span>Join Club &rarr;</a>`
                                        : `<a href="contact.html" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-xl text-white"><i class="bi bi-person-plus me-2"></i>Join Club &rarr;</a>`
                                    }
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Main Content Section -->
                    <section class="py-5 bg-body-tertiary">
                        <div class="container">
                            <div class="row g-5">
                                <!-- Left Column: About, Mission, Leadership Tenures, Events -->
                                <div class="col-lg-8">
                                    <!-- Overview Card -->
                                    <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4">
                                        <h4 class="fw-bold mb-3 text-dark">About ${escapeHtml(club.name)}</h4>
                                        <p class="text-secondary leading-relaxed">${escapeHtml(club.description || 'No description available yet.')}</p>

                                        <div class="row g-4 mt-3">
                                            <div class="col-md-6">
                                                <div class="p-3 bg-body-tertiary rounded-3 border">
                                                    <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i> Mission</h6>
                                                    <p class="small text-secondary mb-0">${escapeHtml(club.mission || 'To empower students through hands-on learning and collaboration.')}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="p-3 bg-body-tertiary rounded-3 border">
                                                    <h6 class="fw-bold text-info mb-2"><i class="bi bi-eye me-1"></i> Vision</h6>
                                                    <p class="small text-secondary mb-0">${escapeHtml(club.vision || 'To lead innovation and build a strong student community.')}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Official Club Photo Gallery Section -->
                                    ${(club.gallery && club.gallery.length > 0) ? `
                                        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 bg-white">
                                            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-images text-primary me-2"></i> Official Club Photo Gallery</h4>
                                            <p class="text-secondary small mb-4">Highlights, orientation sessions, and team moments from ${escapeHtml(club.name)}</p>
                                            <div class="row g-3">
                                                ${club.gallery.map(g => `
                                                    <div class="col-6 col-md-4">
                                                        <div class="rounded-4 overflow-hidden border shadow-xs position-relative" style="height: 150px;">
                                                            <img src="${escapeHtml(g.media_url)}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(g.caption || 'Club Photo')}" onerror="this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=600&auto=format&fit=crop'">
                                                            ${g.caption ? `<div class="position-absolute bottom-0 inset-x-0 p-2 text-white bg-dark bg-opacity-75 small text-truncate fw-semibold" style="font-size:0.75rem;">${escapeHtml(g.caption)}</div>` : ''}
                                                        </div>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                    ` : ''}

                                    <!-- Leadership Roster Grouped by Academic Tenure / Term Year -->
                                    <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-award text-primary me-2"></i> Annual Core Leadership & Tenure History</h4>
                                                <span class="small text-muted">Founding members, faculty advisors, and annual team leads</span>
                                            </div>
                                        </div>

                                        ${Object.keys(tenureMap).length === 0 ? `
                                            <div class="text-center py-4 text-muted bg-light rounded-3">
                                                <i class="bi bi-people fs-2 d-block mb-1"></i>
                                                Core leadership roster updating soon.
                                            </div>
                                        ` : Object.keys(tenureMap).map(term => `
                                            <div class="mb-4">
                                                <div class="d-flex align-items-center gap-2 mb-3">
                                                    <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold fs-6">${escapeHtml(term)} Academic Term</span>
                                                    <hr class="flex-grow-1 my-0">
                                                </div>
                                                <div class="row g-4">
                                                    ${tenureMap[term].map(leader => `
                                                        <div class="col-6 col-md-4 text-center">
                                                            <div class="p-3 bg-body-tertiary rounded-4 border h-100 ccms-card">
                                                                <img src="${escapeHtml(leader.avatar)}" class="rounded-circle mb-3 border shadow-sm" style="width: 72px; height: 72px; object-fit: cover;">
                                                                <h6 class="fw-bold mb-1 text-dark">${escapeHtml(leader.name)}</h6>
                                                                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 small">${escapeHtml(leader.role_title)}</span>
                                                            </div>
                                                        </div>
                                                    `).join('')}
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>

                                    <!-- Club Events Showcase -->
                                    <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-calendar-event text-primary me-2"></i> Official Events & Campus Activities (${club.events ? club.events.length : 0})</h4>
                                                <span class="small text-muted">Hackathons, workshops, and tech sessions hosted by ${escapeHtml(club.name)}</span>
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
                                                    const dateFormatted = ev.event_date ? new Date(ev.event_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'TBA';
                                                    const evType = ev.event_type || 'Workshop';
                                                    const isCompleted = ev.status === 'completed';
                                                    const isOngoing = ev.status === 'ongoing';

                                                    let statusBadge = `<span class="badge bg-primary rounded-pill px-3 py-1 fw-bold"><i class="bi bi-calendar-event me-1"></i> Upcoming</span>`;
                                                    if (isCompleted) {
                                                        statusBadge = `<span class="badge bg-secondary rounded-pill px-3 py-1 fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Concluded</span>`;
                                                    } else if (isOngoing) {
                                                        statusBadge = `<span class="badge bg-success rounded-pill px-3 py-1 fw-bold"><span class="pulse-dot-green me-1.5"></span> Live Now</span>`;
                                                    }

                                                    return `
                                                        <div class="col-md-6">
                                                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 d-flex flex-column justify-content-between transition-all card-hover-lift" style="border: 1px solid #e2e8f0 !important; background: #ffffff;">
                                                                <div>
                                                                    <!-- Event Poster Image -->
                                                                    <div class="position-relative" style="height: 160px; overflow: hidden;">
                                                                        <img src="${escapeHtml(ev.banner)}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(ev.title)}" onerror="this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop'">
                                                                        <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.7) 100%);"></div>
                                                                        <div class="position-absolute top-0 start-0 m-3 z-2">
                                                                            ${statusBadge}
                                                                        </div>
                                                                        <div class="position-absolute top-0 end-0 m-3 z-2">
                                                                            <span class="badge bg-white text-dark rounded-pill px-2.5 py-1 fw-bold small shadow-sm">${escapeHtml(evType)}</span>
                                                                        </div>
                                                                        <div class="position-absolute bottom-0 start-0 m-3 z-2 text-white">
                                                                            <span class="small fw-semibold"><i class="bi bi-clock me-1 text-info"></i>${escapeHtml(dateFormatted)}</span>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Event Content Body -->
                                                                    <div class="p-4">
                                                                        <h5 class="fw-bold text-dark mb-2">${escapeHtml(ev.title)}</h5>
                                                                        <div class="small text-muted mb-3"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${escapeHtml(ev.venue || 'UIT Campus')}</div>
                                                                        <p class="small text-secondary mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${escapeHtml(ev.description || 'Join us for an exciting session of learning and innovation.')}</p>

                                                                        ${isCompleted && (ev.actual_attended || ev.outcomes_summary) ? `
                                                                            <div class="p-3 bg-light rounded-3 border mb-3 small">
                                                                                <div class="d-flex justify-content-between text-dark fw-bold mb-1" style="font-size:0.75rem;">
                                                                                    <span><i class="bi bi-people-fill text-success me-1"></i> Attendees: ${ev.actual_attended || 0}</span>
                                                                                    <span><i class="bi bi-clipboard-check text-primary me-1"></i> Registered: ${ev.registered_count || 0}</span>
                                                                                </div>
                                                                                ${ev.outcomes_summary ? `<p class="mb-0 text-muted small fst-italic mt-1" style="font-size:0.75rem;">"${escapeHtml(ev.outcomes_summary)}"</p>` : ''}
                                                                            </div>
                                                                        ` : ''}
                                                                    </div>
                                                                </div>

                                                                <!-- Card Action Footer -->
                                                                <div class="p-4 pt-0">
                                                                    <a href="event-detail.html?id=${encodeURIComponent(ev.id)}" class="btn ${isCompleted ? 'btn-outline-primary' : 'btn-primary'} rounded-pill w-100 py-2-5 fw-bold text-decoration-none d-flex align-items-center justify-content-center gap-2">
                                                                        <span>${isCompleted ? 'View Event Recap & Photos' : 'View Full Details & RSVP'}</span>
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
                                </div>

                                <!-- Right Sidebar Column: Quick Info & Socials -->
                                <div class="col-lg-4">
                                    <div class="card p-4 border-0 shadow-sm rounded-4 mb-4 sticky-lg-top" style="top: 100px;">
                                        <h5 class="fw-bold mb-3 text-dark">Club Details & Office</h5>
                                        <ul class="list-unstyled space-y-3 text-secondary small mb-4">
                                            <li class="d-flex gap-3 align-items-center mb-3">
                                                <i class="bi bi-building fs-4 text-primary"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Office Location</strong>
                                                    <span>${escapeHtml(club.office_location || 'Student Activity Center, UIT')}</span>
                                                </div>
                                            </li>
                                            <li class="d-flex gap-3 align-items-center mb-3">
                                                <i class="bi bi-geo-alt fs-4 text-primary"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Meeting Location</strong>
                                                    <span>${escapeHtml(club.meeting_location || 'Seminar Hall, UIT')}</span>
                                                </div>
                                            </li>
                                            <li class="d-flex gap-3 align-items-center mb-3">
                                                <i class="bi bi-clock fs-4 text-primary"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Meeting Time</strong>
                                                    <span>${escapeHtml(club.meeting_time || 'Wednesdays 04:00 PM')}</span>
                                                </div>
                                            </li>
                                            <li class="d-flex gap-3 align-items-center mb-3">
                                                <i class="bi bi-envelope fs-4 text-primary"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Contact Email</strong>
                                                    <span>${escapeHtml(club.email || 'club@uit.edu.in')}</span>
                                                </div>
                                            </li>
                                        </ul>

                                        <h6 class="fw-bold mb-3 text-dark">Official Links</h6>
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
