{**
 * ACF Field Partial: Video (Upload/YouTube/Vimeo/External)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="vidAllowYouTube" value=(!isset($fieldConfig.allowYouTube) || $fieldConfig.allowYouTube)}
{assign var="vidAllowVimeo" value=(!isset($fieldConfig.allowVimeo) || $fieldConfig.allowVimeo)}
{assign var="vidAllowUpload" value=(!isset($fieldConfig.allowUpload) || $fieldConfig.allowUpload)}
{assign var="vidAllowUrl" value=(!isset($fieldConfig.allowUrl) || $fieldConfig.allowUrl)}
{assign var="hasVideo" value=($value && is_array($value) && isset($value.source))}
{assign var="isFileVideo" value=($hasVideo && $value.source !== 'youtube' && $value.source !== 'vimeo')}

<div class="acf-video-field" data-type="video" data-slug="{$field.slug|escape:'htmlall':'UTF-8'}">
    {* Hidden input to store the JSON value *}
    <input type="hidden"
           name="{$inputName|escape:'htmlall':'UTF-8'}"
           id="{$inputId|escape:'htmlall':'UTF-8'}_value"
           class="acf-video-value"
           value='{if $hasVideo}{$value|json_encode|escape:'htmlall':'UTF-8'}{else}null{/if}'>

    {* Hidden input tabs for adding new video (always rendered for JavaScript control) *}
    <div class="acf-video-input-tabs" style="display: {if $hasVideo}none{else}block{/if};">

        {assign var="vidMethodCount" value=0}
        {if $vidAllowYouTube}{assign var="vidMethodCount" value=$vidMethodCount+1}{/if}
        {if $vidAllowVimeo}{assign var="vidMethodCount" value=$vidMethodCount+1}{/if}
        {if $vidAllowUpload}{assign var="vidMethodCount" value=$vidMethodCount+1}{/if}
        {if $vidAllowUrl}{assign var="vidMethodCount" value=$vidMethodCount+1}{/if}
        
        {if $vidMethodCount > 1}
            <ul class="nav nav-tabs nav-tabs-sm mb-3" role="tablist">
                {if $vidAllowYouTube}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2 active" type="button" data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_youtube" data-bs-target="#tab_{$inputId}_youtube">
                        <span class="material-icons" style="font-size: 14px;">play_circle</span> YouTube
                    </button>
                </li>
                {/if}
                {if $vidAllowVimeo}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2{if !$vidAllowYouTube} active{/if}" type="button" data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_vimeo" data-bs-target="#tab_{$inputId}_vimeo">
                        <span class="material-icons" style="font-size: 14px;">play_circle</span> Vimeo
                    </button>
                </li>
                {/if}
                {if $vidAllowUpload}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2{if !$vidAllowYouTube && !$vidAllowVimeo} active{/if}" type="button" data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_upload" data-bs-target="#tab_{$inputId}_upload">
                        <span class="material-icons" style="font-size: 14px;">cloud_upload</span> {l s='Upload' mod='wepresta_acf'}
                    </button>
                </li>
                {/if}
                {if $vidAllowUrl}
                <li class="nav-item">
                    <button class="nav-link py-1 px-2{if !$vidAllowYouTube && !$vidAllowVimeo && !$vidAllowUpload} active{/if}" type="button" data-toggle="tab" data-bs-toggle="tab" data-target="#tab_{$inputId}_url" data-bs-target="#tab_{$inputId}_url">
                        <span class="material-icons" style="font-size: 14px;">link</span> {l s='URL' mod='wepresta_acf'}
                    </button>
                </li>
                {/if}
            </ul>
        {/if}
        
        <div class="tab-content">
            {* YouTube panel *}
            {if $vidAllowYouTube}
                <div class="{if $vidMethodCount > 1}tab-pane fade show active{/if}" id="tab_{$inputId}_youtube">
                    <div class="mb-3">
                        <label class="form-label">YouTube URL</label>
                        <input type="url" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_youtube_url"
                               placeholder="https://youtube.com/watch?v=dQw4w9WgXcQ ou https://youtu.be/dQw4w9WgXcQ">
                        <small class="form-text text-muted">{l s='Enter the complete URL of your YouTube video' mod='wepresta_acf'}</small>
                    </div>
                </div>
            {/if}

            {* Vimeo panel *}
            {if $vidAllowVimeo}
                <div class="{if $vidMethodCount > 1}tab-pane fade{if !$vidAllowYouTube} show active{/if}{/if}" id="tab_{$inputId}_vimeo">
                    <div class="mb-3">
                        <label class="form-label">Vimeo URL</label>
                        <input type="url" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_vimeo_url"
                               placeholder="https://vimeo.com/123456789">
                        <small class="form-text text-muted">{l s='Enter the complete URL of your Vimeo video' mod='wepresta_acf'}</small>
                    </div>
                </div>
            {/if}

            {* Upload panel *}
            {if $vidAllowUpload}
                <div class="{if $vidMethodCount > 1}tab-pane fade{if !$vidAllowYouTube && !$vidAllowVimeo} show active{/if}{/if}" id="tab_{$inputId}_upload">
                    <p class="small text-muted mb-3">{l s='Upload video files. Add multiple formats for browser compatibility.' mod='wepresta_acf'}</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{l s='Primary video' mod='wepresta_acf'}<span class="text-muted small">(MP4)</span></label>
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

            {* URL panel *}
            {if $vidAllowUrl}
                <div class="{if $vidMethodCount > 1}tab-pane fade{if !$vidAllowYouTube && !$vidAllowVimeo && !$vidAllowUpload} show active{/if}{/if}" id="tab_{$inputId}_url">
                    <div class="mb-3">
                        <label class="form-label">{l s='Direct Video URL' mod='wepresta_acf'}</label>
                        <input type="url" class="form-control" name="{$inputName|escape:'htmlall':'UTF-8'}_url"
                               placeholder="https://example.com/video.mp4">
                        <small class="form-text text-muted">{l s='Direct link to video file (MP4, WebM, OGG)' mod='wepresta_acf'}</small>
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
    </div>

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
                        <source src="{$value.url|default:''|escape:'htmlall':'UTF-8'}" type="{$value.mime|default:'video/mp4'|escape:'htmlall':'UTF-8'}">
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
    {/if}

        {* JavaScript to update hidden value *}
        <script>
        (function() {
            var fieldEl = document.getElementById('{$inputId|escape:'htmlall':'UTF-8'}').closest('.acf-video-field');
            var hiddenInput = fieldEl.querySelector('.acf-video-value');

            function updateVideoValue() {
                var value = null;

                // Find all inputs (regardless of which tab is active)
                var youtubeInput = fieldEl.querySelector('input[name$="_youtube_url"]');
                var vimeoInput = fieldEl.querySelector('input[name$="_vimeo_url"]');
                var urlInput = fieldEl.querySelector('input[name$="_url"]');
                var fileInput = fieldEl.querySelector('input[name$="_upload"]');


                // Check YouTube input first (highest priority)
                if (youtubeInput && youtubeInput.value.trim()) {
                    var parsed = parseVideoUrl(youtubeInput.value.trim());
                    if (parsed) {
                        value = parsed;
                    }
                }
                // Check Vimeo input
                else if (vimeoInput && vimeoInput.value.trim()) {
                    var parsed = parseVideoUrl(vimeoInput.value.trim());
                    if (parsed) {
                        value = parsed;
                    }
                }
                // Check direct URL input
                else if (urlInput && urlInput.value.trim()) {
                    value = {
                        source: 'url',
                        url: urlInput.value.trim()
                    };
                }
                // Check file upload
                else if (fileInput && fileInput.files.length > 0) {
                    var file = fileInput.files[0];
                    var titleInput = fieldEl.querySelector('input[name*="_title"]');
                    value = {
                        source: 'upload',
                        filename: file.name,
                        size: file.size,
                        mime: file.type,
                        title: titleInput ? titleInput.value : file.name
                    };
                }

                // Update hidden input with JSON value
                var jsonValue = value ? JSON.stringify(value) : null;
                hiddenInput.value = jsonValue;

                // Show/hide UI elements based on whether we have a video
                var inputTabs = fieldEl.querySelector('.acf-video-input-tabs');
                var preview = fieldEl.querySelector('.acf-video-preview');

                if (value && typeof value === 'object') {
                    // We have a video - hide input tabs
                    // Note: preview only exists for existing videos loaded from DB
                    if (inputTabs) inputTabs.style.display = 'none';
                } else {
                    // No video - show input tabs, hide preview if it exists
                    if (inputTabs) inputTabs.style.display = 'block';
                    if (preview) preview.style.display = 'none';
                }

                // Trigger change event for parent containers
                var event = new Event('change', { bubbles: true });
                hiddenInput.dispatchEvent(event);
            }

            function parseVideoUrl(url) {
                // YouTube: youtube.com/watch?v=xxx or youtu.be/xxx
                var ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                if (ytMatch) {
                    return {
                        source: 'youtube',
                        video_id: ytMatch[1],
                        url: url,
                        thumbnail_url: 'https://img.youtube.com/vi/' + ytMatch[1] + '/hqdefault.jpg'
                    };
                }

                // Vimeo: vimeo.com/123456789
                var vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
                if (vimeoMatch) {
                    return {
                        source: 'vimeo',
                        video_id: vimeoMatch[1],
                        url: url
                    };
                }

                return null;
            }

            // Listen for changes in all inputs within this field
            fieldEl.addEventListener('input', function(e) {
                // Debounce updates
                clearTimeout(fieldEl._videoUpdateTimeout);
                fieldEl._videoUpdateTimeout = setTimeout(updateVideoValue, 300);
            });

            fieldEl.addEventListener('blur', function(e) {
                // Update when user leaves any input field
                if (e.target.tagName === 'INPUT') {
                    setTimeout(updateVideoValue, 100);
                }
            }, true);

            fieldEl.addEventListener('change', function(e) {
                // Immediate update for file inputs and select changes
                if (e.target.type === 'file' || e.target.tagName === 'SELECT') {
                    updateVideoValue();
                }
            });

            // Listen for tab changes
            var tabLinks = fieldEl.querySelectorAll('.nav-link[data-toggle], .nav-link[data-bs-toggle]');
            tabLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    // Small delay to let Bootstrap update the active tab
                    setTimeout(updateVideoValue, 50);
                });
            });

            // Initialize with existing value
            if (hiddenInput.value && hiddenInput.value !== 'null') {
                try {
                    var existingValue = JSON.parse(hiddenInput.value);

                    if (existingValue && existingValue.source) {
                        // Pre-fill the appropriate input based on source
                        if (existingValue.source === 'youtube' && existingValue.url) {
                            var youtubeInput = fieldEl.querySelector('input[name*="_youtube_url"]');
                            if (youtubeInput) {
                                youtubeInput.value = existingValue.url;
                                // Activate YouTube tab if available
                                var youtubeTab = fieldEl.querySelector('.nav-link[data-bs-target*="_youtube"]');
                                if (youtubeTab) {
                                    youtubeTab.click();
                                }
                            }
                        } else if (existingValue.source === 'vimeo' && existingValue.url) {
                            var vimeoInput = fieldEl.querySelector('input[name*="_vimeo_url"]');
                            if (vimeoInput) {
                                vimeoInput.value = existingValue.url;
                                // Activate Vimeo tab if available
                                var vimeoTab = fieldEl.querySelector('.nav-link[data-bs-target*="_vimeo"]');
                                if (vimeoTab) {
                                    vimeoTab.click();
                                }
                            }
                        } else if (existingValue.source === 'url' && existingValue.url) {
                            var urlInput = fieldEl.querySelector('input[name*="_url"]');
                            if (urlInput) {
                                urlInput.value = existingValue.url;
                                // Activate URL tab if available
                                var urlTab = fieldEl.querySelector('.nav-link[data-bs-target*="_url"]');
                                if (urlTab) {
                                    urlTab.click();
                                }
                            }
                        }
                    }
                } catch (e) {
                    // Ignore parsing errors for existing values
                }
            }

            // Initial update after a short delay to let Bootstrap tabs initialize
            setTimeout(function() {
            updateVideoValue();
            }, 100);

        })();
        </script>
</div>
