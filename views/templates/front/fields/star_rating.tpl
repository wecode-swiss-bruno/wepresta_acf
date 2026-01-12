{**
 * ACF Star Rating Field Template
 * 
 * Variables:
 *   $value - Field value (rating number)
 *   $field - Field definition array
 *   $config - Field configuration (max)
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    {assign var="rating" value=$value|floatval}
    {assign var="max" value=$config.max|default:5|intval}
    
    <span class="acf-star-rating{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}" 
          title="{$rating}/{$max}"
          {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {for $i=1 to $max}
            {if $i <= $rating}
                <span class="acf-star acf-star--filled">★</span>
            {else}
                <span class="acf-star acf-star--empty">☆</span>
            {/if}
        {/for}
    </span>
{/if}
