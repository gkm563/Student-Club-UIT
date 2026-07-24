/**
 * Dynamic Homepage Featured Clubs Renderer (ClubHub UIT)
 * Connects index.html featured clubs grid directly to /api/clubs.php
 */

document.addEventListener('DOMContentLoaded', () => {
    const featuredGrid = document.getElementById('featuredClubsGrid');

    if (featuredGrid) {
        fetch('/api/clubs.php?sort=popularity')
            .then(res => res.json())
            .then(response => {
                if (response.status !== 'success' || !response.data || response.data.length === 0) {
                    featuredGrid.innerHTML = `
                        <div class="col-12 text-center py-4">
                            <div class="p-4 bg-white rounded-4 border shadow-sm max-w-md mx-auto">
                                <i class="bi bi-trophy fs-2 text-primary d-block mb-2"></i>
                                <h6 class="fw-bold mb-1">Campus Clubs Registering</h6>
                                <p class="text-secondary small mb-3">Dean Sir is currently setting up active student clubs.</p>
                                <a href="/clubs.html" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold text-white">View Club Directory</a>
                            </div>
                        </div>
                    `;
                    return;
                }

                // Show top 5 featured clubs
                const topClubs = response.data.slice(0, 5);
                featuredGrid.innerHTML = topClubs.map(club => `
                    <div class="col-md-4 col-lg">
                        <a href="/club-detail.html?id=${club.id}" class="text-decoration-none text-body">
                            <div class="featured-club-card">
                                <div class="featured-club-image" style="background-image: url('${escapeHtml(club.cover_image)}');">
                                    <div class="featured-club-badge-icon">
                                        <i class="bi ${escapeHtml(club.category_icon || 'bi-trophy')}"></i>
                                    </div>
                                </div>
                                <div class="featured-club-body">
                                    <h6 class="featured-club-title">${escapeHtml(club.name)}</h6>
                                    <div class="featured-club-subtitle">${escapeHtml(club.tagline || club.short_name)}</div>
                                    <span class="featured-club-tag bg-primary-subtle text-primary">${escapeHtml(club.category_name)}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                `).join('');
            })
            .catch(err => {
                console.error('Error loading featured clubs:', err);
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
