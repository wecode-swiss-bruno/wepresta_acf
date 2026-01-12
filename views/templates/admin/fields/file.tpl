{**
 * ACF Field Partial: File Upload
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 * Optional: $base_url, $product_link, $product_attachments
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}

<div class="acf-media-upload acf-file-upload" data-type="file" data-slug="{$field.slug|escape:'htmlall':'UTF-8'}">
    {if $value && is_array($value) && isset($value.url)}
        {assign var="isExternalLink" value=(isset($value.source_type) && $value.source_type === 'link')}
        <div class="acf-media-preview acf-file-preview{if $isExternalLink} acf-external-file{/if}">
            <div class="acf-file-card">
                <div class="acf-file-icon">
                    <span class="material-icons">{if $isExternalLink}link{else}description{/if}</span>
                </div>
                <div class="acf-file-info">
                    <a href="{$value.url|escape:'htmlall':'UTF-8'}" target="_blank" class="acf-file-name" title="{$value.original_name|escape:'htmlall':'UTF-8'}">
                        {$value.original_name|escape:'htmlall':'UTF-8'}
                    </a>
                    <span class="acf-file-meta">
                        {if $isExternalLink}
                            <span class="acf-source-badge acf-source-link">{l s='External link' mod='wepresta_acf'}</span>
                        {elseif isset($value.source_type) && $value.source_type === 'import'}
                            <span class="acf-source-badge acf-source-import">{l s='Imported from URL' mod='wepresta_acf'}</span>
                        {/if}
                        {if isset($value.size) && $value.size}
                            {assign var="fileSize" value=$value.size}
                            {if $fileSize > 1048576}
                                {($fileSize/1048576)|string_format:"%.1f"} MB
                            {elseif $fileSize > 1024}
                                {($fileSize/1024)|string_format:"%.0f"} KB
                            {else}
                                {$fileSize} bytes
                            {/if}
                        {/if}
                        {if isset($value.mime) && $value.mime}
                            · {$value.mime|escape:'htmlall':'UTF-8'}
                        {/if}
                    </span>
                    {if isset($value.source_url) && $value.source_url}
                    <span class="acf-source-url" title="{l s='Source URL' mod='wepresta_acf'}">
                        <span class="material-icons" style="font-size:12px;vertical-align:middle">link</span>
                        <small>{$value.source_url|truncate:50:'...'|escape:'htmlall':'UTF-8'}</small>
                    </span>
                    {/if}
                    {if $isExternalLink}
                        {assign var="fullUrl" value=$value.url}
                    {else}
                        {assign var="fullUrl" value="{$base_url}{$value.url}"}
                    {/if}
                    <div class="acf-url-row">
                        <input type="text" class="acf-url-input" value="{$fullUrl|escape:'htmlall':'UTF-8'}" readonly>
                        <button type="button" class="acf-btn acf-btn-copy" title="{l s='Copy URL' mod='wepresta_acf'}" data-url="{$fullUrl|escape:'htmlall':'UTF-8'}">
                            <span class="material-icons">content_copy</span>
                        </button>
                    </div>
                    {if isset($product_link) && $product_link}
                    {assign var="downloadUrl" value="{$product_link}?acf_download={$field.slug}"}
                    <div class="acf-url-row">
                        <input type="text" class="acf-url-input" value="{$downloadUrl|escape:'htmlall':'UTF-8'}" readonly title="{l s='Download link' mod='wepresta_acf'}">
                        <button type="button" class="acf-btn acf-btn-copy" title="{l s='Copy download link' mod='wepresta_acf'}" data-url="{$downloadUrl|escape:'htmlall':'UTF-8'}">
                            <span class="material-icons">content_copy</span>
                        </button>
                    </div>
                    {/if}
                </div>
                <div class="acf-file-actions">
                    <a href="{$fullUrl|escape:'htmlall':'UTF-8'}" target="_blank" class="acf-btn acf-btn-view" title="{l s='View file' mod='wepresta_acf'}">
                        <span class="material-icons">open_in_new</span>
                    </a>
                    <button type="button" class="acf-btn acf-btn-delete" title="{l s='Delete file' mod='wepresta_acf'}" data-field="{$inputId|escape:'htmlall':'UTF-8'}">
                        <span class="material-icons">delete</span>
                    </button>
                </div>
            </div>
            <input type="hidden" class="acf-delete-flag" id="{$inputId|escape:'htmlall':'UTF-8'}_delete" name="{$inputName|escape:'htmlall':'UTF-8'}_delete" value="0">
        </div>
    {/if}

    {* Title field if enabled *}
    {if isset($fieldConfig.enableTitle) && $fieldConfig.enableTitle}
    <div class="acf-media-meta-field">
        <label class="form-control-label" for="{$inputId|escape:'htmlall':'UTF-8'}_title">{l s='File Title' mod='wepresta_acf'}</label>
        <input type="text" class="form-control form-control-sm" id="{$inputId|escape:'htmlall':'UTF-8'}_title" name="{$inputName|escape:'htmlall':'UTF-8'}_title" value="{if isset($value.title)}{$value.title|escape:'htmlall':'UTF-8'}{/if}" placeholder="{l s='Enter a title for this file' mod='wepresta_acf'}">
    </div>
    {/if}

    {* Description field if enabled *}
    {if isset($fieldConfig.enableDescription) && $fieldConfig.enableDescription}
    <div class="acf-media-meta-field">
        <label class="form-control-label" for="{$inputId|escape:'htmlall':'UTF-8'}_description">{l s='File Description' mod='wepresta_acf'}</label>
        <textarea class="form-control form-control-sm" id="{$inputId|escape:'htmlall':'UTF-8'}_description" name="{$inputName|escape:'htmlall':'UTF-8'}_description" rows="2" placeholder="{l s='Enter a description for this file' mod='wepresta_acf'}">{if isset($value.description)}{$value.description|escape:'htmlall':'UTF-8'}{/if}</textarea>
    </div>
    {/if}

    {* Input method configuration *}
    {assign var="allowUpload" value=(!isset($fieldConfig.allowUpload) || $fieldConfig.allowUpload)}
    {assign var="allowImport" value=(isset($fieldConfig.allowUrlImport) && $fieldConfig.allowUrlImport)}
    {assign var="allowLink" value=(isset($fieldConfig.allowUrlLink) && $fieldConfig.allowUrlLink)}
    {assign var="allowAttachment" value=(isset($fieldConfig.allowAttachment) && $fieldConfig.allowAttachment)}
    {assign var="methodCount" value=0}
    {if $allowUpload}{assign var="methodCount" value=$methodCount+1}{/if}
    {if $allowImport}{assign var="methodCount" value=$methodCount+1}{/if}
    {if $allowLink}{assign var="methodCount" value=$methodCount+1}{/if}
    {if $allowAttachment}{assign var="methodCount" value=$methodCount+1}{/if}

    {assign var="defaultMethod" value=$fieldConfig.defaultInputMethod|default:'upload'}
    {if $defaultMethod === 'upload' && !$allowUpload}
        {if $allowImport}{assign var="defaultMethod" value='import'}{elseif $allowLink}{assign var="defaultMethod" value='link'}{elseif $allowAttachment}{assign var="defaultMethod" value='attachment'}{/if}
    {/if}

    {* Input method tabs *}
    {if $methodCount > 1}
    <div class="acf-input-tabs">
        {if $allowUpload}<button type="button" class="acf-input-tab{if $defaultMethod === 'upload'} active{/if}" data-tab="upload" data-field="{$field.slug|escape:'htmlall':'UTF-8'}"><span class="material-icons">cloud_upload</span> {l s='Upload' mod='wepresta_acf'}</button>{/if}
        {if $allowImport}<button type="button" class="acf-input-tab{if $defaultMethod === 'import'} active{/if}" data-tab="import" data-field="{$field.slug|escape:'htmlall':'UTF-8'}"><span class="material-icons">download</span> {l s='Import URL' mod='wepresta_acf'}</button>{/if}
        {if $allowLink}<button type="button" class="acf-input-tab{if $defaultMethod === 'link'} active{/if}" data-tab="link" data-field="{$field.slug|escape:'htmlall':'UTF-8'}"><span class="material-icons">link</span> {l s='External Link' mod='wepresta_acf'}</button>{/if}
        {if $allowAttachment}<button type="button" class="acf-input-tab{if $defaultMethod === 'attachment'} active{/if}" data-tab="attachment" data-field="{$field.slug|escape:'htmlall':'UTF-8'}"><span class="material-icons">attach_file</span> {l s='Attachment' mod='wepresta_acf'}</button>{/if}
    </div>
    {/if}

    {* Upload dropzone *}
    {if $allowUpload}
    <div class="acf-input-panel acf-panel-upload{if $defaultMethod === 'upload' || $methodCount === 1} active{/if}" data-field="{$field.slug|escape:'htmlall':'UTF-8'}">
        {* Hidden input to store uploaded file data *}
        <input type="hidden" class="acf-file-value" id="{$inputId|escape:'htmlall':'UTF-8'}_value" name="{$inputName|escape:'htmlall':'UTF-8'}_value" value='{if $value && is_array($value) && isset($value.url)}{$value|json_encode|escape:'html':'UTF-8'}{else}null{/if}'>
        <div class="acf-dropzone{if $value && is_array($value) && isset($value.url)} acf-has-file{/if}">
            <input type="file" class="acf-file-input" id="{$inputId|escape:'htmlall':'UTF-8'}" name="{$inputName|escape:'htmlall':'UTF-8'}">
            <div class="acf-dropzone-content">
                <span class="material-icons acf-dropzone-icon">cloud_upload</span>
                <span class="acf-dropzone-text">{l s='Drop file here or click to upload' mod='wepresta_acf'}</span>
                <span class="acf-dropzone-hint">{l s='Replace existing file' mod='wepresta_acf'}</span>
            </div>
        </div>
        {* Show allowed file types info *}
        {if isset($fieldConfig.allowedMimes) && $fieldConfig.allowedMimes|@count > 0}
            {assign var="allowedTypesText" value=""}
            {assign var="firstType" value=true}
            {foreach $fieldConfig.allowedMimes as $mime}
                {if $mime === 'application/pdf'}
                    {if !$firstType}{assign var="allowedTypesText" value=$allowedTypesText|cat:', '}{/if}
                    {assign var="allowedTypesText" value=$allowedTypesText|cat:'PDF'}
                    {assign var="firstType" value=false}
                {/if}
                {if $mime === 'application/msword'}
                    {if !$firstType}{assign var="allowedTypesText" value=$allowedTypesText|cat:', '}{/if}
                    {assign var="allowedTypesText" value=$allowedTypesText|cat:'Word (.doc)'}
                    {assign var="firstType" value=false}
                {/if}
                {if $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'}
                    {if !$firstType}{assign var="allowedTypesText" value=$allowedTypesText|cat:', '}{/if}
                    {assign var="allowedTypesText" value=$allowedTypesText|cat:'Word (.docx)'}
                    {assign var="firstType" value=false}
                {/if}
                {if $mime === 'application/vnd.ms-excel'}
                    {if !$firstType}{assign var="allowedTypesText" value=$allowedTypesText|cat:', '}{/if}
                    {assign var="allowedTypesText" value=$allowedTypesText|cat:'Excel (.xls)'}
                    {assign var="firstType" value=false}
                {/if}
                {if $mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}
                    {if !$firstType}{assign var="allowedTypesText" value=$allowedTypesText|cat:', '}{/if}
                    {assign var="allowedTypesText" value=$allowedTypesText|cat:'Excel (.xlsx)'}
                    {assign var="firstType" value=false}
                {/if}
                {if $mime === 'text/plain'}
                    {if !$firstType}{assign var="allowedTypesText" value=$allowedTypesText|cat:', '}{/if}
                    {assign var="allowedTypesText" value=$allowedTypesText|cat:'Text (.txt)'}
                    {assign var="firstType" value=false}
                {/if}
                {if $mime === 'text/csv'}
                    {if !$firstType}{assign var="allowedTypesText" value=$allowedTypesText|cat:', '}{/if}
                    {assign var="allowedTypesText" value=$allowedTypesText|cat:'CSV'}
                    {assign var="firstType" value=false}
                {/if}
                {if $mime === 'application/zip' || $mime === 'application/x-zip-compressed'}
                    {if !$firstType}{assign var="allowedTypesText" value=$allowedTypesText|cat:', '}{/if}
                    {assign var="allowedTypesText" value=$allowedTypesText|cat:'ZIP'}
                    {assign var="firstType" value=false}
                {/if}
            {/foreach}
            {if $allowedTypesText}
                <small class="form-text text-muted acf-allowed-types-info">
                    <span class="material-icons" style="font-size:14px;vertical-align:middle;">info</span>
                    {l s='Allowed file types:' mod='wepresta_acf'} {$allowedTypesText}
                </small>
            {/if}
        {/if}
    </div>
    {/if}

    {* Import URL panel *}
    {if $allowImport}
    <div class="acf-input-panel acf-panel-import{if $defaultMethod === 'import'} active{/if}" data-field="{$field.slug|escape:'htmlall':'UTF-8'}">
        <div class="acf-url-import">
            <div class="acf-url-input-group">
                <input type="url" class="form-control acf-url-field" id="{$inputId|escape:'htmlall':'UTF-8'}_url" name="{$inputName|escape:'htmlall':'UTF-8'}_url" placeholder="{l s='https://example.com/path/to/file.pdf' mod='wepresta_acf'}">
                <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}_url_mode" value="import">
            </div>
            <small class="form-text text-muted"><span class="material-icons" style="font-size:14px;vertical-align:middle">download</span> {l s='The file will be downloaded and stored on this server.' mod='wepresta_acf'}</small>
        </div>
    </div>
    {/if}

    {* External Link panel *}
    {if $allowLink}
    <div class="acf-input-panel acf-panel-link{if $defaultMethod === 'link'} active{/if}" data-field="{$field.slug|escape:'htmlall':'UTF-8'}">
        <div class="acf-url-import">
            <div class="acf-url-input-group">
                <input type="url" class="form-control acf-url-field" id="{$inputId|escape:'htmlall':'UTF-8'}_link_url" name="{$inputName|escape:'htmlall':'UTF-8'}_link_url" placeholder="{l s='https://example.com/path/to/file.pdf' mod='wepresta_acf'}">
                <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}_link_mode" value="link">
            </div>
            <small class="form-text text-muted"><span class="material-icons" style="font-size:14px;vertical-align:middle">link</span> {l s='The file will remain on the external server.' mod='wepresta_acf'}</small>
        </div>
    </div>
    {/if}

    {* Attachment picker *}
    {if $allowAttachment}
    <div class="acf-input-panel acf-panel-attachment{if $defaultMethod === 'attachment'} active{/if}" data-field="{$field.slug|escape:'htmlall':'UTF-8'}">
        <div class="acf-attachment-picker">
            {if isset($product_attachments) && $product_attachments|@count > 0}
                <select class="form-control" id="{$inputId|escape:'htmlall':'UTF-8'}_attachment" name="{$inputName|escape:'htmlall':'UTF-8'}_attachment">
                    <option value="">-- {l s='Select an attachment' mod='wepresta_acf'} --</option>
                    {foreach $product_attachments as $attachment}
                        <option value="{$attachment.id}" data-mime="{$attachment.mime|escape:'htmlall':'UTF-8'}" data-size="{$attachment.file_size}">{$attachment.name|escape:'htmlall':'UTF-8'} ({$attachment.file_name|escape:'htmlall':'UTF-8'})</option>
                    {/foreach}
                </select>
                <small class="form-text text-muted"><span class="material-icons" style="font-size:14px;vertical-align:middle">attach_file</span> {l s='Choose from existing product attachments.' mod='wepresta_acf'}</small>
            {else}
                <div class="alert alert-warning mb-0"><span class="material-icons" style="font-size:16px;vertical-align:middle">info</span> {l s='No attachments found.' mod='wepresta_acf'}</div>
            {/if}
        </div>
    </div>
    {/if}
</div>

