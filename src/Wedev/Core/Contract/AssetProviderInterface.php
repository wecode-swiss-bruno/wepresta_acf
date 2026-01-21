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

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Core\Contract;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Interface for plugins that provide frontend/admin assets.
 *
 * Implement this interface when your plugin needs to register
 * JavaScript or CSS files in the back-office or front-office.
 *
 * Assets are returned as paths relative to the plugin's directory.
 *
 * @example
 * final class MyPlugin implements PluginInterface, AssetProviderInterface
 * {
 *     public function getAdminJsAssets(): array
 *     {
 *         return [
 *             'views/js/admin/my-plugin.js',
 *             'views/js/admin/components.js',
 *         ];
 *     }
 *
 *     public function getAdminCssAssets(): array
 *     {
 *         return ['views/css/admin/my-plugin.css'];
 *     }
 *
 *     public function getFrontJsAssets(): array
 *     {
 *         return ['views/js/front/my-plugin.js'];
 *     }
 *
 *     public function getFrontCssAssets(): array
 *     {
 *         return ['views/css/front/my-plugin.css'];
 *     }
 * }
 */
interface AssetProviderInterface
{
    /**
     * Returns admin JavaScript asset paths.
     *
     * Paths should be relative to the plugin's root directory.
     *
     * @return array<string> List of JS file paths
     */
    public function getAdminJsAssets(): array;

    /**
     * Returns admin CSS asset paths.
     *
     * Paths should be relative to the plugin's root directory.
     *
     * @return array<string> List of CSS file paths
     */
    public function getAdminCssAssets(): array;

    /**
     * Returns front-office JavaScript asset paths.
     *
     * Paths should be relative to the plugin's root directory.
     *
     * @return array<string> List of JS file paths
     */
    public function getFrontJsAssets(): array;

    /**
     * Returns front-office CSS asset paths.
     *
     * Paths should be relative to the plugin's root directory.
     *
     * @return array<string> List of CSS file paths
     */
    public function getFrontCssAssets(): array;
}
