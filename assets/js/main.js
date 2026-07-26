/**
 * ClubHub Main JavaScript Utilities & Micro-Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // Back to Top FAB Button
    const backToTopBtn = document.getElementById('backToTopBtn');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Official Proposal Form Submission Handler
    const proposalForm = document.getElementById('proposalForm');
    const proposalAlert = document.getElementById('proposalAlert');

    if (proposalForm) {
        proposalForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const payload = {
                proposal_type: document.getElementById('propType').value,
                applicant_name: document.getElementById('propName').value,
                applicant_email: document.getElementById('propEmail').value,
                applicant_phone: document.getElementById('propPhone').value,
                proposed_title: document.getElementById('propTitle').value,
                faculty_mentor: document.getElementById('propMentor').value,
                objective: document.getElementById('propObjective').value
            };

            fetch('api/proposals.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (proposalAlert) {
                    proposalAlert.className = `alert ${data.status === 'success' ? 'alert-success' : 'alert-danger'} rounded-3 small`;
                    proposalAlert.textContent = data.message;
                    proposalAlert.classList.remove('d-none');
                }
                if (data.status === 'success') {
                    proposalForm.reset();
                }
            })
            .catch(() => {
                if (proposalAlert) {
                    proposalAlert.className = 'alert alert-danger rounded-3 small';
                    proposalAlert.textContent = 'Failed to submit proposal. Please check your internet connection.';
                    proposalAlert.classList.remove('d-none');
                }
            });
        });
    }
});
