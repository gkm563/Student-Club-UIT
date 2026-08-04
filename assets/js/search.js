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
                            let html = '';
                            data.results.forEach(item => {
                                const isEvent = item.item_type === 'event';
                                const itemUrl = item.url || (isEvent ? `event-detail.html?id=${encodeURIComponent(item.id)}` : `club-detail.html?id=${encodeURIComponent(item.id)}`);
                                const iconHtml = isEvent 
                                    ? `<i class="bi bi-calendar-event-fill text-danger fs-5 flex-shrink-0"></i>`
                                    : `<i class="bi bi-shield-check text-primary fs-5 flex-shrink-0"></i>`;
                                const badgeHtml = isEvent
                                    ? `<span class="badge bg-danger-subtle text-danger border rounded-pill px-2.5 py-1 small ms-2 flex-shrink-0">Event</span>`
                                    : `<span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small ms-2 flex-shrink-0">${escapeHtml(item.category_name || 'Club')}</span>`;

                                html += `
                                    <a href="${itemUrl}" class="search-result-item">
                                        <div class="d-flex align-items-center gap-2.5 flex-grow-1 min-w-0">
                                            ${iconHtml}
                                            <div class="overflow-hidden min-w-0">
                                                <strong class="d-block text-dark text-truncate" style="font-size:0.9rem;">${escapeHtml(item.name)}</strong>
                                                <small class="text-secondary text-truncate d-block" style="font-size:0.78rem;">${escapeHtml(item.tagline || '')}</small>
                                            </div>
                                        </div>
                                        ${badgeHtml}
                                    </a>
                                `;
                            });
                            
                            html += `
                                <a href="clubs.html?search=${encodeURIComponent(query)}" class="search-result-item bg-light text-center justify-content-center py-2.5">
                                    <div class="text-primary fw-bold small">
                                        <i class="bi bi-arrow-right-circle-fill me-1"></i> View all matches for "<strong>${escapeHtml(query)}</strong>" in Directory →
                                    </div>
                                </a>
                            `;

                            searchDropdown.innerHTML = html;
                            searchDropdown.style.display = 'block';
                        } else {
                            searchDropdown.innerHTML = `
                                <a href="clubs.html?search=${encodeURIComponent(query)}" class="search-result-item text-center justify-content-center py-3">
                                    <div class="text-secondary small fw-semibold">
                                        <i class="bi bi-search me-1 text-primary"></i> Press Enter to search all campus chapters for "<strong>${escapeHtml(query)}</strong>"
                                    </div>
                                </a>
                            `;
                            searchDropdown.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        searchDropdown.innerHTML = `
                            <a href="clubs.html?search=${encodeURIComponent(query)}" class="search-result-item text-center justify-content-center py-3">
                                <div class="text-primary fw-bold small">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Search directory for "${escapeHtml(query)}"
                                </div>
                            </a>
                        `;
                        searchDropdown.style.display = 'block';
                    });
            }, 200);
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
