{**
 * ACF Field Partial: Number
 * Uses Bootstrap input-group for prefix/suffix
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
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
    {if $hasSuffix}
    <div class="input-group-append">
        <span class="input-group-text">{$fieldConfig.suffix|escape:'htmlall':'UTF-8'}</span>
    </div>
    {/if}
</div>
{/if}

{* Display constraints to user *}
{if isset($fieldConfig.min) || isset($fieldConfig.max) || isset($fieldConfig.step)}
<div class="form-text text-muted">
    {if isset($fieldConfig.min) && isset($fieldConfig.max)}
        {l s='Value must be between %min% and %max%' mod='wepresta_acf' sprintf=['%min%' => $fieldConfig.min, '%max%' => $fieldConfig.max]}
        {if isset($fieldConfig.step)} {l s='(step: %step%)' mod='wepresta_acf' sprintf=['%step%' => $fieldConfig.step]}{/if}
    {elseif isset($fieldConfig.min)}
        {l s='Minimum value: %min%' mod='wepresta_acf' sprintf=['%min%' => $fieldConfig.min]}
        {if isset($fieldConfig.step)} {l s='(step: %step%)' mod='wepresta_acf' sprintf=['%step%' => $fieldConfig.step]}{/if}
    {elseif isset($fieldConfig.max)}
        {l s='Maximum value: %max%' mod='wepresta_acf' sprintf=['%max%' => $fieldConfig.max]}
        {if isset($fieldConfig.step)} {l s='(step: %step%)' mod='wepresta_acf' sprintf=['%step%' => $fieldConfig.step]}{/if}
    {elseif isset($fieldConfig.step)}
        {l s='Increment step: %step%' mod='wepresta_acf' sprintf=['%step%' => $fieldConfig.step]}
    {/if}
</div>
{/if}
