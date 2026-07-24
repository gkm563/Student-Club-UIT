/**
 * Dynamic Club Directory & Club Detail Renderer (ClubHub UIT)
 * Connects public frontend HTML directly to PHP REST APIs
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Render Clubs Directory (clubs.html)
    const clubsGrid = document.getElementById('clubsGrid');
    const categoryFilterList = document.getElementById('categoryFilterList');
    const clubSearchInput = document.getElementById('clubSearchInput');
    const sortSelect = document.querySelector('select.form-select');

    if (clubsGrid) {
        let currentCategory = 'all';
        let currentSearch = '';
        let currentSort = 'popularity';

        function loadClubs() {
            clubsGrid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 small">Loading active campus clubs...</p>
                </div>
            `;

            const apiUrl = `/api/clubs.php?category=${encodeURIComponent(currentCategory)}&search=${encodeURIComponent(currentSearch)}&sort=${encodeURIComponent(currentSort)}`;

            fetch(apiUrl)
                .then(res => res.json())
                .then(response => {
                    if (response.status !== 'success' || !response.data || response.data.length === 0) {
                        clubsGrid.innerHTML = `
                            <div class="col-12 text-center py-5">
                                <div class="p-5 bg-white rounded-4 shadow-sm border max-w-md mx-auto">
                                    <i class="bi bi-inbox fs-1 text-primary d-block mb-3"></i>
                                    <h5 class="fw-bold mb-2">No Clubs Found</h5>
                                    <p class="text-secondary small mb-4">There are currently no active clubs matching your search criteria.</p>
                                    <a href="/admin/login.php" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
                                        <i class="bi bi-shield-lock me-1"></i> Dean Admin Login
                                    </a>
                                </div>
                            </div>
                        `;
                        return;
                    }

                    clubsGrid.innerHTML = response.data.map(club => `
                        <div class="col-md-4">
                            <a href="/club-detail.html?id=${club.id}" class="text-decoration-none text-dark">
                                <div class="card p-4 border-0 shadow-sm rounded-4 h-100 ccms-card position-relative">
                                    <button class="btn btn-link text-secondary position-absolute top-0 end-0 m-3 p-0" title="Bookmark"><i class="bi bi-bookmark fs-5"></i></button>
                                    
                                    <div class="bg-primary-subtle text-primary rounded-circle p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                                        <i class="bi ${club.category_icon || 'bi-trophy'} fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold mb-1">${escapeHtml(club.name)}</h5>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 small align-self-start mb-3">${escapeHtml(club.category_name)}</span>
                                    <p class="text-secondary small mb-4 flex-grow-1">${escapeHtml(club.tagline || club.description || '')}</p>

                                    <div class="d-flex align-items-center justify-content-between pt-3 border-top small text-muted">
                                        <span><i class="bi bi-people me-1"></i> ${club.member_count || 0} Members</span>
                                        <span class="${club.recruitment_open ? 'text-success' : 'text-secondary'}">
                                            <i class="bi bi-record-fill me-1"></i> ${club.recruitment_open ? 'Open for All' : 'By Invitation'}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    `).join('');
                })
                .catch(err => {
                    console.error('Error fetching clubs:', err);
                });
        }

        // Category Filter Pill Clicks
        if (categoryFilterList) {
            categoryFilterList.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-category]');
                if (!btn) return;
                
                categoryFilterList.querySelectorAll('button').forEach(b => {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-light', 'text-secondary');
                });

                btn.classList.add('active', 'btn-primary');
                btn.classList.remove('btn-light', 'text-secondary');

                currentCategory = btn.getAttribute('data-category');
                loadClubs();
            });
        }

        // Search Input Listener
        if (clubSearchInput) {
            let debounceTimer;
            clubSearchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentSearch = e.target.value;
                    loadClubs();
                }, 300);
            });
        }

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
                        <a href="/clubs.html" class="btn btn-primary rounded-pill px-4 py-2 fw-bold">Browse All Clubs</a>
                    </div>
                </div>
            `;
            return;
        }

        fetch(`/api/clubs.php?id=${encodeURIComponent(clubId)}`)
            .then(res => res.json())
            .then(response => {
                if (response.status !== 'success' || !response.data) {
                    detailContainer.innerHTML = `
                        <div class="container py-5 text-center">
                            <div class="alert alert-danger rounded-4 border-0 p-5 shadow-sm max-w-md mx-auto">
                                <i class="bi bi-x-circle fs-1 d-block mb-3 text-danger"></i>
                                <h4 class="fw-bold mb-2">Club Not Found</h4>
                                <p class="small text-secondary mb-4">The requested club could not be found or has been removed.</p>
                                <a href="/clubs.html" class="btn btn-primary rounded-pill px-4 py-2 fw-bold">Return to Directory</a>
                            </div>
                        </div>
                    `;
                    return;
                }

                const club = response.data;
                document.title = `${club.name} | ClubHub UIT`;

                // Render Club Details Layout
                detailContainer.innerHTML = `
                    <!-- Hero Cover Banner -->
                    <section class="hero-clubhub py-5" style="background: linear-gradient(180deg, rgba(11, 15, 25, 0.85) 0%, rgba(11, 15, 25, 0.98) 100%), url('${escapeHtml(club.cover_image)}') center/cover;">
                        <div class="container py-4">
                            <div class="row align-items-center g-4">
                                <div class="col-lg-8 d-flex align-items-center gap-4">
                                    <img src="${escapeHtml(club.logo)}" class="rounded-4 border border-white-10 bg-white p-2 shadow-lg flex-shrink-0" style="width: 100px; height: 100px; object-fit: contain;">
                                    <div>
                                        <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1-5 mb-2 fw-semibold small">${escapeHtml(club.category_name)}</span>
                                        <h1 class="hero-headline mb-2" style="font-size: 2.8rem;">${escapeHtml(club.name)}</h1>
                                        <p class="hero-subtitle mb-0">${escapeHtml(club.tagline || '')}</p>
                                    </div>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <a href="/contact.html" class="btn btn-primary rounded-pill px-5 py-2-5 fw-bold shadow-lg">
                                        Join Club &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Main Section -->
                    <section class="py-5 bg-body-tertiary">
                        <div class="container">
                            <div class="row g-5">
                                <!-- Left Column: About, Mission, Leadership -->
                                <div class="col-lg-8">
                                    <!-- Overview Card -->
                                    <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4">
                                        <h4 class="fw-bold mb-3">About ${escapeHtml(club.name)}</h4>
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

                                    <!-- Annual Leadership Roster Card -->
                                    <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <h4 class="fw-bold mb-0">Annual Core Leadership</h4>
                                                <span class="small text-muted">Current Academic Term Roster</span>
                                            </div>
                                        </div>

                                        <div class="row g-4">
                                            ${(!club.leadership || club.leadership.length === 0) ? `
                                                <div class="col-12 text-center py-4 text-muted">
                                                    <i class="bi bi-people fs-2 d-block mb-1"></i>
                                                    Core leadership roster updating soon.
                                                </div>
                                            ` : club.leadership.map(leader => `
                                                <div class="col-6 col-md-4 text-center">
                                                    <div class="p-3 bg-body-tertiary rounded-4 border h-100">
                                                        <img src="${escapeHtml(leader.avatar)}" class="rounded-circle mb-3 border shadow-sm" style="width: 72px; height: 72px; object-fit: cover;">
                                                        <h6 class="fw-bold mb-1 text-dark">${escapeHtml(leader.name)}</h6>
                                                        <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 small">${escapeHtml(leader.role_title)}</span>
                                                        <span class="small text-muted d-block mt-2" style="font-size: 0.72rem;">${escapeHtml(leader.term_year || '')}</span>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Sidebar Column: Contact & Quick Info -->
                                <div class="col-lg-4">
                                    <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
                                        <h5 class="fw-bold mb-3">Club Info & Contacts</h5>
                                        <ul class="list-unstyled space-y-3 text-secondary small mb-4">
                                            <li class="d-flex gap-3 align-items-center mb-2">
                                                <i class="bi bi-geo-alt fs-5 text-primary"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Meeting Location</strong>
                                                    <span>${escapeHtml(club.meeting_location || 'Seminar Hall, UIT')}</span>
                                                </div>
                                            </li>
                                            <li class="d-flex gap-3 align-items-center mb-2">
                                                <i class="bi bi-clock fs-5 text-primary"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Meeting Time</strong>
                                                    <span>${escapeHtml(club.meeting_time || 'Wednesdays 04:00 PM')}</span>
                                                </div>
                                            </li>
                                            <li class="d-flex gap-3 align-items-center mb-2">
                                                <i class="bi bi-envelope fs-5 text-primary"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Contact Email</strong>
                                                    <span>${escapeHtml(club.email || 'club@uit.edu.in')}</span>
                                                </div>
                                            </li>
                                        </ul>

                                        <h6 class="fw-bold mb-3">Follow Us</h6>
                                        <div class="d-flex gap-2">
                                            ${club.instagram ? `<a href="${escapeHtml(club.instagram)}" target="_blank" class="btn btn-outline-primary rounded-circle"><i class="bi bi-instagram"></i></a>` : ''}
                                            ${club.linkedin ? `<a href="${escapeHtml(club.linkedin)}" target="_blank" class="btn btn-outline-primary rounded-circle"><i class="bi bi-linkedin"></i></a>` : ''}
                                            ${club.github ? `<a href="${escapeHtml(club.github)}" target="_blank" class="btn btn-outline-primary rounded-circle"><i class="bi bi-github"></i></a>` : ''}
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
