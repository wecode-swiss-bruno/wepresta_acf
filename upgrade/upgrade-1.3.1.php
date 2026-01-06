<?php
/**
 * WePresta ACF - Upgrade to 1.3.1
 *
 * This upgrade fixes the bug where Product/Category objects were being accessed as arrays
 * in front-office hooks, causing fatal errors.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade module to version 1.3.1
 *
 * @param WeprestaAcf $module
 * @return bool
 */
function upgrade_module_1_3_1($module)
{
    try {
        $module->log('Upgrade 1.3.1: Bug fix for Product/Category object handling in front hooks completed');
        return true;

    } catch (Exception $e) {
        $module->log('Upgrade 1.3.1 failed: ' . $e->getMessage(), 3);
        return false;
    }
}
