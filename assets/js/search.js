/**
 * USC UIT Universal Search JavaScript (Real-Time In-Page Filter + Dropdown Engine)
 * Enables debounced live dropdown search, instant in-page filtering for home page cards,
 * Enter key navigation, and seamless search execution.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Home Page Hero Search
    const heroSearchInput = document.getElementById('heroSearchInput');
    const heroSearchBtn = document.querySelector('.hero-search-btn');
    const searchDropdown = document.getElementById('searchResultsDropdown');

    if (heroSearchInput) {
        let debounceTimer;

        // Perform navigation to clubs page with search query
        const executeSearch = () => {
            const query = heroSearchInput.value.trim();
            if (query.length > 0) {
                window.location.href = `clubs.html?search=${encodeURIComponent(query)}`;
            } else {
                window.location.href = 'clubs.html';
            }
        };

        // Click search button
        if (heroSearchBtn) {
            heroSearchBtn.addEventListener('click', (e) => {
                e.preventDefault();
                executeSearch();
            });
        }

        // Press Enter key
        heroSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                executeSearch();
            }
        });

        // Real-time In-Page Card Filter Function
        const filterHomePageItems = (query) => {
            const q = (query || '').toLowerCase().trim();

            // 1. Featured Clubs Grid items
            const clubCols = document.querySelectorAll('#featuredClubsGrid > div');
            clubCols.forEach(col => {
                if (!q) {
                    col.style.display = '';
                } else {
                    const text = (col.textContent || '').toLowerCase();
                    col.style.display = text.includes(q) ? '' : 'none';
                }
            });

            // 2. Recent Activities items
            const activityCards = document.querySelectorAll('#homeActivityList > div');
            activityCards.forEach(card => {
                if (!q) {
                    card.style.display = '';
                } else {
                    const text = (card.textContent || '').toLowerCase();
                    card.style.display = text.includes(q) ? '' : 'none';
                }
            });

            // 3. Upcoming Events items
            const eventCards = document.querySelectorAll('#homeUpcomingList > div');
            eventCards.forEach(card => {
                if (!q) {
                    card.style.display = '';
                } else {
                    const text = (card.textContent || '').toLowerCase();
                    card.style.display = text.includes(q) ? '' : 'none';
                }
            });
        };

        // Live Input Handler (In-Page Filter + Dropdown Popup)
        heroSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();

            filterHomePageItems(query);

            clearTimeout(debounceTimer);

            if (!searchDropdown) return;

            if (query.length < 2) {
                searchDropdown.style.display = 'none';
                searchDropdown.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`api/search.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.results && Array.isArray(data.results) && data.results.length > 0) {
                            let html = `
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom mb-1" style="border-color:#f1f5f9 !important;">
                                    <span class="text-uppercase fw-extrabold text-secondary" style="font-size:0.68rem; letter-spacing:0.8px;">
                                        <i class="bi bi-lightning-charge-fill me-1 text-primary"></i> Matches Found
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-0.5" style="font-size:0.68rem;">${data.results.length} Results</span>
                                </div>
                            `;

                            data.results.forEach(item => {
                                const isEvent = item.item_type === 'event';
                                const itemUrl = item.url || (isEvent ? `event-detail.html?id=${encodeURIComponent(item.id)}` : `club-detail.html?id=${encodeURIComponent(item.id)}`);
                                const iconBoxHtml = isEvent 
                                    ? `<div class="search-result-icon-box bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px; height:38px;"><i class="bi bi-calendar-event-fill fs-6"></i></div>`
                                    : `<div class="search-result-icon-box bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px; height:38px;"><i class="bi bi-shield-check fs-5"></i></div>`;
                                
                                const badgeHtml = isEvent
                                    ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold ms-2 flex-shrink-0" style="font-size:0.72rem;">Event</span>`
                                    : `<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold ms-2 flex-shrink-0" style="font-size:0.72rem;">${escapeHtml(item.category_name || 'Club')}</span>`;

                                html += `
                                    <a href="${itemUrl}" class="search-result-item">
                                        <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                                            ${iconBoxHtml}
                                            <div class="overflow-hidden min-w-0 flex-grow-1">
                                                <strong class="d-block text-dark text-truncate" style="font-size:0.88rem; font-weight:800; line-height:1.2;">${escapeHtml(item.name)}</strong>
                                                <small class="text-secondary text-truncate d-block mt-0.5" style="font-size:0.76rem;">${escapeHtml(item.tagline || item.short_name || '')}</small>
                                            </div>
                                        </div>
                                        ${badgeHtml}
                                    </a>
                                `;
                            });
                            
                            html += `
                                <a href="clubs.html?search=${encodeURIComponent(query)}" class="search-result-footer-btn">
                                    <span><i class="bi bi-arrow-right-circle-fill me-1.5 text-primary"></i> View all matches for "<strong>${escapeHtml(query)}</strong>" in Directory</span>
                                    <i class="bi bi-chevron-right fs-6"></i>
                                </a>
                            `;

                            searchDropdown.innerHTML = html;
                            searchDropdown.style.display = 'block';
                        } else {
                            searchDropdown.innerHTML = `
                                <a href="clubs.html?search=${encodeURIComponent(query)}" class="search-result-item justify-content-center py-3 text-center">
                                    <div class="text-secondary small fw-semibold">
                                        <i class="bi bi-search me-1 text-primary"></i> Press Enter to search directory for "<strong>${escapeHtml(query)}</strong>"
                                    </div>
                                </a>
                            `;
                            searchDropdown.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        searchDropdown.innerHTML = `
                            <a href="clubs.html?search=${encodeURIComponent(query)}" class="search-result-item justify-content-center py-3 text-center">
                                <div class="text-primary fw-bold small">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Search directory for "${escapeHtml(query)}"
                                </div>
                            </a>
                        `;
                        searchDropdown.style.display = 'block';
                    });
            }, 180);
        });

        // Close dropdown when clicking outside
        if (searchDropdown) {
            document.addEventListener('click', (e) => {
                if (!heroSearchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });
        }
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
