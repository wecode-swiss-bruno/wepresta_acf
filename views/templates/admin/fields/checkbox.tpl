{**
 * ACF Field Partial: Checkbox (Multiple)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="choices" value=$fieldConfig.choices|default:[]}
{assign var="selectedValues" value=$value}
{if !is_array($selectedValues)}
    {assign var="selectedValues" value=[]}
{/if}
{assign var="layout" value=$fieldConfig.layout|default:'vertical'}
{if $layout === 'horizontal'}
    {assign var="layoutClass" value='acf-checkbox-group-inline'}
{else}
    {assign var="layoutClass" value='acf-checkbox-group'}
{/if}

<div class="{$layoutClass}">
    {if is_array($choices) && count($choices) > 0}
        {foreach $choices as $idx => $choice}
            {assign var="choiceId" value="{$inputId}_{$idx}"}
            <div class="form-check{if $layout === 'horizontal'} form-check-inline{/if}">
                {if isset($context.dataSubfield) && $context.dataSubfield}
                    <input type="checkbox" 
                           class="form-check-input acf-subfield-checkbox"
                           data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
                           id="{$choiceId|escape:'htmlall':'UTF-8'}"
                           value="{$choice.value|escape:'htmlall':'UTF-8'}"
                           {if in_array($choice.value, $selectedValues)}checked{/if}>
                {else}
                    <input type="checkbox" 
                           class="form-check-input"
                           id="{$choiceId|escape:'htmlall':'UTF-8'}"
                           name="{$inputName|escape:'htmlall':'UTF-8'}[]"
                           value="{$choice.value|escape:'htmlall':'UTF-8'}"
                           {if in_array($choice.value, $selectedValues)}checked{/if}>
                {/if}
                <label class="form-check-label" for="{$choiceId|escape:'htmlall':'UTF-8'}">
                    {$choice.label|escape:'htmlall':'UTF-8'}
                </label>
            </div>
        {/foreach}
    {else}
        <p class="text-muted">{l s='No choices defined' mod='wepresta_acf'}</p>
    {/if}
</div>

