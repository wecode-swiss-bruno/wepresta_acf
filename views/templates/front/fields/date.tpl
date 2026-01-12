{**
 * ACF Date Field Template
 * 
 * Variables:
 *   $value - Field value (date string)
 *   $field - Field definition array
 *   $config - Field configuration (displayFormat)
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value !== '' && $value !== null}
    {assign var="format" value=$config.displayFormat|default:'d/m/Y'}
    
    <time class="acf-date{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}" 
          datetime="{$value|escape:'html':'UTF-8'}"
          {if $customId}id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {$value|date_format:$format}
    </time>
{/if}
