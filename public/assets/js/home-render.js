/**
 * Home Page Real-Data Renderer (ClubHub UIT)
 * Fetches real events from /api/events.php and populates:
 *  - #homeActivityList  → Recent completed/past events (Latest Activities)
 *  - #homeUpcomingList  → Next upcoming events
 */

document.addEventListener('DOMContentLoaded', () => {
    const activityList  = document.getElementById('homeActivityList');
    const upcomingList  = document.getElementById('homeUpcomingList');

    if (!activityList && !upcomingList) return;

    fetch('/api/events.php')
        .then(res => res.json())
        .then(response => {
            if (response.status !== 'success') {
                showError(activityList, 'Could not load activities.');
                showError(upcomingList,  'Could not load events.');
                return;
            }

            const now     = new Date();
            const allData = response.data || [];

            // Latest Activities = most recent completed / past events (up to 4)
            const pastEvents = allData
                .filter(e => new Date(e.event_date) < now || e.status === 'completed')
                .sort((a, b) => new Date(b.event_date) - new Date(a.event_date))
                .slice(0, 4);

            // Upcoming Events = future events sorted soonest first (up to 4)
            const upcoming = allData
                .filter(e => new Date(e.event_date) >= now && e.status !== 'completed')
                .sort((a, b) => new Date(a.event_date) - new Date(b.event_date))
                .slice(0, 4);

            // ─── Render Latest Activities ─────────────────────────────────────
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
                                <span class="small text-muted text-nowrap">${timeAgo}</span>
                            </div>`;
                    }).join('');
                }
            }

            // ─── Render Upcoming Events ───────────────────────────────────────
            if (upcomingList) {
                if (upcoming.length === 0) {
                    upcomingList.innerHTML = `
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-calendar-check d-block fs-3 mb-2 text-success"></i>
                            No upcoming events scheduled yet. Stay tuned!
                        </div>`;
                } else {
                    upcomingList.innerHTML = upcoming.map(evt => {
                        const d     = new Date(evt.event_date);
                        const day   = String(d.getDate()).padStart(2, '0');
                        const month = d.toLocaleString('default', { month: 'short' }).toUpperCase();
                        const time  = d.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });
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
                                <span class="small text-muted text-nowrap">${time}</span>
                            </div>`;
                    }).join('');
                }
            }
        })
        .catch(() => {
            showError(activityList, 'Failed to load activities.');
            showError(upcomingList,  'Failed to load events.');
        });
});

/* ── Helpers ── */
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
    const diffMs  = Date.now() - date.getTime();
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 60)   return diffMin + 'm ago';
    const diffHr  = Math.floor(diffMin / 60);
    if (diffHr  < 24)   return diffHr  + 'h ago';
    const diffDay = Math.floor(diffHr  / 24);
    if (diffDay < 30)   return diffDay + 'd ago';
    const diffMo  = Math.floor(diffDay / 30);
    return diffMo + 'mo ago';
}
