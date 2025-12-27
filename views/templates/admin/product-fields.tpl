{**
 * ACF - Product Fields Admin Template
 * Displays custom field groups in product edit form
 *}

<div class="acf-product-fields" id="acf-product-fields" data-product-id="{$acf_product_id|intval}">
    {foreach $acf_groups as $group}
        <div class="acf-group card mb-3" data-group-id="{$group.id|intval}">
            <div class="card-header">
                <h3 class="card-header-title mb-0">{$group.title|escape:'html':'UTF-8'}</h3>
                {if $group.description}
                    <p class="text-muted small mb-0">{$group.description|escape:'html':'UTF-8'}</p>
                {/if}
            </div>
            <div class="card-body">
                {foreach $group.fields as $field}
                    <div class="acf-field form-group row mb-4 pb-4" data-field-slug="{$field.slug|escape:'html':'UTF-8'}">
                        <div class="col-md-3 col-lg-2 text-md-left">
                            <label class="form-control-label{if $field.required} required{/if}">
                                {$field.title|escape:'html':'UTF-8'}
                                {if $field.required}<span class="text-danger">*</span>{/if}
                                {if $field.translatable}<span class="acf-translatable-badge" title="{l s='Translatable field' d='Modules.Weprestaacf.Admin'}">🌐</span>{/if}
                            </label>
                            {if $field.instructions}
                                <small class="form-text text-muted d-block">{$field.instructions|escape:'html':'UTF-8'}</small>
                            {/if}
                        </div>
                        <div class="col-md-9 col-lg-10 acf-field-input">
                            {if $field.translatable && $field.lang_inputs|count > 0}
                                {* Native PrestaShop translatable field structure *}
                                <div class="translations tabbable" id="acf_{$field.slug}" tabindex="1">
                                    <ul class="translationsLocales nav nav-pills">
                                        {foreach $field.lang_inputs as $lang_input}
                                            <li class="nav-item">
                                                <a href="#"
                                                   data-locale="{$lang_input.iso_code|lower}"
                                                   class="nav-link{if $lang_input.is_default} active{/if}"
                                                   data-toggle="tab"
                                                   data-target=".translationsFields-acf_{$field.slug}_{$lang_input.id_lang}">
                                                    {$lang_input.iso_code|upper}
                                                </a>
                                            </li>
                                        {/foreach}
                                    </ul>
                                    <div class="translationsFields tab-content">
                                        {foreach $field.lang_inputs as $lang_input}
                                            <div data-locale="{$lang_input.iso_code|lower}"
                                                 class="translationsFields-acf_{$field.slug}_{$lang_input.id_lang} tab-pane translation-field panel panel-default translation-label-{$lang_input.iso_code|lower}{if $lang_input.is_default} show active{/if}">
                                                {$lang_input.html nofilter}
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                            {else}
                                {* Non-translatable field *}
                                {$field.html nofilter}
                            {/if}
                        </div>
                    </div>
                {/foreach}

            </div>
        </div>
    {foreachelse}
        <div class="alert alert-info">
            {l s='No custom field groups defined for this product.' d='Modules.Weprestaacf.Admin'}
        </div>
    {/foreach}
</div>

