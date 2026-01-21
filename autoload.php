<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * Module autoloader - PrestaShop compatible.
 *
 * This autoloader handles two contexts:
 * 1. PrestaShop context: Registers only the module's PSR-4 autoloader (no dev dependencies)
 * 2. CLI context (PHPStan, PHP-CS-Fixer): Loads full Composer autoloader
 *
 * IMPORTANT: PrestaShop loads vendor/autoload.php from modules during kernel boot.
 * After running `composer install`, execute `composer run patch-autoload` to make
 * vendor/autoload.php PrestaShop-safe (avoids Symfony version conflicts).
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}


// In PrestaShop context, register only the module's PSR-4 autoloader
// This avoids conflicts with dev dependencies that have different Symfony versions
if (defined('_PS_VERSION_')) {
    if (! isset($GLOBALS['wepresta_acf_autoloader_registered'])) {
        $GLOBALS['wepresta_acf_autoloader_registered'] = true;
        $moduleDir = __DIR__;

        spl_autoload_register(function ($class) use ($moduleDir) {
            $prefix = 'WeprestaAcf\\';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return false;
            }

            $baseDir = $moduleDir . '/src/';
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;

                return true;
            }

            return false;
        }, true, true);
    }

    return;
}

// Outside PrestaShop (CLI for dev tools): load full Composer autoloader
$vendorAutoload = __DIR__ . '/vendor/autoload.php';

if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}
