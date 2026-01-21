<?php

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
