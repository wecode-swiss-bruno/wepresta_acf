<?php
/**
 * Upgrade script to register missing Symfony hooks for PS9 compatibility.
 *
 * This script ensures that all Symfony FormBuilder hooks are registered
 * even if the module was installed before they were added to the code.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param WeprestaAcf $module
 *
 * @return bool
 */
function upgrade_module_1_0_1($module): bool
{
    try {
        // Get all hooks that should be registered
        $allHooks = $module->getAllHooks();
        
        // Get currently registered hooks
        $registeredHooks = [];
        $sql = 'SELECT h.name 
                FROM ' . _DB_PREFIX_ . 'hook h
                INNER JOIN ' . _DB_PREFIX_ . 'hook_module hm ON h.id_hook = hm.id_hook
                WHERE hm.id_module = ' . (int) $module->id;
        
        $result = Db::getInstance()->executeS($sql);
        if ($result) {
            foreach ($result as $row) {
                $registeredHooks[] = $row['name'];
            }
        }
        
        // Find missing hooks
        $missingHooks = array_diff($allHooks, $registeredHooks);
        
        if (empty($missingHooks)) {
            PrestaShopLogger::addLog('[ACF Upgrade 1.0.1] All hooks already registered', 1);
            return true;
        }
        
        PrestaShopLogger::addLog('[ACF Upgrade 1.0.1] Registering ' . count($missingHooks) . ' missing hooks: ' . implode(', ', $missingHooks), 1);
        
        // Register missing hooks
        foreach ($missingHooks as $hookName) {
            if (!$module->registerHook($hookName)) {
                PrestaShopLogger::addLog('[ACF Upgrade 1.0.1] Failed to register hook: ' . $hookName, 3);
                return false;
            }
            PrestaShopLogger::addLog('[ACF Upgrade 1.0.1] Registered hook: ' . $hookName, 1);
        }
        
        PrestaShopLogger::addLog('[ACF Upgrade 1.0.1] Successfully registered all missing hooks', 1);
        
        // Clear cache
        if (method_exists($module, 'clearCache')) {
            $module->clearCache();
        }
        
        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog('[ACF Upgrade 1.0.1] Error: ' . $e->getMessage(), 3);
        return false;
    }
}
