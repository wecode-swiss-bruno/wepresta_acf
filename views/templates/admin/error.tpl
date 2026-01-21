{**
 * WePresta ACF - Error Display Template
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<div class="alert alert-danger">
    <strong>{l s='Error:' d='Modules.Weprestaacf.Admin'}</strong>
    {$error_message|escape:'html':'UTF-8'}
</div>