{* Inline JavaScript for List and Repeater fields *}
<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initAcfDynamicFields();
        initAcfTabs();
    });

    // Also init when content might be loaded dynamically
    if (document.readyState !== 'loading') {
        initAcfDynamicFields();
        initAcfTabs();
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
                        // Ensure the pane is visible
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
        var container = document.getElementById('acf-product-fields');
        if (!container) return;

        // List field initialization
        initListFields(container);

        // Repeater fields initialization
        initRepeaterFields(container);

        // Initialize relation fields
        initRelationFields(container);

        // Initialize TinyMCE for richtext fields
        initRichtextFields(container);

        // Sync TinyMCE content before form submission
        syncTinyMCEOnSubmit();

        console.debug('[ACF] Dynamic fields initialized');
    }

    // =========================================================================
    // SYNC TinyMCE BEFORE FORM SUBMISSION
    // =========================================================================
    function syncTinyMCEOnSubmit() {
        // Find the product form
        var productForm = document.querySelector('form[name="product"]');
        if (!productForm) {
            productForm = document.querySelector('#form_product');
        }
        if (!productForm) {
            productForm = document.querySelector('form');
        }

        if (productForm) {
            productForm.addEventListener('submit', function() {
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
            // Trigger save on all TinyMCE editors
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

        // Check if TinyMCE is available
        if (typeof tinyMCE === 'undefined' && typeof tinymce === 'undefined') {
            console.warn('[ACF] TinyMCE not loaded. Rich text fields will use plain textarea.');
            return;
        }

        var mce = typeof tinymce !== 'undefined' ? tinymce : tinyMCE;

        textareas.forEach(function(textarea) {
            // Skip if already initialized
            if (textarea.dataset.tinymceInit === '1') return;

            var textareaId = textarea.id;
            if (!textareaId) {
                textareaId = 'acf_rte_' + Math.random().toString(36).substr(2, 9);
                textarea.id = textareaId;
            }

            // Remove any existing TinyMCE instance
            var existing = mce.get(textareaId);
            if (existing) {
                existing.remove();
            }

            // Use PrestaShop's tinySetup if available
            if (typeof tinySetup !== 'undefined') {
                try {
                    tinySetup({ editor_selector: textareaId });
                    textarea.dataset.tinymceInit = '1';
                } catch (e) {
                    console.warn('[ACF] tinySetup failed:', e);
                }
            } else {
                // Fallback: Initialize with default config
                try {
                    mce.init({
                        selector: '#' + textareaId,
                        height: 300,
                        menubar: false,
                        plugins: 'link image table lists paste code',
                        toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
                        relative_urls: false,
                        convert_urls: false,
                        setup: function(editor) {
                            editor.on('change', function() {
                                editor.save();
                            });
                        }
                    });
                    textarea.dataset.tinymceInit = '1';
                } catch (e) {
                    console.warn('[ACF] TinyMCE init failed:', e);
                }
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
            var jsTemplates = { };

            try { subfields = JSON.parse(repeater.dataset.subfields || '[]'); } catch (e) { console.error('[ACF Repeater] Error parsing subfields:', e); }
            try { jsTemplates = JSON.parse(repeater.dataset.jsTemplates || '{ }'); } catch (e) { }

            var rowsContainer = repeater.querySelector('.acf-repeater-rows');
            var valueInput = repeater.querySelector('.acf-repeater-value');
            var addButton = repeater.querySelector('.acf-repeater-add');

            // Generate unique row ID
            function generateRowId() {
                return 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }

            // Get current rows data from DOM
            function collectRowsData() {
                var rows = rowsContainer.querySelectorAll('.acf-repeater-row');
                var data = [];
                rows.forEach(function(row, index) {
                    var rowData = { row_id: row.dataset.rowId || generateRowId(), values: { } };
                    subfields.forEach(function(sf) {
                        var input = row.querySelector('[data-subfield="' + sf.slug + '"]');
                        if (input) {
                            if (input.type === 'checkbox') {
                                rowData.values[sf.slug] = input.checked ? '1' : '0';
                            } else {
                                rowData.values[sf.slug] = input.value || '';
                            }
                        }
                    });
                    data.push(rowData);
                });
                return data;
            }

            // Update hidden input with JSON data
            function updateValue() {
                var data = collectRowsData();
                valueInput.value = JSON.stringify(data);
            }

            // Check row limits
            function canAddRow() {
                if (maxRows <= 0) return true;
                return rowsContainer.querySelectorAll('.acf-repeater-row').length < maxRows;
            }

            // Create new row element
            function createRow(rowId) {
                var rowCount = rowsContainer.querySelectorAll('.acf-repeater-row').length;
                rowId = rowId || generateRowId();

                if (displayMode === 'table') {
                    var tr = document.createElement('tr');
                    tr.className = 'acf-repeater-row';
                    tr.dataset.rowId = rowId;

                    var html = '<td class="acf-col-drag"><span class="acf-repeater-drag material-icons">drag_indicator</span></td>';
                    subfields.forEach(function(sf) {
                        var template = jsTemplates[sf.slug] || '<input type="text" class="form-control form-control-sm acf-subfield-input" data-subfield="' + sf.slug + '" value="">';
                        html += '<td class="acf-repeater-cell" data-subfield-container="' + sf.slug + '">' + template.replace('{ldelim}value{rdelim}', '') + '</td>';
                    });
                    html += '<td class="acf-col-actions"><button type="button" class="btn btn-link btn-sm text-danger acf-repeater-remove p-0" title="Remove"><span class="material-icons" style="font-size:18px;">delete</span></button></td>';
                    tr.innerHTML = html;
                    return tr;
                } else {
                    // Cards mode
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
                        var template = jsTemplates[sf.slug] || '<input type="text" class="form-control acf-subfield-input" data-subfield="' + sf.slug + '" value="">';
                        html += '<div class="acf-repeater-subfield" data-subfield-container="' + sf.slug + '">';
                        html += '<label class="form-control-label">' + (sf.title || sf.slug) + '</label>';
                        html += template.replace('{ldelim}value{rdelim}', '');
                        html += '</div>';
                    });
                    html += '</div></div>';
                    div.innerHTML = html;
                    return div;
                }
            }

            // Add row click handler
            if (addButton) {
                addButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!canAddRow()) {
                        alert('Maximum ' + maxRows + ' rows allowed.');
                        return;
                    }
                    var newRow = createRow();
                    rowsContainer.appendChild(newRow);
                    updateValue();
                });
            }

            // Remove row click handler
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

            // Collapse/expand toggle (cards mode)
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

            // Listen for input changes to update value
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

            // Add item button
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

            // Remove item
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

            // Update value on input change
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
            var excludeId = field.dataset.productId || 0;
            var filterActive = field.dataset.filterActive !== '0';
            var filterStock = field.dataset.filterStock === '1';
            var filterCategories = field.dataset.filterCategories || '';

            var searchTimeout = null;
            var apiUrl = '{$acf_api_base_url|escape:'javascript'}/relation/search';

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

            // Search input handler
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

            // Dropdown item click
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

            // Remove item
            selectedContainer.addEventListener('click', function(e) {
                if (e.target.closest('.acf-relation-remove')) {
                    e.target.closest('.acf-relation-item').remove();
                    updateValue();
                }
            });

            // Hide dropdown on outside click
            document.addEventListener('click', function(e) {
                if (!field.contains(e.target)) {
                    dropdown.classList.add('d-none');
                }
            });
        });
    }
})();
</script>

<style>
/* Translatable badge icon */
.acf-translatable-badge {
    font-size: 0.875rem;
    margin-left: 0.25rem;
    opacity: 0.7;
}
/* Group styling */
.acf-group.card {
    border-left: 4px solid var(--primary, #25b9d7);
}
/* Native PrestaShop translatable uses .translations.tabbable - no custom styles needed */

/* Ensure field-level tabs work correctly */
.acf-image-field .tab-content > .tab-pane.show.active,
.acf-video-field .tab-content > .tab-pane.show.active {
    display: block !important;
}
/* Ensure first visible tab content is shown on load */
.acf-image-field .tab-content > .tab-pane:only-child,
.acf-video-field .tab-content > .tab-pane:only-child {
    display: block !important;
    opacity: 1 !important;
}
/* Fix for dropzone visibility */
.acf-dropzone {
    min-height: 80px;
}
</style>

