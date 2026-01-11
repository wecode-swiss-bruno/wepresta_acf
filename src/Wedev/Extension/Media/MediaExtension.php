<?php

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Extension\Media;

use WeprestaAcf\Wedev\Core\Contract\ExtensionInterface;

/**
 * Media Extension.
 *
 * Provides reusable media handling components:
 * - Dropzone file upload
 * - Image lightbox
 * - Video embed parsing
 * - File previews
 * - MIME type utilities
 */
final class MediaExtension implements ExtensionInterface
{
    public const VERSION = '1.0.0';

    public static function getName(): string
    {
        return 'Media';
    }

    public static function getVersion(): string
    {
        return self::VERSION;
    }

    public static function getDependencies(): array
    {
        return ['UI']; // Requires UI extension for toast notifications
    }

    /**
     * Get admin JS assets.
     *
     * @return string[] Paths relative to extension directory
     */
    public function getAdminJsAssets(): array
    {
        return [
            'assets/js/wedev-media.js',
        ];
    }

    /**
     * Get admin CSS assets.
     *
     * @return string[] Paths relative to extension directory
     */
    public function getAdminCssAssets(): array
    {
        return [
            'assets/css/wedev-media.css',
        ];
    }

    /**
     * Get front JS assets.
     *
     * @return string[]
     */
    public function getFrontJsAssets(): array
    {
        return [
            'assets/js/wedev-media-front.js',
        ];
    }

    /**
     * Get front CSS assets.
     *
     * @return string[]
     */
    public function getFrontCssAssets(): array
    {
        return [];
    }
}
