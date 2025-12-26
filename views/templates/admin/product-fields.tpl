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
                    <div class="acf-field form-group mb-3" data-field-slug="{$field.slug|escape:'html':'UTF-8'}">
                        <label class="form-control-label{if $field.required} required{/if}">
                            {$field.title|escape:'html':'UTF-8'}
                            {if $field.required}<span class="text-danger">*</span>{/if}
                            {if $field.translatable}<span class="acf-translatable-badge" title="{l s='Translatable field' d='Modules.Weprestaacf.Admin'}">🌐</span>{/if}
                        </label>
                        {if $field.instructions}
                            <small class="form-text text-muted d-block mb-1">{$field.instructions|escape:'html':'UTF-8'}</small>
                        {/if}
                        <div class="acf-field-input">
                            {if $field.translatable && $field.lang_inputs|count > 0}
                                {* Translatable field with language tabs *}
                                <div class="acf-translatable-field" data-field="{$field.slug|escape:'html':'UTF-8'}">
                                    <ul class="nav nav-tabs acf-lang-tabs" role="tablist">
                                        {foreach $field.lang_inputs as $lang_input}
                                            <li class="nav-item">
                                                <a class="nav-link{if $lang_input.is_default} active{/if}"
                                                   id="tab-{$field.slug}-{$lang_input.id_lang}"
                                                   data-toggle="tab"
                                                   data-bs-toggle="tab"
                                                   href="#pane-{$field.slug}-{$lang_input.id_lang}"
                                                   role="tab">
                                                    {$lang_input.iso_code|upper}
                                                </a>
                                            </li>
                                        {/foreach}
                                    </ul>
                                    <div class="tab-content acf-lang-content">
                                        {foreach $field.lang_inputs as $lang_input}
                                            <div class="tab-pane fade{if $lang_input.is_default} show active{/if}"
                                                 id="pane-{$field.slug}-{$lang_input.id_lang}"
                                                 role="tabpanel">
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
    });

    // Also init when content might be loaded dynamically
    if (document.readyState !== 'loading') {
        initAcfDynamicFields();
    }

    function initAcfDynamicFields() {
        var container = document.getElementById('acf-product-fields');
        if (!container) return;

        // List field - Add item
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('acf-list-add')) {
                e.preventDefault();
                var btn = e.target;
                var listField = btn.closest('.acf-list-field');
                var itemsContainer = listField.querySelector('.acf-list-items');
                var fieldSlug = listField.getAttribute('data-field');

                var newItem = document.createElement('div');
                newItem.className = 'acf-list-item mb-2 d-flex align-items-center';
                newItem.innerHTML = '<input type="text" class="form-control" name="acf_' + fieldSlug + '[]" value="">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger ms-2 acf-list-remove">×</button>';

                itemsContainer.appendChild(newItem);
                newItem.querySelector('input').focus();
            }
        });

        // List field - Remove item
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('acf-list-remove')) {
                e.preventDefault();
                var item = e.target.closest('.acf-list-item');
                if (item) item.remove();
            }
        });

        // Repeater field - Add row
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('acf-repeater-add')) {
                e.preventDefault();
                var btn = e.target;
                var repeaterField = btn.closest('.acf-repeater-field');
                var rowsContainer = repeaterField.querySelector('.acf-repeater-rows');
                var fieldName = repeaterField.getAttribute('data-field');
                var rowIndex = rowsContainer.querySelectorAll('.acf-repeater-row').length;

                var newRow = document.createElement('div');
                newRow.className = 'acf-repeater-row card mb-2';
                newRow.setAttribute('data-row', rowIndex);
                newRow.innerHTML = '<div class="card-header d-flex justify-content-between align-items-center py-2">' +
                    '<span class="acf-repeater-row-handle" style="cursor:move;">☰ Row ' + (rowIndex + 1) + '</span>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger acf-repeater-remove">×</button>' +
                    '</div>' +
                    '<div class="card-body py-2">' +
                    '<div class="form-group mb-2">' +
                    '<label class="form-control-label">Value</label>' +
                    '<input type="text" class="form-control form-control-sm" name="acf_' + fieldName + '[' + rowIndex + '][value]" value="">' +
                    '</div>' +
                    '</div>';

                rowsContainer.appendChild(newRow);
            }
        });

        // Repeater field - Remove row
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('acf-repeater-remove')) {
                e.preventDefault();
                var row = e.target.closest('.acf-repeater-row');
                if (row && confirm('Remove this row?')) {
                    row.remove();
                }
            }
        });

        console.debug('[ACF] Dynamic fields initialized');
    }
})();
</script>

<style>
.acf-list-field .acf-list-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.acf-list-field .acf-list-item input {
    flex: 1;
}
.acf-repeater-row .card-header {
    background: #f8f9fa;
    padding: 0.5rem 0.75rem;
}
.acf-repeater-row .card-body {
    padding: 0.75rem;
}
/* Translatable field styles */
.acf-translatable-badge {
    font-size: 0.875rem;
    margin-left: 0.25rem;
    opacity: 0.7;
}
.acf-translatable-field .acf-lang-tabs {
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 0;
}
.acf-translatable-field .acf-lang-tabs .nav-link {
    padding: 0.35rem 0.75rem;
    font-size: 0.8125rem;
    color: #6c757d;
    border: 1px solid transparent;
    border-top-left-radius: 0.25rem;
    border-top-right-radius: 0.25rem;
}
.acf-translatable-field .acf-lang-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
}
.acf-translatable-field .acf-lang-tabs .nav-link.active {
    color: #25b9d7;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}
.acf-translatable-field .acf-lang-content {
    border: 1px solid #dee2e6;
    border-top: none;
    padding: 0.75rem;
    background: #fff;
}
</style>

