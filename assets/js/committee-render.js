/**
 * Dynamic Management Committee & Institutional Leadership Renderer (ClubHub UIT)
 * Fetches management hierarchy from /api/committee.php and populates UI cards matching UGI committee aesthetic.
 */

document.addEventListener('DOMContentLoaded', () => {
    const committeeContainer = document.getElementById('managementCommitteeContainer');
    if (!committeeContainer) return;

    fetch('api/committee.php')
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                const members = response.data;
                committeeContainer.innerHTML = members.map(m => {
                    const avatar = m.photo || 'assets/United Logo.webp';
                    return `
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 text-center p-4 transition-hover style-committee-card">
                                <div class="position-relative d-inline-block mx-auto mb-3">
                                    <img src="${avatar}" alt="${m.name}" class="rounded-circle shadow-sm border border-4 border-white" style="width: 140px; height: 140px; object-fit: cover;">
                                </div>
                                <h5 class="fw-bold text-dark mb-1">${m.name}</h5>
                                <div class="badge bg-primary-subtle text-primary fw-bold text-uppercase px-3 py-1-5 rounded-pill mb-2 d-inline-block" style="font-size: 0.75rem; letter-spacing: 1px;">
                                    ${m.designation}
                                </div>
                                <p class="small text-muted mb-0 fw-medium">${m.role_title}</p>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        })
        .catch(err => console.error('Error fetching management committee:', err));
});
