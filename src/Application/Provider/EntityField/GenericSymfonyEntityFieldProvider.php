<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use WeprestaAcf\Application\Config\EntityHooksConfig;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Generic entity field provider for Symfony form entities.
 *
 * This class provides a reusable implementation for all entities
 * that use the FormBuilderModifier pattern in PrestaShop 9.
 *
 * Instead of creating 41+ individual provider classes, this generic
 * provider can be instantiated with configuration from EntityHooksConfig.
 */
final class GenericSymfonyEntityFieldProvider implements EntityFieldProviderInterface
{
    /**
     * @param string $entityType Entity type identifier (e.g., 'customer', 'category')
     * @param array{
     *     label: string,
     *     category: string,
     *     form_builder_hook: string,
     *     form_handler_hooks: array<string>,
     *     id_param: string,
     *     display_hooks?: array<string>
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
        if ($config === null || ($config['integration_type'] ?? '') !== 'symfony') {
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
        // For Symfony entities, action hooks are the FormHandler hooks
        return $this->config['form_handler_hooks'] ?? [];
    }

    /**
     * Gets the FormBuilderModifier hook name.
     *
     * @return string Hook name
     */
    public function getFormBuilderHook(): string
    {
        return $this->config['form_builder_hook'];
    }

    /**
     * Gets the FormHandler hooks (create and update).
     *
     * @return array<string> Hook names
     */
    public function getFormHandlerHooks(): array
    {
        return $this->config['form_handler_hooks'] ?? [];
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
     * Gets the ID parameter name used in hooks.
     *
     * @return string Parameter name (e.g., 'id', 'id_customer')
     */
    public function getIdParam(): string
    {
        return $this->config['id_param'] ?? 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function buildContext(int $entityId): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id' => $entityId,
        ];
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
     * @return bool Always true for this class
     */
    public function usesSymfonyForms(): bool
    {
        return true;
    }

    /**
     * Gets all hooks that need to be registered for this entity.
     *
     * @return array<string> All hook names
     */
    public function getAllHooks(): array
    {
        $hooks = [];

        // FormBuilder hook
        $hooks[] = $this->config['form_builder_hook'];

        // FormHandler hooks
        foreach ($this->config['form_handler_hooks'] ?? [] as $hook) {
            $hooks[] = $hook;
        }

        // Display hooks
        foreach ($this->config['display_hooks'] ?? [] as $hook) {
            $hooks[] = $hook;
        }

        return $hooks;
    }
}

