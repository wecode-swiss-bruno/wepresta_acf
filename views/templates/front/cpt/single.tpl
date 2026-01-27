{**
* WePresta ACF - Advanced Custom Fields for PrestaShop
*
* @author WePresta
* @copyright 2024-2025 WePresta
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
*}

{extends file='page.tpl'}

{block name='page_title'}
<h1>{$cpt_post.title|escape:'htmlall':'UTF-8'}</h1>
{/block}

{block name='page_content'}
<article class="cpt-single cpt-single-{$cpt_type.slug|escape:'htmlall':'UTF-8'}" id="cpt-post-{$cpt_post.id|escape:'htmlall':'UTF-8'}">



    {* Featured Image ACF field *}
    {if $acf->has('featured_image')}
    <div class="cpt-post-featured-image">
        {$acf->render('featured_image') nofilter}
    </div>
    {/if}

    {* Main Content ACF field *}
    {if $acf->has('content')}
    <div class="cpt-post-content">
        {$acf->render('content') nofilter}
    </div>
    {/if}

    {* Display all ACF fields from assigned groups *}
    {* Note: Repeater fields are excluded here as they require special handling *}
    <div class="cpt-post-fields">
        {assign var="groups" value=$acf->getActiveGroupsArray()}
        {foreach $groups as $group}
        {foreach $group.fields as $field}
        {if $field.has_value && $field.slug != 'featured_image' && $field.slug != 'content' && $field.type !=
        'repeater'}
        <div class="acf-field acf-field-{$field.type|escape:'htmlall':'UTF-8'}">
            {if $field.title}
            <h3 class="acf-field-title">{$field.title|escape:'htmlall':'UTF-8'}</h3>
            {/if}
            <div class="acf-field-value">
                {$field.rendered nofilter}
            </div>
        </div>
        {/if}
        {/foreach}
        {/foreach}

        {* Auto-render repeater fields *}
        {foreach $groups as $group}
        {foreach $group.fields as $field}
        {if $field.type == 'repeater' && $field.has_value}
        <div class="acf-field acf-field-repeater">
            {if $field.title}
            <h3 class="acf-field-title">{$field.title|escape:'htmlall':'UTF-8'}</h3>
            {/if}
            <div class="acf-repeater-rows">
                <p class="text-muted">{$field.row_count|default:0|escape:'htmlall':'UTF-8'} {l s='items' d='Modules.Weprestaacf.Front'}</p>
                {foreach $acf->repeater($field.slug) as $row}
                <div class="acf-repeater-row">
                    {foreach $row as $subfield_slug => $subfield_value}
                    {if $subfield_value}
                    <div class="acf-subfield">
                        <strong>{$subfield_slug|replace:'_':' '|capitalize|escape:'htmlall':'UTF-8'}:</strong>

                        {* Handle arrays (images, files, etc) *}
                        {if is_array($subfield_value)}
                        {* Check if it looks like an image/file object *}
                        {if isset($subfield_value.url)}
                        {if isset($subfield_value.mime) && $subfield_value.mime|strstr:'image'}
                        <div class="acf-image-preview">
                            <img src="{$subfield_value.url|escape:'htmlall':'UTF-8'}" alt="{$subfield_value.filename|escape:'htmlall':'UTF-8'}"
                                style="max-width: 200px; height: auto;">
                        </div>
                        {else}
                        <div class="acf-file-link">
                            <a href="{$subfield_value.url|escape:'htmlall':'UTF-8'}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="material-icons">file_download</i> {$subfield_value.filename|escape:'htmlall':'UTF-8'}
                            </a>
                        </div>
                        {/if}
                        {else}
                        {* Fallback for other arrays *}
                        <pre>{$subfield_value|@json_encode|escape:'htmlall':'UTF-8'}</pre>
                        {/if}
                        {else}
                        {$subfield_value|escape:'htmlall':'UTF-8'}
                        {/if}
                    </div>
                    {/if}
                    {/foreach}
                </div>
                {/foreach}
            </div>
        </div>
        {/if}
        {/foreach}
        {/foreach}
    </div>

    {* Repeater example *}
    {if $acf->countRepeater('gallery') > 0}
    <div class="cpt-post-gallery">
        <h3>{l s='Gallery' d='Modules.Weprestaacf.Front'}</h3>
        <div class="gallery-grid">
            {foreach $acf->repeater('gallery') as $row}
            <div class="gallery-item">
                {if isset($row.image)}
                {$acf->render('image') nofilter}
                {/if}
                {if isset($row.caption)}
                <p>{$row.caption|escape:'htmlall':'UTF-8'}</p>
                {/if}
            </div>
            {/foreach}
        </div>
    </div>
    {/if}
</article>

<div class="cpt-post-meta">
    {if $cpt_post.date_upd}
    <time datetime="{$cpt_post.date_upd|escape:'htmlall':'UTF-8'}">
        {$cpt_post.date_upd|date_format:'%d/%m/%Y'|escape:'htmlall':'UTF-8'}
    </time>
    {/if}
    <a href="{$cpt_type.url|escape:'htmlall':'UTF-8'}" class="cpt-back-link">
        &larr; {l s='Back to' d='Modules.Weprestaacf.Front'} {$cpt_type.name|escape:'htmlall':'UTF-8'}
    </a>
</div>

{/block}