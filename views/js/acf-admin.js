/**
 * ACF Admin - Field Interactions
 * Handles tabs, previews, lightbox, sortable for ACF fields
 */
(function() {
    'use strict';

    // =========================================================================
    // TABS (Works with Bootstrap 4 and 5)
    // =========================================================================
    
    function initTabs() {
        document.querySelectorAll('.nav-link[data-bs-toggle="tab"], .nav-link[data-toggle="tab"]').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var target = this.dataset.bsTarget || this.dataset.target || this.getAttribute('href');
                if (!target) return;

                // Deactivate siblings
                var navList = this.closest('.nav, .nav-tabs');
                if (navList) {
                    navList.querySelectorAll('.nav-link').forEach(function(t) {
                        t.classList.remove('active');
                    });
                }
                this.classList.add('active');

                // Hide sibling panes, show target
                var tabContent = document.querySelector(target)?.closest('.tab-content');
                if (tabContent) {
                    tabContent.querySelectorAll('.tab-pane').forEach(function(p) {
                        p.classList.remove('show', 'active');
                    });
                }
                var pane = document.querySelector(target);
                if (pane) {
                    pane.classList.add('show', 'active');
                }
            });
        });
    }

    // =========================================================================
    // DROPZONE & FILE PREVIEW
    // =========================================================================
    
    function initDropzones() {
        // Drag events
        document.querySelectorAll('.acf-dropzone').forEach(function(dz) {
            if (dz.dataset.init === '1') return;
            dz.dataset.init = '1';

            dz.addEventListener('dragenter', function(e) { e.preventDefault(); dz.classList.add('dragover'); });
            dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.classList.add('dragover'); });
            dz.addEventListener('dragleave', function() { dz.classList.remove('dragover'); });
            dz.addEventListener('drop', function(e) {
                e.preventDefault();
                dz.classList.remove('dragover');
                var input = dz.querySelector('input[type="file"]');
                if (input && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
    }

    function initImagePreview() {
        document.addEventListener('change', function(e) {
            var input = e.target;
            if (!input.matches('input[type="file"]')) return;

            var field = input.closest('.acf-image-field, .acf-field[data-type="image"]');
            if (!field) return;

            var file = input.files && input.files[0];
            if (!file || !file.type.startsWith('image/')) return;

            // Find or create preview
            var preview = field.querySelector('.acf-image-preview, .acf-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'card mb-3 acf-image-preview';
                preview.innerHTML = '<div class="row g-0"><div class="col-auto"><img class="img-thumbnail" style="width:120px;height:120px;object-fit:cover;"></div><div class="col"><div class="card-body py-2"><h6 class="card-title mb-1"></h6><p class="card-text text-muted small mb-2"></p><span class="badge bg-success">New</span></div></div></div>';
                input.closest('.tab-pane, .acf-dropzone')?.parentElement.insertBefore(preview, input.closest('.tab-pane, .acf-dropzone')?.parentElement.firstChild);
            }

            var img = preview.querySelector('img');
            var title = preview.querySelector('.card-title, h6');
            var size = preview.querySelector('.card-text, p');

            var reader = new FileReader();
            reader.onload = function(ev) {
                if (img) img.src = ev.target.result;
                if (title) title.textContent = file.name;
                if (size) size.textContent = formatSize(file.size);
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    // =========================================================================
    // GALLERY
    // =========================================================================
    
    function initGalleryPreview() {
        document.addEventListener('change', function(e) {
            var input = e.target;
            if (!input.classList.contains('acf-gallery-input')) return;

            var field = input.closest('.acf-gallery-field');
            var grid = field?.querySelector('.row, .acf-gallery-grid');
            if (!grid || !input.files) return;

            Array.from(input.files).forEach(function(file, idx) {
                if (!file.type.startsWith('image/')) return;

                var reader = new FileReader();
                reader.onload = function(ev) {
                    var col = document.createElement('div');
                    col.className = 'col acf-gallery-item';
                    col.dataset.index = 'new-' + Date.now() + '-' + idx;
                    col.innerHTML = '<div class="card h-100 border"><div class="position-relative">' +
                        '<img src="' + ev.target.result + '" class="card-img-top" style="height:80px;object-fit:cover;">' +
                        '<span class="badge bg-success position-absolute top-0 start-0 m-1">New</span>' +
                        '<button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 acf-gallery-remove" style="width:20px;height:20px;line-height:1;">' +
                        '<span class="material-icons" style="font-size:14px">close</span></button></div>' +
                        '<div class="card-body p-2"><small class="text-truncate d-block text-muted">' + file.name.substring(0, 15) + '</small>' +
                        '<small class="text-muted">' + formatSize(file.size) + '</small></div></div>';
                    grid.appendChild(col);
                };
                reader.readAsDataURL(file);
            });
        });

        // Remove gallery item
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.acf-gallery-remove');
            if (!btn) return;
            e.preventDefault();
            var item = btn.closest('.acf-gallery-item, .col');
            if (item) {
                item.classList.toggle('acf-marked-delete');
                var deleteInput = item.querySelector('[name*="_delete"]');
                if (deleteInput) deleteInput.value = item.classList.contains('acf-marked-delete') ? '1' : '0';
            }
        });
    }

    // =========================================================================
    // FILES LIST
    // =========================================================================
    
    function initFilesPreview() {
        document.addEventListener('change', function(e) {
            var input = e.target;
            if (!input.classList.contains('acf-files-input')) return;

            var field = input.closest('.acf-files-field');
            var list = field?.querySelector('.list-group');
            if (!list || !input.files) return;

            // Remove empty state
            var empty = list.querySelector('.acf-files-empty');
            if (empty) empty.remove();

            Array.from(input.files).forEach(function(file, idx) {
                var li = document.createElement('li');
                li.className = 'list-group-item acf-files-item';
                li.dataset.index = 'new-' + Date.now() + '-' + idx;
                li.innerHTML = '<div class="d-flex align-items-center gap-2">' +
                    '<span class="text-muted" style="cursor:grab"><span class="material-icons" style="font-size:18px">drag_indicator</span></span>' +
                    '<span class="material-icons text-secondary">' + getMimeIcon(file.type) + '</span>' +
                    '<div class="flex-grow-1 text-truncate"><span class="fw-medium">' + file.name + '</span>' +
                    '<small class="text-muted ms-2">(' + formatSize(file.size) + ')</small></div>' +
                    '<span class="badge bg-success">New</span>' +
                    '<button type="button" class="btn btn-outline-danger btn-sm acf-files-remove">' +
                    '<span class="material-icons" style="font-size:16px">delete</span></button></div>';
                list.appendChild(li);
            });
        });

        // Remove file item
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.acf-files-remove');
            if (!btn) return;
            e.preventDefault();
            var item = btn.closest('.acf-files-item');
            if (item) item.classList.toggle('acf-marked-delete');
        });
    }

    // =========================================================================
    // LIGHTBOX
    // =========================================================================
    
    var lightbox = null;

    function initLightbox() {
        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('[data-lightbox], .acf-image-preview img, .acf-gallery-item img');
            if (!trigger) return;
            e.preventDefault();
            var url = trigger.dataset.lightbox || trigger.src;
            if (url) openLightbox(url);
        });
    }

    function openLightbox(url) {
        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.className = 'acf-lightbox';
            lightbox.style.cssText = 'position:fixed;inset:0;z-index:1060;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.9);opacity:0;visibility:hidden;transition:opacity 0.2s';
            lightbox.innerHTML = '<img style="max-width:90vw;max-height:90vh;object-fit:contain"><button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"></button>';
            lightbox.addEventListener('click', function(ev) {
                if (ev.target === lightbox || ev.target.classList.contains('btn-close')) closeLightbox();
            });
            document.body.appendChild(lightbox);
        }
        lightbox.querySelector('img').src = url;
        lightbox.style.opacity = '1';
        lightbox.style.visibility = 'visible';
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (lightbox) {
            lightbox.style.opacity = '0';
            lightbox.style.visibility = 'hidden';
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLightbox();
    });

    // =========================================================================
    // COPY TO CLIPBOARD
    // =========================================================================
    
    function initClipboard() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-copy]');
            if (!btn) return;
            e.preventDefault();
            navigator.clipboard.writeText(btn.dataset.copy).then(function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '<span class="material-icons text-success" style="font-size:14px">check</span>';
                setTimeout(function() { btn.innerHTML = orig; }, 1500);
            });
        });
    }

    // =========================================================================
    // DELETE TOGGLE
    // =========================================================================
    
    function initDeleteToggle() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-delete-toggle]');
            if (!btn) return;
            e.preventDefault();
            var target = btn.dataset.deleteToggle;
            var preview = document.getElementById(target) || btn.closest('.acf-image-preview, .card');
            if (preview) {
                preview.classList.toggle('acf-marked-delete');
                var flag = preview.querySelector('[name*="_delete"]');
                if (flag) flag.value = preview.classList.contains('acf-marked-delete') ? '1' : '0';
            }
        });
    }

    // =========================================================================
    // SORTABLE (Native HTML5)
    // =========================================================================
    
    function initSortable() {
        document.querySelectorAll('.acf-gallery-field .row, .acf-files-field .list-group').forEach(function(container) {
            if (container.dataset.sortable === '1') return;
            container.dataset.sortable = '1';

            var draggedItem = null;

            container.querySelectorAll('.acf-gallery-item, .acf-files-item').forEach(function(item) {
                item.setAttribute('draggable', 'true');

                item.addEventListener('dragstart', function(e) {
                    draggedItem = item;
                    item.classList.add('opacity-50');
                    e.dataTransfer.effectAllowed = 'move';
                });

                item.addEventListener('dragend', function() {
                    item.classList.remove('opacity-50');
                    draggedItem = null;
                });

                item.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (!draggedItem || draggedItem === item) return;
                    var rect = item.getBoundingClientRect();
                    var mid = rect.top + rect.height / 2;
                    if (e.clientY < mid) {
                        container.insertBefore(draggedItem, item);
                    } else {
                        container.insertBefore(draggedItem, item.nextSibling);
                    }
                });
            });
        });
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================
    
    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return Math.round(bytes / 1024) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function getMimeIcon(mimeType) {
        if (!mimeType) return 'insert_drive_file';
        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType.startsWith('video/')) return 'movie';
        if (mimeType.startsWith('audio/')) return 'audiotrack';
        if (mimeType.includes('pdf')) return 'picture_as_pdf';
        if (mimeType.includes('word') || mimeType.includes('document')) return 'description';
        if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'grid_on';
        return 'insert_drive_file';
    }

    // =========================================================================
    // INIT
    // =========================================================================
    
    function init() {
        initTabs();
        initDropzones();
        initImagePreview();
        initGalleryPreview();
        initFilesPreview();
        initLightbox();
        initClipboard();
        initDeleteToggle();
        initSortable();
    }

    function setupMutationObserver() {
        // Re-init on dynamic content
        if (document.body) {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(m) {
                    if (m.addedNodes.length) {
                        initDropzones();
                        initSortable();
                    }
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            init();
            setupMutationObserver();
        });
    } else {
        init();
        setupMutationObserver();
    }

    // Expose for debugging
    window.AcfAdmin = { init: init, openLightbox: openLightbox, closeLightbox: closeLightbox };
})();

