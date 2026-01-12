<?php

declare(strict_types=1);

use WeprestaAcf\Application\Config\EntityHooksConfig;

/**
 * Upgrade to 1.2.2
 * - Registers new hooks for CMS pages (cms_page) without requiring module re-install.
 */
function upgrade_module_1_2_2(Module $module): bool
{
    // Register all hooks from centralized config (idempotent).
    return (bool) $module->registerHook(EntityHooksConfig::getAllHooks());
}
