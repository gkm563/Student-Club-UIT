/**
 * Dynamic Management Committee & Institutional Leadership Renderer (ClubHub UIT)
 * Fetches management hierarchy from /api/committee.php and populates UI cards matching UGI committee aesthetic.
 */

document.addEventListener('DOMContentLoaded', () => {
    const committeeContainer = document.getElementById('managementCommitteeContainer');
    if (!committeeContainer) return;

    if (window.UITSkeletonLoader) {
        committeeContainer.innerHTML = window.UITSkeletonLoader.getMemberCardSkeleton(4);
    }

    fetch('api/committee.php')
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                const members = response.data;
                committeeContainer.innerHTML = members.map(m => {
                    const avatar = m.photo || 'assets/United Logo.webp';
                    const designation = m.designation || '';
                    
                    let badgeClass = 'blue';
                    let borderColor = '#2563eb';
                    let badgeIcon = 'bi-award-fill';

                    if (designation.includes('CHAIRMAN') || designation.includes('PRESIDENT')) {
                        badgeClass = 'gold';
                        borderColor = '#f59e0b';
                    } else if (designation.includes('DEAN') || designation.includes('DSW')) {
                        badgeClass = 'purple';
                        borderColor = '#7c3aed';
                    } else if (designation.includes('STUDENT')) {
                        badgeClass = 'green';
                        borderColor = '#10b981';
                        badgeIcon = 'bi-person-badge-fill';
                    } else if (designation.includes('PRINCIPAL')) {
                        badgeClass = 'blue';
                        borderColor = '#2563eb';
                        badgeIcon = 'bi-bank2';
                    }

                    return `
                        <div class="col">
                            <div class="committee-card-3d text-center p-3 h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="committee-avatar-ring mx-auto mb-3" style="width:110px;height:110px;border-radius:50%;overflow:hidden;border:3px solid ${borderColor};box-shadow:0 8px 20px rgba(0,0,0,0.12);">
                                        <img src="${avatar}" alt="${m.name}" class="w-100 h-100 object-fit-cover" onerror="this.src='assets/United Logo.webp'">
                                    </div>
                                    <span class="committee-role-badge ${badgeClass} mb-2 d-inline-block">
                                        <i class="bi ${badgeIcon} me-1"></i> ${m.designation}
                                    </span>
                                    <h5 class="fw-black fs-6 mb-1 text-dark">${m.name}</h5>
                                    <span class="d-block text-secondary small fw-bold mb-2">${m.role_title}</span>
                                </div>
                                <div>
                                    ${m.bio ? `<p class="small text-muted mb-0 px-2" style="font-size: 0.8rem; line-height: 1.4;">${m.bio}</p>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        })
        .catch(err => console.error('Error fetching management committee:', err));
});
