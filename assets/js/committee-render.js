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
                    const isStudent = (m.designation && m.designation.toUpperCase().includes('STUDENT'));
                    const cardExtraClass = isStudent ? 'committee-card-student' : '';

                    return `
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="committee-card-3d text-center ${cardExtraClass}">
                                <div class="committee-avatar-ring">
                                    <img src="${avatar}" alt="${m.name}" class="committee-avatar-img" onerror="this.src='assets/United Logo.webp'">
                                </div>
                                <h5 class="fw-bold text-dark mb-1" style="font-size: 1.15rem; letter-spacing: -0.3px;">${m.name}</h5>
                                <div class="committee-designation-badge">
                                    <i class="bi bi-award-fill me-1"></i> ${m.designation}
                                </div>
                                <p class="committee-role-tag mb-2">${m.role_title}</p>
                                ${m.bio ? `<p class="small text-muted mb-0 px-2" style="font-size: 0.8rem; line-height: 1.4;">${m.bio}</p>` : ''}
                            </div>
                        </div>
                    `;
                }).join('');
            }
        })
        .catch(err => console.error('Error fetching management committee:', err));
});
