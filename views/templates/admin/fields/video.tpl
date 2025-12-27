{**
 * ACF Field Partial: Video (Upload/YouTube/Vimeo/External)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="vidAllowUpload" value=(!isset($fieldConfig.allowUpload) || $fieldConfig.allowUpload)}
{assign var="vidAllowExternal" value=(!isset($fieldConfig.allowUrl) || $fieldConfig.allowUrl)}
{assign var="hasVideo" value=($value && is_array($value) && isset($value.source))}
{assign var="isFileVideo" value=($hasVideo && $value.source !== 'youtube' && $value.source !== 'vimeo')}

<div class="acf-video-field" data-type="video" data-slug="{$field.slug|escape:'htmlall':'UTF-8'}">
    {if $hasVideo}
        {* Existing video preview *}
        <div class="card mb-3 acf-video-preview" id="preview_{$inputId|escape:'htmlall':'UTF-8'}">
            <div class="card-body p-3">
                {if $value.source === 'youtube'}
                    <div class="ratio ratio-16x9 mb-2" style="max-width: 400px;">
                        <iframe src="https://www.youtube.com/embed/{$value.video_id|escape:'htmlall':'UTF-8'}" allowfullscreen></iframe>
                    </div>
                    <span class="badge bg-danger">YouTube</span>
                {elseif $value.source === 'vimeo'}
                    <div class="ratio ratio-16x9 mb-2" style="max-width: 400px;">
                        <iframe src="https://player.vimeo.com/video/{$value.video_id|escape:'htmlall':'UTF-8'}" allowfullscreen></iframe>
                    </div>
                    <span class="badge bg-info">Vimeo</span>
                {else}
                    <video controls class="rounded mb-2" style="max-width: 100%; max-height: 200px;"
                           {if isset($value.poster_url) && $value.poster_url}poster="{$value.poster_url|escape:'htmlall':'UTF-8'}"{/if}>
                        <source src="{$value.url|escape:'htmlall':'UTF-8'}" type="{$value.mime|default:'video/mp4'|escape:'htmlall':'UTF-8'}">
                        {if isset($value.sources) && is_array($value.sources)}
                            {foreach $value.sources as $src}
                                <source src="{$src.url|escape:'htmlall':'UTF-8'}" type="{$src.mime|escape:'htmlall':'UTF-8'}">
                            {/foreach}
                        {/if}
                    </video>
                    <div>
                        {if $value.source === 'upload'}
                            <span class="badge bg-success">{l s='Uploaded' mod='wepresta_acf'}</span>
                        {else}
                            <span class="badge bg-secondary">{l s='External' mod='wepresta_acf'}</span>
                        {/if}
                    </div>
                {/if}
                
                {* Meta info for file videos *}
                {if $isFileVideo}
                    <div class="mt-2 small">
                        {if isset($value.sources) && is_array($value.sources) && count($value.sources) > 0}
                            <span class="text-success me-3">
                                <span class="material-icons" style="font-size: 14px;">check_circle</span> {l s='Alt source' mod='wepresta_acf'}
                            </span>
                        {/if}
                        {if isset($value.poster_url) && $value.poster_url}
                            <span class="text-success">
                                <span class="material-icons" style="font-size: 14px;">check_circle</span> {l s='Poster' mod='wepresta_acf'}
                            </span>
                        {/if}
                    </div>
                {/if}
                
                {* Delete button *}
                <div class="mt-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            data-delete-toggle="preview_{$inputId|escape:'htmlall':'UTF-8'}">
                        <span class="material-icons" style="font-size: 14px;">delete</span> {l s='Remove' mod='wepresta_acf'}
                    </button>
                    <input type="hidden" name="{$inputName|escape:'htmlall':'UTF-8'}_delete" value="0" id="preview_{$inputId|escape:'htmlall':'UTF-8'}_delete_flag">
                </div>
            </div>
            
            {* Update options for file videos *}
            {if $isFileVideo}
                <div class="card-footer bg-light">
                    <p class="small text-muted mb-2">
                        <span class="material-icons" style="font-size: 14px;">edit</span> {l s='Update options' mod='wepresta_acf'}
                    </p>
                    
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">{l s='Replace video' mod='wepresta_acf'}</label>
                            <input type="file" class="form-control form-control-sm" name="{$inputName|escape:'htmlall':'UTF-8'}_replace" accept="video/*">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{l s='Alt format' mod='wepresta_acf'}</label>
                            <input type="file" class="form-control form-control-sm" name="{$inputName|escape:'htmlall':'UTF-8'}_alt" accept="video/*">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{l s='Poster' mod='wepresta_acf'}</label>
                            <input type="file" class="form-control form-control-sm" name="{$inputName|escape:'htmlall':'UTF-8'}_poster" accept="image/*">
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    {else}
        {* No video - input tabs *}
        {assign var="vidMethodCount" value=0}
        {if $vidAllowUpload}{assign var="vidMethodCount" value=$vidMethodCount+1}{/if}
        {if $vidAllowExternal}{assign var="vidMethodCount" value=$vidMethodCount+1}{/if}
        
        {if $vidMethodCount > 1}
            <ul class="nav nav-tabs nav-tabs-sm mb-3" role="tablist">
                {if $vidAllowUpload}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2 active" type="button" data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_upload" data-bs-target="#tab_{$inputId}_upload">
                        <span class="material-icons" style="font-size: 14px;">cloud_upload</span> {l s='Upload' mod='wepresta_acf'}
                    </button>
                </li>
                {/if}
                {if $vidAllowExternal}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2{if !$vidAllowUpload} active{/if}" type="button" data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_external" data-bs-target="#tab_{$inputId}_external">
                        <span class="material-icons" style="font-size: 14px;">link</span> {l s='External' mod='wepresta_acf'}
                    </button>
                </li>
                {/if}
            </ul>
        {/if}
        
        <div class="tab-content">
            {* Upload panel *}
            {if $vidAllowUpload}
                <div class="{if $vidMethodCount > 1}tab-pane fade show active{/if}" id="tab_{$inputId}_upload">
                    <p class="small text-muted mb-3">{l s='Upload video files. Add multiple formats for browser compatibility.' mod='wepresta_acf'}</p>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{l s='Primary video' mod='wepresta_acf'} <span class="text-muted small">(MP4)</span></label>
                            <input type="file" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}" accept="video/mp4,video/webm,video/ogg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{l s='Alternative format' mod='wepresta_acf'} <span class="text-muted small">({l s='optional' mod='wepresta_acf'})</span></label>
                            <input type="file" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_alt" accept="video/webm,video/ogg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{l s='Poster image' mod='wepresta_acf'} <span class="text-muted small">({l s='optional' mod='wepresta_acf'})</span></label>
                            <input type="file" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_poster" accept="image/*">
                            <small class="form-text text-muted">{l s='Shown before video plays' mod='wepresta_acf'}</small>
                        </div>
                    </div>
                    
                    {if isset($fieldConfig.enableTitle) && $fieldConfig.enableTitle}
                        <div class="mt-3">
                            <label class="form-label">{l s='Title' mod='wepresta_acf'}</label>
                            <input type="text" class="form-control form-control-sm" name="{$inputName|escape:'htmlall':'UTF-8'}_title">
                        </div>
                    {/if}
                </div>
            {/if}
            
            {* External panel *}
            {if $vidAllowExternal}
                <div class="{if $vidMethodCount > 1}tab-pane fade{if !$vidAllowUpload} show active{/if}{/if}" id="tab_{$inputId}_external">
                    <div class="mb-3">
                        <label class="form-label">{l s='Video URL' mod='wepresta_acf'}</label>
                        <input type="url" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_url" 
                               placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
                        <small class="form-text text-muted">{l s='YouTube, Vimeo, or direct video URL' mod='wepresta_acf'}</small>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{l s='Alt URL' mod='wepresta_acf'} <span class="text-muted small">({l s='optional' mod='wepresta_acf'})</span></label>
                            <input type="url" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_url_alt" 
                                   placeholder="https://example.com/video.webm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{l s='Poster URL' mod='wepresta_acf'} <span class="text-muted small">({l s='optional' mod='wepresta_acf'})</span></label>
                            <input type="url" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_poster_url" 
                                   placeholder="https://example.com/poster.jpg">
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    {/if}
</div>
