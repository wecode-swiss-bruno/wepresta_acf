{**
 * ACF Color Field Template
 * 
 * Variables:
 *   $value - Field value (color hex string)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    <span class="acf-color{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}" 
          style="background-color: {$value|escape:'html':'UTF-8'};"
          title="{$value|escape:'html':'UTF-8'}"
          {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}>
    </span>
{/if}
