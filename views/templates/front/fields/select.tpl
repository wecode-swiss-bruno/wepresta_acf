{**
 * ACF Select Field Template
 * 
 * Variables:
 *   $value - Field value (selected value)
 *   $field - Field definition array
 *   $config - Field configuration (contains choices)
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *   $lang_id - Current language ID (passed by renderer)
 *}

{if $value !== '' && $value !== null}
    {assign var="label" value=$value}
    {assign var="currentLangId" value=$lang_id|default:Context::getContext()->language->id}
    
    {* Find label from choices with translation support *}
    {if isset($config.choices) && is_array($config.choices)}
        {foreach $config.choices as $choice}
            {if is_array($choice) && isset($choice.value) && $choice.value === $value}
                {* Check for translation first *}
                {if isset($choice.translations) && isset($choice.translations[$currentLangId]) && $choice.translations[$currentLangId] !== ''}
                    {assign var="label" value=$choice.translations[$currentLangId]}
                {elseif isset($choice.translations) && isset($choice.translations["{$currentLangId}"]) && $choice.translations["{$currentLangId}"] !== ''}
                    {* Try with string key *}
                    {assign var="label" value=$choice.translations["{$currentLangId}"]}
                {elseif isset($choice.label) && $choice.label !== ''}
                    {assign var="label" value=$choice.label}
                {/if}
                {break}
            {/if}
        {/foreach}
    {/if}
    
    <span class="acf-select{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {$label|escape:'html':'UTF-8'}
    </span>
{/if}
