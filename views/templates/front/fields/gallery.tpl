{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF Gallery Field Template
 * 
 * Variables:
 *   $value - Field value (array of images)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value && is_array($value) && count($value) > 0}
    <div class="acf-gallery{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {foreach $value as $item}
            {if is_array($item)}
                {assign var="imgUrl" value=$item.url|default:''}
                {assign var="imgAlt" value=$item.alt|default:$item.title|default:''}
            {else}
                {assign var="imgUrl" value=$item}
                {assign var="imgAlt" value=''}
            {/if}
            
            {if $imgUrl !== ''}
                <div class="acf-gallery-item">
                    <img src="{$imgUrl|escape:'html':'UTF-8'}" 
                         alt="{$imgAlt|escape:'html':'UTF-8'}"
                         loading="lazy">
                </div>
            {/if}
        {/foreach}
    </div>
{/if}
