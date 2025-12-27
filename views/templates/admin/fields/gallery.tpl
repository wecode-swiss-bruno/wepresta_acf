{**
 * ACF Field Partial: Gallery (Multiple Images)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="galEnableTitle" value=$fieldConfig.enableTitle|default:false}

<div class="acf-gallery-field" data-type="gallery" data-slug="{$field.slug|escape:'htmlall':'UTF-8'}">
    {* Gallery Grid *}
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-2 mb-3" id="acf_gallery_{$field.slug|escape:'htmlall':'UTF-8'}">
        {if $value && is_array($value)}
            {foreach $value as $idx => $item}
                <div class="col acf-gallery-item" data-index="{$idx}" draggable="true">
                    <div class="card h-100 border">
                        <div class="position-relative">
                            <img src="{$item.url|escape:'htmlall':'UTF-8'}" 
                                 class="card-img-top" 
                                 alt="{$item.title|default:$item.original_name|escape:'htmlall':'UTF-8'}"
                                 style="height: 80px; object-fit: cover; cursor: pointer;"
                                 data-lightbox="{$item.url|escape:'htmlall':'UTF-8'}">
                            <span class="position-absolute top-0 start-0 m-1 text-muted" style="cursor: grab;">
                                <span class="material-icons" style="font-size: 16px;">drag_indicator</span>
                            </span>
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 acf-gallery-remove" 
                                    data-index="{$idx}" style="width: 20px; height: 20px; line-height: 1;">
                                <span class="material-icons" style="font-size: 14px;">close</span>
                            </button>
                        </div>
                        <div class="card-body p-2">
                            <small class="text-truncate d-block text-muted" title="{$item.original_name|escape:'htmlall':'UTF-8'}">
                                {$item.original_name|truncate:15:'...'|escape:'htmlall':'UTF-8'}
                            </small>
                            {if isset($item.size)}
                                <small class="text-muted">{($item.size / 1024)|round:0} KB</small>
                            {/if}
                            {if $galEnableTitle}
                                <input type="text" class="form-control form-control-sm mt-1" 
                                       placeholder="{l s='Title' mod='wepresta_acf'}" 
                                       value="{$item.title|default:''|escape:'htmlall':'UTF-8'}"
                                       name="{$inputName|escape:'htmlall':'UTF-8'}_title[{$idx}]">
                            {/if}
                        </div>
                        <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}_items[]" 
                               class="acf-item-data" value="{$item|json_encode|escape:'htmlall':'UTF-8'}">
                    </div>
                </div>
            {/foreach}
        {/if}
    </div>
    
    {* Pending uploads preview *}
    <div class="acf-gallery-pending alert alert-info d-none mb-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="material-icons">schedule</span>
            <strong>{l s='Pending upload' mod='wepresta_acf'}</strong>
        </div>
        <div class="row row-cols-4 g-2 acf-gallery-pending-items"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 acf-gallery-pending-clear">
            <span class="material-icons" style="font-size: 14px;">close</span> {l s='Clear' mod='wepresta_acf'}
        </button>
    </div>
    
    {* Add images input *}
    <div class="input-group">
        <input type="file"
               class="form-control acf-gallery-input"
               name="{$inputName|escape:'htmlall':'UTF-8'}_new[]"
               accept="image/*"
               multiple>
        <span class="input-group-text">
            <span class="material-icons" style="font-size: 18px;">add_photo_alternate</span>
        </span>
    </div>
    <small class="form-text text-muted">{l s='Select multiple images. Drag to reorder.' mod='wepresta_acf'}</small>
</div>
