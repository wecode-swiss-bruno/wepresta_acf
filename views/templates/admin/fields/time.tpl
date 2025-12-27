{**
 * ACF Field Partial: Time
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
<input type="time" 
       class="form-control{if isset($context.size) && $context.size === 'sm'} form-control-sm{/if}{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}" 
       id="{$prefix|escape:'htmlall':'UTF-8'}{$field.slug|escape:'htmlall':'UTF-8'}{if isset($context.suffix)}{$context.suffix|escape:'htmlall':'UTF-8'}{/if}" 
       {if isset($context.dataSubfield) && $context.dataSubfield}
           data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
       {else}
           name="{$prefix|escape:'htmlall':'UTF-8'}{$field.slug|escape:'htmlall':'UTF-8'}{if isset($context.suffix)}{$context.suffix|escape:'htmlall':'UTF-8'}{/if}"
       {/if}
       value="{$value|escape:'htmlall':'UTF-8'}"
       {if isset($fieldConfig.step) && $fieldConfig.step > 1}step="{$fieldConfig.step * 60}"{/if}>

