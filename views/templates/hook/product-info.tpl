{**
 * ACF - Product Info Hook Template
 * Displays custom fields on product page front-office
 *}

{if $acf_fields && count($acf_fields) > 0}
<div class="acf-product-info" id="acf-product-info-{$acf_product_id|intval}">
    <div class="acf-fields-list">
        {foreach $acf_fields as $field}
            {* Skip field if not visible in front-office options *}
            {if isset($field.fo_options.visible) && !$field.fo_options.visible}
                {continue}
            {/if}
            {* Build field wrapper classes *}
            {$fieldClasses = 'acf-field acf-field--'|cat:$field.type|escape:'html':'UTF-8'}
            {if isset($field.wrapper.class) && $field.wrapper.class}
                {$fieldClasses = $fieldClasses|cat:' '|cat:$field.wrapper.class}
            {/if}
            <div class="{$fieldClasses}"{if isset($field.wrapper.id) && $field.wrapper.id} id="{$field.wrapper.id|escape:'html':'UTF-8'}"{/if} data-slug="{$field.slug|escape:'html':'UTF-8'}">
                {if $field.fo_options.show_label|default:true}
                    <span class="acf-field__label">{$field.title|escape:'html':'UTF-8'}:</span>
                {/if}
                <span class="acf-field__value">{$field.rendered nofilter}</span>
            </div>
        {/foreach}
    </div>
</div>


$ingredients = acf_field('ingredients');

{if $ingredients}
    <div class="acf-field acf-field--repeater">
        <span class="acf-field__label">Ingredients:</span>
        foreach ($ingredients as $ingredient) {
            <div class="acf-field__value">{$ingredient.name}</div>
            <div class="acf-field__value">{$ingredient.quantity}</div>
            <div class="acf-field__value">{$ingredient.unit}</div>
        }
    </div>
{/if}

<style>
/* ACF Product Info Styles */
.acf-product-info {
    margin: 1rem 0;
}
.acf-fields-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.acf-field {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0;
}
.acf-field__label {
    font-weight: 600;
    color: #333;
    min-width: 120px;
}
.acf-field__value {
    flex: 1;
}
/* Field presentation styles - applied via wrapper properties */
.acf-field[id] {
    /* Custom styling for fields with custom IDs */
}
</style>
{/if}
