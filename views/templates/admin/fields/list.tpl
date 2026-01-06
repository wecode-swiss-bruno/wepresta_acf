{**
 * ACF Field Partial: List (Simple Repeater for Text Items)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="listMin" value=$fieldConfig.min|default:0}
{assign var="listMax" value=$fieldConfig.max|default:0}
{assign var="showIcon" value=$fieldConfig.showIcon|default:false}
{assign var="showLink" value=$fieldConfig.showLink|default:false}
{assign var="iconSet" value=$fieldConfig.iconSet|default:'material'}
{assign var="listPlaceholder" value=$fieldConfig.placeholder|default:''}

{* Parse existing value *}
{assign var="listItems" value=[]}
{if $value}
    {if is_array($value)}
        {assign var="listItems" value=$value}
    {else}
        {assign var="listItems" value=$value|json_decode:true}
    {/if}
{/if}

<div class="acf-list-field" 
     data-type="list" 
     data-slug="{$field.slug|escape:'htmlall':'UTF-8'}"
     data-min="{$listMin|intval}"
     data-max="{$listMax|intval}"
     data-show-icon="{if $showIcon}1{else}0{/if}"
     data-show-link="{if $showLink}1{else}0{/if}"
     data-icon-set="{$iconSet|escape:'htmlall':'UTF-8'}"
     data-placeholder="{$listPlaceholder|escape:'htmlall':'UTF-8'}">
    
    {* JSON encode the value *}
    {if is_array($value)}
        {assign var="listJsonValue" value=$value|@json_encode}
    {elseif $value}
        {assign var="listJsonValue" value=$value}
    {else}
        {assign var="listJsonValue" value='[]'}
    {/if}
    <input type="hidden" 
           {if isset($context.dataSubfield) && $context.dataSubfield}data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"{else}name="{$inputName|escape:'htmlall':'UTF-8'}"{/if}
           id="{$inputId|escape:'htmlall':'UTF-8'}_value"
           class="{if isset($context.dataSubfield) && $context.dataSubfield}acf-subfield-input {/if}acf-list-value"
           value="{$listJsonValue|escape:'htmlall':'UTF-8'}">
    
    <div class="acf-list-items" id="{$inputId|escape:'htmlall':'UTF-8'}_items">
        {if $listItems && is_array($listItems)}
            {foreach $listItems as $item}
                {* Safely get item properties with defaults *}
                {assign var="itemId" value=$item.id|default:''}
                {assign var="itemText" value=$item.text|default:''}
                {assign var="itemIcon" value=$item.icon|default:''}
                {assign var="itemLink" value=$item.link|default:''}
                <div class="acf-list-item d-flex align-items-center gap-2 mb-2" data-id="{$itemId|escape:'htmlall':'UTF-8'}">
                    <span class="acf-list-drag material-icons text-muted" style="cursor:grab;">drag_indicator</span>
                    <input type="text" 
                           class="form-control acf-list-text flex-grow-1" 
                           value="{$itemText|escape:'htmlall':'UTF-8'}"
                           placeholder="{$listPlaceholder|escape:'htmlall':'UTF-8'}">
                    {if $showIcon}
                        <input type="text" 
                               class="form-control acf-list-icon" 
                               value="{$itemIcon|escape:'htmlall':'UTF-8'}"
                               placeholder="{l s='Icon' mod='wepresta_acf'}"
                               style="width: 100px;">
                    {/if}
                    {if $showLink}
                        <input type="url" 
                               class="form-control acf-list-link" 
                               value="{$itemLink|escape:'htmlall':'UTF-8'}"
                               placeholder="{l s='URL' mod='wepresta_acf'}"
                               style="width: 150px;">
                    {/if}
                    <button type="button" class="btn btn-link text-danger acf-list-remove p-1" title="{l s='Remove' mod='wepresta_acf'}">
                        <span class="material-icons">close</span>
                    </button>
                </div>
            {/foreach}
        {/if}
    </div>
    
    <button type="button" class="btn btn-outline-secondary btn-sm acf-list-add">
        <span class="material-icons">add</span>
        {l s='Add Item' mod='wepresta_acf'}
    </button>
    
    {if $listMin > 0 || $listMax > 0}
        <small class="form-text text-muted acf-list-limits">
            {if $listMin > 0 && $listMax > 0}
                {l s='Between %min% and %max% items' mod='wepresta_acf' sprintf=['%min%' => $listMin, '%max%' => $listMax]}
            {elseif $listMin > 0}
                {l s='Minimum %min% items' mod='wepresta_acf' sprintf=['%min%' => $listMin]}
            {else}
                {l s='Maximum %max% items' mod='wepresta_acf' sprintf=['%max%' => $listMax]}
            {/if}
        </small>
    {/if}
</div>

