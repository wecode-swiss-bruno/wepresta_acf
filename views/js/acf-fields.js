/**
 * ACF Fields - Dynamic Field Initialization
 *
 * This file handles all dynamic ACF field functionality:
 * - List fields
 * - Repeater fields
 * - Relation fields (AJAX search)
 * - Rich text (TinyMCE) fields
 * - Translation tabs
 * - AJAX save
 *
 * Works with both Smarty (entity-fields.tpl) and Twig (acf_entity_fields.html.twig) templates.
 */
(function() {
    'use strict';

    // =========================================================================
    // INITIALIZATION
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function() {
        injectAcfContainersFromDataAttributes();
        initAcfDynamicFields();
        initAcfTabs();
        initVideoDeleteToggle();
        initAcfAjaxSave();
    });

    // Also init when content might be loaded dynamically
    if (document.readyState !== 'loading') {
        injectAcfContainersFromDataAttributes();
        initAcfDynamicFields();
        initAcfTabs();
        initVideoDeleteToggle();
        initAcfAjaxSave();
    }

    // =========================================================================
    // DELETE TOGGLE FOR VIDEO FIELDS
    // =========================================================================

    function initVideoDeleteToggle() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-delete-toggle]');
            if (!btn) return;

            // Only handle delete toggles inside video fields (avoid hijacking image delete toggles)
            var videoField = btn.closest('.acf-video-field');
            if (!videoField) return;

            var targetId = btn.dataset.deleteToggle;
            if (!targetId) return;

            e.preventDefault();

            // Hide the preview if it exists (for existing videos)
            var preview = document.getElementById(targetId);
            if (preview) {
                preview.style.display = 'none';
            }

            // Reset the hidden input to null
            var hiddenInput = videoField.querySelector('.acf-video-value');
            if (hiddenInput) {
                hiddenInput.value = 'null';
            }

            // Clear any visible inputs in the tabs
            var inputs = videoField.querySelectorAll('input[type="url"], input[type="file"]');
            inputs.forEach(function(input) {
                input.value = '';
            });

            // Reset the delete flag if it exists (for existing videos)
            var deleteFlag = videoField.querySelector('[name*="_delete"]');
            if (deleteFlag) {
                deleteFlag.value = '1'; // Mark for deletion on save
            }

            // Show the input tabs (they are always rendered now)
            var inputTabs = videoField.querySelector('.acf-video-input-tabs');
            if (inputTabs) {
                inputTabs.style.display = 'block';
            }
        });
    }

    // =========================================================================
    // INJECT ACF HTML FROM HIDDEN INPUTS (Symfony Forms)
    // =========================================================================
    function injectAcfContainersFromDataAttributes() {
        // Find all hidden inputs with ACF HTML data
        document.querySelectorAll('input.acf-container-data[data-acf-html]').forEach(function(input) {
            var base64Html = input.getAttribute('data-acf-html');
            if (!base64Html) return;

            try {
                // Decode base64 HTML
                var html = atob(base64Html);
                if (!html || html.trim() === '') return;

                // Create container div for ACF content
                var container = document.createElement('div');
                container.className = 'acf-injected-container';
                container.innerHTML = html;

                // Insert after the hidden input's parent form-group (or after the input itself)
                var formGroup = input.closest('.form-group');
                if (formGroup) {
                    // Insert after the form-group
                    formGroup.parentNode.insertBefore(container, formGroup.nextSibling);
                } else {
                    // Insert after the input
                    input.parentNode.insertBefore(container, input.nextSibling);
                }

                // Mark input as processed
                input.removeAttribute('data-acf-html');
                input.setAttribute('data-acf-injected', 'true');
            } catch (e) {
                console.error('[ACF] Failed to decode ACF HTML:', e);
            }
        });
    }

    // =========================================================================
    // TAB INITIALIZATION
    // =========================================================================
    function initAcfTabs() {
        // Ensure active translation panes are visible
        document.querySelectorAll('.translations.tabbable').forEach(function(container) {
            var activeTab = container.querySelector('.translationsLocales .nav-link.active');
            if (activeTab) {
                var target = activeTab.dataset.target;
                if (target) {
                    var pane = container.querySelector(target);
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                }
            }
        });

        // Handle translation tab clicks
        document.querySelectorAll('.translations.tabbable .translationsLocales .nav-link').forEach(function(tabLink) {
            tabLink.addEventListener('click', function(e) {
                e.preventDefault();
                var container = this.closest('.translations.tabbable');
                var target = this.dataset.target;
                if (!container || !target) return;

                // Deactivate all tabs
                container.querySelectorAll('.translationsLocales .nav-link').forEach(function(t) { t.classList.remove('active'); });
                container.querySelectorAll('.translationsFields .tab-pane').forEach(function(p) { p.classList.remove('show', 'active'); });

                // Activate clicked tab
                this.classList.add('active');
                var pane = container.querySelector(target);
                if (pane) pane.classList.add('show', 'active');
            });
        });

        // Initialize field-level tabs (image, video, etc.)
        document.querySelectorAll('.acf-image-field, .acf-video-field').forEach(function(field) {
            var navTabs = field.querySelector('.nav-tabs');
            var tabContent = field.querySelector('.tab-content');
            if (!navTabs || !tabContent) return;

            // Find the active tab button
            var activeBtn = navTabs.querySelector('.nav-link.active');
            if (activeBtn) {
                var target = activeBtn.dataset.target || activeBtn.dataset.bsTarget;
                if (target) {
                    var pane = tabContent.querySelector(target);
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                }
            } else {
                // No active button, activate the first one
                var firstBtn = navTabs.querySelector('.nav-link');
                var firstPane = tabContent.querySelector('.tab-pane');
                if (firstBtn && firstPane) {
                    firstBtn.classList.add('active');
                    firstPane.classList.add('show', 'active');
                }
            }

            // Add click handlers for Bootstrap 4 compatibility
            navTabs.querySelectorAll('.nav-link').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = this.dataset.target || this.dataset.bsTarget;
                    if (!target) return;

                    // Deactivate all tabs
                    navTabs.querySelectorAll('.nav-link').forEach(function(t) { t.classList.remove('active'); });
                    tabContent.querySelectorAll('.tab-pane').forEach(function(p) { p.classList.remove('show', 'active'); });

                    // Activate clicked tab
                    this.classList.add('active');
                    var pane = tabContent.querySelector(target);
                    if (pane) pane.classList.add('show', 'active');
                });
            });
        });
    }

    function initAcfDynamicFields() {
        var container = document.getElementById('acf-entity-fields');
        if (!container) return;

        // List field initialization
        initListFields(container);

        // Repeater fields initialization
        initRepeaterFields(container);

        // Initialize relation fields
        initRelationFields(container);

        // Initialize TinyMCE for richtext fields
        initRichtextFields(container);

        // Initialize ps-switch (boolean fields) status badges
        initPsSwitchStatus(container);

        // Sync TinyMCE content before form submission
        syncTinyMCEOnSubmit();

        console.debug('[ACF] Dynamic fields initialized for entity');
    }

    // =========================================================================
    // PS-SWITCH STATUS BADGE UPDATES
    // =========================================================================
    function initPsSwitchStatus(container) {
        container.querySelectorAll('.ps-switch').forEach(function(psSwitch) {
            // Find status badge (support older browsers, no optional chaining)
            var flexContainer = psSwitch.closest('.d-flex');
            if (!flexContainer) return;
            
            var statusContainer = flexContainer.querySelector('.acf-boolean-status');
            if (!statusContainer) return;
            
            var statusBadge = statusContainer.querySelector('.badge');
            if (!statusBadge) return;

            var radios = psSwitch.querySelectorAll('input[type="radio"]');
            
            // Get labels with fallback
            var trueLabelEl = psSwitch.querySelector('input[value="1"] + label');
            var falseLabelEl = psSwitch.querySelector('input[value="0"] + label');
            var trueLabel = trueLabelEl ? trueLabelEl.textContent.trim() : 'Oui';
            var falseLabel = falseLabelEl ? falseLabelEl.textContent.trim() : 'Non';

            // Check data-initial-value attribute for fallback
            var initialValue = psSwitch.getAttribute('data-initial-value');

            function updateStatus() {
                var checked = psSwitch.querySelector('input[type="radio"]:checked');

                // If no radio is checked, use data-initial-value to set one
                if (!checked && initialValue !== null) {
                    var targetRadio = psSwitch.querySelector('input[type="radio"][value="' + initialValue + '"]');
                    if (targetRadio) {
                        targetRadio.checked = true;
                        checked = targetRadio;
                    }
                }

                // Still no checked? Default to OFF (value=0)
                if (!checked) {
                    var offRadio = psSwitch.querySelector('input[type="radio"][value="0"]');
                    if (offRadio) {
                        offRadio.checked = true;
                        checked = offRadio;
                    }
                }

                var isTrue = checked && checked.value === '1';
                statusBadge.textContent = isTrue ? trueLabel : falseLabel;
                statusBadge.className = 'badge badge-' + (isTrue ? 'success' : 'secondary');
            }

            // Initial update
            updateStatus();

            // Update on change
            radios.forEach(function(radio) {
                radio.addEventListener('change', updateStatus);
            });
        });
    }

    // =========================================================================
    // SYNC TinyMCE BEFORE FORM SUBMISSION
    // =========================================================================
    function syncTinyMCEOnSubmit() {
        // Find the entity form (could be product, order, customer, etc.)
        var form = document.querySelector('form[name="product"]') ||
                   document.querySelector('#form_product') ||
                   document.querySelector('form[name="order"]') ||
                   document.querySelector('form[name="customer"]') ||
                   document.querySelector('form');

        if (form) {
            form.addEventListener('submit', function() {
                syncAllTinyMCE();
            });
        }

        // Also sync on any button click that might trigger form submission
        document.querySelectorAll('button[type="submit"], input[type="submit"], .btn-action-save, .js-btn-save').forEach(function(btn) {
            btn.addEventListener('click', function() {
                syncAllTinyMCE();
            });
        });
    }

    function syncAllTinyMCE() {
        var mce = typeof tinymce !== 'undefined' ? tinymce : (typeof tinyMCE !== 'undefined' ? tinyMCE : null);
        if (!mce) return;

        try {
            mce.triggerSave();
            console.debug('[ACF] TinyMCE content synced to textareas');
        } catch (e) {
            console.warn('[ACF] Failed to sync TinyMCE:', e);
        }
    }

    // =========================================================================
    // RICHTEXT/TinyMCE INITIALIZATION
    // =========================================================================
    function initRichtextFields(container) {
        var textareas = container.querySelectorAll('textarea.autoload_rte');
        if (textareas.length === 0) return;

        if (typeof tinyMCE === 'undefined' && typeof tinymce === 'undefined') {
            console.warn('[ACF] TinyMCE not loaded. Rich text fields will use plain textarea.');
            return;
        }

        var mce = typeof tinymce !== 'undefined' ? tinymce : tinyMCE;

        textareas.forEach(function(textarea) {
            if (textarea.dataset.tinymceInit === '1') return;

            var textareaId = textarea.id;
            if (!textareaId) {
                textareaId = 'acf_rte_' + Math.random().toString(36).substr(2, 9);
                textarea.id = textareaId;
            }

            var existing = mce.get(textareaId);
            if (existing) {
                existing.remove();
            }

            try {
                mce.init({
                    selector: '#' + textareaId,
                    height: 300,
                    menubar: false,
                    plugins: 'link image table lists paste code',
                    toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
                    relative_urls: false,
                    convert_urls: false,
                    forced_root_block: false,
                    force_br_newlines: false,
                    force_p_newlines: false,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
                    valid_elements: '*[*]',
                    extended_valid_elements: '*[*]',
                    cleanup: false,
                    verify_html: false,
                    apply_source_formatting: false,
                    remove_trailing_brs: false,
                    setup: function(editor) {
                        editor.on('change', function() {
                            editor.save();
                        });
                        editor.on('GetContent', function(e) {
                            e.content = editor.getBody().innerHTML;
                        });
                    }
                });
                textarea.dataset.tinymceInit = '1';
            } catch (e) {
                console.warn('[ACF] TinyMCE init failed:', e);
            }
        });
    }

    // =========================================================================
    // REPEATER FIELD HANDLING
    // =========================================================================
    function initRepeaterFields(container) {
        var repeaterFields = container.querySelectorAll('.acf-repeater-field');
        repeaterFields.forEach(function(repeater) {
            if (repeater.dataset.init === '1') return;
            repeater.dataset.init = '1';

            var slug = repeater.dataset.slug;
            var displayMode = repeater.dataset.displayMode || 'table';
            var minRows = parseInt(repeater.dataset.min) || 0;
            var maxRows = parseInt(repeater.dataset.max) || 0;
            var buttonLabel = repeater.dataset.buttonLabel || 'Add Row';
            var subfields = [];
            var jsTemplates = {};

            try { subfields = JSON.parse(repeater.dataset.subfields || '[]'); } catch (e) { console.error('[ACF Repeater] Error parsing subfields:', e); }
            try { jsTemplates = JSON.parse(repeater.dataset.jsTemplates || '{}'); } catch (e) {}

            var rowsContainer = repeater.querySelector('.acf-repeater-rows');
            var valueInput = repeater.querySelector('.acf-repeater-value');
            var addButton = repeater.querySelector('.acf-repeater-add');

            function generateRowId() {
                return 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }

            function collectRowsData() {
                var rows = rowsContainer.querySelectorAll('.acf-repeater-row');
                var data = [];
                rows.forEach(function(row, index) {
                    var rowData = { row_id: row.dataset.rowId || generateRowId(), values: {} };
                    subfields.forEach(function(sf) {
                        // Check if subfield is translatable (has translations tabbable container)
                        var translatableContainer = row.querySelector('[data-subfield-container="' + sf.slug + '"] .translations.tabbable');
                        
                        if (translatableContainer) {
                            // Translatable subfield - collect values per language
                            var langValues = {};
                            var panes = translatableContainer.querySelectorAll('.tab-pane');
                            
                            panes.forEach(function(pane) {
                                // Extract langId from class name like "translationsFields-acf_repeater_field_slug_row_id_subfield_slug_langId"
                                var classNames = pane.className.split(' ');
                                var langId = null;
                                
                                for (var i = 0; i < classNames.length; i++) {
                                    var className = classNames[i];
                                    // Match pattern: translationsFields-acf_repeater_*_*_*_langId
                                    var match = className.match(/^translationsFields-acf_repeater_.+_\d+_(\d+)$/);
                                    if (match && match[1]) {
                                        langId = match[1];
                                        break;
                                    }
                                }
                                
                                if (!langId) {
                                    // Try alternative pattern: look for data-lang-id attribute or data-lang-id in input
                                    var inputWithLang = pane.querySelector('[data-lang-id]');
                                    if (inputWithLang) {
                                        langId = inputWithLang.dataset.langId;
                                    }
                                }
                                
                                if (!langId) return;
                                
                                // Find input in this pane
                                var input = pane.querySelector('input, textarea, select');
                                if (input) {
                                    var val = null;
                                    
                                    if (input.type === 'checkbox') {
                                        val = input.checked ? '1' : '0';
                                    } else if (input.type === 'radio') {
                                        if (input.checked) {
                                            val = input.value || '';
                                        }
                                    } else {
                                        val = input.value || '';
                                    }
                                    
                                    // Only add if value is not empty (or allow empty for translatable fields to support clearing)
                                    if (val !== null && val !== undefined && val !== '') {
                                        langValues[langId] = val;
                                    }
                                }
                            });
                            
                            // Only add translatable field if it has at least one language value
                            if (Object.keys(langValues).length > 0) {
                                rowData.values[sf.slug] = langValues;
                            }
                        } else {
                            // Non-translatable subfield - collect single value
                            var input = row.querySelector('[data-subfield="' + sf.slug + '"]');
                            if (input) {
                                if (input.type === 'checkbox') {
                                    rowData.values[sf.slug] = input.checked ? '1' : '0';
                                } else if (input.type === 'radio') {
                                    var radio = row.querySelector('[data-subfield="' + sf.slug + '"]:checked');
                                    if (radio) {
                                        rowData.values[sf.slug] = radio.value || '';
                                    }
                                } else {
                                    rowData.values[sf.slug] = input.value || '';
                                }
                            }
                        }
                    });
                    data.push(rowData);
                });
                return data;
            }

            function updateValue() {
                var data = collectRowsData();
                valueInput.value = JSON.stringify(data);
            }

            function canAddRow() {
                if (maxRows <= 0) return true;
                return rowsContainer.querySelectorAll('.acf-repeater-row').length < maxRows;
            }

            // Helper function to generate HTML for a translatable subfield
            function generateTranslatableSubfieldHtml(subfield, rowId, repeaterSlug, languages, defaultLangId, size) {
                size = size || 'sm';
                var sizeClass = size === 'sm' ? 'form-control-sm' : '';
                var translationsId = 'acf_repeater_' + repeaterSlug + '_' + rowId + '_' + subfield.slug;
                
                var html = '<div class="acf-repeater-subfield-translatable" data-subfield-slug="' + subfield.slug + '" data-row-id="' + rowId + '">';
                html += '<div class="translations tabbable acf-repeater-translations" id="' + translationsId + '" tabindex="1">';
                html += '<ul class="translationsLocales nav nav-pills" style="font-size: 0.7rem; margin-bottom: 0.25rem;">';
                
                languages.forEach(function(lang) {
                    var isActive = lang.id_lang == defaultLangId ? ' active' : '';
                    var isDefault = lang.is_default ? ' is-default' : '';
                    html += '<li class="nav-item">';
                    html += '<a href="#" data-locale="' + lang.iso_code.toLowerCase() + '" class="nav-link' + isActive + isDefault + '" data-toggle="tab" data-target=".translationsFields-' + translationsId + '_' + lang.id_lang + '">';
                    html += lang.iso_code.toUpperCase();
                    if (lang.is_default) {
                        html += ' <span class="material-icons" style="font-size: 12px;">star</span>';
                    }
                    html += '</a></li>';
                });
                
                html += '</ul><div class="translationsFields tab-content">';
                
                languages.forEach(function(lang) {
                    var isActive = lang.id_lang == defaultLangId ? ' show active' : '';
                    var paneClass = 'translationsFields-' + translationsId + '_' + lang.id_lang;
                    html += '<div data-locale="' + lang.iso_code.toLowerCase() + '" class="' + paneClass + ' tab-pane translation-field' + isActive + '">';
                    
                    // Generate input using template if available, otherwise use simple input
                    var template = jsTemplates[subfield.slug] || '<input type="text" class="form-control ' + sizeClass + ' acf-subfield-input" data-subfield="' + subfield.slug + '" data-lang-id="' + lang.id_lang + '" value="">';
                    // Remove {value} placeholder and ensure data-lang-id is set
                    template = template.replace(/{ldelim}value{rdelim}/g, '').replace(/\{value\}/g, '');
                    if (!template.includes('data-lang-id')) {
                        template = template.replace(/data-subfield="([^"]*)"/, 'data-subfield="$1" data-lang-id="' + lang.id_lang + '"');
                    }
                    html += template;
                    html += '</div>';
                });
                
                html += '</div></div></div>';
                return html;
            }

            function createRow(rowId) {
                var rowCount = rowsContainer.querySelectorAll('.acf-repeater-row').length;
                rowId = rowId || generateRowId();
                
                // Get languages from data attribute
                var languages = [];
                var defaultLangId = 1;
                try {
                    languages = JSON.parse(repeater.dataset.languages || '[]');
                    defaultLangId = parseInt(repeater.dataset.defaultLangId || '1');
                } catch (e) {
                    console.error('[ACF Repeater] Error parsing languages:', e);
                }

                if (displayMode === 'table') {
                    var tr = document.createElement('tr');
                    tr.className = 'acf-repeater-row';
                    tr.dataset.rowId = rowId;

                    var html = '<td class="acf-col-drag"><span class="acf-repeater-drag material-icons">drag_indicator</span></td>';
                    subfields.forEach(function(sf) {
                        html += '<td class="acf-repeater-cell" data-subfield-container="' + sf.slug + '" data-subfield-slug="' + sf.slug + '">';
                        
                        // Check if subfield is translatable (check translatable flag or lang_inputs presence)
                        var isTranslatable = sf.translatable || (sf.lang_inputs && sf.lang_inputs.length > 0);
                        if (isTranslatable && languages.length > 0) {
                            html += generateTranslatableSubfieldHtml(sf, rowId, slug, languages, defaultLangId, 'sm');
                        } else {
                            var template = jsTemplates[sf.slug] || '<input type="text" class="form-control form-control-sm acf-subfield-input" data-subfield="' + sf.slug + '" value="">';
                            html += template.replace(/{ldelim}value{rdelim}/g, '').replace(/\{value\}/g, '');
                        }
                        
                        html += '</td>';
                    });
                    html += '<td class="acf-col-actions"><button type="button" class="btn btn-link btn-sm text-danger acf-repeater-remove p-0" title="Remove"><span class="material-icons" style="font-size:18px;">delete</span></button></td>';
                    tr.innerHTML = html;
                    return tr;
                } else {
                    var div = document.createElement('div');
                    div.className = 'acf-repeater-row acf-repeater-card';
                    div.dataset.rowId = rowId;

                    var html = '<div class="acf-repeater-row-header">';
                    html += '<span class="acf-repeater-drag material-icons">drag_indicator</span>';
                    html += '<button type="button" class="acf-repeater-toggle"><span class="material-icons acf-toggle-icon">expand_more</span></button>';
                    html += '<span class="acf-repeater-row-title">Row ' + (rowCount + 1) + '</span>';
                    html += '<button type="button" class="btn btn-link text-danger acf-repeater-remove" title="Remove"><span class="material-icons">delete</span></button>';
                    html += '</div>';
                    html += '<div class="acf-repeater-row-content"><div class="acf-repeater-subfields">';
                    subfields.forEach(function(sf) {
                        html += '<div class="acf-repeater-subfield" data-subfield-container="' + sf.slug + '" data-subfield-slug="' + sf.slug + '">';
                        html += '<label class="form-control-label">' + (sf.title || sf.slug);
                        if (sf.translatable) {
                            html += ' <span class="badge badge-info ml-2" style="font-size: 0.7rem;"><i class="material-icons" style="font-size: 12px; vertical-align: middle;">language</i> Translatable</span>';
                        }
                        html += '</label>';
                        
                        // Check if subfield is translatable (check translatable flag or lang_inputs presence)
                        var isTranslatable = sf.translatable || (sf.lang_inputs && sf.lang_inputs.length > 0);
                        if (isTranslatable && languages.length > 0) {
                            html += generateTranslatableSubfieldHtml(sf, rowId, slug, languages, defaultLangId, '');
                        } else {
                            var template = jsTemplates[sf.slug] || '<input type="text" class="form-control acf-subfield-input" data-subfield="' + sf.slug + '" value="">';
                            html += template.replace(/{ldelim}value{rdelim}/g, '').replace(/\{value\}/g, '');
                        }
                        
                        html += '</div>';
                    });
                    html += '</div></div>';
                    div.innerHTML = html;
                    return div;
                }
            }

            if (addButton) {
                addButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!canAddRow()) {
                        alert('Maximum ' + maxRows + ' rows allowed.');
                        return;
                    }
                    var newRow = createRow();
                    rowsContainer.appendChild(newRow);
                    
                    // Re-initialize field types that need event listeners
                    initRelationFields(newRow);
                    initListFields(newRow);
                    
                    updateValue();
                });
            }

            repeater.addEventListener('click', function(e) {
                if (e.target.closest('.acf-repeater-remove')) {
                    e.preventDefault();
                    var row = e.target.closest('.acf-repeater-row');
                    var currentRows = rowsContainer.querySelectorAll('.acf-repeater-row').length;
                    if (currentRows <= minRows) {
                        alert('Minimum ' + minRows + ' rows required.');
                        return;
                    }
                    if (row && confirm('Remove this row?')) {
                        row.remove();
                        updateValue();
                    }
                }
            });

            repeater.addEventListener('click', function(e) {
                if (e.target.closest('.acf-repeater-toggle')) {
                    var row = e.target.closest('.acf-repeater-row');
                    if (row) {
                        row.classList.toggle('acf-collapsed');
                        var icon = row.querySelector('.acf-toggle-icon');
                        if (icon) icon.textContent = row.classList.contains('acf-collapsed') ? 'chevron_right' : 'expand_more';
                    }
                }
            });

            repeater.addEventListener('input', function(e) {
                if (e.target.classList.contains('acf-subfield-input')) {
                    updateValue();
                }
            });
            repeater.addEventListener('change', function(e) {
                if (e.target.classList.contains('acf-subfield-input') || e.target.closest('.acf-repeater-cell')) {
                    updateValue();
                }
            });

            console.debug('[ACF Repeater] Initialized:', slug, 'with', subfields.length, 'subfields');
        });
    }

    // =========================================================================
    // LIST FIELD HANDLER
    // =========================================================================
    function initListFields(container) {
        var listFields = container.querySelectorAll('.acf-list-field');
        listFields.forEach(function(field) {
            if (field.dataset.init === '1') return;
            field.dataset.init = '1';

            var slug = field.dataset.slug;
            var itemsContainer = field.querySelector('.acf-list-items');
            var valueInput = field.querySelector('.acf-list-value');
            var addBtn = field.querySelector('.acf-list-add');
            var showIcon = field.dataset.showIcon === '1';
            var showLink = field.dataset.showLink === '1';
            var placeholder = field.dataset.placeholder || '';
            var maxItems = parseInt(field.dataset.max) || 0;
            var minItems = parseInt(field.dataset.min) || 0;

            function generateId() {
                return 'item_' + Math.random().toString(36).substr(2, 9);
            }

            function updateValue() {
                var items = itemsContainer.querySelectorAll('.acf-list-item');
                var data = [];
                var position = 0;
                items.forEach(function(item) {
                    var textInput = item.querySelector('.acf-list-text');
                    var iconInput = item.querySelector('.acf-list-icon');
                    var linkInput = item.querySelector('.acf-list-link');
                    var obj = {
                        id: item.dataset.id || generateId(),
                        text: textInput ? textInput.value : '',
                        position: position++
                    };
                    if (showIcon && iconInput) obj.icon = iconInput.value;
                    if (showLink && linkInput) obj.link = linkInput.value;
                    data.push(obj);
                });
                valueInput.value = JSON.stringify(data);
                
                // Dispatch change event so parent containers (repeater) can update their value
                valueInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function createItemElement(itemData) {
                itemData = itemData || { id: generateId(), text: '' };
                var div = document.createElement('div');
                div.className = 'acf-list-item d-flex align-items-center gap-2 mb-2';
                div.dataset.id = itemData.id;

                var html = '<span class="acf-list-drag material-icons text-muted" style="cursor:grab;">drag_indicator</span>';
                html += '<input type="text" class="form-control acf-list-text" value="' + escapeHtml(itemData.text || '') + '" placeholder="' + escapeHtml(placeholder) + '">';
                if (showIcon) {
                    html += '<input type="text" class="form-control acf-list-icon" value="' + escapeHtml(itemData.icon || '') + '" placeholder="Icon" style="width:100px;">';
                }
                if (showLink) {
                    html += '<input type="url" class="form-control acf-list-link" value="' + escapeHtml(itemData.link || '') + '" placeholder="URL" style="width:150px;">';
                }
                html += '<button type="button" class="btn btn-link text-danger acf-list-remove p-1" title="Remove"><span class="material-icons">close</span></button>';

                div.innerHTML = html;
                return div;
            }

            function escapeHtml(text) {
                if (!text) return '';
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            if (addBtn) {
                addBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (maxItems > 0 && itemsContainer.querySelectorAll('.acf-list-item').length >= maxItems) {
                        alert('Maximum ' + maxItems + ' items allowed.');
                        return;
                    }
                    var newItem = createItemElement();
                    itemsContainer.appendChild(newItem);
                    newItem.querySelector('.acf-list-text').focus();
                    updateValue();
                });
            }

            field.addEventListener('click', function(e) {
                if (e.target.closest('.acf-list-remove')) {
                    e.preventDefault();
                    var item = e.target.closest('.acf-list-item');
                    var currentCount = itemsContainer.querySelectorAll('.acf-list-item').length;
                    if (minItems > 0 && currentCount <= minItems) {
                        alert('Minimum ' + minItems + ' items required.');
                        return;
                    }
                    if (item) {
                        item.remove();
                        updateValue();
                    }
                }
            });

            field.addEventListener('input', function(e) {
                if (e.target.classList.contains('acf-list-text') ||
                    e.target.classList.contains('acf-list-icon') ||
                    e.target.classList.contains('acf-list-link')) {
                    updateValue();
                }
            });

            console.debug('[ACF List] Initialized:', slug);
        });
    }

    // =========================================================================
    // RELATION FIELD AJAX SEARCH
    // =========================================================================
    function initRelationFields(container) {
        var relationFields = container.querySelectorAll('.acf-relation-field');
        relationFields.forEach(function(field) {
            if (field.dataset.init === '1') return;
            field.dataset.init = '1';

            var searchInput = field.querySelector('.acf-relation-search-input');
            var dropdown = field.querySelector('.acf-relation-dropdown');
            var selectedContainer = field.querySelector('.acf-relation-selected');
            var valueInput = field.querySelector('.acf-relation-value');
            var entityType = field.dataset.entityType || 'product';
            var isMultiple = field.dataset.multiple === '1';
            var displayFormat = field.dataset.displayFormat || 'name';
            var filterActive = field.dataset.filterActive !== '0';
            var filterStock = field.dataset.filterStock === '1';
            var filterCategories = field.dataset.filterCategories || '';

            // Find the main ACF container for API URL and entity ID
            var mainContainer = field.closest('#acf-entity-fields') || field.closest('#acf-product-fields') || field.closest('.acf-product-fields') || container;
            var excludeId = field.dataset.entityId || field.dataset.productId || mainContainer.dataset.entityId || mainContainer.dataset.productId || 0;
            
            var searchTimeout = null;
            var apiUrl = (mainContainer.dataset.apiUrl || container.dataset.apiUrl || '') + '/relation/search';

            function getSelectedIds() {
                var items = selectedContainer.querySelectorAll('.acf-relation-item');
                var ids = [];
                items.forEach(function(item) { ids.push(parseInt(item.dataset.id)); });
                return ids;
            }

            function updateValue() {
                var items = selectedContainer.querySelectorAll('.acf-relation-item');
                var data = [];
                items.forEach(function(item) {
                    var obj = { id: parseInt(item.dataset.id) };
                    var nameEl = item.querySelector('.acf-relation-name');
                    if (nameEl) obj.name = nameEl.textContent.trim();
                    var refEl = item.querySelector('.acf-relation-reference');
                    if (refEl) obj.reference = refEl.textContent.replace(/[()]/g, '').trim();
                    var imgEl = item.querySelector('.acf-relation-thumb');
                    if (imgEl) obj.image = imgEl.src;
                    data.push(obj);
                });
                valueInput.value = isMultiple ? JSON.stringify(data) : (data.length > 0 ? JSON.stringify(data[0]) : '');
                
                // Dispatch change event so parent containers (repeater) can update their value
                valueInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function addItem(item) {
                var existing = getSelectedIds();
                if (!isMultiple && existing.length > 0) {
                    selectedContainer.innerHTML = '';
                }
                if (existing.includes(item.id)) return;

                var div = document.createElement('div');
                div.className = 'acf-relation-item list-group-item d-flex align-items-center p-2';
                div.dataset.id = item.id;
                var html = '';
                if (displayFormat === 'thumbnail_name' && item.image) {
                    html += '<img src="' + item.image + '" alt="" class="acf-relation-thumb rounded me-2" style="width:32px;height:32px;object-fit:cover;">';
                }
                html += '<span class="acf-relation-name flex-grow-1">' + escapeHtml(item.name) + '</span>';
                if (displayFormat === 'name_reference' && item.reference) {
                    html += '<span class="acf-relation-reference text-muted small me-2">(' + escapeHtml(item.reference) + ')</span>';
                }
                html += '<button type="button" class="btn btn-sm btn-outline-danger acf-relation-remove p-1" title="Remove"><span class="material-icons" style="font-size:16px;">close</span></button>';
                div.innerHTML = html;
                selectedContainer.appendChild(div);
                updateValue();
            }

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            if (!searchInput) return;

            searchInput.addEventListener('input', function() {
                var query = this.value.trim();
                clearTimeout(searchTimeout);
                if (query.length < 2) {
                    dropdown.classList.add('d-none');
                    dropdown.innerHTML = '';
                    return;
                }
                searchTimeout = setTimeout(function() {
                    var url = apiUrl + '?q=' + encodeURIComponent(query) + '&type=' + entityType + '&limit=10';
                    if (excludeId > 0) url += '&exclude=' + excludeId;
                    if (filterActive) url += '&active=1';
                    if (filterStock) url += '&in_stock=1';
                    if (filterCategories) url += '&categories=' + encodeURIComponent(filterCategories);

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            if (!res.success || !res.data || res.data.length === 0) {
                                dropdown.innerHTML = '<div class="list-group-item text-muted py-2">No results</div>';
                                dropdown.classList.remove('d-none');
                                return;
                            }
                            var html = '';
                            var selectedIds = getSelectedIds();
                            res.data.forEach(function(item) {
                                var isSelected = selectedIds.includes(item.id);
                                html += '<div class="list-group-item list-group-item-action d-flex align-items-center p-2' + (isSelected ? ' disabled' : '') + '" data-item=\'' + JSON.stringify(item).replace(/'/g, '&#39;') + '\' style="cursor:pointer;">';
                                if (item.image) {
                                    html += '<img src="' + item.image + '" alt="" class="rounded me-2" style="width:32px;height:32px;object-fit:cover;">';
                                }
                                html += '<span class="flex-grow-1">' + escapeHtml(item.name) + '</span>';
                                if (item.reference) {
                                    html += '<span class="text-muted small">(' + escapeHtml(item.reference) + ')</span>';
                                }
                                if (isSelected) {
                                    html += '<span class="badge bg-success ms-2">Selected</span>';
                                }
                                html += '</div>';
                            });
                            dropdown.innerHTML = html;
                            dropdown.classList.remove('d-none');
                        })
                        .catch(function(err) {
                            console.error('[ACF Relation] Search error:', err);
                            dropdown.innerHTML = '<div class="list-group-item text-danger py-2">Search error</div>';
                            dropdown.classList.remove('d-none');
                        });
                }, 300);
            });

            dropdown.addEventListener('click', function(e) {
                var itemEl = e.target.closest('[data-item]');
                if (!itemEl || itemEl.classList.contains('disabled')) return;
                try {
                    var item = JSON.parse(itemEl.dataset.item);
                    addItem(item);
                    searchInput.value = '';
                    dropdown.classList.add('d-none');
                    dropdown.innerHTML = '';
                } catch (err) { console.error('[ACF Relation] Parse error:', err); }
            });

            selectedContainer.addEventListener('click', function(e) {
                if (e.target.closest('.acf-relation-remove')) {
                    e.target.closest('.acf-relation-item').remove();
                    updateValue();
                }
            });

            document.addEventListener('click', function(e) {
                if (!field.contains(e.target)) {
                    dropdown.classList.add('d-none');
                }
            });
        });
    }

    // =========================================================================
    // AJAX SAVE FUNCTIONALITY
    // =========================================================================
    function initAcfAjaxSave() {
        var container = document.getElementById('acf-entity-fields') || document.getElementById('acf-product-fields');
        var saveBtn = document.getElementById('acf-ajax-save');
        if (!container || !saveBtn) return;

        // Prevent double initialization
        if (saveBtn.dataset.acfInit === '1') return;
        saveBtn.dataset.acfInit = '1';

        var entityType = container.dataset.entityType;

        var entityId = parseInt(container.dataset.entityId) || 0;
        var apiUrl = container.dataset.apiUrl || '';
        var statusText = container.querySelector('.acf-status-text');
        var saveIcon = saveBtn.querySelector('.acf-save-icon');
        var saveLabel = saveBtn.querySelector('.acf-save-label');
        var saveSpinner = saveBtn.querySelector('.acf-save-spinner');

        if (!entityId || !apiUrl) {
            console.error('[ACF] Missing entityId or apiUrl for AJAX save');
            return;
        }

        function setStatus(type, message) {
            if (!statusText) return;
            statusText.textContent = message;
            statusText.className = 'acf-status-text small';
            if (type === 'success') statusText.classList.add('text-success');
            else if (type === 'error') statusText.classList.add('text-danger');
            else statusText.classList.add('text-muted');
        }

        function setLoading(loading) {
            if (loading) {
                saveBtn.disabled = true;
                if (saveIcon) saveIcon.classList.add('d-none');
                if (saveSpinner) saveSpinner.classList.remove('d-none');
                if (saveLabel) saveLabel.textContent = 'Saving...';
            } else {
                saveBtn.disabled = false;
                if (saveIcon) saveIcon.classList.remove('d-none');
                if (saveSpinner) saveSpinner.classList.add('d-none');
                if (saveLabel) saveLabel.textContent = 'Save Custom Fields';
            }
        }

        function findPrimaryVideoFileInput(videoField) {
            // Only pick the "main" upload input (exclude alt/poster/replace inputs)
            return videoField.querySelector('input[type="file"]:not([name*="_alt"]):not([name*="_poster"]):not([name*="_replace"])');
        }

        function uploadSelectedFiles() {
            var fileFields = Array.from(container.querySelectorAll('.acf-file-upload'));
            var uploads = [];

            fileFields.forEach(function(fileField) {
                var fileInput = fileField.querySelector('input[type="file"]');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    return;
                }

                var slug = fileField.dataset.slug;
                if (!slug) {
                    return;
                }

                var file = fileInput.files[0];
                var formData = new FormData();
                formData.append('file', file);
                formData.append('field_slug', slug);
                formData.append('entity_type', entityType || 'product');
                formData.append('entity_id', String(entityId));

                uploads.push(
                    fetch(apiUrl + '/upload-file', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function(result) {
                            if (!result.ok || !result.data || result.data.success !== true) {
                                var msg = (result.data && (result.data.error || result.data.message)) ? (result.data.error || result.data.message) : 'File upload failed';
                                throw new Error(msg);
                            }

                            var uploaded = result.data.data || {};
                            var hiddenValue = {
                                filename: uploaded.filename || null,
                                path: uploaded.path || null,
                                url: uploaded.url || '',
                                size: uploaded.size || null,
                                mime: uploaded.mime || file.type || null,
                                original_name: uploaded.original_name || file.name || null
                            };

                            // Store the uploaded data in a hidden input for later collection
                            var hiddenInput = fileField.querySelector('.acf-file-value');
                            if (hiddenInput) {
                                hiddenInput.value = JSON.stringify(hiddenValue);
                            }

                            // Clear the file input to avoid re-upload on subsequent saves
                            fileInput.value = '';
                        })
                );
            });

            return Promise.all(uploads);
        }

        function uploadSelectedImages() {
            var imageFields = Array.from(container.querySelectorAll('.acf-image-field'));
            var uploads = [];

            imageFields.forEach(function(imageField) {
                var fileInput = imageField.querySelector('input[type="file"]');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    return;
                }

                var slug = imageField.dataset.slug;
                if (!slug) {
                    return;
                }

                var file = fileInput.files[0];
                var formData = new FormData();
                formData.append('file', file);
                formData.append('field_slug', slug);
                formData.append('entity_type', entityType || 'product');
                formData.append('entity_id', String(entityId));

                uploads.push(
                    fetch(apiUrl + '/upload-file', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function(result) {
                            if (!result.ok || !result.data || result.data.success !== true) {
                                var msg = (result.data && (result.data.error || result.data.message)) ? (result.data.error || result.data.message) : 'Image upload failed';
                                throw new Error(msg);
                            }

                            var uploaded = result.data.data || {};
                            var hiddenValue = {
                                filename: uploaded.filename || null,
                                path: uploaded.path || null,
                                url: uploaded.url || '',
                                size: uploaded.size || null,
                                mime: uploaded.mime || file.type || null,
                                original_name: uploaded.original_name || file.name || null
                            };

                            // Store the uploaded data in a hidden input for later collection
                            var hiddenInput = imageField.querySelector('.acf-image-value');
                            if (hiddenInput) {
                                hiddenInput.value = JSON.stringify(hiddenValue);
                            }

                            // Clear the file input to avoid re-upload on subsequent saves
                            fileInput.value = '';
                        })
                );
            });

            return Promise.all(uploads);
        }

        function uploadSelectedGalleries() {
            var galleryFields = Array.from(container.querySelectorAll('.acf-gallery-field'));
            var allUploads = [];

            galleryFields.forEach(function(galleryField) {
                var fileInput = galleryField.querySelector('.acf-gallery-input');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    return;
                }

                var slug = galleryField.dataset.slug;
                if (!slug) {
                    return;
                }

                var hiddenInput = galleryField.querySelector('.acf-gallery-value');
                var galleryUploads = [];

                // Upload each file in the gallery
                Array.from(fileInput.files).forEach(function(file, index) {
                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append('field_slug', slug);
                    formData.append('entity_type', entityType || 'product');
                    formData.append('entity_id', String(entityId));

                    var uploadPromise = fetch(apiUrl + '/upload-file', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function(result) {
                            if (!result.ok || !result.data || result.data.success !== true) {
                                var msg = (result.data && (result.data.error || result.data.message)) ? (result.data.error || result.data.message) : 'Gallery image upload failed';
                                throw new Error(msg);
                            }

                            return result.data.data || {};
                        });

                    galleryUploads.push(uploadPromise);
                });

                // After all files for this gallery are uploaded, update the hidden input
                var galleryPromise = Promise.all(galleryUploads).then(function(uploadedFiles) {
                    if (hiddenInput && uploadedFiles.length > 0) {
                        // Get existing gallery items - reset if corrupted
                        var existingItems = [];
                        try {
                            var existingJson = hiddenInput.value || '[]';
                            if (existingJson.trim() === '' || existingJson === 'null') {
                                existingItems = [];
                            } else {
                                existingItems = JSON.parse(existingJson);
                                if (!Array.isArray(existingItems)) {
                                    console.warn('[ACF Gallery] Invalid existing data, resetting:', existingJson);
                                    existingItems = [];
                                }
                            }
                        } catch (e) {
                            console.warn('[ACF Gallery] Corrupted JSON, resetting gallery:', existingJson);
                            existingItems = [];
                        }

                        // Add uploaded files to the gallery
                        uploadedFiles.forEach(function(uploaded, index) {
                            var galleryItem = {
                                filename: uploaded.filename || null,
                                path: uploaded.path || null,
                                url: uploaded.url || '',
                                size: uploaded.size || null,
                                mime: uploaded.mime || null,
                                original_name: uploaded.original_name || null,
                                id: uploaded.id || null,
                                position: existingItems.length + index
                            };
                            existingItems.push(galleryItem);
                        });

                        // Update the hidden input with complete gallery
                        hiddenInput.value = JSON.stringify(existingItems);
                    }

                    // Clear the file input
                    fileInput.value = '';
                });

                allUploads.push(galleryPromise);
            });

            return Promise.all(allUploads);
        }

        function uploadSelectedVideoFiles() {
            var videoFields = Array.from(container.querySelectorAll('.acf-video-field'));
            var uploads = [];

            videoFields.forEach(function(videoField) {
                var fileInput = findPrimaryVideoFileInput(videoField);
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    return;
                }

                var hiddenInput = videoField.querySelector('.acf-video-value');
                if (!hiddenInput) {
                    return;
                }

                var slug = videoField.dataset.slug;
                if (!slug) {
                    return;
                }

                var file = fileInput.files[0];
                var formData = new FormData();
                formData.append('file', file);
                formData.append('field_slug', slug);
                formData.append('entity_type', entityType || 'product');
                formData.append('entity_id', String(entityId));

                uploads.push(
                    fetch(apiUrl + '/upload-video', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function(result) {
                            if (!result.ok || !result.data || result.data.success !== true) {
                                var msg = (result.data && (result.data.error || result.data.message)) ? (result.data.error || result.data.message) : 'Video upload failed';
                                throw new Error(msg);
                            }

                            var uploaded = result.data.data || {};
                            var titleInput = videoField.querySelector('input[name$="_title"]');

                            var value = {
                                source: 'upload',
                                url: uploaded.url || '',
                                filename: uploaded.filename || null,
                                path: uploaded.path || null,
                                size: uploaded.size || null,
                                mime: uploaded.mime || file.type || null,
                                original_name: uploaded.original_name || file.name || null,
                                title: titleInput ? (titleInput.value || '') : ''
                            };

                            hiddenInput.value = JSON.stringify(value);

                            // Clear the file input to avoid re-upload on subsequent saves
                            fileInput.value = '';
                        })
                );
            });

            return Promise.all(uploads);
        }

        function collectAllValues() {
            var values = {};

            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            // Manual video field value sync before collecting
            document.querySelectorAll('.acf-video-field').forEach(function(videoField) {
                var hiddenInput = videoField.querySelector('.acf-video-value');
                if (!hiddenInput) {
                    return;
                }

                // Find inputs manually
                var youtubeInput = videoField.querySelector('input[name$="_youtube_url"]');
                var vimeoInput = videoField.querySelector('input[name$="_vimeo_url"]');
                var urlInput = videoField.querySelector('input[name$="_url"]:not([name*="_youtube_url"]):not([name*="_vimeo_url"])');

                var value = null;

                // Check YouTube
                if (youtubeInput && youtubeInput.value.trim()) {
                    var url = youtubeInput.value.trim();
                    var ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                    if (ytMatch) {
                        value = {
                            source: 'youtube',
                            video_id: ytMatch[1],
                            url: url,
                            thumbnail_url: 'https://img.youtube.com/vi/' + ytMatch[1] + '/hqdefault.jpg'
                        };
                    }
                }
                // Check Vimeo
                else if (vimeoInput && vimeoInput.value.trim()) {
                    var url = vimeoInput.value.trim();
                    var vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
                    if (vimeoMatch) {
                        value = {
                            source: 'vimeo',
                            video_id: vimeoMatch[1],
                            url: url
                        };
                    }
                }
                // Check URL
                else if (urlInput && urlInput.value.trim()) {
                    value = {
                        source: 'url',
                        url: urlInput.value.trim()
                    };
                }

                // Update hidden input
                var jsonValue = value ? JSON.stringify(value) : null;
                // Do not overwrite an already uploaded value
                if (jsonValue !== null) {
                    hiddenInput.value = jsonValue;
                }
            });

            container.querySelectorAll('.acf-field').forEach(function(fieldEl) {
                var slug = fieldEl.dataset.fieldSlug;
                if (!slug) return;
                
                // Skip fields inside repeaters - they are collected by the repeater itself
                // (but not the repeater field itself, which has its own hidden input)
                if (fieldEl.closest('.acf-repeater-field')) {
                    return;
                }

                var translatableContainer = fieldEl.querySelector('.translations.tabbable');

                // For repeater fields, skip the translatable container check and go straight to collectFieldValue
                // This prevents collecting subfield translations as if they were the repeater value
                var hasRepeater = fieldEl.querySelector('.acf-repeater-field');
                if (hasRepeater) {
                    translatableContainer = null; // Force using collectFieldValue instead
                }
                if (translatableContainer) {
                    var langValues = {};
                    var panes = translatableContainer.querySelectorAll('.tab-pane');
                    panes.forEach(function(pane) {
                        // Extract langId from class name like "translationsFields-acf_text_field_1"
                        // Match pattern: translationsFields-acf_{any_slug}_{langId}
                        var classNames = pane.className.split(' ');
                        var langId = null;
                        for (var i = 0; i < classNames.length; i++) {
                            var className = classNames[i];
                            var match = className.match(/^translationsFields-acf_.+_(\d+)$/);
                            if (match && match[1]) {
                                langId = match[1];
                                break;
                            }
                        }
                        if (!langId) return;

                        var input = pane.querySelector('input, textarea, select');
                        if (input) {
                            var val = getInputValue(input);
                            // Allow empty values for translatable fields (to support clearing)
                            if (val !== null && val !== undefined) {
                                langValues[langId] = val;
                            }
                        }
                    });
                    if (Object.keys(langValues).length > 0) {
                        values[slug] = langValues;
                    }
                } else {
                    var val = collectFieldValue(fieldEl, slug);
                    if (val !== undefined) {
                        if (typeof val === 'object' && val !== null && !Array.isArray(val) && Object.keys(val).length === 0) {
                            // Empty object -> empty string
                            values[slug] = '';
                        } else if (Array.isArray(val) && val.length === 0) {
                            // Empty array -> null (will be normalized to null by PHP)
                            values[slug] = null;
                        } else {
                            values[slug] = val;
                        }
                    }
                }
            });

            return values;
        }

        function getInputValue(input) {
            if (!input) return '';
            if (input.type === 'checkbox') return input.checked ? '1' : '0';
            if (input.type === 'radio') {
                var checked = input.closest('form, .acf-field').querySelector('input[name="' + input.name + '"]:checked');
                return checked ? checked.value : '';
            }
            
            // Handle select multiple - must collect all selected options
            if (input.tagName === 'SELECT' && input.multiple) {
                var selected = Array.from(input.selectedOptions).map(function(opt) {
                    return opt.value;
                }).filter(function(val) {
                    return val !== ''; // Exclude empty "-- Select --" option
                });
                return selected.length > 0 ? selected : '';
            }
            
            return input.value || '';
        }

        function collectFieldValue(fieldEl, slug) {
            // Check for special hidden value inputs FIRST (repeater, list, relation, video, file, image, gallery)
            var hiddenValue = fieldEl.querySelector('.acf-repeater-value, .acf-list-value, .acf-relation-value, .acf-video-value, .acf-file-value, .acf-image-value, .acf-gallery-value');
            if (hiddenValue) {
                try {
                    var parsed = JSON.parse(hiddenValue.value || 'null');
                    if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed) && Object.keys(parsed).length === 0) {
                        return '';
                    }
                    // Convert empty arrays to null to avoid "Array to string conversion" errors
                    if (Array.isArray(parsed) && parsed.length === 0) {
                        return null;
                    }
                    return parsed;
                } catch (e) {
                    // JSON parsing failed - likely corrupted data, return null to reset
                    console.warn('[ACF] Invalid JSON in field ' + slug + ':', hiddenValue.value);
                    return null;
                }
            }

            // Check for ps-switch (boolean toggle) - look for checked radio in ps-switch container
            var psSwitchContainer = fieldEl.querySelector('.ps-switch');
            if (psSwitchContainer) {
                var psSwitchChecked = psSwitchContainer.querySelector('input[type="radio"]:checked');
                if (psSwitchChecked) {
                    return psSwitchChecked.value;
                }
            }

            // CHECK FOR CHECKBOX FIELD FIRST (multiple inputs, need to collect all checked)
            var checkboxes = fieldEl.querySelectorAll('input[type="checkbox"][name^="acf_' + slug + '"]:checked');
            if (checkboxes.length > 0) {
                return Array.from(checkboxes).map(function(cb) {
                    return cb.value;
                });
            }

            // Look for regular input/textarea/select, but EXCLUDE the special hidden inputs we already checked
            var input = fieldEl.querySelector('input[name^="acf_' + slug + '"]:not(.acf-repeater-value):not(.acf-list-value):not(.acf-relation-value):not(.acf-video-value):not(.acf-file-value):not(.acf-image-value):not(.acf-gallery-value), textarea[name^="acf_' + slug + '"], select[name^="acf_' + slug + '"]');
            if (input) {
                var val = getInputValue(input);
                return val !== null && val !== undefined ? val : '';
            }

            return undefined;
        }

        saveBtn.addEventListener('click', function(e) {
            e.preventDefault();

            setLoading(true);
            setStatus('info', 'Uploading files...');

            uploadSelectedFiles()
                .then(function() {
                    setStatus('info', 'Uploading images...');
                    return uploadSelectedImages();
                })
                .then(function() {
                    setStatus('info', 'Uploading gallery images...');
                    return uploadSelectedGalleries();
                })
                .then(function() {
                    setStatus('info', 'Uploading videos...');
                    return uploadSelectedVideoFiles();
                })
                .then(function() {
                    setStatus('info', 'Collecting field values...');
                    var values = collectAllValues();

                    setStatus('info', 'Sending to server...');

                    // Prepare request data - API expects 'productId' for products, generic names for other entities
                    var requestData = {
                        values: values
                    };

                    if (entityType === 'product') {
                        requestData.productId = entityId;
                    } else {
                        requestData.entityType = entityType;
                        requestData.entityId = entityId;
                    }

                    fetch(apiUrl + '/values', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function(result) {
                            setLoading(false);
                            if (result.ok && result.data.success) {
                                setStatus('success', 'Custom fields saved successfully!');
                                saveBtn.classList.add('btn-success');
                                saveBtn.classList.remove('btn-primary');
                                setTimeout(function() {
                                    saveBtn.classList.remove('btn-success');
                                    saveBtn.classList.add('btn-primary');
                                    setStatus('', '');
                                }, 3000);
                            } else {
                                var errorMsg = result.data.error;
                                if (!errorMsg && result.data.errors) {
                                    // Handle validation errors object: { field_slug: [errors] }
                                    var errorParts = [];
                                    if (typeof result.data.errors === 'object') {
                                        for (var fieldSlug in result.data.errors) {
                                            if (result.data.errors.hasOwnProperty(fieldSlug)) {
                                                var fieldErrors = result.data.errors[fieldSlug];
                                                if (Array.isArray(fieldErrors)) {
                                                    errorParts.push(fieldSlug + ': ' + fieldErrors.join(', '));
                                                } else if (typeof fieldErrors === 'string') {
                                                    errorParts.push(fieldSlug + ': ' + fieldErrors);
                                                }
                                            }
                                        }
                                    } else if (Array.isArray(result.data.errors)) {
                                        errorParts = result.data.errors;
                                    }
                                    errorMsg = errorParts.length > 0 ? errorParts.join('; ') : 'Validation errors occurred';
                                }
                                errorMsg = errorMsg || 'Unknown error';
                                setStatus('error', 'Error: ' + errorMsg);
                            }
                        })
                        .catch(function(error) {
                            setLoading(false);
                            setStatus('error', 'Network error: ' + error.message);
                            console.error('[ACF] Save error:', error);
                        });
                })
                .catch(function(error) {
                    setLoading(false);
                    setStatus('error', 'Upload error: ' + error.message);
                });
        });

        console.debug('[ACF] AJAX save initialized for', entityType, entityId);
    }

    // Export for potential external use
    window.AcfFields = {
        init: function() {
            initAcfDynamicFields();
            initAcfTabs();
            initVideoDeleteToggle();
            initAcfAjaxSave();
        },
        initTabs: initAcfTabs,
        initDynamicFields: initAcfDynamicFields,
        initVideoDeleteToggle: initVideoDeleteToggle,
        initAjaxSave: initAcfAjaxSave
    };
})();

