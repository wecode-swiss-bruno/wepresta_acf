{**
 * ACF Field Partial: Select
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="choices" value=$fieldConfig.choices|default:[]}

<select class="form-control{if isset($context.size) && $context.size === 'sm'} form-control-sm{/if}{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}"
        id="{$inputId|escape:'htmlall':'UTF-8'}"
        {if isset($fieldConfig.allowMultiple) && $fieldConfig.allowMultiple}multiple{/if}
        {if isset($context.dataSubfield) && $context.dataSubfield}
            data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
        {else}
            name="{$inputName|escape:'htmlall':'UTF-8'}"
        {/if}>
    <option value="">-- {l s='Select' mod='wepresta_acf'} --</option>
    {if is_array($choices)}
        {foreach $choices as $choice}
            <option value="{$choice.value|escape:'htmlall':'UTF-8'}"
                    {if $value == $choice.value}selected{/if}>
                {$choice.label|escape:'htmlall':'UTF-8'}
            </option>
        {/foreach}
    {/if}
</select>

