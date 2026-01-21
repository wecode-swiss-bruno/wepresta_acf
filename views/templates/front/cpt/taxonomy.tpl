{**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{**
 * CPT Taxonomy Template - Default template for taxonomy term archives
 *
 * Available variables:
 * - $cpt_type: Type information
 * - $cpt_taxonomy: Taxonomy information
 * - $cpt_term: Term information
 * - $cpt_posts: Array of posts
 * - $cpt_pagination: Pagination data
 * - $acf: ACF service
 *}

{extends file='page.tpl'}

{block name='page_title'}
    <h1>{$cpt_term.name}</h1>
{/block}

{block name='page_content'}
    <div class="cpt-taxonomy cpt-taxonomy-{$cpt_taxonomy.slug}">
        
        <div class="cpt-breadcrumb">
            <a href="{$cpt_type.url}">{$cpt_type.name}</a>
            <span class="separator">/</span>
            <span>{$cpt_term.name}</span>
        </div>

        {if $cpt_term.description}
            <div class="cpt-term-description">
                {$cpt_term.description}
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

                        {* ACF fields for each post *}
                        {$acf->forEntity('cpt_post', $post.id)}
                        
                        {if $acf->has('excerpt')}
                            <div class="cpt-post-excerpt">
                                {$acf->field('excerpt')}
                            </div>
                        {/if}

                        {if $acf->has('featured_image')}
                            <div class="cpt-post-thumbnail">
                                {$acf->render('featured_image')}
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
                                <a href="{$link->getModuleLink('wepresta_acf', 'cpttaxonomy', ['type' => $cpt_type.slug, 'taxonomy' => $cpt_taxonomy.id, 'term' => $cpt_term.slug, 'p' => $cpt_pagination.current_page - 1])}">
                                    &laquo; {l s='Previous' d='Shop.Theme'}
                                </a>
                            </li>
                        {/if}

                        {for $i=1 to $cpt_pagination.total_pages}
                            <li {if $i == $cpt_pagination.current_page}class="active"{/if}>
                                <a href="{$link->getModuleLink('wepresta_acf', 'cpttaxonomy', ['type' => $cpt_type.slug, 'taxonomy' => $cpt_taxonomy.id, 'term' => $cpt_term.slug, 'p' => $i])}">
                                    {$i}
                                </a>
                            </li>
                        {/for}

                        {if $cpt_pagination.current_page < $cpt_pagination.total_pages}
                            <li>
                                <a href="{$link->getModuleLink('wepresta_acf', 'cpttaxonomy', ['type' => $cpt_type.slug, 'taxonomy' => $cpt_taxonomy.id, 'term' => $cpt_term.slug, 'p' => $cpt_pagination.current_page + 1])}">
                                    {l s='Next' d='Shop.Theme'} &raquo;
                                </a>
                            </li>
                        {/if}
                    </ul>
                </nav>
            {/if}
        {else}
            <p class="alert alert-info">
                {l s='No posts found in this category' d='Modules.Weprestaacf.Front'}
            </p>
        {/if}
    </div>
{/block}
