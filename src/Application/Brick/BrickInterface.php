<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Brick;

if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Wedev\Core\Contract\SubPluginInterface;

/**
 * Interface for ACF "Bricks" - mini-modules that extend ACF.
 *
 * Bricks can provide:
 * - Custom field types
 * - Location rules
 * - Data export formats
 * - etc.
 *
 * Extends SubPluginInterface which provides:
 * - getType(): string (brick type: field_type, location, exporter)
 * - getDescription(): string
 * - getAuthor(): string
 *
 * @example
 * final class MyBrick implements BrickInterface
 * {
 *     public static function getName(): string { return 'my_brick'; }
 *     public static function getVersion(): string { return '1.0.0'; }
 *     public static function getDependencies(): array { return ['wepresta_acf']; }
 *     public function boot(): void {}
 *     public function getFieldTypes(): array { return []; }
 *     public function getServices(): array { return []; }
 *     public function getType(): string { return 'field_type'; }
 *     public function getDescription(): string { return 'My custom brick'; }
 *     public function getAuthor(): string { return 'WECODE'; }
 * }
 */
interface BrickInterface extends SubPluginInterface
{
    // SubPluginInterface already provides:
    // - getType(): string
    // - getDescription(): string
    // - getAuthor(): string
    //
    // PluginInterface provides:
    // - getName(): string
    // - getVersion(): string
    // - getDependencies(): array
    // - boot(): void
    // - getFieldTypes(): array
    // - getServices(): array
}
