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
                        </label>
                        {if $field.instructions}
                            <small class="form-text text-muted d-block mb-1">{$field.instructions|escape:'html':'UTF-8'}</small>
                        {/if}
                        <div class="acf-field-input">
                            {$field.html nofilter}
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

