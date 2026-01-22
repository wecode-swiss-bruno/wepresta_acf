{**
* WePresta ACF - Advanced Custom Fields for PrestaShop
*
* @author WePresta
* @copyright 2024-2025 WePresta
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
*}

{**
* ACF Field Wrapper Template
*
* Wraps field content with title, instructions, and CSS classes.
*
* Variables:
* $innerHtml - Rendered field HTML
* $field - Field definition array
* $showTitle - Whether to show field title
* $title - Field title
* $instructions - Field instructions
* $type - Field type
* $slug - Field slug
* $customClass - Custom CSS class
* $customId - Custom HTML ID
*}

{if $innerHtml !== ''}
<div class="acf-field-wrapper acf-field-wrapper--{$type|escape:'html':'UTF-8'}{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"
    data-field="{$slug|escape:'html':'UTF-8'}" {if $customId}id="{$customId|escape:'html':'UTF-8'}" {/if}>

    {if $showTitle && $title !== ''}
    <label class="acf-field-label">{$title|escape:'html':'UTF-8'}</label>
    {/if}

    <div class="acf-field-value">
        {$innerHtml}
    </div>

    {if $instructions !== ''}
    <p class="acf-field-instructions">{$instructions|escape:'html':'UTF-8'}</p>
    {/if}
</div>
{/if}