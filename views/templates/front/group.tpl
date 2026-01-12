{**
 * ACF Group Template
 * 
 * Renders all fields from a group.
 * 
 * Variables:
 *   $fields - Array of field data with rendered values
 *   $options - Render options
 *}

{if $fields && count($fields) > 0}
    <div class="acf-group">
        {foreach $fields as $field}
            {if $field.has_value}
                <div class="acf-group-field acf-group-field--{$field.type|escape:'html':'UTF-8'}" data-field="{$field.slug|escape:'html':'UTF-8'}">
                    {if isset($options.showTitles) && $options.showTitles && $field.title !== ''}
                        <label class="acf-field-label">{$field.title|escape:'html':'UTF-8'}</label>
                    {/if}
                    
                    <div class="acf-field-content">
                        {$field.rendered nofilter}
                    </div>
                </div>
            {/if}
        {/foreach}
    </div>
{/if}
