{**
 * ACF - Product Info Hook Template
 * Displays custom fields on product page front-office
 *}

{if $acf_fields && count($acf_fields) > 0}
<div class="acf-product-info" id="acf-product-info-{$acf_product_id|intval}">
    <div class="acf-fields-list">
        {foreach $acf_fields as $field}
            <div class="acf-field acf-field--{$field.type|escape:'html':'UTF-8'}" data-slug="{$field.slug|escape:'html':'UTF-8'}">
                {if $field.fo_options.show_label|default:true}
                    <span class="acf-field__label">{$field.title|escape:'html':'UTF-8'}:</span>
                {/if}
                <span class="acf-field__value">{$field.rendered nofilter}</span>
            </div>
        {/foreach}
    </div>
</div>
{/if}
