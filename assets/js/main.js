/**
 * ClubHub Main JavaScript Utilities & Micro-Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // Back to Top FAB Button Handler
    const updateBackToTopState = () => {
        const btn = document.getElementById('backToTopBtn');
        if (!btn) return;
        const scrollY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
        if (scrollY > 200) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }
    };
    window.addEventListener('scroll', updateBackToTopState, { passive: true });
    updateBackToTopState();

    // Official Proposal Form Submission & Student Verification Toggle Handler
    const proposalForm = document.getElementById('proposalForm');
    const proposalAlert = document.getElementById('proposalAlert');
    const isUitStudentToggle = document.getElementById('isUitStudentToggle');
    const uitStudentFieldsPanel = document.getElementById('uitStudentFieldsPanel');

    const studentInputs = [
        document.getElementById('studentIdNumber'),
        document.getElementById('studentIdPhoto'),
        document.getElementById('departmentBranch'),
        document.getElementById('academicYear'),
        document.getElementById('currentSemester')
    ];

    if (isUitStudentToggle && uitStudentFieldsPanel) {
        isUitStudentToggle.addEventListener('change', () => {
            if (isUitStudentToggle.checked) {
                uitStudentFieldsPanel.classList.remove('d-none');
                studentInputs.forEach(el => { if (el) el.setAttribute('required', 'required'); });
            } else {
                uitStudentFieldsPanel.classList.add('d-none');
                studentInputs.forEach(el => { if (el) el.removeAttribute('required'); });
            }
        });
    }

    if (proposalForm) {
        proposalForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData(proposalForm);
            formData.append('is_uit_student', isUitStudentToggle && isUitStudentToggle.checked ? '1' : '0');

            fetch('api/proposals.php', {
                method: 'POST',
                body: formData
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
                    if (isUitStudentToggle) {
                        isUitStudentToggle.checked = false;
                        isUitStudentToggle.dispatchEvent(new Event('change'));
                    }
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

    // Official Contact Secretariat Form Handler
    const contactForm = document.getElementById('contactForm');
    const contactAlert = document.getElementById('contactAlert');
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(contactForm);

            fetch('api/contact.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (contactAlert) {
                    contactAlert.className = `alert ${data.status === 'success' ? 'alert-success' : 'alert-danger'} rounded-3 small`;
                    contactAlert.textContent = data.message;
                    contactAlert.classList.remove('d-none');
                }
                if (data.status === 'success') {
                    contactForm.reset();
                }
            })
            .catch(() => {
                if (contactAlert) {
                    contactAlert.className = 'alert alert-danger rounded-3 small';
                    contactAlert.textContent = 'Failed to send message. Please check your network connection.';
                    contactAlert.classList.remove('d-none');
                }
            });
        });
    }

    // Hero Campus Carousel Desktop Mouse Drag Swipe Handler
    const heroCarousel = document.getElementById('heroCampusCarousel');
    if (heroCarousel) {
        let startX = 0;
        let endX = 0;
        let isDragging = false;

        heroCarousel.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.pageX;
        });

        heroCarousel.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            endX = e.pageX;
        });

        heroCarousel.addEventListener('mouseup', () => {
            if (!isDragging) return;
            isDragging = false;
            const diff = startX - endX;
            if (Math.abs(diff) > 40) {
                if (typeof bootstrap !== 'undefined') {
                    const carouselInstance = bootstrap.Carousel.getOrCreateInstance(heroCarousel);
                    if (diff > 0) {
                        carouselInstance.next();
                    } else {
                        carouselInstance.prev();
                    }
                }
            }
        });
    }
});

// Gallery Fullscreen Lightbox Modal Helper
function openGalleryModal(imgSrc, title, subtitle) {
    const modalImg = document.getElementById('galleryModalImg');
    const modalTitle = document.getElementById('galleryModalTitle');
    const modalSubtitle = document.getElementById('galleryModalSubtitle');
    const modalEl = document.getElementById('galleryLightboxModal');

    if (modalImg) modalImg.src = imgSrc;
    if (modalTitle) modalTitle.textContent = title || 'Campus Highlight';
    if (modalSubtitle) modalSubtitle.textContent = subtitle || 'United Institute of Technology';

    if (modalEl && typeof bootstrap !== 'undefined') {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    }
}
