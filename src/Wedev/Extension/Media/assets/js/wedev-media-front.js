/**
 * WEDEV Media Extension - Front-office (Minimal)
 * Lightbox for product images/galleries.
 */

(function() {
    'use strict';

    let lightbox = null;

    function openLightbox(url) {
        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.className = 'wedev-lightbox';
            lightbox.innerHTML = '<img src="" alt=""><span class="wedev-lightbox-close">&times;</span>';
            lightbox.addEventListener('click', closeLightbox);
            document.body.appendChild(lightbox);
        }
        lightbox.querySelector('img').src = url;
        lightbox.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (lightbox) {
            lightbox.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
    });

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-lightbox]');
        if (trigger) {
            e.preventDefault();
            openLightbox(trigger.dataset.lightbox || trigger.src || trigger.href);
        }
    });

    // Expose API
    window.WedevMediaFront = { openLightbox, closeLightbox };
})();

