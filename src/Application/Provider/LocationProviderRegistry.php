<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider;

use WeprestaAcf\Application\Service\EntityFieldRegistryInitializer;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldRegistry;

/**
 * Registry for location providers
 */
final class LocationProviderRegistry
{
    /** @var array<string, LocationProviderInterface> */
    private array $providers = [];
    /** @var array<array<string, mixed>>|null */
    private ?array $locationsCache = null;
    private ?EntityFieldRegistryInitializer $initializer = null;

    public function __construct(
        private readonly ?EntityFieldRegistry $entityFieldRegistry = null
    ) {
        $this->register(new CoreLocationProvider($this->entityFieldRegistry));
    }

    /**
     * Set the initializer to ensure entity types are registered before getting locations
     */
    public function setInitializer(EntityFieldRegistryInitializer $initializer): void
    {
        $this->initializer = $initializer;
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

    public function has(string $identifier): bool { return isset($this->providers[$identifier]); }
    public function get(string $identifier): ?LocationProviderInterface { return $this->providers[$identifier] ?? null; }

    /** @return array<LocationProviderInterface> */
    public function getAll(): array
    {
        $providers = array_values($this->providers);
        usort($providers, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
        return $providers;
    }

    /** @return array<array{type: string, value: string, label: string, group: string, icon?: string, description?: string, provider: string}> */
    public function getAllLocations(): array
    {
        if ($this->locationsCache !== null) {
            return $this->locationsCache;
        }

        // Ensure entity types are registered before retrieving locations
        if ($this->initializer !== null) {
            $this->initializer->initialize();
        }

        $locations = [];
        foreach ($this->getAll() as $provider) {
            foreach ($provider->getLocations() as $location) {
                $location['provider'] = $provider->getIdentifier();
                $locations[] = $location;
            }
        }

        // Add entity types from EntityFieldRegistry
        if ($this->entityFieldRegistry !== null) {
            $langId = (int) (\Context::getContext()->language->id ?? 1);
            foreach ($this->entityFieldRegistry->getAllEntityTypes() as $entityType => $provider) {
                $locations[] = [
                    'type' => 'entity_type',
                    'value' => $entityType,
                    'label' => $provider->getEntityLabel($langId),
                    'group' => 'PrestaShop Entities',
                    'icon' => 'inventory_2',
                    'description' => sprintf('Display fields on %s edit pages', $provider->getEntityLabel($langId)),
                    'provider' => 'entity_field_registry',
                ];
            }
        }

        $this->locationsCache = $locations;
        return $locations;
    }

    /** @return array<string, array<array<string, mixed>>> */
    public function getLocationsGrouped(): array
    {
        $grouped = [];
        foreach ($this->getAllLocations() as $location) {
            $grouped[$location['group'] ?? 'Other'][] = $location;
        }
        return $grouped;
    }

    /** @param array<array<string, mixed>> $rules @param array<string, mixed> $context */
    public function matchLocation(array $rules, array $context): bool
    {
        if (empty($rules)) { return true; }
        foreach ($rules as $ruleGroup) {
            if ($this->matchRuleGroup($ruleGroup, $context)) { return true; }
        }
        return false;
    }

    /** @param array<string, mixed>|array<array<string, mixed>> $ruleGroup @param array<string, mixed> $context */
    private function matchRuleGroup(array $ruleGroup, array $context): bool
    {
        if (isset($ruleGroup['type'])) {
            return $this->matchSingleRule($ruleGroup, $context);
        }
        foreach ($ruleGroup as $rule) {
            if (!$this->matchSingleRule($rule, $context)) { return false; }
        }
        return true;
    }

    /** @param array<string, mixed> $rule @param array<string, mixed> $context */
    private function matchSingleRule(array $rule, array $context): bool
    {
        foreach ($this->getAll() as $provider) {
            if ($provider->matchLocation($rule, $context)) { return true; }
        }
        return false;
    }

    public function clearCache(): void { $this->locationsCache = null; }
    /** @return array<string> */
    public function getProviderIdentifiers(): array { return array_keys($this->providers); }
}

