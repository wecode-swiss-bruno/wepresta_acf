{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
* CPT Archive Template - Default template for post listings
*
* Available variables:
* - $cpt_type: Type information
* - $cpt_posts: Array of posts
* - $cpt_pagination: Pagination data
* - $acf: ACF service for custom fields
*}

{extends file='page.tpl'}

{block name='page_title'}
<h1>{$cpt_type.name}</h1>
{/block}

{block name='page_content'}
<div class="cpt-archive cpt-archive-{$cpt_type.slug}">

    {if $cpt_type.description}
    <div class="cpt-description">
        {$cpt_type.description}
    </div>
    {/if}

    {if $cpt_posts && count($cpt_posts) > 0}
    <div class="cpt-posts-grid">
        {foreach $cpt_posts as $post}
        <article class="cpt-post-item" id="cpt-post-{$post.id}">
            <h2 class="cpt-post-title">
                <a href="{$post.url}">{$post.title}</a>
            </h2>

            <div class="cpt-post-meta">
                <time datetime="{$post.date_upd}">
                    {$post.date_upd|date_format:'%d/%m/%Y'}
                </time>
            </div>

            {* ACF fields can be accessed here *}
            {assign var='acf_post' value=$acf->forEntity('cpt_post', $post.id)}

            {if $acf_post->has('excerpt')}
            <div class="cpt-post-excerpt">
                {$acf_post->field('excerpt')}
            </div>
            {/if}

            {if $acf_post->has('featured_image')}
            <div class="cpt-post-thumbnail">
                {$acf_post->render('featured_image')}
            </div>
            {/if}

            <a href="{$post.url}" class="btn btn-primary">
                {l s='Read more' d='Modules.Weprestaacf.Front'}
            </a>
        </article>
        {/foreach}
    </div>

    {* Pagination *}
    {if $cpt_pagination.total_pages > 1}
    <nav class="cpt-pagination">
        <ul class="pagination">
            {if $cpt_pagination.current_page > 1}
            <li>
                <a
                    href="{$link->getModuleLink('wepresta_acf', 'cptarchive', ['type' => $cpt_type.slug, 'p' => $cpt_pagination.current_page - 1])}">
                    &laquo; {l s='Previous' d='Shop.Theme'}
                </a>
            </li>
            {/if}

            {for $i=1 to $cpt_pagination.total_pages}
            <li {if $i==$cpt_pagination.current_page}class="active" {/if}>
                <a href="{$link->getModuleLink('wepresta_acf', 'cptarchive', ['type' => $cpt_type.slug, 'p' => $i])}">
                    {$i}
                </a>
            </li>
            {/for}

            {if $cpt_pagination.current_page < $cpt_pagination.total_pages} <li>
                <a
                    href="{$link->getModuleLink('wepresta_acf', 'cptarchive', ['type' => $cpt_type.slug, 'p' => $cpt_pagination.current_page + 1])}">
                    {l s='Next' d='Shop.Theme'} &raquo;
                </a>
                </li>
                {/if}
        </ul>
    </nav>
    {/if}
    {else}
    <p class="alert alert-info">
        {l s='No posts found' d='Modules.Weprestaacf.Front'}
    </p>
    {/if}
</div>
{/block}
