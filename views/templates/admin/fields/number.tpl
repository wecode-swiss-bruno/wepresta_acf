{**
 * ACF Field Partial: Number
 * Uses Bootstrap input-group for prefix/unit (prepend/append)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="hasPrepend" value=isset($fieldConfig.prepend) && $fieldConfig.prepend !== ''}
{assign var="hasUnit" value=isset($fieldConfig.unit) && $fieldConfig.unit !== ''}
{assign var="hasInputGroup" value=$hasPrepend || $hasUnit}

{if $hasInputGroup}
<div class="input-group{if isset($context.size) && $context.size === 'sm'} input-group-sm{/if}">
    {if $hasPrepend}
    <div class="input-group-prepend">
        <span class="input-group-text">{$fieldConfig.prepend|escape:'htmlall':'UTF-8'}</span>
    </div>
    {/if}
{/if}

<input type="number"
       class="form-control{if isset($context.size) && $context.size === 'sm'} form-control-sm{/if}{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}"
       id="{$inputId|escape:'htmlall':'UTF-8'}"
       {if isset($context.dataSubfield) && $context.dataSubfield}
           data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
       {else}
           name="{$inputName|escape:'htmlall':'UTF-8'}"
       {/if}
       value="{$value|escape:'htmlall':'UTF-8'}"
       {if isset($fieldConfig.min)}min="{$fieldConfig.min|escape:'htmlall':'UTF-8'}"{/if}
       {if isset($fieldConfig.max)}max="{$fieldConfig.max|escape:'htmlall':'UTF-8'}"{/if}
       {if isset($fieldConfig.step)}step="{$fieldConfig.step|escape:'htmlall':'UTF-8'}"{else}step="any"{/if}
       {if isset($fieldConfig.placeholder) && $fieldConfig.placeholder}placeholder="{$fieldConfig.placeholder|escape:'htmlall':'UTF-8'}"{/if}>

{if $hasInputGroup}
    {if $hasUnit}
    <div class="input-group-append">
        <span class="input-group-text">{$fieldConfig.unit|escape:'htmlall':'UTF-8'}</span>
    </div>
    {/if}
</div>
{/if}
