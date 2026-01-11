{**
 * ACF Field Partial: Select
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{* Add [] to name for multiple select to receive array in POST *}
{if isset($fieldConfig.allowMultiple) && $fieldConfig.allowMultiple}
    {assign var="inputName" value="{$inputName}[]"}
{/if}
{assign var="choices" value=$fieldConfig.choices|default:[]}

{* Function to get translated choice label *}
{function getChoiceLabel choice=[] currentLangId=''}
    {if isset($choice.translations) && isset($choice.translations[$currentLangId]) && !empty($choice.translations[$currentLangId])}
        {$choice.translations[$currentLangId]|escape:'htmlall':'UTF-8'}
    {elseif !empty($choice.label)}
        {$choice.label|escape:'htmlall':'UTF-8'}
    {else}
        {$choice.value|escape:'htmlall':'UTF-8'}
    {/if}
{/function}
{* Decode JSON value if it's a string (for multiple selections) *}
{assign var="displayValue" value=$value}
{if isset($fieldConfig.allowMultiple) && $fieldConfig.allowMultiple && is_string($value) && $value}
    {assign var="displayValue" value=$value|json_decode:true}
{/if}

<select class="form-control{if isset($context.size) && $context.size === 'sm'} form-control-sm{/if}{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}"
        id="{$inputId|escape:'htmlall':'UTF-8'}"
        {if isset($fieldConfig.allowMultiple) && $fieldConfig.allowMultiple}multiple{/if}
        {if isset($context.dataSubfield) && $context.dataSubfield}
            data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
        {else}
            name="{$inputName|escape:'htmlall':'UTF-8'}"
        {/if}
        {if isset($context.dataLangId) && $context.dataLangId}data-lang-id="{$context.dataLangId|escape:'htmlall':'UTF-8'}"{/if}>
    <option value="">-- {l s='Select' mod='wepresta_acf'} --</option>
    {if is_array($choices)}
        {foreach $choices as $choice}
            {* Check if value matches (single) or is in array (multiple) *}
            {assign var="isSelected" value=false}
            {if isset($fieldConfig.allowMultiple) && $fieldConfig.allowMultiple}
                {if is_array($displayValue) && in_array($choice.value, $displayValue)}
                    {assign var="isSelected" value=true}
                {/if}
            {else}
                {if $displayValue == $choice.value}
                    {assign var="isSelected" value=true}
                {/if}
            {/if}
            <option value="{$choice.value|escape:'htmlall':'UTF-8'}"
                    {if $isSelected}selected{/if}>
                {getChoiceLabel choice=$choice currentLangId=$currentLangId}
            </option>
        {/foreach}
    {/if}
</select>

