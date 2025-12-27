{**
 * ACF - Entity Fields Admin Template (Generic)
 * Displays custom field groups for any entity type
 *}
<div class="acf-entity-fields" id="acf-entity-fields"
     data-entity-type="{$acf_entity_type|escape:'htmlall':'UTF-8'}"
     data-entity-id="{$acf_entity_id|intval}"
     data-api-url="{$acf_api_base_url|escape:'htmlall':'UTF-8'}">

    {* AJAX Save Button *}
    <div class="acf-save-toolbar mb-3 d-flex align-items-center justify-content-between">
        <div class="acf-save-status">
            <span class="acf-status-text text-muted small"></span>
        </div>
        <button type="button" class="btn btn-primary acf-save-btn" id="acf-ajax-save">
            <span class="acf-save-icon material-icons me-1" style="font-size:18px;vertical-align:middle;">save</span>
            <span class="acf-save-label">{l s='Save Custom Fields' d='Modules.Weprestaacf.Admin'}</span>
            <span class="acf-save-spinner spinner-border spinner-border-sm ms-1 d-none" role="status"></span>
        </button>
    </div>

    {foreach $acf_groups as $group}
        <div class="acf-group card mb-3" data-group-id="{$group.id|intval}">
            <div class="card-header">
                <h3 class="card-header-title mb-0">{$group.title|escape:'html':'UTF-8'}</h3>
                {if $group.description}
                    <p class="text-muted small mb-0">{$group.description|escape:'html':'UTF-8'}</p>
                {/if}
            </div>
            <div class="card-body">
                {foreach $group.fields as $field}
                    <div class="acf-field form-group row mb-4 pb-4" data-field-slug="{$field.slug|escape:'html':'UTF-8'}">
                        <div class="col-md-3 col-lg-2 text-md-left">
                            <label class="form-control-label{if $field.required} required{/if}">
                                {$field.title|escape:'html':'UTF-8'}
                                {if $field.required}<span class="text-danger">*</span>{/if}
                                {if $field.translatable}<span class="acf-translatable-badge" title="{l s='Translatable field' d='Modules.Weprestaacf.Admin'}">🌐</span>{/if}
                            </label>
                            {if $field.instructions}
                                <small class="form-text text-muted d-block">{$field.instructions|escape:'html':'UTF-8'}</small>
                            {/if}
                        </div>
                        <div class="col-md-9 col-lg-10 acf-field-input">
                            {if $field.translatable && $field.lang_inputs|count > 0}
                                {* Native PrestaShop translatable field structure *}
                                <div class="translations tabbable" id="acf_{$field.slug}" tabindex="1">
                                    <ul class="translationsLocales nav nav-pills">
                                        {foreach $field.lang_inputs as $lang_input}
                                            <li class="nav-item">
                                                <a href="#"
                                                   class="nav-link{if $lang_input.is_default} active{/if}"
                                                   data-lang-id="{$lang_input.id_lang|intval}"
                                                   data-iso-code="{$lang_input.iso_code|escape:'html':'UTF-8'}">
                                                    {$lang_input.name|escape:'html':'UTF-8'}
                                                </a>
                                            </li>
                                        {/foreach}
                                    </ul>
                                    <div class="translationsFields tab-content">
                                        {foreach $field.lang_inputs as $lang_input}
                                            <div class="tab-pane{if $lang_input.is_default} active{/if}"
                                                 data-lang-id="{$lang_input.id_lang|intval}"
                                                 data-iso-code="{$lang_input.iso_code|escape:'html':'UTF-8'}">
                                                {$lang_input.html nofilter}
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                            {else}
                                {$field.html nofilter}
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
    {/foreach}
</div>

{* Include the same JavaScript as product-fields.tpl *}
<script>
(function() {
    const container = document.getElementById('acf-entity-fields');
    if (!container) return;

    const entityType = container.dataset.entityType;
    const entityId = parseInt(container.dataset.entityId, 10);
    const apiUrl = container.dataset.apiUrl;

    // Reuse product fields JavaScript logic
    // The JS will be updated to support entity_type + entity_id in Phase 4
    if (typeof acfAdmin !== 'undefined') {
        acfAdmin.initEntityFields(entityType, entityId, apiUrl);
    }
})();
</script>

