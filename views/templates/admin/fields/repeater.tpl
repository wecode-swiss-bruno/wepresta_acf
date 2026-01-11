{**
 * ACF Field Partial: Repeater (Group Repeater with Subfields)
 * Variables: $field, $fieldConfig, $prefix, $value, $context
 * Required: $fieldRenderer (for rendering subfields)
 *}
{assign var="inputId" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="inputName" value="{$prefix}{$field.slug}{if isset($context.suffix)}{$context.suffix}{/if}"}
{assign var="repMin" value=$fieldConfig.min|default:0}
{assign var="repMax" value=$fieldConfig.max|default:0}
{assign var="repCollapsed" value=$fieldConfig.collapsed|default:false}
{assign var="rowTitle" value=$fieldConfig.rowTitle|default:''}
{assign var="buttonLabel" value=$fieldConfig.buttonLabel|default:'Add Row'}
{assign var="displayMode" value=$fieldConfig.displayMode|default:'table'}

{* Parse existing value *}
{assign var="repeaterRows" value=[]}
{if $value}
    {if is_array($value)}
        {assign var="repeaterRows" value=$value}
    {else}
        {assign var="repeaterRows" value=$value|json_decode:true}
    {/if}
{/if}

{* Get subfields - they should be passed in field.children *}
{assign var="subfields" value=$field.children|default:[]}

