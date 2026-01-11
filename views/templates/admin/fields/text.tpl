{**
 * ACF Field Partial: Text, Email, URL
 * Uses Bootstrap input-group for prefix/suffix
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputType" value=$context.inputType|default:'text'}
{assign var="hasPrefix" value=isset($fieldConfig.prefix) && $fieldConfig.prefix !== ''}
{assign var="hasSuffix" value=isset($fieldConfig.suffix) && $fieldConfig.suffix !== ''}
{assign var="hasInputGroup" value=$hasPrefix || $hasSuffix}

{if $hasInputGroup}
<div class="input-group{if isset($context.size) && $context.size === 'sm'} input-group-sm{/if}">
    {if $hasPrefix}
    <div class="input-group-prepend">
        <span class="input-group-text">{$fieldConfig.prefix|escape:'htmlall':'UTF-8'}</span>
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
       {if isset($context.dataLangId) && $context.dataLangId}data-lang-id="{$context.dataLangId|escape:'htmlall':'UTF-8'}"{/if}
       value="{$value|escape:'htmlall':'UTF-8'}"
       {if isset($fieldConfig.placeholder) && $fieldConfig.placeholder}placeholder="{$fieldConfig.placeholder|escape:'htmlall':'UTF-8'}"{/if}
       {if isset($fieldConfig.maxLength) && $fieldConfig.maxLength}maxlength="{$fieldConfig.maxLength|intval}"{/if}>

{if $hasInputGroup}
    {if $hasSuffix}
    <div class="input-group-append">
        <span class="input-group-text">{$fieldConfig.suffix|escape:'htmlall':'UTF-8'}</span>
    </div>
    {/if}
</div>
{/if}

{* Display maxLength constraint to user *}
{if isset($fieldConfig.maxLength) && $fieldConfig.maxLength}
<div class="form-text text-muted">
    {l s='Maximum %length% characters' mod='wepresta_acf' sprintf=['%length%' => $fieldConfig.maxLength]}
</div>
{/if}
