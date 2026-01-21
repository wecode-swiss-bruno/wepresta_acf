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
 * Interface for WEDEV Plugins.
 *
 * Plugins are third-party extensions that can extend WEDEV-based modules.
 * They are discovered automatically from:
 * - modules/[name]/src/Plugin/
 * - themes/[name]/modules/[name]/Plugin/
 * - Custom paths registered via PluginDiscovery
 *
 * @example
 * // A plugin that adds custom field types to ACF
 * final class MyFieldsPlugin implements PluginInterface
 * {
 *     public static function getName(): string
 *     {
 *         return 'MyFields';
 *     }
 *
 *     public static function getVersion(): string
 *     {
 *         return '1.0.0';
 *     }
 *
 *     public static function getDependencies(): array
 *     {
 *         return ['wepresta_acf']; // Requires ACF module
 *     }
 *
 *     public function boot(): void
 *     {
 *         // Called when plugin is loaded
 *     }
 *
 *     public function getFieldTypes(): array
 *     {
 *         return [
 *             'my_custom_field' => MyCustomFieldType::class,
 *         ];
 *     }
 *
 *     public function getServices(): array
 *     {
 *         return [
 *             MyService::class,
 *         ];
 *     }
 * }
 */
interface PluginInterface
{
    /**
     * Returns the unique name of the plugin.
     *
     * Should be a unique identifier in snake_case or PascalCase.
     */
    public static function getName(): string;

    /**
     * Returns the version of the plugin.
     *
     * Format: SemVer (MAJOR.MINOR.PATCH)
     */
    public static function getVersion(): string;

    /**
     * Returns the dependencies of this plugin.
     *
     * Dependencies can be:
     * - Module names (e.g., 'wepresta_acf')
     * - Other plugin names
     * - WEDEV extensions (e.g., 'ext:Http', 'ext:Jobs')
     *
     * @return array<string>
     */
    public static function getDependencies(): array;

    /**
     * Called when the plugin is booted.
     *
     * Use this to register hooks, event subscribers, or perform initialization.
     */
    public function boot(): void;

    /**
     * Returns custom field types provided by this plugin.
     *
     * Used by ACF-style modules to discover additional field types.
     *
     * @return array<string, class-string> Map of field type key => class name
     */
    public function getFieldTypes(): array;

    /**
     * Returns service classes to be registered in the container.
     *
     * @return array<class-string> List of service class names
     */
    public function getServices(): array;
}
