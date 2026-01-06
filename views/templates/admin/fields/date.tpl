{**
 * ACF Field Partial: Date
 * Uses HTML5 date with sensible year constraints
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{* Default min/max to reasonable years (1900-2100) unless specified *}
{assign var="minDate" value=$fieldConfig.minDate|default:'1900-01-01'}
{assign var="maxDate" value=$fieldConfig.maxDate|default:'2100-12-31'}

<input type="date"
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

{* Display date constraints to user *}
{if isset($fieldConfig.minDate) && $fieldConfig.minDate !== '1900-01-01' || isset($fieldConfig.maxDate) && $fieldConfig.maxDate !== '2100-12-31'}
<div class="form-text text-muted">
    {if isset($fieldConfig.minDate) && $fieldConfig.minDate !== '1900-01-01' && isset($fieldConfig.maxDate) && $fieldConfig.maxDate !== '2100-12-31'}
        {l s='Date must be between %min% and %max%' mod='wepresta_acf' sprintf=['%min%' => $fieldConfig.minDate, '%max%' => $fieldConfig.maxDate]}
    {elseif isset($fieldConfig.minDate) && $fieldConfig.minDate !== '1900-01-01'}
        {l s='Minimum date: %min%' mod='wepresta_acf' sprintf=['%min%' => $fieldConfig.minDate]}
    {elseif isset($fieldConfig.maxDate) && $fieldConfig.maxDate !== '2100-12-31'}
        {l s='Maximum date: %max%' mod='wepresta_acf' sprintf=['%max%' => $fieldConfig.maxDate]}
    {/if}
</div>
{/if}
