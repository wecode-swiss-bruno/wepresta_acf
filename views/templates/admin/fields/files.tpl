{**
 * ACF Field Partial: Files (Multiple Files)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="filesEnableTitle" value=$fieldConfig.enableTitle|default:false}
{assign var="filesEnableDescription" value=$fieldConfig.enableDescription|default:false}

<div class="acf-files-field" data-type="files" data-slug="{$field.slug|escape:'htmlall':'UTF-8'}">
    {* Files List *}
    <ul class="list-group mb-3" id="acf_files_{$field.slug|escape:'htmlall':'UTF-8'}">
        {if $value && is_array($value)}
            {foreach $value as $idx => $item}
                <li class="list-group-item acf-files-item" data-index="{$idx}" draggable="true">
                    <div class="d-flex align-items-center gap-2">
                        {* Drag handle *}
                        <span class="text-muted" style="cursor: grab;">
                            <span class="material-icons" style="font-size: 18px;">drag_indicator</span>
                        </span>
                        
                        {* File icon *}
                        <span class="material-icons text-secondary">description</span>
                        
                        {* File info *}
                        <div class="flex-grow-1 text-truncate">
                            <span class="fw-medium">{$item.original_name|escape:'htmlall':'UTF-8'}</span>
                            {if isset($item.size)}
                                <small class="text-muted ms-2">({($item.size / 1024)|round:0} KB)</small>
                            {/if}
                        </div>
                        
                        {* Title input *}
                        {if $filesEnableTitle}
                            <input type="text" class="form-control form-control-sm" style="max-width: 150px;"
                                   placeholder="{l s='Title' mod='wepresta_acf'}" 
                                   value="{$item.title|default:''|escape:'htmlall':'UTF-8'}"
                                   name="{$inputName|escape:'htmlall':'UTF-8'}_title[{$idx}]">
                        {/if}
                        
                        {* Description input *}
                        {if $filesEnableDescription}
                            <input type="text" class="form-control form-control-sm" style="max-width: 200px;"
                                   placeholder="{l s='Description' mod='wepresta_acf'}" 
                                   value="{$item.description|default:''|escape:'htmlall':'UTF-8'}"
                                   name="{$inputName|escape:'htmlall':'UTF-8'}_desc[{$idx}]">
                        {/if}
                        
                        {* Delete button *}
                        <button type="button" class="btn btn-outline-danger btn-sm acf-files-remove" data-index="{$idx}">
                            <span class="material-icons" style="font-size: 16px;">delete</span>
                        </button>
                    </div>
                    <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}_items[]" 
                           class="acf-item-data" value="{$item|json_encode|escape:'htmlall':'UTF-8'}">
                </li>
            {/foreach}
        {else}
            <li class="list-group-item text-muted text-center py-3 acf-files-empty">
                <span class="material-icons" style="font-size: 24px; opacity: 0.5;">folder_open</span>
                <div>{l s='No files uploaded' mod='wepresta_acf'}</div>
            </li>
        {/if}
    </ul>
    
    {* Pending uploads preview *}
    <div class="acf-files-pending alert alert-info d-none mb-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="material-icons">schedule</span>
            <strong>{l s='Pending upload' mod='wepresta_acf'}</strong>
        </div>
        <ul class="list-group list-group-flush acf-files-pending-items"></ul>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 acf-files-pending-clear">
            <span class="material-icons" style="font-size: 14px;">close</span> {l s='Clear' mod='wepresta_acf'}
        </button>
    </div>
    
    {* Add files input *}
    <div class="input-group">
        <input type="file" 
               class="form-control acf-files-input" 
               name="{$inputName|escape:'htmlall':'UTF-8'}_new[]"
               multiple>
        <span class="input-group-text">
            <span class="material-icons" style="font-size: 18px;">attach_file</span>
        </span>
    </div>
    <small class="form-text text-muted">{l s='Select files to upload. Drag to reorder.' mod='wepresta_acf'}</small>
</div>
