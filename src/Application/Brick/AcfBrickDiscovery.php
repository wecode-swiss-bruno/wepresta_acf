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
