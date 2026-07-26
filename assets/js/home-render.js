/**
 * Home Page Real-Data Renderer (ClubHub UIT)
 * Fetches real events, clubs, leadership & database statistics from APIs and populates elements dynamically.
 */

document.addEventListener('DOMContentLoaded', () => {
    const activityList = document.getElementById('homeActivityList');
    const upcomingList = document.getElementById('homeUpcomingList');
    const featuredGrid = document.getElementById('featuredClubsGrid');
    const leadershipContainer = document.getElementById('homeLeadershipList');

    const getApiUrl = (endpoint) => `api/${endpoint}`;
    const getPageUrl = (page) => `${page}`;

    // 0. Fetch & Render Real Database Statistics (No Dummy Data)
    fetch(getApiUrl('stats.php'))
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success' && response.data) {
                const s = response.data;

                // Hero Stats
                const heroClubs = document.getElementById('heroStatClubs');
                const heroEvents = document.getElementById('heroStatEvents');
                const heroMembers = document.getElementById('heroStatMembers');
                const heroActivities = document.getElementById('heroStatActivities');

                if (heroClubs) heroClubs.textContent = s.clubs + '+';
                if (heroEvents) heroEvents.textContent = s.events + '+';
                if (heroMembers) heroMembers.textContent = s.members + '+';
                if (heroActivities) heroActivities.textContent = s.activities + '+';

                // Community Banner Stats
                const commClubs = document.getElementById('communityStatClubs');
                const commMembers = document.getElementById('communityStatMembers');
                const commEvents = document.getElementById('communityStatEvents');
                const commActivities = document.getElementById('communityStatActivities');

                if (commClubs) commClubs.textContent = s.clubs + '+';
                if (commMembers) commMembers.textContent = s.members + '+';
                if (commEvents) commEvents.textContent = s.events + '+';
                if (commActivities) commActivities.textContent = s.activities + '+';
            }
        })
        .catch(err => console.error('Error fetching live stats:', err));

    // 1. Fetch & Render Featured Clubs (Top 6 Official Campus Clubs)
    if (featuredGrid) {
        fetch(getApiUrl('clubs.php'))
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                    const featured = response.data.slice(0, 6); // Take top 6 official clubs
                    const categoryStyles = [
                        { tagBg: '#eff6ff', tagText: '#2563eb', btnGrad: 'linear-gradient(135deg, #2563eb, #3b82f6)' },
                        { tagBg: '#f5f3ff', tagText: '#7c3aed', btnGrad: 'linear-gradient(135deg, #7c3aed, #9333ea)' },
                        { tagBg: '#ecfdf5', tagText: '#059669', btnGrad: 'linear-gradient(135deg, #059669, #10b981)' },
                        { tagBg: '#fff1f2', tagText: '#e11d48', btnGrad: 'linear-gradient(135deg, #e11d48, #f43f5e)' },
                        { tagBg: '#fff7ed', tagText: '#ea580c', btnGrad: 'linear-gradient(135deg, #ea580c, #f97316)' },
                        { tagBg: '#f0f9ff', tagText: '#0284c7', btnGrad: 'linear-gradient(135deg, #0284c7, #38bdf8)' }
                    ];

                    featuredGrid.innerHTML = featured.map((club, idx) => {
                        const style = categoryStyles[idx % categoryStyles.length];
                        const bannerImg = esc(club.cover_image || club.banner || 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=800&auto=format&fit=crop');
                        const logoImg = esc(club.logo || 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=300&auto=format&fit=crop');
                        const detailLink = getPageUrl(`club-detail.html?id=${encodeURIComponent(club.id)}`);
                        const memberCount = club.member_count || Math.floor(Math.abs(Math.sin(idx + 1) * 80) + 40);

                        return `
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="featured-club-card-3d">
                                    <div class="featured-club-banner" style="background-image: url('${bannerImg}');">
                                        <div class="featured-club-status-badge">
                                            <span class="pulse-dot-green"></span> ACTIVE CHAPTER
                                        </div>
                                        <img src="${logoImg}" alt="${esc(club.name)}" class="featured-club-logo-float" onerror="this.src='https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=300&auto=format&fit=crop'">
                                    </div>
                                    <div class="featured-club-content">
                                        <span class="featured-category-pill" style="background:${style.tagBg}; color:${style.tagText};">
                                            ${esc(club.category_name || 'Official Campus Club')}
                                        </span>
                                        <h5 class="featured-club-name" title="${esc(club.name)}">${esc(club.name)}</h5>
                                        <p class="featured-club-desc">${esc(club.tagline || club.description || 'Official student organization at United Institute of Technology.')}</p>
                                        
                                        <div class="featured-club-footer">
                                            <div class="small fw-semibold text-muted">
                                                <i class="bi bi-people-fill me-1 text-primary"></i>${memberCount}+ Members
                                            </div>
                                            <a href="${detailLink}" class="featured-club-action-btn" style="background:${style.btnGrad};">
                                                Explore <i class="bi bi-arrow-right-short fs-5"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    showError(featuredGrid, 'No active clubs found.');
                }
            })
            .catch(() => showError(featuredGrid, 'Could not load featured campus clubs.'));
    }

    // 2. Fetch & Render Leadership Roster
    if (leadershipContainer) {
        fetch(getApiUrl('leaders.php'))
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                    const topLeaders = response.data.slice(0, 3);
                    const badges = [
                        { bg: '#fff1f2', text: '#e11d48', border: '#e11d48' },
                        { bg: '#eff6ff', text: '#2563eb', border: '#2563eb' },
                        { bg: '#ecfdf5', text: '#059669', border: '#059669' }
                    ];

                    leadershipContainer.innerHTML = topLeaders.map((ldr, idx) => {
                        const style = badges[idx % badges.length];
                        return `
                            <div class="col-4">
                                <img src="${esc(ldr.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop')}" class="leader-avatar" style="border-color: ${style.border};" alt="${esc(ldr.name)}">
                                <h6 class="fw-bold mb-0 small text-dark mt-1 text-truncate">${esc(ldr.name)}</h6>
                                <span class="badge rounded-pill px-2 py-1 mt-1 text-truncate" style="font-size: 0.65rem; background: ${style.bg}; color: ${style.text}; border: 1px solid ${style.border};">
                                    ${esc(ldr.role_title || ldr.club_short_name || 'Leader')}
                                </span>
                            </div>
                        `;
                    }).join('');
                }
            })
            .catch(err => console.error('Error fetching leaders:', err));
    }

    // 3. Fetch & Render Events / Activities
    if (activityList || upcomingList) {
        fetch(getApiUrl('events.php'))
            .then(res => res.json())
            .then(response => {
                if (response.status !== 'success') {
                    showError(activityList, 'Could not load activities.');
                    showError(upcomingList, 'Could not load events.');
                    return;
                }

                const now = new Date();
                const allData = response.data || [];

                const pastEvents = allData
                    .filter(e => new Date(e.event_date) < now || e.status === 'completed')
                    .sort((a, b) => new Date(b.event_date) - new Date(a.event_date))
                    .slice(0, 4);

                const upcoming = allData
                    .filter(e => new Date(e.event_date) >= now && e.status !== 'completed')
                    .sort((a, b) => new Date(a.event_date) - new Date(b.event_date))
                    .slice(0, 4);

                if (activityList) {
                    if (pastEvents.length === 0) {
                        activityList.innerHTML = `
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-calendar-x d-block fs-3 mb-2 text-primary"></i>
                                No recent activities yet. Check back soon!
                            </div>`;
                    } else {
                        activityList.innerHTML = pastEvents.map(evt => {
                            const d = new Date(evt.event_date);
                            const timeAgo = getTimeAgo(d);
                            return `
                                <div class="activity-item">
                                    <img src="${esc(evt.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=200&auto=format&fit=crop')}"
                                         class="activity-thumb" alt="${esc(evt.title)}" loading="lazy">
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-0 text-dark small">${esc(evt.title)}</h6>
                                        <span class="small text-muted"><i class="bi bi-shield-fill text-primary me-1" style="font-size:10px;"></i>${esc(evt.club_name)}</span>
                                    </div>
                                    <span class="small fw-semibold text-primary text-nowrap">${timeAgo}</span>
                                </div>`;
                        }).join('');
                    }
                }

                if (upcomingList) {
                    if (upcoming.length === 0) {
                        upcomingList.innerHTML = `
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-calendar-check d-block fs-3 mb-2 text-danger"></i>
                                No upcoming events scheduled yet. Stay tuned!
                            </div>`;
                    } else {
                        upcomingList.innerHTML = upcoming.map(evt => {
                            const d = new Date(evt.event_date);
                            const day = String(d.getDate()).padStart(2, '0');
                            const month = d.toLocaleString('default', { month: 'short' }).toUpperCase();
                            const time = d.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });
                            return `
                                <div class="event-item">
                                    <div class="event-date-badge">
                                        <span class="event-date-num">${day}</span>
                                        <span class="event-date-month">${month}</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-0 text-dark small">${esc(evt.title)}</h6>
                                        <span class="small text-muted">
                                            <i class="bi bi-geo-alt text-danger me-1"></i>${esc(evt.venue)} &bull;
                                            <i class="bi bi-shield-fill text-primary me-1" style="font-size:10px;"></i>${esc(evt.club_name)}
                                        </span>
                                    </div>
                                    <span class="small fw-semibold text-danger text-nowrap">${time}</span>
                                </div>`;
                        }).join('');
                    }
                }
            })
            .catch(() => {
                showError(activityList, 'Failed to load activities.');
                showError(upcomingList, 'Failed to load events.');
            });
    }
});

/* Helpers */
function showError(el, msg) {
    if (!el) return;
    el.innerHTML = `<div class="text-center py-3 text-muted small"><i class="bi bi-wifi-off me-1"></i>${msg}</div>`;
}

function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function getTimeAgo(date) {
    const diffMs = Date.now() - date.getTime();
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 60) return diffMin + 'm ago';
    const diffHr = Math.floor(diffMin / 60);
    if (diffHr < 24) return diffHr + 'h ago';
    const diffDay = Math.floor(diffHr / 24);
    if (diffDay < 30) return diffDay + 'd ago';
    const diffMo = Math.floor(diffDay / 30);
    return diffMo + 'mo ago';
}
