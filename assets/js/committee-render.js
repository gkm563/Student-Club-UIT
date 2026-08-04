/**
 * Dynamic Management Committee & Institutional Leadership Renderer (ClubHub UIT)
 * Fetches management hierarchy from /api/committee.php and populates UI cards matching UGI committee aesthetic.
 */

function renderCommittee() {
    const committeeContainer = document.getElementById('managementCommitteeContainer');
    if (!committeeContainer) return;

    const defaultMembers = [
        { name: 'Prof. (Dr.) Sanjay Srivastava', designation: 'PRINCIPAL', role_title: 'United Institute of Technology', photo: 'assets/img/committee/sanjay-srivastava.webp', bio: 'UIT Institutional Head' },
        { name: 'Dr. Manas Pandey', designation: 'DEAN STUDENT WELFARE (DSW)', role_title: 'Student Club Affairs, UIT', photo: 'assets/img/committee/manas-pandey.jpg', bio: 'Student Club Lead' },
        { name: 'Dr. Ankit Gupta', designation: 'FACULTY COORDINATOR', role_title: 'Faculty Lead, Cultural Club', photo: 'assets/img/committee/ankit-gupta.jpg', bio: 'UIT Faculty Lead' },
        { name: 'Arya Keshari', designation: 'STUDENT PRESIDENT', role_title: 'Student Club President', photo: 'assets/img/committee/arya-keshari.jpg', bio: 'USC UIT Council Lead' }
    ];

    fetch('api/committee.php')
        .then(res => res.json())
        .then(response => {
            let members = defaultMembers;
            if (response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                members = response.data;
            }
            populateCommitteeUI(committeeContainer, members);
        })
        .catch(err => {
            console.error('Error fetching management committee:', err);
            populateCommitteeUI(committeeContainer, defaultMembers);
        });
}

function populateCommitteeUI(container, members) {
    container.innerHTML = members.map(m => {
        const avatar = m.photo || 'assets/United Logo.webp';
        const designation = (m.designation || '').toUpperCase();
        
        let badgeClass = 'blue';
        let borderColor = '#2563eb';
        let badgeIcon = 'bi-award-fill';
        let subBadgeStyle = 'background:#eff6ff;color:#2563eb;border-color:rgba(37,99,235,0.3)!important;';

        if (designation.includes('CHAIRMAN') || designation.includes('PRESIDENT')) {
            badgeClass = 'gold';
            borderColor = '#f59e0b';
            subBadgeStyle = 'background:#fffbeb;color:#d97706;border-color:rgba(217,119,6,0.3)!important;';
        } else if (designation.includes('DEAN') || designation.includes('DSW')) {
            badgeClass = 'purple';
            borderColor = '#7c3aed';
            subBadgeStyle = 'background:#f5f3ff;color:#7c3aed;border-color:rgba(124,58,237,0.3)!important;';
        } else if (designation.includes('STUDENT')) {
            badgeClass = 'green';
            borderColor = '#10b981';
            badgeIcon = 'bi-person-badge-fill';
            subBadgeStyle = 'background:#ecfdf5;color:#059669;border-color:rgba(5,150,105,0.3)!important;';
        } else if (designation.includes('PRINCIPAL')) {
            badgeClass = 'blue';
            borderColor = '#2563eb';
            badgeIcon = 'bi-bank2';
        } else if (designation.includes('FACULTY')) {
            badgeClass = 'cyan';
            borderColor = '#0891b2';
            badgeIcon = 'bi-person-workspace';
            subBadgeStyle = 'background:#ecfeff;color:#0891b2;border-color:rgba(8,145,178,0.3)!important;';
        }

        return `
            <div class="col">
                <div class="committee-card-3d text-center p-3 h-100 bg-white rounded-4 shadow-sm border d-flex flex-column justify-content-between">
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
                        ${m.bio ? `<span class="badge rounded-pill px-3 py-1 small fw-bold" style="${subBadgeStyle}">${m.bio}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCommittee);
} else {
    renderCommittee();
}
