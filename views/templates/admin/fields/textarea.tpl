{**
 * ACF Field Partial: Textarea
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
<textarea class="form-control{if isset($context.size) && $context.size === 'sm'} form-control-sm{/if}{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}" 
          id="{$prefix|escape:'htmlall':'UTF-8'}{$field.slug|escape:'htmlall':'UTF-8'}{if isset($context.suffix)}{$context.suffix|escape:'htmlall':'UTF-8'}{/if}" 
          {if isset($context.dataSubfield) && $context.dataSubfield}
              data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
          {else}
              name="{$prefix|escape:'htmlall':'UTF-8'}{$field.slug|escape:'htmlall':'UTF-8'}{if isset($context.suffix)}{$context.suffix|escape:'htmlall':'UTF-8'}{/if}"
          {/if}
          rows="{if isset($fieldConfig.rows)}{$fieldConfig.rows|intval}{else}4{/if}"
          {if isset($fieldConfig.placeholder) && $fieldConfig.placeholder}placeholder="{$fieldConfig.placeholder|escape:'htmlall':'UTF-8'}"{/if}
          {if isset($fieldConfig.maxLength) && $fieldConfig.maxLength}maxlength="{$fieldConfig.maxLength|intval}"{/if}>{$value|escape:'htmlall':'UTF-8'}</textarea>

