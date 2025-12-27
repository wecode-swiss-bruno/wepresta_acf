{**
 * ACF Field Partial: URL Input
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="placeholder" value=$fieldConfig.placeholder|default:'https://example.com'}

<div class="input-group">
    <span class="input-group-text"><span class="material-icons" style="font-size: 18px;">link</span></span>
    <input type="url" 
           class="form-control{if isset($context.dataSubfield) && $context.dataSubfield} acf-subfield-input{/if}" 
           id="{$inputId|escape:'htmlall':'UTF-8'}" 
           name="{$inputName|escape:'htmlall':'UTF-8'}" 
           value="{$value|escape:'htmlall':'UTF-8'}"
           placeholder="{$placeholder|escape:'htmlall':'UTF-8'}"
           {if isset($context.dataSubfield) && $context.dataSubfield}data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"{/if}>
    {if $value}
        <a href="{$value|escape:'htmlall':'UTF-8'}" target="_blank" class="btn btn-outline-secondary" title="{l s='Open URL' mod='wepresta_acf'}">
            <span class="material-icons" style="font-size: 18px;">open_in_new</span>
        </a>
    {/if}
</div>

