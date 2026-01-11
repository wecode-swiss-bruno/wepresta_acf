<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider;

use WeprestaAcf\Application\Config\EntityHooksConfig;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldRegistry;

/**
 * Registry for location providers.
 */
final class LocationProviderRegistry
{
    /** @var array<string, LocationProviderInterface> */
    private array $providers = [];

    /** @var array<array<string, mixed>>|null */
    private ?array $locationsCache = null;

    /** @var array<EntityFieldProviderInterface> */
    private array $entityFieldProviders = [];

    private bool $entityFieldsInitialized = false;

    public function __construct(
        private readonly ?EntityFieldRegistry $entityFieldRegistry = null
    ) {
        $this->register(new CoreLocationProvider($this->entityFieldRegistry));
    }

    /**
     * Set the entity field providers directly - they will be registered on demand.
     *
     * @param array<EntityFieldProviderInterface> $providers
     */
    public function setEntityFieldProviders(array $providers): void
    {
        $this->entityFieldProviders = $providers;
        $this->entityFieldsInitialized = false;
        $this->locationsCache = null;
    }

    public function register(LocationProviderInterface $provider): self
    {
        $this->providers[$provider->getIdentifier()] = $provider;
        $this->locationsCache = null;

        return $this;
    }

    public function unregister(string $identifier): self
    {
        unset($this->providers[$identifier]);
        $this->locationsCache = null;

        return $this;
    }

    public function has(string $identifier): bool
    {
        return isset($this->providers[$identifier]);
    }

    public function get(string $identifier): ?LocationProviderInterface
    {
        return $this->providers[$identifier] ?? null;
    }

    /**
     * @return array<LocationProviderInterface>
     */
    public function getAll(): array
    {
        $providers = array_values($this->providers);
        usort($providers, fn ($a, $b) => $b->getPriority() <=> $a->getPriority());

        return $providers;
    }

