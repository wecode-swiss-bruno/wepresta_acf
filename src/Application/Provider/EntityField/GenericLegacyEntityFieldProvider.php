<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use WeprestaAcf\Application\Config\EntityHooksConfig;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Generic entity field provider for legacy ObjectModel entities.
 *
 * This class provides a reusable implementation for entities
 * that don't use Symfony forms but rely on ObjectModel hooks.
 */
final class GenericLegacyEntityFieldProvider implements EntityFieldProviderInterface
{
    /**
     * @param string $entityType Entity type identifier (e.g., 'supplier', 'customer_address')
     * @param array{
     *     label: string,
     *     category: string,
     *     object_class: string,
     *     display_hooks?: array<string>,
     *     action_hooks: array<string>
     * } $config Configuration from EntityHooksConfig
     */
    public function __construct(
        private readonly string $entityType,
        private readonly array $config
    ) {
    }

    /**
     * Creates a provider from EntityHooksConfig.
     *
     * @param string $entityType Entity type identifier
     * @return self|null Provider instance or null if entity not found
     */
    public static function createFromConfig(string $entityType): ?self
    {
        $config = EntityHooksConfig::getEntityConfig($entityType);
        if ($config === null || ($config['integration_type'] ?? '') !== 'legacy') {
            return null;
        }

        return new self($entityType, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayHooks(): array
    {
        return $this->config['display_hooks'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getActionHooks(): array
    {
        return $this->config['action_hooks'] ?? [];
    }

    /**
     * Gets the ObjectModel class name.
     *
     * @return string Class name (e.g., 'Address', 'Supplier')
     */
    public function getObjectClass(): string
    {
        return $this->config['object_class'];
    }

    /**
     * Gets the category this entity belongs to.
     *
     * @return string Category name
     */
    public function getCategory(): string
    {
        return $this->config['category'] ?? 'Other';
    }

    /**
     * {@inheritdoc}
     */
    public function buildContext(int $entityId): array
    {
        $context = [
            'entity_type' => $this->entityType,
            'entity_id' => $entityId,
        ];

        // Try to load additional context from ObjectModel if class exists
        $className = $this->config['object_class'];
        if (class_exists($className)) {
            try {
                $object = new $className($entityId);
                if (\Validate::isLoadedObject($object)) {
                    // Add common properties to context
                    if (property_exists($object, 'id_shop')) {
                        $context['shop_id'] = (int) $object->id_shop;
                    }
                    if (property_exists($object, 'active')) {
                        $context['active'] = (bool) $object->active;
                    }
                }
            } catch (\Exception $e) {
                // Silently fail if object cannot be loaded
            }
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityLabel(int $langId): string
    {
        return $this->config['label'] ?? ucfirst(str_replace('_', ' ', $this->entityType));
    }

    /**
     * Whether this entity uses Symfony forms.
     *
     * @return bool Always false for this class
     */
    public function usesSymfonyForms(): bool
    {
        return false;
    }

    /**
     * Gets all hooks that need to be registered for this entity.
     *
     * @return array<string> All hook names
     */
    public function getAllHooks(): array
    {
        $hooks = [];

        // Action hooks
        foreach ($this->config['action_hooks'] ?? [] as $hook) {
            $hooks[] = $hook;
        }

        // Display hooks
        foreach ($this->config['display_hooks'] ?? [] as $hook) {
            $hooks[] = $hook;
        }

        return $hooks;
    }
}

