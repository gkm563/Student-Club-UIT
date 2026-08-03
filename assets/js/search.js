/**
 * USC UIT Universal Search JavaScript
 * Enables debounced live dropdown search on home page, Enter key navigation, search button clicks,
 * and seamless synchronization with clubs.html & events.html directory searches.
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
        heroSearchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                executeSearch();
            }
        });

        // Live input dropdown
        if (searchDropdown) {
            heroSearchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                const query = e.target.value.trim();

                if (query.length < 2) {
                    searchDropdown.style.display = 'none';
                    searchDropdown.innerHTML = '';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`api/search.php?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.results && data.results.length > 0) {
                                let html = '';
                                data.results.forEach(item => {
                                    const itemUrl = `club-detail.html?id=${encodeURIComponent(item.id || item.slug)}`;
                                    html += `
                                        <a href="${itemUrl}" class="search-result-item">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-shield-check text-primary fs-5"></i>
                                                <div>
                                                    <strong class="d-block text-dark">${escapeHtml(item.name)}</strong>
                                                    <small class="text-secondary">${escapeHtml(item.tagline || item.description || '')}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small ms-2">${escapeHtml(item.category_name || 'Club')}</span>
                                        </a>
                                    `;
                                });
                                searchDropdown.innerHTML = html;
                                searchDropdown.style.display = 'block';
                            } else {
                                searchDropdown.innerHTML = `
                                    <a href="clubs.html?search=${encodeURIComponent(query)}" class="search-result-item text-center py-3">
                                        <div class="w-100 text-muted">
                                            <i class="bi bi-search me-1 text-primary"></i> Press Enter to search all chapters for "<strong>${escapeHtml(query)}</strong>"
                                        </div>
                                    </a>
                                `;
                                searchDropdown.style.display = 'block';
                            }
                        })
                        .catch(err => {
                            // Fallback client-side navigation prompt
                            searchDropdown.innerHTML = `
                                <a href="clubs.html?search=${encodeURIComponent(query)}" class="search-result-item text-center py-3">
                                    <div class="w-100 text-primary fw-bold">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Search directory for "${escapeHtml(query)}"
                                    </div>
                                </a>
                            `;
                            searchDropdown.style.display = 'block';
                        });
                }, 250);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!heroSearchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });
        }
    }
});

function escapeHtml(str) {
    return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
