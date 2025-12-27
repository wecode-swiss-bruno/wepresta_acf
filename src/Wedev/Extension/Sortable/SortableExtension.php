<?php

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Extension\Sortable;

use WeprestaAcf\Wedev\Core\Contract\ExtensionInterface;

/**
 * Sortable Extension.
 * 
 * Provides reusable sortable list functionality:
 * - Drag and drop reordering
 * - Order tracking via hidden inputs
 * - Fallback for browsers without SortableJS
 */
final class SortableExtension implements ExtensionInterface
{
    public const VERSION = '1.0.0';

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return 'Sortable';
    }

    /**
     * @inheritDoc
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return []; // No dependencies
    }

    /**
     * Get admin JS assets.
     *
     * @return string[]
     */
    public function getAdminJsAssets(): array
    {
        return [
            'assets/js/wedev-sortable.js',
        ];
    }

    /**
     * Get admin CSS assets.
     *
     * @return string[]
     */
    public function getAdminCssAssets(): array
    {
        return []; // Uses Bootstrap classes only
    }
}

