{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF Repeater Field Template
 * 
 * Note: Repeaters should generally be iterated using $acf->repeater() or {acf_foreach}
 * This template is a fallback that shows a message.
 * 
 * Variables:
 *   $value - Field value (array of rows)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value && is_array($value) && count($value) > 0}
    <div class="acf-repeater{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}" 
         data-rows="{$value|count}"
         {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {* 
         * To display repeater content, use:
         * 
         * {foreach $acf->repeater('field_slug') as $row}
         *     {$row.subfield_slug}
         * {/foreach}
         * 
         * Or with {acf_foreach}:
         * 
         * {acf_foreach repeater="field_slug" item="row"}
         *     {$row.subfield_slug}
         * {/acf_foreach}
         *}
        <p class="acf-repeater-notice">{$value|count} items</p>
    </div>
{/if}
