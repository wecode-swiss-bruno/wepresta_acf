{**
 * ACF File Field Template
 * 
 * Variables:
 *   $value - Field value (URL string or array with url, title, original_name)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value}
    {if is_array($value)}
        {assign var="fileUrl" value=$value.url|default:''}
        {assign var="fileName" value=$value.title|default:$value.original_name|default:'Download'}
    {else}
        {assign var="fileUrl" value=$value}
        {assign var="fileName" value='Download'}
    {/if}
    
    {if $fileUrl !== ''}
        <a href="{$fileUrl|escape:'html':'UTF-8'}" 
           class="acf-file{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"
           {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}
           download>
            <span class="acf-file-icon">📄</span>
            <span class="acf-file-name">{$fileName|escape:'html':'UTF-8'}</span>
        </a>
    {/if}
{/if}
