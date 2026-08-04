/**
 * Home Page Real-Data Renderer (ClubHub UIT)
 * Fetches real events, clubs, leadership & database statistics from APIs and populates elements dynamically.
 */

document.addEventListener('DOMContentLoaded', () => {
    const activityList = document.getElementById('homeActivityList');
    const upcomingList = document.getElementById('homeUpcomingList');
    const featuredGrid = document.getElementById('featuredClubsGrid');
    const leadershipContainer = document.getElementById('homeLeadershipList') || document.getElementById('leadershipRosterContainer');

    const getApiUrl = (endpoint) => `api/${endpoint}`;
    const getPageUrl = (page) => `${page}`;

    // Show initial skeleton loaders
    if (window.UITSkeletonLoader) {
        if (featuredGrid && (!featuredGrid.children.length || featuredGrid.innerHTML.trim() === '')) featuredGrid.innerHTML = window.UITSkeletonLoader.getClubCardSkeleton(6);
        if (upcomingList && (!upcomingList.children.length || upcomingList.innerHTML.trim() === '')) upcomingList.innerHTML = window.UITSkeletonLoader.getEventCardSkeleton(3);
        if (activityList && (!activityList.children.length || activityList.innerHTML.trim() === '')) activityList.innerHTML = window.UITSkeletonLoader.getTimelineSkeleton(4);
        if (leadershipContainer && (!leadershipContainer.children.length || leadershipContainer.innerHTML.trim() === '')) leadershipContainer.innerHTML = window.UITSkeletonLoader.getMemberCardSkeleton(4);
    }

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

    // 1. Fetch & Render Featured Clubs (Sorted by Most Active Recent Events)
    if (featuredGrid) {
        fetch(getApiUrl('clubs.php?sort=active'))
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                    // Filter and sort by highest event count & recent activity
                    const featured = response.data
                        .sort((a, b) => (parseInt(b.event_count || 0) - parseInt(a.event_count || 0)))
                        .slice(0, 6);

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
                        const logoImg = esc(club.logo || 'assets/img/usc-logo.png');
                        const detailLink = getPageUrl(`club-detail.html?id=${encodeURIComponent(club.id)}`);
                        const eventCount = parseInt(club.event_count || 0);

                        return `
                            <div class="col-lg-4 col-md-6 mb-4" onclick="window.location.href='${detailLink}'" style="cursor: pointer;">
                                <div class="featured-club-card-3d">
                                    <div class="featured-club-banner position-relative overflow-hidden" style="height: 140px;">
                                        <img src="${bannerImg}" alt="${esc(club.name)}" class="featured-club-banner-img w-100 h-100 object-fit-cover transition-all" onerror="this.src='https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=800&auto=format&fit=crop'">
                                        <div class="featured-club-overlay"></div>
                                        <div class="featured-club-status-badge">
                                            <span class="pulse-dot-green"></span> MOST ACTIVE CHAPTER
                                        </div>
                                        <div class="featured-club-logo-float">
                                            <img src="${logoImg}" alt="${esc(club.name)}" class="featured-club-logo-img w-100 h-100 object-fit-cover rounded-circle shadow-sm" onerror="this.src='assets/img/usc-logo.png'">
                                        </div>
                                    </div>
                                    <div class="featured-club-content p-4 pt-4 mt-1 flex-grow-1 d-flex flex-column justify-content-between">
                                        <div>
                                            <span class="featured-category-pill mb-2 d-inline-block" style="background:${style.tagBg}; color:${style.tagText}; font-size: 0.75rem; font-weight: 700;">
                                                ${esc(club.category_name || 'Official Campus Club')}
                                            </span>
                                            <h5 class="featured-club-name fw-black text-dark fs-6 mb-2" title="${esc(club.name)}">${esc(club.name)}</h5>
                                            <p class="featured-club-desc text-secondary small line-clamp-2 mb-3">${esc(club.tagline || club.description || 'Official student organization at United Institute of Technology.')}</p>
                                        </div>
                                        
                                        <div class="featured-club-footer d-flex align-items-center justify-content-between pt-3 border-top border-light">
                                            <div class="small fw-bold text-dark">
                                                <i class="bi bi-calendar-event-fill me-1 text-primary"></i><span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">${eventCount} Recent Event${eventCount === 1 ? '' : 's'}</span>
                                            </div>
                                            <a href="${detailLink}" class="featured-club-action-btn" style="background:${style.btnGrad};" onclick="event.stopPropagation();">
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
                    const topLeaders = response.data.slice(0, 4);
                    const badges = [
                        { bg: '#fff1f2', text: '#e11d48' },
                        { bg: '#eff6ff', text: '#2563eb' },
                        { bg: '#f5f3ff', text: '#7c3aed' },
                        { bg: '#ecfdf5', text: '#059669' }
                    ];

                    leadershipContainer.innerHTML = topLeaders.map((ldr, idx) => {
                        const style = badges[idx % badges.length];
                        const avatar = esc(ldr.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop');
                        return `
                            <div class="col-12">
                                <div class="leader-item-3d">
                                    <img src="${avatar}" class="leader-avatar-3d" alt="${esc(ldr.name)}" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop'">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="fw-bold mb-0 text-dark text-truncate" style="font-size: 0.9rem;">${esc(ldr.name)}</h6>
                                        <span class="small text-muted text-truncate d-block" style="font-size: 0.75rem;">${esc(ldr.club_name || ldr.club_short_name || 'Official Chapter')}</span>
                                    </div>
                                    <span class="badge rounded-pill px-2-5 py-1 text-nowrap fw-bold" style="font-size: 0.68rem; background: ${style.bg}; color: ${style.text};">
                                        ${esc(ldr.role_title || 'Leader')}
                                    </span>
                                </div>
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

                const allData    = response.data     || [];
                const apiPast    = response.past      || [];
                const apiUpcoming = response.upcoming || [];
                const now        = new Date();

                // ── Use API arrays; fallback: compute from allData ────────
                let pastEvents = apiPast.length > 0
                    ? apiPast
                    : allData.filter(e => {
                        const d = new Date(e.event_date);
                        return d < now || e.status === 'completed' || e.status === 'past';
                      }).sort((a, b) => new Date(b.event_date) - new Date(a.event_date));

                let upcomingEvt = apiUpcoming.length > 0
                    ? apiUpcoming
                    : allData.filter(e => {
                        const d = new Date(e.event_date);
                        return d >= now && e.status !== 'completed' && e.status !== 'past';
                      }).sort((a, b) => new Date(a.event_date) - new Date(b.event_date));

                // If still no past events, show most recent from all
                if (pastEvents.length === 0 && allData.length > 0) {
                    pastEvents = [...allData].sort((a, b) => new Date(b.event_date) - new Date(a.event_date));
                }

                const SHOW_COUNT = 6;

                // ── Category colour palette ───────────────────────────────
                const catColors = {
                    'technical': { bg: '#eff6ff', text: '#2563eb', dot: '#3b82f6', accent: 'rgba(37,99,235,0.08)' },
                    'cultural':  { bg: '#fff1f2', text: '#e11d48', dot: '#f43f5e', accent: 'rgba(225,29,72,0.08)' },
                    'academic':  { bg: '#f5f3ff', text: '#7c3aed', dot: '#8b5cf6', accent: 'rgba(124,58,237,0.08)' },
                    'social':    { bg: '#ecfdf5', text: '#059669', dot: '#10b981', accent: 'rgba(5,150,105,0.08)' },
                };
                const defaultColor = { bg: '#f8fafc', text: '#475569', dot: '#94a3b8', accent: 'rgba(71,85,105,0.06)' };

                function getCatColor(evt) {
                    const slug = (evt.category_slug || '').toLowerCase();
                    for (const key of Object.keys(catColors)) {
                        if (slug.includes(key)) return catColors[key];
                    }
                    return defaultColor;
                }

                function formatDate(dateStr) {
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
                }

                // ── RECENT ACTIVITIES ─────────────────────────────────────
                if (activityList) {
                    const display = pastEvents.slice(0, SHOW_COUNT);

                    if (display.length === 0) {
                        activityList.innerHTML = `
                            <div class="act-empty-state">
                                <div class="act-empty-icon">
                                    <i class="bi bi-calendar-x"></i>
                                </div>
                                <p class="fw-semibold text-dark mb-1" style="font-size:0.95rem;">No recent activities yet</p>
                                <p class="text-muted small mb-0">Check back soon — events will appear here!</p>
                            </div>`;
                    } else {
                        activityList.innerHTML = display.map((evt, idx) => {
                            const c         = getCatColor(evt);
                            const d         = new Date(evt.event_date);
                            const timeAgo   = getTimeAgo(d);
                            const dateStr   = formatDate(evt.event_date);
                            const img       = esc(evt.banner || evt.cover_image || '');
                            const link      = `event-detail.html?id=${encodeURIComponent(evt.id)}`;
                            const clubName  = esc(evt.club_name || evt.club_short_name || 'Campus Club');
                            const catName   = esc(evt.category_name || 'Event');
                            const isCompleted = evt.status === 'completed';
                            const statusLabel = isCompleted
                                ? `<span class="act-status-chip act-status-done"><i class="bi bi-check-circle-fill"></i> Completed</span>`
                                : `<span class="act-status-chip act-status-past"><i class="bi bi-clock-history"></i> Past</span>`;
                            const thumbHtml = img
                                ? `<img src="${img}" class="act-thumb" alt="${esc(evt.title)}" loading="lazy"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
                                : '';
                            const fallbackIcon = `<div class="act-thumb-icon" style="background:${c.accent};color:${c.text};${img ? 'display:none' : ''}">
                                                      <i class="bi bi-calendar-event"></i>
                                                  </div>`;

                            return `
                            <a href="${link}" class="act-card" style="--act-accent:${c.dot}; --act-bg:${c.accent};" data-aos="fade-up" data-aos-delay="${idx * 40}">
                                <div class="act-card__left">
                                    ${thumbHtml}${fallbackIcon}
                                </div>
                                <div class="act-card__body">
                                    <div class="act-card__meta">
                                        <span class="act-cat-chip" style="background:${c.bg};color:${c.text};">
                                            <span class="act-cat-dot" style="background:${c.dot};"></span>
                                            ${catName}
                                        </span>
                                        ${statusLabel}
                                    </div>
                                    <h6 class="act-card__title">${esc(evt.title)}</h6>
                                    <div class="act-card__info">
                                        <span><i class="bi bi-shield-fill-check" style="color:${c.dot};"></i> ${clubName}</span>
                                        <span><i class="bi bi-calendar3"></i> ${dateStr}</span>
                                    </div>
                                </div>
                                <div class="act-card__right">
                                    <span class="act-time-ago">${timeAgo}</span>
                                    <i class="bi bi-arrow-right act-arrow"></i>
                                </div>
                            </a>`;
                        }).join('');

                        // Footer counter
                        if (pastEvents.length > SHOW_COUNT) {
                            activityList.innerHTML += `
                            <a href="events.html" class="act-view-all">
                                <i class="bi bi-grid-3x3-gap-fill me-2"></i>
                                View all ${pastEvents.length} past activities
                                <i class="bi bi-arrow-right ms-1"></i>
                            </a>`;
                        }
                    }
                }

                // ── UPCOMING EVENTS ───────────────────────────────────────
                if (upcomingList) {
                    let display = upcomingEvt.slice(0, SHOW_COUNT);

                    if (display.length === 0) {
                        display = [
                            { id: null, title: 'UIT Annual Tech & Innovation Summit 2026',   venue: 'UIT Auditorium, Prayagraj',       club_name: 'GDG on Campus UIT',            event_date: '2026-09-15 10:00:00', category_slug: 'technical' },
                            { id: null, title: 'GeeksforGeeks Campus Coding Sprint 2026',    venue: 'Computer Labs 1 & 2, UIT',        club_name: 'GeeksforGeeks Student Chapter', event_date: '2026-10-10 11:00:00', category_slug: 'technical' },
                            { id: null, title: 'RoboWars & Hardware Prototype Showcase',     venue: 'Robotics Workshop Center, UIT',   club_name: 'Robotics & Hardware Club',     event_date: '2026-11-05 09:30:00', category_slug: 'technical' }
                        ];
                    }

                    upcomingList.innerHTML = display.map((evt, idx) => {
                        const c      = getCatColor(evt);
                        const d      = new Date(evt.event_date);
                        const day    = String(d.getDate()).padStart(2, '0');
                        const month  = d.toLocaleString('default', { month: 'short' }).toUpperCase();
                        const time   = d.toLocaleTimeString('default', { hour: '2-digit', minute: '2-digit' });
                        const link   = evt.id ? `event-detail.html?id=${encodeURIComponent(evt.id)}` : 'events.html';
                        const venue  = esc(evt.venue || 'UIT Campus, Prayagraj');
                        const club   = esc(evt.club_name || 'USC UIT');

                        return `
                        <a href="${link}" class="upc-card" style="--upc-accent:${c.dot};" data-aos="fade-up" data-aos-delay="${idx * 40}">
                            <div class="upc-date-badge" style="background:linear-gradient(135deg, ${c.dot}, ${c.text});">
                                <span class="upc-day">${day}</span>
                                <span class="upc-month">${month}</span>
                            </div>
                            <div class="upc-body">
                                <h6 class="upc-title">${esc(evt.title)}</h6>
                                <div class="upc-meta">
                                    <span><i class="bi bi-geo-alt-fill" style="color:${c.dot};"></i> ${venue}</span>
                                    <span><i class="bi bi-patch-check-fill" style="color:${c.dot};"></i> ${club}</span>
                                </div>
                            </div>
                            <div class="upc-time" style="color:${c.text};background:${c.bg};">
                                <i class="bi bi-clock-fill"></i> ${time}
                            </div>
                        </a>`;
                    }).join('');

                    if (upcomingEvt.length > SHOW_COUNT) {
                        upcomingList.innerHTML += `
                        <a href="events.html" class="act-view-all" style="--act-accent:#f43f5e;">
                            <i class="bi bi-calendar-week-fill me-2"></i>
                            View all ${upcomingEvt.length} upcoming events
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>`;
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
