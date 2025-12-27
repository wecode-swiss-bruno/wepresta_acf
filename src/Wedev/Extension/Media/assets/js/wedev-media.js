/**
 * =============================================================================
 * WEDEV Media Extension - Admin
 * =============================================================================
 * Reusable media components: Dropzone, Lightbox, Preview, Video parsing.
 * Uses Bootstrap classes where possible.
 * =============================================================================
 */

window.WedevMedia = (function() {
    'use strict';

    // =========================================================================
    // DROPZONE
    // =========================================================================
    
    /**
     * Initialize a dropzone element.
     * 
     * @param {HTMLElement} element - Dropzone container
     * @param {Object} options - Configuration
     * @param {Function} options.onFile - Callback when file selected
     * @param {string[]} options.accept - Accepted MIME types
     */
    function initDropzone(element, options = {}) {
        if (!element || element.dataset.wedevDropzone === '1') return;
        element.dataset.wedevDropzone = '1';

        const input = element.querySelector('input[type="file"]');
        if (!input) return;

        // Drag events
        element.addEventListener('dragenter', (e) => {
            e.preventDefault();
            element.classList.add('dragover');
        });

        element.addEventListener('dragover', (e) => {
            e.preventDefault();
            element.classList.add('dragover');
        });

        element.addEventListener('dragleave', () => {
            element.classList.remove('dragover');
        });

        element.addEventListener('drop', (e) => {
            e.preventDefault();
            element.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        // File change callback
        if (options.onFile) {
            input.addEventListener('change', () => {
                if (input.files && input.files[0]) {
                    options.onFile(input.files[0], input);
                }
            });
        }
    }

    /**
     * Auto-initialize all dropzones in container.
     */
    function initAllDropzones(container = document) {
        container.querySelectorAll('.acf-dropzone, .wedev-dropzone').forEach((el) => {
            initDropzone(el);
        });
    }

    // =========================================================================
    // LIGHTBOX
    // =========================================================================
    
    let lightboxEl = null;

    /**
     * Open lightbox with image URL.
     * 
     * @param {string} url - Image URL to display
     */
    function openLightbox(url) {
        if (!lightboxEl) {
            lightboxEl = document.createElement('div');
            lightboxEl.className = 'wedev-lightbox';
            lightboxEl.innerHTML = `
                <img src="" alt="">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" aria-label="Close"></button>
            `;
            lightboxEl.addEventListener('click', (e) => {
                if (e.target === lightboxEl || e.target.classList.contains('btn-close')) {
                    closeLightbox();
                }
            });
            document.body.appendChild(lightboxEl);
        }

        lightboxEl.querySelector('img').src = url;
        lightboxEl.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close the lightbox.
     */
    function closeLightbox() {
        if (lightboxEl) {
            lightboxEl.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    // Escape key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightboxEl?.classList.contains('show')) {
            closeLightbox();
        }
    });

    /**
     * Auto-initialize lightbox triggers.
     */
    function initLightbox(container = document) {
        container.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-lightbox]');
            if (trigger) {
                e.preventDefault();
                openLightbox(trigger.dataset.lightbox || trigger.src || trigger.href);
            }
        });
    }

    // =========================================================================
    // IMAGE PREVIEW
    // =========================================================================
    
    /**
     * Preview image from file input.
     * 
     * @param {HTMLInputElement} input - File input element
     * @param {HTMLImageElement|string} target - Target image or selector
     */
    function previewImage(input, target) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];
        if (!file.type.startsWith('image/')) return;

        const imgEl = typeof target === 'string' ? document.querySelector(target) : target;
        if (!imgEl) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            imgEl.src = e.target.result;
            imgEl.closest('.wedev-preview, .acf-preview')?.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    /**
     * Auto-initialize image preview inputs.
     */
    function initImagePreview(container = document) {
        container.addEventListener('change', (e) => {
            const input = e.target;
            if (!input.matches('.wedev-image-input, .acf-image-input')) return;

            const previewId = input.dataset.preview;
            if (previewId) {
                const img = document.getElementById(previewId) || 
                            input.closest('.acf-field, .wedev-field')?.querySelector('img');
                if (img) previewImage(input, img);
            }
        });
    }

    // =========================================================================
    // VIDEO EMBED PARSING
    // =========================================================================
    
    const VIDEO_PATTERNS = {
        youtube: [
            /youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/,
            /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
            /youtu\.be\/([a-zA-Z0-9_-]{11})/,
            /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/
        ],
        vimeo: [
            /vimeo\.com\/(\d+)/,
            /player\.vimeo\.com\/video\/(\d+)/
        ]
    };

    /**
     * Parse video URL and return embed info.
     * 
     * @param {string} url - Video URL
     * @returns {Object|null} { source, id, embedUrl, thumbnail }
     */
    function parseVideoUrl(url) {
        if (!url) return null;

        // YouTube
        for (const pattern of VIDEO_PATTERNS.youtube) {
            const match = url.match(pattern);
            if (match) {
                return {
                    source: 'youtube',
                    id: match[1],
                    embedUrl: `https://www.youtube.com/embed/${match[1]}`,
                    thumbnail: `https://img.youtube.com/vi/${match[1]}/hqdefault.jpg`
                };
            }
        }

        // Vimeo
        for (const pattern of VIDEO_PATTERNS.vimeo) {
            const match = url.match(pattern);
            if (match) {
                return {
                    source: 'vimeo',
                    id: match[1],
                    embedUrl: `https://player.vimeo.com/video/${match[1]}`,
                    thumbnail: null
                };
            }
        }

        // Direct video file
        if (/\.(mp4|webm|ogg|ogv|mov)$/i.test(url)) {
            return {
                source: 'file',
                id: null,
                url: url,
                embedUrl: null
            };
        }

        return null;
    }

    /**
     * Generate embed HTML for video URL.
     * 
     * @param {string} url - Video URL
     * @returns {string} HTML embed code
     */
    function getVideoEmbed(url) {
        const info = parseVideoUrl(url);
        if (!info) return '';

        if (info.source === 'youtube' || info.source === 'vimeo') {
            return `<div class="ratio ratio-16x9"><iframe src="${info.embedUrl}" allowfullscreen></iframe></div>`;
        }

        if (info.source === 'file') {
            return `<video controls class="w-100"><source src="${info.url}"></video>`;
        }

        return '';
    }

    // =========================================================================
    // FILE UTILITIES
    // =========================================================================
    
    /**
     * Format bytes to human-readable size.
     * 
     * @param {number} bytes - Size in bytes
     * @returns {string} Formatted size
     */
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return Math.round(bytes / 1024) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    /**
     * Get Material Icon name for MIME type.
     * 
     * @param {string} mimeType - MIME type
     * @returns {string} Icon name
     */
    function getMimeIcon(mimeType) {
        if (!mimeType) return 'insert_drive_file';
        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType.startsWith('video/')) return 'movie';
        if (mimeType.startsWith('audio/')) return 'audiotrack';
        if (mimeType.includes('pdf')) return 'picture_as_pdf';
        if (mimeType.includes('word') || mimeType.includes('document')) return 'description';
        if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'grid_on';
        if (mimeType.includes('zip') || mimeType.includes('compressed')) return 'folder_zip';
        return 'insert_drive_file';
    }

    // =========================================================================
    // INITIALIZATION
    // =========================================================================
    
    function init(container = document) {
        initAllDropzones(container);
        initLightbox(container);
        initImagePreview(container);
    }

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init());
    } else {
        init();
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================
    
    return {
        // Dropzone
        initDropzone,
        initAllDropzones,
        
        // Lightbox
        openLightbox,
        closeLightbox,
        initLightbox,
        
        // Preview
        previewImage,
        initImagePreview,
        
        // Video
        parseVideoUrl,
        getVideoEmbed,
        
        // Utilities
        formatFileSize,
        getMimeIcon,
        
        // General init
        init
    };
})();

// Attach to Wedev namespace if available
if (typeof window.Wedev !== 'undefined') {
    window.Wedev.Media = window.WedevMedia;
}

console.log('WEDEV Media Extension loaded');

