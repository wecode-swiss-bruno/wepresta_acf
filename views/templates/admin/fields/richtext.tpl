{**
 * ACF Field Partial: Rich Text (TinyMCE)
 * Uses PrestaShop's native autoload_rte class for TinyMCE initialization
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="rows" value=$fieldConfig.rows|default:10}

<div class="acf-richtext-field" data-field="{$field.slug|escape:'htmlall':'UTF-8'}">
    <textarea class="rte autoload_rte{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}" 
              id="{$inputId|escape:'htmlall':'UTF-8'}" 
              {if isset($context.dataSubfield) && $context.dataSubfield}
                  data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
              {else}
                  name="{$inputName|escape:'htmlall':'UTF-8'}"
              {/if}
              rows="{$rows|intval}">{$value nofilter}</textarea>
</div>
