<?php
/**
 * Upgrade script for WePresta ACF 1.2.0
 *
 * Registers all FormBuilderModifier, FormHandler, and ObjectModel hooks
 * for 49 entity types (41 Symfony + 8 Legacy).
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade to 1.2.0 - Register universal entity hooks
 *
 * @param WeprestaAcf $module
 * @return bool
 */
function upgrade_module_1_2_0($module): bool
{
    // Get all hooks from EntityHooksConfig
    $allHooks = \WeprestaAcf\Application\Config\EntityHooksConfig::getAllHooks();

    // Add static hooks
    $staticHooks = [
        'actionAdminControllerSetMedia',
        'actionFrontControllerSetMedia',
        'displayHeader',
        'displayProductAdditionalInfo',
    ];

    $hooks = array_unique(array_merge($staticHooks, $allHooks));

    // Register all hooks
    $success = true;
    foreach ($hooks as $hookName) {
        try {
            if (!$module->isRegisteredInHook($hookName)) {
                if (!$module->registerHook($hookName)) {
                    PrestaShopLogger::addLog(
                        "[wepresta_acf] Upgrade 1.2.0: Failed to register hook: {$hookName}",
                        2
                    );
                    // Don't fail the whole upgrade for a single hook
                }
            }
        } catch (\Exception $e) {
            PrestaShopLogger::addLog(
                "[wepresta_acf] Upgrade 1.2.0: Error registering hook {$hookName}: " . $e->getMessage(),
                3
            );
        }
    }

    PrestaShopLogger::addLog(
        "[wepresta_acf] Upgrade 1.2.0 completed: Registered " . count($hooks) . " hooks",
        1
    );

    return $success;
}

