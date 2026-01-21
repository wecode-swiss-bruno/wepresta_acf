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

namespace WeprestaAcf\Application\Brick;

if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Application\FieldType\FieldTypeInterface;
use WeprestaAcf\Wedev\Core\Contract\AssetProviderInterface;

/**
 * Interface for bricks that provide custom field types.
 *
 * Extends:
 * - BrickInterface (plugin metadata)
 * - AssetProviderInterface (JS/CSS assets)
 *
 * @example
 * final class SignatureBrick implements FieldTypeBrickInterface
 * {
 *     public static function getName(): string { return 'signature'; }
 *     public static function getVersion(): string { return '1.0.0'; }
 *     public static function getDependencies(): array { return ['wepresta_acf']; }
 *     public function boot(): void {}
 *     public function getFieldTypes(): array { return ['signature' => SignatureField::class]; }
 *     public function getServices(): array { return []; }
 *     public function getType(): string { return 'field_type'; }
 *     public function getDescription(): string { return 'Signature pad field'; }
 *     public function getAuthor(): string { return 'WECODE'; }
 *
 *     public function getFieldType(): FieldTypeInterface { return new SignatureField(); }
 *     public function getAdminJsAssets(): array { return ['views/js/signature.js']; }
 *     public function getAdminCssAssets(): array { return []; }
 *     public function getFrontJsAssets(): array { return []; }
 *     public function getFrontCssAssets(): array { return []; }
 * }
 */
interface FieldTypeBrickInterface extends BrickInterface, AssetProviderInterface
{
    /**
     * Returns the field type instance.
     */
    public function getFieldType(): FieldTypeInterface;
}
