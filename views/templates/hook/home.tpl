{**
 * Module Starter - Hook displayHome
 *
 * Variables disponibles:
 * - $wepresta_acf.title
 * - $wepresta_acf.description
 * - $wepresta_acf.link
 *}

<div class="wepresta_acf-home">
    <div class="wepresta_acf-container">
        {if $wepresta_acf.title}
            <h2 class="wepresta_acf-title">{$wepresta_acf.title|escape:'html':'UTF-8'}</h2>
        {/if}

        {if $wepresta_acf.description}
            <div class="wepresta_acf-description">
                {$wepresta_acf.description nofilter}
            </div>
        {/if}

        {if $wepresta_acf.link}
            <a href="{$wepresta_acf.link}" class="btn btn-primary wepresta_acf-btn">
                {l s='En savoir plus' d='Modules.WeprestaAcf.Shop'}
            </a>
        {/if}
    </div>
</div>
