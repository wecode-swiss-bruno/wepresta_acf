{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF Relation Field Template
 * 
 * Variables:
 *   $value - Field value (entity object or array of entities with id, name, link, reference, image)
 *   $field - Field definition array
 *   $config - Field configuration (entityType, multiple, displayFormat)
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *
 * displayFormat options:
 *   - name_only (default): Just the name
 *   - name_reference: Name + Reference
 *   - thumbnail_name: Thumbnail + Name
 *}

{if $value}
    {assign var="displayFormat" value=$config.displayFormat|default:'name_only'}
    
    {* Single relation *}
    {if is_array($value) && isset($value.id)}
        <div class="acf-relation acf-relation--{$displayFormat}{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
            {if $displayFormat == 'thumbnail_name' && isset($value.image) && $value.image}
                <img src="{$value.image|escape:'html':'UTF-8'}" alt="{$value.name|escape:'html':'UTF-8'}" class="acf-relation__image" loading="lazy">
            {/if}
            <div class="acf-relation__content">
                {if isset($value.link) && $value.link}
                    <a href="{$value.link|escape:'html':'UTF-8'}" class="acf-relation__link">
                        {$value.name|default:"#{$value.id}"|escape:'html':'UTF-8'}
                    </a>
                {else}
                    <span class="acf-relation__name">{$value.name|default:"#{$value.id}"|escape:'html':'UTF-8'}</span>
                {/if}
                {if $displayFormat == 'name_reference' && isset($value.reference) && $value.reference}
                    <span class="acf-relation__reference">({$value.reference|escape:'html':'UTF-8'})</span>
                {/if}
            </div>
        </div>
    
    {* Multiple relations *}
    {elseif is_array($value) && count($value) > 0}
        <ul class="acf-relations acf-relations--{$displayFormat}{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
            {foreach $value as $item}
                {if is_array($item) && isset($item.id)}
                    <li class="acf-relation-item">
                        {if $displayFormat == 'thumbnail_name' && isset($item.image) && $item.image}
                            <img src="{$item.image|escape:'html':'UTF-8'}" alt="{$item.name|escape:'html':'UTF-8'}" class="acf-relation__image" loading="lazy">
                        {/if}
                        <div class="acf-relation__content">
                            {if isset($item.link) && $item.link}
                                <a href="{$item.link|escape:'html':'UTF-8'}" class="acf-relation__link">
                                    {$item.name|default:"#{$item.id}"|escape:'html':'UTF-8'}
                                </a>
                            {else}
                                <span class="acf-relation__name">{$item.name|default:"#{$item.id}"|escape:'html':'UTF-8'}</span>
                            {/if}
                            {if $displayFormat == 'name_reference' && isset($item.reference) && $item.reference}
                                <span class="acf-relation__reference">({$item.reference|escape:'html':'UTF-8'})</span>
                            {/if}
                        </div>
                    </li>
                {/if}
            {/foreach}
        </ul>
    {/if}
{/if}
