{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * ACF Video Field Template
 * 
 * Variables:
 *   $value - Field value (URL string or array with source, video_id, poster_url)
 *   $field - Field definition array
 *   $config - Field configuration
 *   $foOptions - Front-office options
 *   $slug - Field slug
 *   $customClass - Custom CSS class
 *   $customId - Custom HTML ID
 *}

{if $value}
    {if is_array($value)}
        {assign var="source" value=$value.source|default:'upload'}
        {assign var="videoId" value=$value.video_id|default:''}
        {assign var="videoUrl" value=$value.url|default:''}
        {assign var="poster" value=$value.poster_url|default:''}
    {else}
        {assign var="source" value='url'}
        {assign var="videoId" value=''}
        {assign var="videoUrl" value=$value}
        {assign var="poster" value=''}
    {/if}
    
    <div class="acf-video acf-video--{$source|escape:'html':'UTF-8'}{if $customClass} {$customClass|escape:'html':'UTF-8'}{/if}"{if $customId} id="{$customId|escape:'html':'UTF-8'}"{/if}>
        {if $source === 'youtube' && $videoId !== ''}
            <div class="acf-video-embed">
                <iframe src="https://www.youtube.com/embed/{$videoId|escape:'html':'UTF-8'}" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen
                        loading="lazy"></iframe>
            </div>
        {elseif $source === 'vimeo' && $videoId !== ''}
            <div class="acf-video-embed">
                <iframe src="https://player.vimeo.com/video/{$videoId|escape:'html':'UTF-8'}" 
                        frameborder="0" 
                        allow="autoplay; fullscreen; picture-in-picture" 
                        allowfullscreen
                        loading="lazy"></iframe>
            </div>
        {elseif $videoUrl !== ''}
            <video src="{$videoUrl|escape:'html':'UTF-8'}" 
                   controls
                   {if $poster}poster="{$poster|escape:'html':'UTF-8'}"{/if}
                   preload="metadata">
                Your browser does not support the video tag.
            </video>
        {/if}
    </div>
{/if}