<div class="acf-repeater-field acf-repeater-{$displayMode|escape:'htmlall':'UTF-8'}"
     data-type="repeater"
     data-slug="{$field.slug|escape:'htmlall':'UTF-8'}"
     data-min="{$repMin|intval}"
     data-max="{$repMax|intval}"
     data-collapsed="{if $repCollapsed}1{else}0{/if}"
     data-row-title="{$rowTitle|escape:'htmlall':'UTF-8'}"
     data-button-label="{$buttonLabel|escape:'htmlall':'UTF-8'}"
     data-display-mode="{$displayMode|escape:'htmlall':'UTF-8'}"
     data-subfields='{$subfields|json_encode|escape:'htmlall':'UTF-8'}'
     data-js-templates='{if isset($field.jsTemplates)}{$field.jsTemplates|json_encode|escape:'htmlall':'UTF-8'}{else}{ldelim}{rdelim}{/if}'
     data-languages='{if isset($languages)}{$languages|json_encode|escape:'htmlall':'UTF-8'}{else}[]{/if}'
     data-default-lang-id="{if isset($default_lang_id)}{$default_lang_id|intval}{else}1{/if}">

    {* JSON encode the value *}
    {if is_array($value) && $value|count > 0}
        {assign var="repeaterJsonValue" value=$value|json_encode}
    {elseif $value && !is_array($value)}
        {assign var="repeaterJsonValue" value=$value}
    {else}
        {assign var="repeaterJsonValue" value='[]'}
    {/if}
    <input type="hidden"
           name="{$inputName|escape:'htmlall':'UTF-8'}"
           id="{$inputId|escape:'htmlall':'UTF-8'}_value"
           class="acf-repeater-value"
           value='{$repeaterJsonValue|escape:'htmlall':'UTF-8'}'>

    {if $displayMode === 'table'}
        {* TABLE MODE *}
        <div class="acf-repeater-table-wrapper">
            <table class="acf-repeater-table table table-bordered">
                <thead>
                    <tr>
                        <th class="acf-col-drag" style="width: 40px;"></th>
                        {if $subfields}
                            {foreach $subfields as $subfield}
                                <th>{$subfield.title|escape:'htmlall':'UTF-8'}</th>
                            {/foreach}
                        {/if}
                        <th class="acf-col-actions" style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody class="acf-repeater-rows" id="{$inputId|escape:'htmlall':'UTF-8'}_rows">
                    {if $repeaterRows && is_array($repeaterRows)}
                        {foreach $repeaterRows as $row}
                            <tr class="acf-repeater-row" data-row-id="{$row.row_id|escape:'htmlall':'UTF-8'}">
                                <td class="acf-col-drag">
                                    <span class="acf-repeater-drag material-icons">drag_indicator</span>
                                </td>
                                {if $subfields}
                                    {foreach $subfields as $subfield}
                                        <td class="acf-repeater-cell" data-subfield-container="{$subfield.slug|escape:'htmlall':'UTF-8'}" data-subfield-slug="{$subfield.slug|escape:'htmlall':'UTF-8'}">
                                            {* Get value for this row and subfield *}
                                            {* Ensure row.values is an array *}
                                            {if is_string($row.values)}
                                                {assign var="rowValuesDecoded" value=$row.values|json_decode:true}
                                                {if is_array($rowValuesDecoded)}
                                                    {assign var="rowValues" value=$rowValuesDecoded}
                                                {else}
                                                    {assign var="rowValues" value=[]}
                                                {/if}
                                            {else}
                                                {assign var="rowValues" value=$row.values|default:[]}
                                            {/if}
                                            {assign var="subfieldValue" value=$rowValues[$subfield.slug]|default:''}
                                            {assign var="subfieldTranslatable" value=$subfield.translatable|default:false}
                                            {assign var="subfieldLangInputs" value=$subfield.lang_inputs|default:[]}
                                            
                                            {* Check if subfield is translatable and has lang_inputs *}
                                            {if $subfieldTranslatable && $subfieldLangInputs|count > 0}
                                                {* Translatable subfield - render with language tabs *}
                                                <div class="acf-repeater-subfield-translatable" data-subfield-slug="{$subfield.slug|escape:'htmlall':'UTF-8'}" data-row-id="{$row.row_id|escape:'htmlall':'UTF-8'}">
                                                    <div class="translations tabbable acf-repeater-translations" id="acf_repeater_{$field.slug|escape:'htmlall':'UTF-8'}_{$row.row_id|escape:'htmlall':'UTF-8'}_{$subfield.slug|escape:'htmlall':'UTF-8'}" tabindex="1">
                                                        <ul class="translationsLocales nav nav-pills" style="font-size: 0.7rem; margin-bottom: 0.25rem;">
                                                            {foreach $subfieldLangInputs as $lang_input}
                                                                <li class="nav-item">
                                                                    <a href="#"
                                                                       data-locale="{$lang_input.iso_code|lower}"
                                                                       class="nav-link{if $lang_input.is_default} active{/if}"
                                                                       data-toggle="tab"
                                                                       data-target=".translationsFields-acf_repeater_{$field.slug|escape:'htmlall':'UTF-8'}_{$row.row_id|escape:'htmlall':'UTF-8'}_{$subfield.slug|escape:'htmlall':'UTF-8'}_{$lang_input.id_lang}">
                                                                        {$lang_input.iso_code|upper}
                                                                    </a>
                                                                </li>
                                                            {/foreach}
                                                        </ul>
                                                        <div class="translationsFields tab-content">
                                                            {foreach $subfieldLangInputs as $lang_input}
                                                                {* Extract value for this language from row values *}
                                                                {assign var="langValue" value=''}
                                                                {* Handle case where subfieldValue might be a JSON string *}
                                                                {if is_string($subfieldValue)}
                                                                    {assign var="subfieldValueDecoded" value=$subfieldValue|json_decode:true}
                                                                    {if is_array($subfieldValueDecoded)}
                                                                        {assign var="subfieldValue" value=$subfieldValueDecoded}
                                                                    {/if}
                                                                {/if}
                                                                {if is_array($subfieldValue) && isset($subfieldValue[$lang_input.id_lang])}
                                                                    {assign var="langValue" value=$subfieldValue[$lang_input.id_lang]}
                                                                {elseif !is_array($subfieldValue) && $lang_input.is_default}
                                                                    {assign var="langValue" value=$subfieldValue}
                                                                {/if}
                                                                <div data-locale="{$lang_input.iso_code|lower}"
                                                                     class="translationsFields-acf_repeater_{$field.slug|escape:'htmlall':'UTF-8'}_{$row.row_id|escape:'htmlall':'UTF-8'}_{$subfield.slug|escape:'htmlall':'UTF-8'}_{$lang_input.id_lang} tab-pane translation-field{if $lang_input.is_default} show active{/if}">
                                                                    {if isset($fieldRenderer)}
                                                                        {$fieldRenderer->renderAdminInput($subfield, $langValue, ['size' => 'sm', 'dataSubfield' => true, 'dataLangId' => $lang_input.id_lang, 'prefix' => 'acf_repeater_', 'suffix' => '_'|cat:$lang_input.id_lang]) nofilter}
                                                                    {else}
                                                                        <input type="text" 
                                                                               class="form-control form-control-sm acf-subfield-input" 
                                                                               data-subfield="{$subfield.slug|escape:'htmlall':'UTF-8'}" 
                                                                               data-lang-id="{$lang_input.id_lang}"
                                                                               value="{$langValue|escape:'htmlall':'UTF-8'}">
                                                                    {/if}
                                                                </div>
                                                            {/foreach}
                                                        </div>
                                                    </div>
                                                </div>
                                            {else}
                                                {* Non-translatable subfield *}
                                                {if isset($fieldRenderer)}
                                                    {$fieldRenderer->renderAdminInput($subfield, $subfieldValue, ['size' => 'sm', 'dataSubfield' => true]) nofilter}
                                                {else}
                                                    <input type="text" class="form-control form-control-sm acf-subfield-input" data-subfield="{$subfield.slug|escape:'htmlall':'UTF-8'}" value="{$subfieldValue|escape:'htmlall':'UTF-8'}">
                                                {/if}
                                            {/if}
                                        </td>
                                    {/foreach}
                                {/if}
                                <td class="acf-col-actions">
                                    <button type="button" class="btn btn-link btn-sm text-danger acf-repeater-remove p-0" title="{l s='Remove' mod='wepresta_acf'}">
                                        <span class="material-icons" style="font-size: 18px;">delete</span>
                                    </button>
                                </td>
                            </tr>
                        {/foreach}
                    {/if}
                </tbody>
            </table>
            {if !$subfields}
                <div class="alert alert-warning">
                    {l s='No subfields defined for this repeater.' mod='wepresta_acf'}
                </div>
            {/if}
        </div>
    {else}
        {* CARDS MODE *}
        <div class="acf-repeater-rows acf-repeater-cards" id="{$inputId|escape:'htmlall':'UTF-8'}_rows">
            {if $repeaterRows && is_array($repeaterRows)}
                {assign var="rowIndex" value=1}
                {foreach $repeaterRows as $row}
                    <div class="acf-repeater-row acf-repeater-card{if $row.collapsed|default:$repCollapsed} acf-collapsed{/if}"
                         data-row-id="{$row.row_id|escape:'htmlall':'UTF-8'}">
                        <div class="acf-repeater-row-header">
                            <span class="acf-repeater-drag material-icons">drag_indicator</span>
                            <button type="button" class="acf-repeater-toggle">
                                <span class="material-icons acf-toggle-icon">
                                    {if $row.collapsed|default:$repCollapsed}chevron_right{else}expand_more{/if}
                                </span>
                            </button>
                            <span class="acf-repeater-row-title">Row {$rowIndex}</span>
                            <button type="button" class="btn btn-link text-danger acf-repeater-remove" title="{l s='Remove row' mod='wepresta_acf'}">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                        <div class="acf-repeater-row-content">
                            <div class="acf-repeater-subfields">
                                {if $subfields}
                                    {foreach $subfields as $subfield}
                                        <div class="acf-repeater-subfield" data-subfield-container="{$subfield.slug|escape:'htmlall':'UTF-8'}" data-subfield-slug="{$subfield.slug|escape:'htmlall':'UTF-8'}">
                                            <label class="form-control-label">
                                                {$subfield.title|escape:'htmlall':'UTF-8'}
                                                {if $subfield.translatable|default:false}
                                                    <span class="badge badge-info ml-2" style="font-size: 0.7rem;">
                                                        <i class="material-icons" style="font-size: 12px; vertical-align: middle;">language</i>
                                                        {l s='Translatable' mod='wepresta_acf'}
                                                    </span>
                                                {/if}
                                            </label>
                                            
                                            {* Get value for this row and subfield *}
                                            {* Ensure row.values is an array *}
                                            {if is_string($row.values)}
                                                {assign var="rowValuesDecoded" value=$row.values|json_decode:true}
                                                {if is_array($rowValuesDecoded)}
                                                    {assign var="rowValues" value=$rowValuesDecoded}
                                                {else}
                                                    {assign var="rowValues" value=[]}
                                                {/if}
                                            {else}
                                                {assign var="rowValues" value=$row.values|default:[]}
                                            {/if}
                                            {assign var="subfieldValue" value=$rowValues[$subfield.slug]|default:''}
                                            {assign var="subfieldTranslatable" value=$subfield.translatable|default:false}
                                            {assign var="subfieldLangInputs" value=$subfield.lang_inputs|default:[]}
                                            
                                            {* Check if subfield is translatable and has lang_inputs *}
                                            {if $subfieldTranslatable && $subfieldLangInputs|count > 0}
                                                {* Translatable subfield - render with language tabs *}
                                                <div class="acf-repeater-subfield-translatable" data-subfield-slug="{$subfield.slug|escape:'htmlall':'UTF-8'}" data-row-id="{$row.row_id|escape:'htmlall':'UTF-8'}">
                                                    <div class="translations tabbable acf-repeater-translations" id="acf_repeater_{$field.slug|escape:'htmlall':'UTF-8'}_{$row.row_id|escape:'htmlall':'UTF-8'}_{$subfield.slug|escape:'htmlall':'UTF-8'}" tabindex="1">
                                                        <ul class="translationsLocales nav nav-pills">
                                                            {foreach $subfieldLangInputs as $lang_input}
                                                                <li class="nav-item">
                                                                    <a href="#"
                                                                       data-locale="{$lang_input.iso_code|lower}"
                                                                       class="nav-link{if $lang_input.is_default} active{/if}"
                                                                       data-toggle="tab"
                                                                       data-target=".translationsFields-acf_repeater_{$field.slug|escape:'htmlall':'UTF-8'}_{$row.row_id|escape:'htmlall':'UTF-8'}_{$subfield.slug|escape:'htmlall':'UTF-8'}_{$lang_input.id_lang}">
                                                                        {$lang_input.iso_code|upper}
                                                                        {if $lang_input.is_default}
                                                                            <span class="material-icons" style="font-size: 12px;">star</span>
                                                                        {/if}
                                                                    </a>
                                                                </li>
                                                            {/foreach}
                                                        </ul>
                                                        <div class="translationsFields tab-content">
                                                            {foreach $subfieldLangInputs as $lang_input}
                                                                {* Extract value for this language from row values *}
                                                                {assign var="langValue" value=''}
                                                                {* Handle case where subfieldValue might be a JSON string *}
                                                                {if is_string($subfieldValue)}
                                                                    {assign var="subfieldValueDecoded" value=$subfieldValue|json_decode:true}
                                                                    {if is_array($subfieldValueDecoded)}
                                                                        {assign var="subfieldValue" value=$subfieldValueDecoded}
                                                                    {/if}
                                                                {/if}
                                                                {if is_array($subfieldValue) && isset($subfieldValue[$lang_input.id_lang])}
                                                                    {assign var="langValue" value=$subfieldValue[$lang_input.id_lang]}
                                                                {elseif !is_array($subfieldValue) && $lang_input.is_default}
                                                                    {assign var="langValue" value=$subfieldValue}
                                                                {/if}
                                                                <div data-locale="{$lang_input.iso_code|lower}"
                                                                     class="translationsFields-acf_repeater_{$field.slug|escape:'htmlall':'UTF-8'}_{$row.row_id|escape:'htmlall':'UTF-8'}_{$subfield.slug|escape:'htmlall':'UTF-8'}_{$lang_input.id_lang} tab-pane translation-field{if $lang_input.is_default} show active{/if}">
                                                                    {if isset($fieldRenderer)}
                                                                        {$fieldRenderer->renderAdminInput($subfield, $langValue, ['dataSubfield' => true, 'dataLangId' => $lang_input.id_lang, 'prefix' => 'acf_repeater_', 'suffix' => '_'|cat:$lang_input.id_lang]) nofilter}
                                                                    {else}
                                                                        <input type="text" 
                                                                               class="form-control acf-subfield-input" 
                                                                               data-subfield="{$subfield.slug|escape:'htmlall':'UTF-8'}" 
                                                                               data-lang-id="{$lang_input.id_lang}"
                                                                               value="{$langValue|escape:'htmlall':'UTF-8'}">
                                                                    {/if}
                                                                </div>
                                                            {/foreach}
                                                        </div>
                                                    </div>
                                                </div>
                                            {else}
                                                {* Non-translatable subfield *}
                                                {if isset($fieldRenderer)}
                                                    {$fieldRenderer->renderAdminInput($subfield, $subfieldValue, ['dataSubfield' => true]) nofilter}
                                                {else}
                                                    <input type="text" class="form-control acf-subfield-input" data-subfield="{$subfield.slug|escape:'htmlall':'UTF-8'}" value="{$subfieldValue|escape:'htmlall':'UTF-8'}">
                                                {/if}
                                            {/if}
                                        </div>
                                    {/foreach}
                                {else}
                                    <div class="alert alert-warning">
                                        {l s='No subfields defined for this repeater.' mod='wepresta_acf'}
                                    </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                    {assign var="rowIndex" value=$rowIndex+1}
                {/foreach}
            {/if}
        </div>
    {/if}

    <button type="button" class="btn btn-outline-secondary btn-sm acf-repeater-add mt-2">
        <span class="material-icons">add</span>
        {$buttonLabel|escape:'htmlall':'UTF-8'}
    </button>

    {if $repMin > 0 || $repMax > 0}
        <small class="form-text text-muted acf-repeater-limits">
            {if $repMin > 0 && $repMax > 0}
                {l s='Between %min% and %max% rows' mod='wepresta_acf' sprintf=['%min%' => $repMin, '%max%' => $repMax]}
            {elseif $repMin > 0}
                {l s='Minimum %min% rows' mod='wepresta_acf' sprintf=['%min%' => $repMin]}
            {else}
                {l s='Maximum %max% rows' mod='wepresta_acf' sprintf=['%max%' => $repMax]}
            {/if}
        </small>
    {/if}
</div>

