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

namespace WeprestaAcf\Wedev\Extension\EntityFields;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Central registry for all entity types that support custom fields.
 *
 * Modules register their entity types via EntityFieldProviderInterface,
 * and the registry provides access to hooks, context builders, and entity metadata.
 *
 * @example
 * // Register an entity type
 * $registry->registerEntityType('product', $productProvider);
 *
 * // Get all hooks for an entity type
 * $hooks = $registry->getHooksForEntityType('product');
 * // Returns: ['displayAdminProductsExtra', 'actionProductUpdate', 'actionProductAdd']
 *
 * // Get provider for building context
 * $provider = $registry->getEntityType('product');
 * $context = $provider->buildContext(123);
 */
final class EntityFieldRegistry
{
    /**
     * Registered entity type providers.
     *
     * @var array<string, EntityFieldProviderInterface>
     */
    private array $providers = [];

    /**
     * Cache of all hooks by entity type.
     *
     * @var array<string, array<string>>|null
     */
    private ?array $hooksCache = null;

    /**
     * Registers an entity type provider.
     *
     * @param string $entityType Entity type identifier (e.g., 'product', 'cpt_event')
     * @param EntityFieldProviderInterface $provider Provider instance
     */
    public function registerEntityType(string $entityType, EntityFieldProviderInterface $provider): void
    {
        $this->providers[$entityType] = $provider;
        $this->hooksCache = null; // Invalidate cache
    }

    /**
     * Unregisters an entity type.
     */
    public function unregisterEntityType(string $entityType): void
    {
        unset($this->providers[$entityType]);
        $this->hooksCache = null;
    }

    /**
     * Gets the provider for a specific entity type.
     */
    public function getEntityType(string $entityType): ?EntityFieldProviderInterface
    {
        return $this->providers[$entityType] ?? null;
    }

    /**
     * Checks if an entity type is registered.
     */
    public function hasEntityType(string $entityType): bool
    {
        return isset($this->providers[$entityType]);
    }

    /**
     * Gets all registered entity types.
     *
     * @return array<string, EntityFieldProviderInterface>
     */
    public function getAllEntityTypes(): array
    {
        return $this->providers;
    }

    /**
     * Gets all entity type identifiers.
     *
     * @return array<string>
     */
    public function getEntityTypeIds(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Gets all hooks (display + action) for a specific entity type.
     *
     * @return array<string> Hook names
     */
    public function getHooksForEntityType(string $entityType): array
    {
        $provider = $this->getEntityType($entityType);

        if ($provider === null) {
            return [];
        }

        return array_merge(
            $provider->getDisplayHooks(),
            $provider->getActionHooks()
        );
    }

    /**
     * Gets all hooks for all registered entity types.
     *
     * @return array<string> Unique hook names
     */
    public function getAllHooks(): array
    {
        if ($this->hooksCache !== null) {
            return $this->hooksCache;
        }

        $allHooks = [];

        foreach ($this->providers as $provider) {
            $allHooks = array_merge(
                $allHooks,
                $provider->getDisplayHooks(),
                $provider->getActionHooks()
            );
        }

        $this->hooksCache = array_unique($allHooks);

        return $this->hooksCache;
    }

    /**
     * Gets all display hooks for all entity types.
     *
     * @return array<string> Unique display hook names
     */
    public function getAllDisplayHooks(): array
    {
        $hooks = [];

        foreach ($this->providers as $provider) {
            $hooks = array_merge($hooks, $provider->getDisplayHooks());
        }

        return array_unique($hooks);
    }

    /**
     * Gets all action hooks for all entity types.
     *
     * @return array<string> Unique action hook names
     */
    public function getAllActionHooks(): array
    {
        $hooks = [];

        foreach ($this->providers as $provider) {
            $hooks = array_merge($hooks, $provider->getActionHooks());
        }

        return array_unique($hooks);
    }

    /**
     * Finds entity types that use a specific hook.
     *
     * @return array<string> Entity type identifiers
     */
    public function getEntityTypesForHook(string $hookName): array
    {
        $entityTypes = [];

        foreach ($this->providers as $entityType => $provider) {
            $hooks = array_merge(
                $provider->getDisplayHooks(),
                $provider->getActionHooks()
            );

            if (\in_array($hookName, $hooks, true)) {
                $entityTypes[] = $entityType;
            }
        }

        return $entityTypes;
    }

    /**
     * Clears the hooks cache.
     */
    public function clearCache(): void
    {
        $this->hooksCache = null;
    }
}
