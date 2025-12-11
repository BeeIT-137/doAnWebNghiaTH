// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    /* =========================
     * MOBILE DRAWER MENU
     * =======================*/
    const drawer = document.querySelector('.js-mobile-drawer');
    const openMenuBtn = document.querySelector('.js-menu-toggle');
    const closeDrawerButtons = document.querySelectorAll('.js-close-drawer');

    if (drawer && openMenuBtn) {
        openMenuBtn.addEventListener('click', () => {
            drawer.classList.add('is-open');
            openMenuBtn.style.opacity = '0.5';
        });

        closeDrawerButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                drawer.classList.remove('is-open');
                openMenuBtn.style.opacity = '1';
            });
        });

        // Đóng menu khi click overlay
        const overlay = drawer.querySelector('.mobile-drawer__overlay');
        if (overlay) {
            overlay.addEventListener('click', () => {
                drawer.classList.remove('is-open');
                openMenuBtn.style.opacity = '1';
            });
        }
    }

    /* =========================
     * MINI-CART OVERLAY
     * =======================*/
    const miniCart = document.querySelector('.js-mini-cart');
    const openMiniCartBtn = document.querySelector('.js-open-mini-cart');
    const closeMiniCartButtons = document.querySelectorAll('.js-close-mini-cart');

    if (miniCart && openMiniCartBtn) {
        openMiniCartBtn.addEventListener('click', () => {
            miniCart.classList.add('is-open');
        });

        closeMiniCartButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                miniCart.classList.remove('is-open');
            });
        });

        const miniOverlay = miniCart.querySelector('.mini-cart__overlay');
        if (miniOverlay) {
            miniOverlay.addEventListener('click', () => {
                miniCart.classList.remove('is-open');
            });
        }
    }

    /* =========================
     * HERO SLIDER (AUTO + DOTS)
     * =======================*/
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-slider__dot');
    let currentSlide = 0;
    let sliderTimer;

    function showSlide(index) {
        if (!slides.length) return;
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
        });
        currentSlide = index;
    }

    function nextSlide() {
        const next = (currentSlide + 1) % slides.length;
        showSlide(next);
    }

    if (slides.length) {
        // init
        showSlide(0);

        // dot click
        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                showSlide(i);
                // reset timer
                if (sliderTimer) {
                    clearInterval(sliderTimer);
                }
                sliderTimer = setInterval(nextSlide, 6000);
            });
        });

        sliderTimer = setInterval(nextSlide, 6000);
    }

    /* =========================
     * SIMPLE FORM VALIDATION
     * (dùng cho form có class .js-validate)
     * =======================*/
    const forms = document.querySelectorAll('form.js-validate');

    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            let valid = true;
            const requiredInputs = form.querySelectorAll('[data-required="true"]');

            requiredInputs.forEach(input => {
                const value = input.value.trim();
                if (!value) {
                    valid = false;
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ các trường bắt buộc.');
            }
        });
    });

    /* =========================
     * ESC TO CLOSE OVERLAYS
     * =======================*/
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' || e.key === 'Esc') {
            if (drawer && drawer.classList.contains('is-open')) {
                drawer.classList.remove('is-open');
                if (openMenuBtn) openMenuBtn.style.opacity = '1';
            }
            if (miniCart && miniCart.classList.contains('is-open')) {
                miniCart.classList.remove('is-open');
            }
        }
    });
});
