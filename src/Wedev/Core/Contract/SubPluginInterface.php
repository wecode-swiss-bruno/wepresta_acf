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
 * Interface for sub-plugins (plugins that extend other plugins).
 *
 * This is a generic pattern for "plugins that extend plugins":
 * - ACF Bricks (custom field types)
 * - Payment Gateways
 * - Shipping Carriers
 * - Export Formats
 * - etc.
 *
 * Each module can define its own interface extending SubPluginInterface
 * with module-specific methods.
 *
 * @example
 * // ACF module defines its brick interface
 * interface BrickInterface extends SubPluginInterface
 * {
 *     public function getFieldType(): FieldTypeInterface;
 * }
 *
 * // Third-party module implements it
 * final class SignatureBrick implements BrickInterface
 * {
 *     public static function getName(): string { return 'signature'; }
 *     public static function getVersion(): string { return '1.0.0'; }
 *     public static function getDependencies(): array { return ['wepresta_acf']; }
 *     public function getType(): string { return 'field_type'; }
 *     public function getDescription(): string { return 'Signature field type'; }
 *     public function getAuthor(): string { return 'WeCode'; }
 *     // ...
 * }
 */
interface SubPluginInterface extends PluginInterface
{
    /**
     * Returns the type/category of this sub-plugin.
     *
     * Examples:
     * - 'field_type' for ACF field types
     * - 'payment' for payment gateways
     * - 'carrier' for shipping carriers
     * - 'exporter' for export formats
     */
    public function getType(): string;

    /**
     * Returns a human-readable description of the sub-plugin.
     */
    public function getDescription(): string;

    /**
     * Returns the author name or company.
     */
    public function getAuthor(): string;
}
