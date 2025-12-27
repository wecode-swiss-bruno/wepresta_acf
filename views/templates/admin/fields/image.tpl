{**
 * ACF Field Partial: Image Upload
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 * Optional: $base_url, $product_link, $product_attachments
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}

<div class="acf-image-field" data-type="image" data-slug="{$field.slug|escape:'htmlall':'UTF-8'}">
    {* Existing image preview *}
    {if $value && is_array($value) && isset($value.url)}
        <div class="card mb-3 acf-image-preview" id="preview_{$inputId|escape:'htmlall':'UTF-8'}">
            <div class="row g-0">
                <div class="col-auto">
                    <img src="{$value.url|escape:'htmlall':'UTF-8'}" 
                         class="img-thumbnail" 
                         alt="{$value.original_name|default:'Image'|escape:'htmlall':'UTF-8'}"
                         style="width: 120px; height: 120px; object-fit: cover; cursor: pointer;"
                         data-lightbox="{$value.url|escape:'htmlall':'UTF-8'}">
                </div>
                <div class="col">
                    <div class="card-body py-2">
                        <h6 class="card-title mb-1 text-truncate" title="{$value.original_name|escape:'htmlall':'UTF-8'}">
                            {$value.original_name|escape:'htmlall':'UTF-8'}
                        </h6>
                        <p class="card-text text-muted small mb-2">
                            {if isset($value.size)}
                                {assign var="fileSize" value=$value.size}
                                {if $fileSize > 1048576}{($fileSize/1048576)|string_format:"%.1f"} MB{elseif $fileSize > 1024}{($fileSize/1024)|string_format:"%.0f"} KB{else}{$fileSize} bytes{/if}
                            {/if}
                        </p>
                        
                        {* URL copy row *}
                        {assign var="fullImgUrl" value="{$base_url}{$value.url}"}
                        <div class="input-group input-group-sm mb-2">
                            <input type="text" class="form-control form-control-sm font-monospace" 
                                   value="{$fullImgUrl|escape:'htmlall':'UTF-8'}" readonly style="font-size: 11px;">
                            <button type="button" class="btn btn-outline-secondary" title="{l s='Copy URL' mod='wepresta_acf'}" 
                                    data-copy="{$fullImgUrl|escape:'htmlall':'UTF-8'}">
                                <span class="material-icons" style="font-size: 14px;">content_copy</span>
                            </button>
                        </div>
                        
                        {* Actions *}
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                data-delete-toggle="preview_{$inputId|escape:'htmlall':'UTF-8'}">
                            <span class="material-icons" style="font-size: 14px;">delete</span> {l s='Remove' mod='wepresta_acf'}
                        </button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}_delete" value="0">
        </div>
    {/if}
    
    {* Title field if enabled *}
    {if isset($fieldConfig.enableTitle) && $fieldConfig.enableTitle}
        <div class="mb-2">
            <label class="form-label small" for="{$inputId|escape:'htmlall':'UTF-8'}_title">{l s='Alt Text / Title' mod='wepresta_acf'}</label>
            <input type="text" class="form-control form-control-sm" id="{$inputId|escape:'htmlall':'UTF-8'}_title" 
                   name="{$inputName|escape:'htmlall':'UTF-8'}_title" 
                   value="{if isset($value.title)}{$value.title|escape:'htmlall':'UTF-8'}{/if}" 
                   placeholder="{l s='Image alt text' mod='wepresta_acf'}">
        </div>
    {/if}
    
    {* Input method configuration *}
    {assign var="imgAllowUpload" value=(!isset($fieldConfig.allowUpload) || $fieldConfig.allowUpload)}
    {assign var="imgAllowImport" value=(isset($fieldConfig.allowUrlImport) && $fieldConfig.allowUrlImport)}
    {assign var="imgAllowLink" value=(isset($fieldConfig.allowUrlLink) && $fieldConfig.allowUrlLink)}
    {assign var="imgAllowAttachment" value=(isset($fieldConfig.allowAttachment) && $fieldConfig.allowAttachment)}
    {assign var="imgMethodCount" value=0}
    {if $imgAllowUpload}{assign var="imgMethodCount" value=$imgMethodCount+1}{/if}
    {if $imgAllowImport}{assign var="imgMethodCount" value=$imgMethodCount+1}{/if}
    {if $imgAllowLink}{assign var="imgMethodCount" value=$imgMethodCount+1}{/if}
    {if $imgAllowAttachment}{assign var="imgMethodCount" value=$imgMethodCount+1}{/if}
    
    {assign var="imgDefaultMethod" value=$fieldConfig.defaultInputMethod|default:'upload'}
    {if $imgDefaultMethod === 'upload' && !$imgAllowUpload}
        {if $imgAllowImport}{assign var="imgDefaultMethod" value='import'}{elseif $imgAllowLink}{assign var="imgDefaultMethod" value='link'}{elseif $imgAllowAttachment}{assign var="imgDefaultMethod" value='attachment'}{/if}
    {/if}
    
    {* Input method tabs - Bootstrap nav-tabs (BS4 + BS5 compatible) *}
    {if $imgMethodCount > 1}
        <ul class="nav nav-tabs nav-tabs-sm mb-2" role="tablist">
            {if $imgAllowUpload}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2{if $imgDefaultMethod === 'upload'} active{/if}" type="button" 
                            data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_upload" data-bs-target="#tab_{$inputId}_upload">
                        <span class="material-icons" style="font-size: 14px;">cloud_upload</span> {l s='Upload' mod='wepresta_acf'}
                    </button>
                </li>
            {/if}
            {if $imgAllowImport}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2{if $imgDefaultMethod === 'import'} active{/if}" type="button" 
                            data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_import" data-bs-target="#tab_{$inputId}_import">
                        <span class="material-icons" style="font-size: 14px;">download</span> {l s='Import' mod='wepresta_acf'}
                    </button>
                </li>
            {/if}
            {if $imgAllowLink}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2{if $imgDefaultMethod === 'link'} active{/if}" type="button" 
                            data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_link" data-bs-target="#tab_{$inputId}_link">
                        <span class="material-icons" style="font-size: 14px;">link</span> {l s='URL' mod='wepresta_acf'}
                    </button>
                </li>
            {/if}
            {if $imgAllowAttachment}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2{if $imgDefaultMethod === 'attachment'} active{/if}" type="button" 
                            data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_attachment" data-bs-target="#tab_{$inputId}_attachment">
                        <span class="material-icons" style="font-size: 14px;">attach_file</span> {l s='Attachment' mod='wepresta_acf'}
                    </button>
                </li>
            {/if}
        </ul>
    {/if}
    
    <div class="tab-content">
        {* Upload panel *}
        {if $imgAllowUpload}
            <div class="{if $imgMethodCount > 1}tab-pane fade{if $imgDefaultMethod === 'upload'} show active{/if}{/if}" id="tab_{$inputId}_upload">
                <div class="dz-default dz-message openfilemanager acf-dropzone border rounded p-3 text-center bg-light position-relative">
                    <input type="file" class="form-control position-absolute top-0 start-0 w-100 h-100 opacity-0" 
                           style="cursor: pointer; z-index: 2;"
                           id="{$inputId|escape:'htmlall':'UTF-8'}" name="{$inputName|escape:'htmlall':'UTF-8'}" 
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="py-2">
                        <span class="material-icons text-muted mb-1" style="font-size: 32px;">add_photo_alternate</span>
                        <div class="small text-muted">{l s='Drop image or click to upload' mod='wepresta_acf'}</div>
                        <div class="small text-muted opacity-75">JPG, PNG, GIF, WebP</div>
                    </div>
                </div>
            </div>
        {/if}
        
        {* Import URL panel *}
        {if $imgAllowImport}
            <div class="{if $imgMethodCount > 1}tab-pane fade{if $imgDefaultMethod === 'import'} show active{/if}{/if}" id="tab_{$inputId}_import">
                <div class="input-group">
                    <input type="url" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_url" 
                           placeholder="https://example.com/image.jpg">
                    <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}_url_mode" value="import">
                </div>
                <small class="form-text text-muted">
                    <span class="material-icons" style="font-size: 12px;">download</span> {l s='Image will be downloaded to server' mod='wepresta_acf'}
                </small>
            </div>
        {/if}
        
        {* External Link panel *}
        {if $imgAllowLink}
            <div class="{if $imgMethodCount > 1}tab-pane fade{if $imgDefaultMethod === 'link'} show active{/if}{/if}" id="tab_{$inputId}_link">
                <div class="input-group">
                    <input type="url" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_link_url" 
                           placeholder="https://example.com/image.jpg">
                    <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}_link_mode" value="link">
                </div>
                <small class="form-text text-muted">
                    <span class="material-icons" style="font-size: 12px;">link</span> {l s='Image stays on external server' mod='wepresta_acf'}
                </small>
            </div>
        {/if}
        
        {* Attachment picker *}
        {if $imgAllowAttachment}
            <div class="{if $imgMethodCount > 1}tab-pane fade{if $imgDefaultMethod === 'attachment'} show active{/if}{/if}" id="tab_{$inputId}_attachment">
                {if isset($product_attachments) && $product_attachments|@count > 0}
                    <select class="form-control custom-select" name="{$inputName|escape:'htmlall':'UTF-8'}_attachment">
                        <option value="">-- {l s='Select attachment' mod='wepresta_acf'} --</option>
                        {foreach $product_attachments as $attachment}
                            {if strpos($attachment.mime, 'image/') === 0}
                                <option value="{$attachment.id}">{$attachment.name|escape:'htmlall':'UTF-8'}</option>
                            {/if}
                        {/foreach}
                    </select>
                {else}
                    <div class="alert alert-warning mb-0 py-2">
                        <span class="material-icons" style="font-size: 14px;">info</span> {l s='No image attachments' mod='wepresta_acf'}
                    </div>
                {/if}
            </div>
        {/if}
    </div>
</div>
