{**
 * ACF Time Field Template
 * 
 * Variables:
 *   $value - Field value (time string)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    <time class="acf-time{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"
          {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {$value|escape:'html':'UTF-8'}
    </time>
{/if}
