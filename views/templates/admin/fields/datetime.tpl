{**
 * ACF Field Partial: DateTime
 * Uses HTML5 datetime-local with sensible year constraints
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{* Default min/max to reasonable years (1900-2100) unless specified *}
{assign var="minDate" value=$fieldConfig.minDate|default:'1900-01-01T00:00'}
{assign var="maxDate" value=$fieldConfig.maxDate|default:'2100-12-31T23:59'}

<input type="datetime-local"
       class="form-control{if isset($context.size) && $context.size === 'sm'} form-control-sm{/if}{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}"
       id="{$inputId|escape:'htmlall':'UTF-8'}"
       {if isset($context.dataSubfield) && $context.dataSubfield}
           data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
       {else}
           name="{$inputName|escape:'htmlall':'UTF-8'}"
       {/if}
       value="{$value|escape:'htmlall':'UTF-8'}"
       min="{$minDate|escape:'htmlall':'UTF-8'}"
       max="{$maxDate|escape:'htmlall':'UTF-8'}">
