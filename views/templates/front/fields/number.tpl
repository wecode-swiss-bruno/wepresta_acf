{**
 * ACF Number Field Template
 * 
 * Variables:
 *   $value - Field value (number)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    <span class="acf-number{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {if isset($config.prefix)}{$config.prefix|escape:'html':'UTF-8'}{/if}
        {$value|escape:'html':'UTF-8'}
        {if isset($config.suffix)}{$config.suffix|escape:'html':'UTF-8'}{/if}
    </span>
{/if}
