{**
 * ACF Field Partial: Text, Email, URL
 * Uses Bootstrap input-group for prefix/suffix (prepend/append)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputType" value=$context.inputType|default:'text'}
{assign var="hasPrepend" value=isset($fieldConfig.prepend) && $fieldConfig.prepend !== ''}
{assign var="hasAppend" value=isset($fieldConfig.append) && $fieldConfig.append !== ''}
{assign var="hasInputGroup" value=$hasPrepend || $hasAppend}

{if $hasInputGroup}
<div class="input-group{if isset($context.size) && $context.size === 'sm'} input-group-sm{/if}">
    {if $hasPrepend}
    <div class="input-group-prepend">
        <span class="input-group-text">{$fieldConfig.prepend|escape:'htmlall':'UTF-8'}</span>
    </div>
    {/if}
{/if}

<input type="{$inputType|escape:'htmlall':'UTF-8'}"
       class="form-control{if isset($context.size) && $context.size === 'sm'} form-control-sm{/if}{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}"
       id="{$inputId|escape:'htmlall':'UTF-8'}"
       {if isset($context.dataSubfield) && $context.dataSubfield}
           data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
       {else}
           name="{$inputName|escape:'htmlall':'UTF-8'}"
       {/if}
       value="{$value|escape:'htmlall':'UTF-8'}"
       {if isset($fieldConfig.placeholder) && $fieldConfig.placeholder}placeholder="{$fieldConfig.placeholder|escape:'htmlall':'UTF-8'}"{/if}
       {if isset($fieldConfig.maxLength) && $fieldConfig.maxLength}maxlength="{$fieldConfig.maxLength|intval}"{/if}>

{if $hasInputGroup}
    {if $hasAppend}
    <div class="input-group-append">
        <span class="input-group-text">{$fieldConfig.append|escape:'htmlall':'UTF-8'}</span>
    </div>
    {/if}
</div>
{/if}
