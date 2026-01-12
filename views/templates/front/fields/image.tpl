{**
 * ACF Image Field Template
 * 
 * Variables:
 *   $value - Field value (URL string or array with url, alt, title)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value}
    {if is_array($value)}
        {assign var="imgUrl" value=$value.url|default:''}
        {assign var="imgAlt" value=$value.alt|default:$value.title|default:''}
        {assign var="imgTitle" value=$value.title|default:''}
    {else}
        {assign var="imgUrl" value=$value}
        {assign var="imgAlt" value=''}
        {assign var="imgTitle" value=''}
    {/if}
    
    {if $imgUrl !== ''}
        <img src="{$imgUrl|escape:'html':'UTF-8'}" 
             alt="{$imgAlt|escape:'html':'UTF-8'}"
             {if $imgTitle}title="{$imgTitle|escape:'html':'UTF-8'}"{/if}
             class="acf-image{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"
             {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}
             {if isset($config.width)}width="{$config.width|intval}"{/if}
             {if isset($config.height)}height="{$config.height|intval}"{/if}
             loading="lazy">
    {/if}
{/if}
