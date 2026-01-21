{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF DateTime Field Template
 * 
 * Variables:
 *   $value - Field value (datetime string)
 *   $field - Field definition array
 *   $config - Field configuration (displayFormat)
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    {assign var="format" value=$config.displayFormat|default:'d/m/Y H:i'}
    
    <time class="acf-datetime{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}" 
          datetime="{$value|escape:'html':'UTF-8'}"
          {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {$value|date_format:$format}
    </time>
{/if}
