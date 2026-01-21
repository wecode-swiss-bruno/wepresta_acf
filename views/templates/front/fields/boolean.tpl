{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF Boolean Field Template
 * 
 * Variables:
 *   $value - Field value (boolean or truthy value)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{assign var="boolValue" value=($value && $value !== '0' && $value !== 'false')}

<span class="acf-boolean acf-boolean--{if $boolValue}true{else}false{/if}{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
    {if $boolValue}
        {if isset($config.trueLabel)}{$config.trueLabel|escape:'html':'UTF-8'}{else}✓{/if}
    {else}
        {if isset($config.falseLabel)}{$config.falseLabel|escape:'html':'UTF-8'}{else}✗{/if}
    {/if}
</span>
