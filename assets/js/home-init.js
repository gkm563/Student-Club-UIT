/**
 * USC UIT Landing Page — Swiper, AOS, Counter Animations
 */

document.addEventListener('DOMContentLoaded', () => {
    // Swiper hero carousel
    if (typeof Swiper !== 'undefined' && document.querySelector('.home-swiper')) {
        new Swiper('.home-swiper', {
            loop: true,
            autoplay: { delay: 4000, disableOnInteraction: false },
            effect: 'fade',
            fadeEffect: { crossFade: true },
            pagination: { el: '.swiper-pagination', clickable: true },
            speed: 800,
        });
    }

    // AOS scroll animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 700,
            easing: 'ease-out-cubic',
            once: true,
            offset: 60,
            disable: window.innerWidth < 768 ? 'phone' : false,
        });
    }

    // Animated stat counters
    const counterEls = document.querySelectorAll('[data-count]');
    if (counterEls.length && 'IntersectionObserver' in window) {
        const animateCounter = (el) => {
            const target = parseInt(el.dataset.count, 10);
            const suffix = el.dataset.suffix || '+';
            const duration = 1400;
            const start = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(eased * target) + suffix;
                if (progress < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        counterEls.forEach(el => observer.observe(el));
    }

    // Smooth scroll for in-page anchor links
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            const id = link.getAttribute('href');
            if (id.length <= 1) return;
            const target = document.querySelector(id);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
