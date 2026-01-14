{*
* ACF Entity Fields Vue Container
* This template renders the container for the Vue.js app.
* Data is passed via data attributes.
*}
<div class="acf-entity-fields-vue-container" data-entity-type="{$acf_entity_type|escape:'html':'UTF-8'}"
    data-entity-id="{$acf_entity_id|intval}" data-groups='{$acf_groups|json_encode}'
    data-values='{$acf_values|json_encode}' data-languages='{$acf_languages|json_encode}'
    data-current-lang-id="{$acf_current_lang|intval}" data-shop-id="{$acf_shop_id|intval}"
    data-api-url="{$acf_api_base_url|escape:'html':'UTF-8'}" data-token="{$acf_token|escape:'html':'UTF-8'}"
    data-form-name-prefix="acf">
    <div class="acf-loading">
        <i class="material-icons">refresh</i> {l s='Loading fields...' mod='wepresta_acf'}
    </div>
</div>

{* Add hidden inputs for non-JS fallback (optional, but Vue app handles this) *}
{* The Vue app will generate hidden inputs inside the container. *}