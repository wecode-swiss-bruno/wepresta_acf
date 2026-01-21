{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF Email Field Template
 * 
 * Variables:
 *   $value - Field value (email string)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    <a href="mailto:{$value|escape:'html':'UTF-8'}" 
       class="acf-email{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"
       {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {$value|escape:'html':'UTF-8'}
    </a>
{/if}
