{**
 * ACF Field Partial: Boolean (Toggle Switch)
 * Uses native PrestaShop ps-switch component
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="yesLabel" value=$fieldConfig.trueLabel|default:'Oui'}
{assign var="noLabel" value=$fieldConfig.falseLabel|default:'Non'}

{if isset($context.dataSubfield) && $context.dataSubfield}
    {* Compact version for repeater subfields *}
    <div class="custom-control custom-switch">
        <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}" value="0">
        <input type="checkbox"
               class="custom-control-input acf-subfield-input"
               data-subfield="{$field.slug|escape:'htmlall':'UTF-8'}"
               id="{$inputId|escape:'htmlall':'UTF-8'}"
               name="{$inputName|escape:'htmlall':'UTF-8'}"
               value="1"
               {if $value}checked{/if}>
        <label class="custom-control-label" for="{$inputId|escape:'htmlall':'UTF-8'}"></label>
    </div>
{else}
    {* Native PrestaShop toggle switch - value is 1 or 0 from PHP *}
    <div class="d-flex align-items-center gap-2">
        <span class="ps-switch ps-switch-lg" id="{$inputId|escape:'htmlall':'UTF-8'}_container" data-initial-value="{$value}">
            <input type="radio"
                   class="ps-switch"
                   name="{$inputName|escape:'htmlall':'UTF-8'}"
                   id="{$inputId|escape:'htmlall':'UTF-8'}_off"
                   value="0"{if $value == 0} checked="checked"{/if}>
            <label for="{$inputId|escape:'htmlall':'UTF-8'}_off">{$noLabel|escape:'htmlall':'UTF-8'}</label>
            <input type="radio"
                   class="ps-switch"
                   name="{$inputName|escape:'htmlall':'UTF-8'}"
                   id="{$inputId|escape:'htmlall':'UTF-8'}_on"
                   value="1"{if $value == 1} checked="checked"{/if}>
            <label for="{$inputId|escape:'htmlall':'UTF-8'}_on">{$yesLabel|escape:'htmlall':'UTF-8'}</label>
            <span class="slide-button"></span>
        </span>
        <small class="text-muted acf-boolean-status">
            <span class="badge badge-{if $value == 1}success{else}secondary{/if}">
                {if $value == 1}{$yesLabel|escape:'htmlall':'UTF-8'}{else}{$noLabel|escape:'htmlall':'UTF-8'}{/if}
            </span>
        </small>
    </div>
{/if}