    /**
     * @return array<array{type: string, value: string, label: string, group: string, icon?: string, description?: string, provider: string, enabled: bool}>
     */
    public function getAllLocations(): array
    {
        if ($this->locationsCache !== null) {
            return $this->locationsCache;
        }

        // Ensure entity types are registered before retrieving locations
        $this->ensureEntityFieldsInitialized();

        $locations = [];

        foreach ($this->getAll() as $provider) {
            foreach ($provider->getLocations() as $location) {
                $location['provider'] = $provider->getIdentifier();
                $location['enabled'] = EntityHooksConfig::isEntityEnabled($location['value'] ?? '');
                $locations[] = $location;
            }
        }

        // Add ALL entity types from EntityHooksConfig (comprehensive list)
        $groupedEntities = EntityHooksConfig::getEntitiesGroupedByCategory();

        foreach ($groupedEntities as $category => $entities) {
            foreach ($entities as $entityType => $entityInfo) {
                // Skip if already added from a provider
                $alreadyExists = false;

                foreach ($locations as $loc) {
                    if (($loc['value'] ?? '') === $entityType) {
                        $alreadyExists = true;

                        break;
                    }
                }

                if ($alreadyExists) {
                    continue;
                }

                $icon = match ($category) {
                    'Catalog' => 'inventory_2',
                    'Customers' => 'people',
                    'Orders' => 'shopping_cart',
                    'CMS' => 'article',
                    'Localization' => 'language',
                    'Configuration' => 'settings',
                    'Marketing' => 'campaign',
                    'Advanced' => 'code',
                    'Other' => 'more_horiz',
                    default => 'category',
                };

                $locations[] = [
                    'type' => 'entity_type',
                    'value' => $entityType,
                    'label' => $entityInfo['label'],
                    'group' => $category,
                    'icon' => $icon,
                    'description' => \sprintf('Display fields on %s edit pages', $entityInfo['label']),
                    'provider' => 'entity_hooks_config',
                    'integration_type' => $entityInfo['type'], // 'symfony' or 'legacy'
                    'enabled' => EntityHooksConfig::isEntityEnabled($entityType),
                ];
            }
        }

        $this->locationsCache = $locations;

        return $locations;
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public function getLocationsGrouped(): array
    {
        $grouped = [];

        foreach ($this->getAllLocations() as $location) {
            $grouped[$location['group'] ?? 'Other'][] = $location;
        }

        // Sort locations within each group: enabled first, then disabled
        foreach ($grouped as $groupName => &$locations) {
            usort($locations, function ($a, $b) {
                $aEnabled = $a['enabled'] ?? true; // Default to enabled if not set
                $bEnabled = $b['enabled'] ?? true;

                if ($aEnabled === $bEnabled) {
                    // If same status, sort alphabetically by label
                    return strcmp($a['label'] ?? '', $b['label'] ?? '');
                }

                // Enabled items come first
                return $aEnabled ? -1 : 1;
            });
        }
        unset($locations); // Break reference

        return $grouped;
    }

    /**
     * Match location rules against context.
     * Rules are stored in JsonLogic format: {"==": [{"var": "entity_type"}, "product"]}.
     *
     * @param array<array<string, mixed>> $rules
     * @param array<string, mixed> $context
     */
    public function matchLocation(array $rules, array $context): bool
    {
        // If no rules defined, match everything (backward compatibility)
        if (empty($rules)) {
            return true;
        }

        // Any rule matching = group is shown (OR logic between rules)
        foreach ($rules as $rule) {
            if ($this->matchJsonLogicRule($rule, $context)) {
                return true;
            }
        }

        return false;
    }

    public function clearCache(): void
    {
        $this->locationsCache = null;
    }

    /**
     * @return array<string>
     */
    public function getProviderIdentifiers(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Ensure all entity field providers are registered with the registry.
     */
    private function ensureEntityFieldsInitialized(): void
    {
        if ($this->entityFieldsInitialized || $this->entityFieldRegistry === null) {
            return;
        }

        foreach ($this->entityFieldProviders as $provider) {
            if ($provider instanceof EntityFieldProviderInterface) {
                $this->entityFieldRegistry->registerEntityType(
                    $provider->getEntityType(),
                    $provider
                );
            }
        }

        $this->entityFieldsInitialized = true;
    }

    /**
     * Match a single JsonLogic rule against context.
     * Format: {"==": [{"var": "entity_type"}, "product"]}
     *         {"!=": [{"var": "entity_type"}, "category"]}.
     *
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $context
     */
    private function matchJsonLogicRule(array $rule, array $context): bool
    {
        // Handle "==" operator
        if (isset($rule['==']) && \is_array($rule['==']) && \count($rule['==']) === 2) {
            [$left, $right] = $rule['=='];
            $leftValue = $this->resolveJsonLogicValue($left, $context);
            $rightValue = $this->resolveJsonLogicValue($right, $context);

            return $leftValue === $rightValue;
        }

        // Handle "!=" operator
        if (isset($rule['!=']) && \is_array($rule['!=']) && \count($rule['!=']) === 2) {
            [$left, $right] = $rule['!='];
            $leftValue = $this->resolveJsonLogicValue($left, $context);
            $rightValue = $this->resolveJsonLogicValue($right, $context);

            return $leftValue !== $rightValue;
        }

        // Legacy format support: {"type": "entity_type", "operator": "equals", "value": "product"}
        if (isset($rule['type'])) {
            return $this->matchLegacyRule($rule, $context);
        }

        return false;
    }

    /**
     * Resolve a JsonLogic value (either a literal or a variable reference).
     *
     * @param array<string, mixed> $context
     */
    private function resolveJsonLogicValue(mixed $value, array $context): mixed
    {
        if (\is_array($value) && isset($value['var'])) {
            // Variable reference: {"var": "entity_type"}
            $varName = $value['var'];

            return $context[$varName] ?? null;
        }

        // Literal value
        return $value;
    }

    /**
     * Match legacy format rules (for backward compatibility).
     *
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $context
     */
    private function matchLegacyRule(array $rule, array $context): bool
    {
        foreach ($this->getAll() as $provider) {
            if ($provider->matchLocation($rule, $context)) {
                return true;
            }
        }

        return false;
    }
}
