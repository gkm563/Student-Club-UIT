/**
 * CCMS Debounced Search & Filter JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    const heroSearchInput = document.getElementById('heroSearchInput');
    const searchDropdown = document.getElementById('searchResultsDropdown');

    if (heroSearchInput && searchDropdown) {
        let debounceTimer;

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
                                html += `
                                    <a href="club-detail.html?id=${encodeURIComponent(item.id || item.slug)}" class="search-result-item">
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
                            searchDropdown.innerHTML = `<div class="p-3 text-muted text-center"><i class="bi bi-search me-1"></i> No matching clubs found for "${escapeHtml(query)}"</div>`;
                            searchDropdown.style.display = 'block';
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
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

    function escapeHtml(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
