{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF Default Field Template
 * 
 * Fallback template for unknown field types.
 * 
 * Variables:
 *   $value - Field value
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $type - Field type
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    <span class="acf-field acf-field--{$type|escape:'html':'UTF-8'}{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {if is_array($value)}
            {$value|json_encode}
        {else}
            {$value|escape:'html':'UTF-8'}
        {/if}
    </span>
{/if}
