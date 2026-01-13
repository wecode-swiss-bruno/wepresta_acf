{**
 * CPT Single Template - Default template for single post
 *
 * Available variables:
 * - $cpt_type: Type information
 * - $cpt_post: Post information
 * - $cpt: CPT service
 * - $acf: ACF service for custom fields
 *}

{extends file='page.tpl'}

{block name='page_title'}
    <h1>{$cpt_post.title}</h1>
{/block}

{block name='page_content'}
    <article class="cpt-single cpt-single-{$cpt_type.slug}" id="cpt-post-{$cpt_post.id}">
        
        <div class="cpt-post-meta">
            <time datetime="{$cpt_post.date_upd}">
                {$cpt_post.date_upd|date_format:'%d/%m/%Y'}
            </time>
            <a href="{$cpt_type.url}" class="cpt-back-link">
                &larr; {l s='Back to' d='Modules.Weprestaacf.Front'} {$cpt_type.name}
            </a>
        </div>

        {* Featured Image ACF field *}
        {if $acf->has('featured_image')}
            <div class="cpt-post-featured-image">
                {$acf->render('featured_image')}
            </div>
        {/if}

        {* Main Content ACF field *}
        {if $acf->has('content')}
            <div class="cpt-post-content">
                {$acf->render('content')}
            </div>
        {/if}

        {* Display all ACF fields from assigned groups *}
        <div class="cpt-post-fields">
            {foreach $acf->group($cpt_type.id) as $field}
                {if $field.has_value && $field.slug != 'featured_image' && $field.slug != 'content'}
                    <div class="acf-field acf-field-{$field.type}">
                        {if $field.title}
                            <h3 class="acf-field-title">{$field.title}</h3>
                        {/if}
                        <div class="acf-field-value">
                            {$field.rendered nofilter}
                        </div>
                    </div>
                {/if}
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
                                {$acf->render('image')}
                            {/if}
                            {if isset($row.caption)}
                                <p>{$row.caption}</p>
                            {/if}
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
    </article>
{/block}
