{**
 * ACF URL Field Template
 * 
 * Variables:
 *   $value - Field value (URL string)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    {assign var="linkText" value=$config.linkText|default:$value}
    {assign var="target" value=""}
    {if isset($config.openInNewTab) && $config.openInNewTab}
        {assign var="target" value=' target="_blank" rel="noopener noreferrer"'}
    {/if}
    
    <a href="{$value|escape:'html':'UTF-8'}" 
       class="acf-link{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"
       {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}
       {$target nofilter}>
        {$linkText|escape:'html':'UTF-8'}
    </a>
{/if}
