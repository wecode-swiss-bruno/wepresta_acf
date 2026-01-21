/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * WePresta ACF - Sync (Export/Import) JavaScript
 */

(function() {
    'use strict';

    const API_BASE = (window.acfSyncConfig && window.acfSyncConfig.apiBase) || '/modules/wepresta_acf/api';
    const VALIDATE_URL = (window.acfSyncConfig && window.acfSyncConfig.validateUrl) || API_BASE + '/import/validate';
    const IMPORT_URL = (window.acfSyncConfig && window.acfSyncConfig.importUrl) || API_BASE + '/import';
    let selectedFile = null;
    let filePreview = null;

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initDropZone();
        initFileInput();
        initImportButton();
        initRemoveFile();
        initAutoSync();
    }

    /**
     * Initialize drag & drop zone
     */
    function initDropZone() {
        const dropZone = document.getElementById('drop-zone');
        if (!dropZone) return;

        const fileInput = document.getElementById('file-input');

        // Click to select file
        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        // Drag & drop events
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });
    }

    /**
     * Initialize file input
     */
    function initFileInput() {
        const fileInput = document.getElementById('file-input');
        if (!fileInput) return;

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
    }

    /**
     * Handle file selection
     */
    function handleFileSelect(file) {
        // Validate file extension
        if (!file.name.toLowerCase().endsWith('.json')) {
            showError('Invalid file type. Only JSON files are allowed.');
            return;
        }

        selectedFile = file;
        validateAndPreviewFile(file);
    }

    /**
     * Validate file and show preview
     */
    function validateAndPreviewFile(file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            try {
                const content = e.target.result;
                const data = JSON.parse(content);

                // Validate via API
                validateFileViaAPI(file).then(result => {
                    if (result.success) {
                        showFilePreview(file, result.data);
                    } else {
                        showError(result.error || 'Validation failed');
                        selectedFile = null;
                    }
                }).catch(error => {
                    showError('Validation error: ' + error.message);
                    selectedFile = null;
                });
            } catch (error) {
                showError('Invalid JSON file: ' + error.message);
                selectedFile = null;
            }
        };

        reader.onerror = function() {
            showError('Cannot read file');
            selectedFile = null;
        };

        reader.readAsText(file);
    }

    /**
     * Validate file via API
     */
    function validateFileViaAPI(file) {
        const formData = new FormData();
        formData.append('file', file);

        return fetch(VALIDATE_URL, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json());
    }

    /**
     * Show file preview
     */
    function showFilePreview(file, previewData) {
        const dropZone = document.getElementById('drop-zone');
        const filePreview = document.getElementById('file-preview');
        const fileName = document.getElementById('file-name');
        const fileInfo = document.getElementById('file-info');
        const importModeSection = document.getElementById('import-mode-section');
        const importButtonSection = document.getElementById('import-button-section');

        if (!dropZone || !filePreview) return;

        // Update UI
        dropZone.classList.add('has-file');
        fileName.textContent = file.name;
        fileInfo.textContent = previewData.groups_count + ' groups, ' + previewData.fields_count + ' fields';
        
        filePreview.style.display = 'block';
        importModeSection.style.display = 'block';
        importButtonSection.style.display = 'block';
    }

    /**
     * Initialize import button
     */
    function initImportButton() {
        const importBtn = document.getElementById('import-btn');
        if (!importBtn) return;

        importBtn.addEventListener('click', () => {
            if (!selectedFile) {
                showError('Please select a file first');
                return;
            }

            const mode = document.querySelector('input[name="import-mode"]:checked')?.value || 'merge';

            // Confirmation for replace mode
            if (mode === 'replace') {
                if (!confirm('This action will delete all existing groups. Continue?')) {
                    return;
                }
            }

            performImport(selectedFile, mode);
        });
    }

    /**
     * Perform import
     */
    function performImport(file, mode) {
        const importBtn = document.getElementById('import-btn');
        const originalHtml = importBtn.innerHTML;

        // Show loading
        importBtn.disabled = true;
        importBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Importing...';

        const formData = new FormData();
        formData.append('file', file);
        formData.append('mode', mode);

        fetch(IMPORT_URL, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showSuccess(result.message || 'Import completed successfully');
                
                // Reset form
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showError(result.error || 'Import failed');
                importBtn.disabled = false;
                importBtn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            showError('Import error: ' + error.message);
            importBtn.disabled = false;
            importBtn.innerHTML = originalHtml;
        });
    }

    /**
     * Initialize remove file button
     */
    function initRemoveFile() {
        const removeBtn = document.getElementById('remove-file-btn');
        if (!removeBtn) return;

        removeBtn.addEventListener('click', () => {
            resetForm();
        });
    }

    /**
     * Reset form
     */
    function resetForm() {
        selectedFile = null;
        const dropZone = document.getElementById('drop-zone');
        const filePreview = document.getElementById('file-preview');
        const importModeSection = document.getElementById('import-mode-section');
        const importButtonSection = document.getElementById('import-button-section');
        const fileInput = document.getElementById('file-input');

        if (dropZone) dropZone.classList.remove('has-file');
        if (filePreview) filePreview.style.display = 'none';
        if (importModeSection) importModeSection.style.display = 'none';
        if (importButtonSection) importButtonSection.style.display = 'none';
        if (fileInput) fileInput.value = '';
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        if (window.dispatchEvent) {
            window.dispatchEvent(new CustomEvent('wedev-toast', {
                detail: {
                    type: 'success',
                    message: message,
                    duration: 5000
                }
            }));
        } else {
            alert(message);
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        if (window.dispatchEvent) {
            window.dispatchEvent(new CustomEvent('wedev-toast', {
                detail: {
                    type: 'danger',
                    message: message,
                    duration: 8000
                }
            }));
        } else {
            alert('Error: ' + message);
        }
    }

    /**
     * Initialize auto-sync features
     */
    function initAutoSync() {
        const config = window.acfSyncConfig || {};
        
        // Toggle auto-sync - no longer saves immediately, just visual feedback
        // The actual save happens when clicking the Save button in the toolbar
        // No event listeners needed - form submission handled by Save button

        // Export now button
        const exportNowBtn = document.getElementById('btn-export-now');
        if (exportNowBtn) {
            exportNowBtn.addEventListener('click', function() {
                exportNow();
            });
        }

        // View file button
        const viewFileBtn = document.getElementById('btn-view-file');
        if (viewFileBtn && config.fileInfo && config.fileInfo.path) {
            viewFileBtn.addEventListener('click', function() {
                // Open file in new tab (if accessible)
                const fileUrl = config.fileInfo.path.replace(/^.*\/modules\/wepresta_acf/, '/modules/wepresta_acf');
                window.open(fileUrl, '_blank');
            });
        }

        // Import from sync button (notification)
        const importFromSyncBtn = document.getElementById('btn-import-from-sync');
        if (importFromSyncBtn && config.importFromSyncUrl) {
            importFromSyncBtn.addEventListener('click', function() {
                importFromSync();
            });
        }

        // Dismiss notification button
        const dismissBtn = document.getElementById('btn-dismiss-notification');
        if (dismissBtn && config.dismissNotificationUrl) {
            dismissBtn.addEventListener('click', function() {
                dismissNotification();
            });
        }

        // Sync NOW button
        const syncNowBtn = document.getElementById('btn-sync-now');
        if (syncNowBtn && config.syncNowUrl) {
            syncNowBtn.addEventListener('click', function() {
                syncNow();
            });
        }
    }

    // Toggle auto-sync is now handled by form submission via Save button
    // No AJAX call needed - the form is submitted when clicking Save

    /**
     * Export configuration immediately
     */
    function exportNow() {
        const config = window.acfSyncConfig || {};
        const btn = document.getElementById('btn-export-now');
        const originalHtml = btn ? btn.innerHTML : '';

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Exporting...';
        }

        fetch(config.exportNowUrl || '/modules/wepresta_acf/api/auto-sync/export-now', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showSuccess(result.message || 'Configuration exported successfully');
                // Reload page to update file info
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showError(result.error || 'Export failed');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        })
        .catch(error => {
            showError('Export error: ' + error.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    /**
     * Import from sync file
     */
    function importFromSync() {
        const config = window.acfSyncConfig || {};
        const btn = document.getElementById('btn-import-from-sync');
        const banner = document.getElementById('sync-notification-banner');
        
        if (!confirm('Import configuration from sync file? This will merge with existing groups.')) {
            return;
        }

        const originalHtml = btn ? btn.innerHTML : '';

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Importing...';
        }

        fetch(config.importFromSyncUrl || '/modules/wepresta_acf/api/auto-sync/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'merge=1'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showSuccess(result.message || 'Configuration imported successfully');
                // Hide banner and reload
                if (banner) {
                    banner.style.display = 'none';
                }
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showError(result.error || 'Import failed');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        })
        .catch(error => {
            showError('Import error: ' + error.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    /**
     * Dismiss sync notification
     */
    function dismissNotification() {
        const config = window.acfSyncConfig || {};
        const banner = document.getElementById('sync-notification-banner');
        const btn = document.getElementById('btn-dismiss-notification');
        
        const originalHtml = btn ? btn.innerHTML : '';

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        }

        fetch(config.dismissNotificationUrl || '/modules/wepresta_acf/api/auto-sync/dismiss', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Hide banner
                if (banner) {
                    banner.style.display = 'none';
                }
            } else {
                showError(result.error || 'Failed to dismiss notification');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        })
        .catch(error => {
            showError('Error: ' + error.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    /**
     * Sync NOW: intelligently import or export based on sync status
     */
    function syncNow() {
        const config = window.acfSyncConfig || {};
        const btn = document.getElementById('btn-sync-now');
        const originalHtml = btn ? btn.innerHTML : '';

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> ' + 'Syncing...';
        }

        fetch(config.syncNowUrl || '/modules/wepresta_acf/api/auto-sync/sync-now', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showSuccess(result.message || 'Synchronization completed successfully');
                // Reload page to update status
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showError(result.error || 'Synchronization failed');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        })
        .catch(error => {
            showError('Sync error: ' + error.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }
})();
