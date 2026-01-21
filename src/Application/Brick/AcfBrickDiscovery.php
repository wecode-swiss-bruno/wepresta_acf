<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Brick;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Discovers ACF bricks from installed modules.
 *
 * Scans for bricks in:
 * - modules/wepresta_acf_[name]/src/Brick/
 * - themes/[theme]/modules/acf_[name]/Brick/
 */
final class AcfBrickDiscovery
{
    /** @var array<string, BrickInterface> */
    private array $bricks = [];

    private bool $discovered = false;

    /**
     * Discovers all ACF bricks.
     *
     * @return array<string, BrickInterface>
     */
    public function discoverBricks(): array
    {
        if ($this->discovered) {
            return $this->bricks;
        }

        $this->discovered = true;

        // Scan modules directory for ACF brick modules
        $modulesDir = _PS_MODULE_DIR_;
        $pattern = $modulesDir . 'wepresta_acf_*/src/Brick/*Brick.php';

        foreach (glob($pattern) as $file) {
            $this->loadBrickFromFile($file);
        }

        // Also scan themes for ACF bricks
        $themesDir = _PS_ALL_THEMES_DIR_;
        $themePattern = $themesDir . '*/modules/acf_*/Brick/*Brick.php';

        foreach (glob($themePattern) as $file) {
            $this->loadBrickFromFile($file);
        }

        return $this->bricks;
    }

    /**
     * Gets field type bricks only.
     *
     * @return array<string, FieldTypeBrickInterface>
     */
    public function discoverFieldTypeBricks(): array
    {
        return array_filter(
            $this->discoverBricks(),
            fn ($brick) => $brick instanceof FieldTypeBrickInterface
        );
    }

    /**
     * Collects all field types from all bricks.
     *
     * @return array<string, class-string>
     */
    public function collectFieldTypes(): array
    {
        $fieldTypes = [];

        foreach ($this->discoverFieldTypeBricks() as $brick) {
            $type = $brick->getFieldType();
            $fieldTypes[$type->getType()] = \get_class($type);
        }

        return $fieldTypes;
    }

    /**
     * Collects all admin JS assets from field type bricks.
     *
     * @return array<string>
     */
    public function collectAdminJsAssets(): array
    {
        $assets = [];

        foreach ($this->discoverFieldTypeBricks() as $brick) {
            foreach ($brick->getAdminJsAssets() as $asset) {
                $assets[] = $asset;
            }
        }

        return array_unique($assets);
    }

    /**
     * Collects all admin CSS assets from field type bricks.
     *
     * @return array<string>
     */
    public function collectAdminCssAssets(): array
    {
        $assets = [];

        foreach ($this->discoverFieldTypeBricks() as $brick) {
            foreach ($brick->getAdminCssAssets() as $asset) {
                $assets[] = $asset;
            }
        }

        return array_unique($assets);
    }

    /**
     * Gets a specific brick by name.
     */
    public function getBrick(string $name): ?BrickInterface
    {
        $this->discoverBricks();

        return $this->bricks[$name] ?? null;
    }

    /**
     * Checks if a brick exists.
     */
    public function hasBrick(string $name): bool
    {
        return $this->getBrick($name) !== null;
    }

    /**
     * Clears the discovery cache.
     */
    public function clearCache(): void
    {
        $this->bricks = [];
        $this->discovered = false;
    }

    /**
     * Loads a brick from a PHP file.
     */
    private function loadBrickFromFile(string $file): void
    {
        if (! file_exists($file)) {
            return;
        }

        // Extract class name from file
        $content = file_get_contents($file);

        // Find namespace
        if (! preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
            return;
        }

        // Find class name
        if (! preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return;
        }

        $className = $nsMatch[1] . '\\' . $classMatch[1];

        // Require the file if class doesn't exist
        if (! class_exists($className)) {
            require_once $file;
        }

        if (! class_exists($className)) {
            return;
        }

        // Check if it implements BrickInterface
        if (! is_subclass_of($className, BrickInterface::class)) {
            return;
        }

        // Instantiate and register
        $brick = new $className();
        $this->bricks[$brick::getName()] = $brick;
    }
}
