{**
 * ACF Field Partial: Color Picker
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="colorValue" value=$value|default:'#000000'}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}

<div class="acf-color-input-group">
    <input type="color" 
           class="form-control acf-color-picker{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}"
           id="{$inputId|escape:'htmlall':'UTF-8'}" 
           {if isset($context.dataSubfield) && $context.dataSubfield}
               data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
           {else}
               name="{$inputName|escape:'htmlall':'UTF-8'}"
           {/if}
           value="{$colorValue|escape:'htmlall':'UTF-8'}">
    <input type="text" 
           class="form-control acf-color-hex{if isset($context.size) && $context.size === 'sm'} form-control-sm{/if}"
           value="{$colorValue|escape:'htmlall':'UTF-8'}"
           readonly>
</div>

