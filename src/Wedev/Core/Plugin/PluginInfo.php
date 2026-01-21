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

namespace WeprestaAcf\Wedev\Core\Plugin;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Value object containing plugin metadata.
 *
 * Immutable object returned by PluginDiscovery with all plugin information.
 */
final readonly class PluginInfo
{
    /**
     * @param string $name Plugin unique name
     * @param string $version Plugin version (SemVer)
     * @param array<string> $dependencies Required dependencies
     * @param class-string $className Full class name
     * @param string $filePath Absolute path to plugin file
     */
    public function __construct(
        public string $name,
        public string $version,
        public array $dependencies,
        public string $className,
        public string $filePath
    ) {
    }

    /**
     * Creates a new plugin instance.
     */
    public function createInstance(): object
    {
        return new ($this->className)();
    }

    /**
     * Checks if this plugin depends on another.
     */
    public function dependsOn(string $dependency): bool
    {
        return \in_array($dependency, $this->dependencies, true);
    }

    /**
     * Checks if this plugin requires a WEDEV extension.
     */
    public function requiresExtension(string $extension): bool
    {
        return \in_array('ext:' . $extension, $this->dependencies, true);
    }

    /**
     * Converts to array for serialization.
     *
     * @return array{name: string, version: string, dependencies: array<string>, className: string, filePath: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'dependencies' => $this->dependencies,
            'className' => $this->className,
            'filePath' => $this->filePath,
        ];
    }
}
