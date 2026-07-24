/**
 * Dynamic Events Renderer (ClubHub UIT)
 * Fetches real events from /api/events.php and renders upcoming & past events
 */

document.addEventListener('DOMContentLoaded', () => {
    const upcomingContainer = document.getElementById('upcomingEventsList');
    const pastContainer = document.getElementById('pastEventsList');

    if (upcomingContainer) {
        fetch('/api/events.php')
            .then(res => res.json())
            .then(response => {
                if (response.status !== 'success') {
                    renderEmptyState(upcomingContainer, 'Failed to load events.');
                    return;
                }

                const upcoming = response.upcoming || [];
                const past = response.past || [];

                // Render Upcoming Events
                if (upcoming.length === 0) {
                    renderEmptyState(upcomingContainer, 'No upcoming events currently scheduled.', 'Club heads can publish new workshops and events from the admin portal.');
                } else {
                    upcomingContainer.innerHTML = upcoming.map(event => renderEventCard(event)).join('');
                }

                // Render Past Events
                if (pastContainer) {
                    if (past.length === 0) {
                        pastContainer.innerHTML = `<div class="text-center py-4 text-muted small">No past events recorded yet.</div>`;
                    } else {
                        pastContainer.innerHTML = past.map(event => renderEventCard(event, true)).join('');
                    }
                }
            })
            .catch(err => {
                console.error('Error fetching events:', err);
                renderEmptyState(upcomingContainer, 'Unable to connect to events database.');
            });
    }
});

function renderEventCard(event, isPast = false) {
    const eventDate = new Date(event.event_date);
    const day = String(eventDate.getDate()).padStart(2, '0');
    const month = eventDate.toLocaleString('default', { month: 'short' }).toUpperCase();
    const timeStr = eventDate.toLocaleString('default', { hour: '2-digit', minute: '2-digit' });

    return `
        <div class="card p-3 p-md-4 border-0 shadow-sm rounded-4 ccms-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-md-4">
                    <img src="${escapeHtml(event.banner || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=600&auto=format&fit=crop')}" class="img-fluid rounded-4" style="height: 140px; width: 100%; object-fit: cover;" alt="${escapeHtml(event.title)}">
                </div>
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="event-date-badge flex-shrink-0">
                            <span class="event-date-num">${day}</span>
                            <span class="event-date-month">${month}</span>
                        </div>
                        <div>
                            <span class="badge ${isPast ? 'bg-secondary-subtle text-secondary' : 'bg-primary-subtle text-primary'} border rounded-pill px-3 py-1 small mb-1">
                                ${isPast ? 'Past Event' : 'Upcoming Event'}
                            </span>
                            <h5 class="fw-bold mb-0 text-dark">${escapeHtml(event.title)}</h5>
                        </div>
                    </div>
                    <p class="text-secondary small mb-3">${escapeHtml(event.description || 'Join us for an exciting campus event.')}</p>
                    
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2 border-top">
                        <div class="small text-muted">
                            <span><i class="bi bi-geo-alt text-danger me-1"></i> ${escapeHtml(event.venue)}</span>
                            <span class="ms-3"><i class="bi bi-clock me-1"></i> ${timeStr}</span>
                            <span class="ms-3 text-primary"><i class="bi bi-flag me-1"></i> ${escapeHtml(event.club_name)}</span>
                        </div>
                        ${!isPast ? `
                            <a href="${escapeHtml(event.registration_link || '/contact.html')}" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-bold text-white text-decoration-none shadow-sm">
                                Register Now
                            </a>
                        ` : `
                            <span class="badge bg-light text-muted border rounded-pill px-3 py-1">Completed</span>
                        `}
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderEmptyState(container, title, subtitle = '') {
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="p-5 bg-white rounded-4 shadow-sm border max-w-md mx-auto">
                <i class="bi bi-calendar-x fs-1 text-primary d-block mb-3"></i>
                <h5 class="fw-bold mb-2">${escapeHtml(title)}</h5>
                <p class="text-secondary small mb-4">${escapeHtml(subtitle)}</p>
                <a href="/admin/login.php" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
                    <i class="bi bi-shield-lock me-1"></i> Admin Portal Login
                </a>
            </div>
        </div>
    `;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
