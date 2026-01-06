{**
 * ACF Field Partial: Relation (Product/Category Picker)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 * Optional: $id_product
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="entityType" value=$fieldConfig.entityType|default:'product'}
{assign var="multiple" value=$fieldConfig.multiple|default:false}
{assign var="displayFormat" value=$fieldConfig.displayFormat|default:'name'}
{assign var="filters" value=$fieldConfig.filters|default:[]}

<div class="acf-relation-field" 
     data-type="relation"
     data-slug="{$field.slug|escape:'htmlall':'UTF-8'}"
     data-entity-type="{$entityType|escape:'htmlall':'UTF-8'}"
     data-multiple="{if $multiple}1{else}0{/if}"
     data-display-format="{$displayFormat|escape:'htmlall':'UTF-8'}"
     data-filter-active="{if isset($filters.active) && !$filters.active}0{else}1{/if}"
     data-filter-stock="{if isset($filters.in_stock) && $filters.in_stock}1{else}0{/if}"
     data-filter-exclude="{if isset($filters.exclude_current) && !$filters.exclude_current}0{else}1{/if}"
     data-filter-categories="{$filters.categories|default:''|escape:'htmlall':'UTF-8'}"
     data-product-id="{$id_product|default:0|escape:'htmlall':'UTF-8'}">
    
    {* Hidden input to store selected IDs *}
    <input type="hidden" 
           {if isset($context.dataSubfield) && $context.dataSubfield}data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"{else}name="{$inputName|escape:'htmlall':'UTF-8'}"{/if}
           id="{$inputId|escape:'htmlall':'UTF-8'}_value"
           class="{if isset($context.dataSubfield) && $context.dataSubfield}acf-subfield-input {/if}acf-relation-value"
           value="{if $value}{if is_array($value)}{$value|@json_encode|escape:'htmlall':'UTF-8'}{else}{$value|escape:'htmlall':'UTF-8'}{/if}{/if}">
    
    {* Selected items display - use $entities which contains loaded entity data *}
    <div class="acf-relation-selected list-group list-group-flush mb-2" id="{$inputId|escape:'htmlall':'UTF-8'}_selected">
        {if isset($entities) && $entities && is_array($entities)}
            {foreach $entities as $entity}
                <div class="acf-relation-item list-group-item d-flex align-items-center p-2" data-id="{$entity.id|escape:'htmlall':'UTF-8'}">
                    {if $displayFormat === 'thumbnail_name' && isset($entity.image) && $entity.image}
                        <img src="{$entity.image|escape:'htmlall':'UTF-8'}" alt="" class="acf-relation-thumb rounded me-2" style="width:32px;height:32px;object-fit:cover;">
                    {/if}
                    <span class="acf-relation-name flex-grow-1">{$entity.name|escape:'htmlall':'UTF-8'}</span>
                    {if $displayFormat === 'name_reference' && isset($entity.reference) && $entity.reference}
                        <span class="acf-relation-reference text-muted small me-2">({$entity.reference|escape:'htmlall':'UTF-8'})</span>
                    {/if}
                    <button type="button" class="btn btn-sm btn-outline-danger acf-relation-remove p-1" title="{l s='Remove' mod='wepresta_acf'}">
                        <span class="material-icons" style="font-size:16px;">close</span>
                    </button>
                </div>
            {/foreach}
        {elseif $value && !is_array($value)}
            {* Single ID value - show loading state, JS will load details *}
            <div class="acf-relation-item list-group-item acf-loading d-flex align-items-center p-2" data-id="{$value|escape:'htmlall':'UTF-8'}">
                <span class="spinner-border spinner-border-sm me-2"></span>
                <span class="acf-relation-name">{l s='Loading...' mod='wepresta_acf'}</span>
            </div>
        {/if}
    </div>
    
    {* Search input *}
    <div class="acf-relation-search position-relative">
        <div class="input-group">
            <span class="input-group-text"><span class="material-icons" style="font-size:18px;">search</span></span>
            <input type="text" 
                   class="form-control acf-relation-search-input"
                   placeholder="{if $entityType === 'category'}{l s='Search categories...' mod='wepresta_acf'}{else}{l s='Search products...' mod='wepresta_acf'}{/if}"
                   autocomplete="off">
        </div>
        <div class="acf-relation-dropdown list-group position-absolute w-100 shadow d-none" style="z-index:1050;max-height:200px;overflow-y:auto;">
            {* Results will be inserted here by JS *}
        </div>
    </div>
</div>
