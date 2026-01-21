{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF List Field Template
 * 
 * Variables:
 *   $value - Field value (array of strings)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value && is_array($value) && count($value) > 0}
    <ul class="acf-list{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {foreach $value as $item}
            <li class="acf-list-item">{$item|escape:'html':'UTF-8'}</li>
        {/foreach}
    </ul>
{/if}
