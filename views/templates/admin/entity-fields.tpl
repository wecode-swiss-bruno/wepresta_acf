{**
 * ACF - Entity Fields Admin Template (Generic)
 * Displays custom field groups for any entity type
 * JavaScript is loaded from views/js/acf-fields.js
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
                                                   data-locale="{$lang_input.iso_code|lower}"
                                                   class="nav-link{if $lang_input.is_default} active{/if}"
                                                   data-toggle="tab"
                                                   data-target=".translationsFields-acf_{$field.slug}_{$lang_input.id_lang}">
                                                    {$lang_input.iso_code|upper}
                                                </a>
                                            </li>
                                        {/foreach}
                                    </ul>
                                    <div class="translationsFields tab-content">
                                        {foreach $field.lang_inputs as $lang_input}
                                            <div data-locale="{$lang_input.iso_code|lower}"
                                                 class="translationsFields-acf_{$field.slug}_{$lang_input.id_lang} tab-pane translation-field panel panel-default translation-label-{$lang_input.iso_code|lower}{if $lang_input.is_default} show active{/if}">
                                                {$lang_input.html nofilter}
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                            {else}
                                {* Non-translatable field *}
                                {$field.html nofilter}
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
    {foreachelse}
        <div class="alert alert-info">
            {l s='No custom field groups defined for this entity.' d='Modules.Weprestaacf.Admin'}
        </div>
    {/foreach}
</div>

<style>
/* AJAX Save Toolbar */
.acf-save-toolbar {
    padding: 0.75rem 1rem;
    background: linear-gradient(to right, #f8f9fa, #fff);
    border: 1px solid #dee2e6;
    border-radius: 4px;
    position: sticky;
    top: 60px;
    z-index: 100;
}
.acf-save-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.acf-save-btn.btn-success {
    animation: acf-save-flash 0.3s ease-out;
}
@keyframes acf-save-flash {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
/* Translatable badge icon */
.acf-translatable-badge {
    font-size: 0.875rem;
    margin-left: 0.25rem;
    opacity: 0.7;
}
/* Group styling */
.acf-group.card {
    border-left: 4px solid var(--primary, #25b9d7);
}
/* Ensure field-level tabs work correctly */
.acf-image-field .tab-content > .tab-pane.show.active,
.acf-video-field .tab-content > .tab-pane.show.active {
    display: block !important;
}
.acf-image-field .tab-content > .tab-pane:only-child,
.acf-video-field .tab-content > .tab-pane:only-child {
    display: block !important;
    opacity: 1 !important;
}
.acf-dropzone {
    min-height: 80px;
}
</style>
