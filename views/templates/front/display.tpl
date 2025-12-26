{**
 * Module Starter - Page front display
 *
 * Variables disponibles:
 * - $wepresta_acf.title
 * - $wepresta_acf.description
 *}

{extends file='page.tpl'}

{block name='page_title'}
    {$wepresta_acf.title|escape:'html':'UTF-8'}
{/block}

{block name='page_content'}
    <div class="wepresta_acf-page">
        <div class="wepresta_acf-content">
            {if $wepresta_acf.description}
                <div class="wepresta_acf-description">
                    {$wepresta_acf.description nofilter}
                </div>
            {/if}

            <div class="wepresta_acf-info">
                <p>{l s='Bienvenue sur la page du module.' d='Modules.WeprestaAcf.Shop'}</p>
            </div>
        </div>
    </div>
{/block}

