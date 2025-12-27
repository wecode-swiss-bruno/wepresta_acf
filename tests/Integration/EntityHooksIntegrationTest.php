<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WeprestaAcf\Application\Config\EntityHooksConfig;
use WeprestaAcf\Application\Provider\EntityField\GenericSymfonyEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\GenericLegacyEntityFieldProvider;

/**
 * Integration tests for the Universal Entity Hooks system.
 *
 * These tests verify that all components work together correctly.
 */
class EntityHooksIntegrationTest extends TestCase
{
    // =========================================================================
    // ENTITY TYPE COVERAGE TESTS
    // =========================================================================

    public function testAllSymfonyEntitiesHaveWorkingProviders(): void
    {
        $failedEntities = [];

        foreach (EntityHooksConfig::SYMFONY_ENTITIES as $entityType => $config) {
            $provider = GenericSymfonyEntityFieldProvider::createFromConfig($entityType);

            if ($provider === null) {
                $failedEntities[] = $entityType;
                continue;
            }

            // Verify provider returns correct data
            $this->assertSame($entityType, $provider->getEntityType());
            $this->assertTrue($provider->usesSymfonyForms());
            $this->assertNotEmpty($provider->getFormBuilderHook());
            $this->assertNotEmpty($provider->getFormHandlerHooks());
        }

        $this->assertEmpty(
            $failedEntities,
            'Failed to create providers for: ' . implode(', ', $failedEntities)
        );
    }

    public function testAllLegacyEntitiesHaveWorkingProviders(): void
    {
        $failedEntities = [];

        foreach (EntityHooksConfig::LEGACY_ENTITIES as $entityType => $config) {
            $provider = GenericLegacyEntityFieldProvider::createFromConfig($entityType);

            if ($provider === null) {
                $failedEntities[] = $entityType;
                continue;
            }

            // Verify provider returns correct data
            $this->assertSame($entityType, $provider->getEntityType());
            $this->assertFalse($provider->usesSymfonyForms());
            $this->assertNotEmpty($provider->getActionHooks());
        }

        $this->assertEmpty(
            $failedEntities,
            'Failed to create providers for: ' . implode(', ', $failedEntities)
        );
    }

    // =========================================================================
    // HOOK MAPPING CONSISTENCY TESTS
    // =========================================================================

    public function testEveryFormBuilderHookMapsBackToEntity(): void
    {
        foreach (EntityHooksConfig::SYMFONY_ENTITIES as $entityType => $config) {
            $hook = $config['form_builder_hook'];
            $mappedEntity = EntityHooksConfig::getEntityByHook($hook);

            $this->assertSame(
                $entityType,
                $mappedEntity,
                "Hook {$hook} should map back to entity {$entityType}"
            );
        }
    }

    public function testEveryFormHandlerHookMapsBackToEntity(): void
    {
        foreach (EntityHooksConfig::SYMFONY_ENTITIES as $entityType => $config) {
            foreach ($config['form_handler_hooks'] as $hook) {
                $mappedEntity = EntityHooksConfig::getEntityByHook($hook);

                $this->assertSame(
                    $entityType,
                    $mappedEntity,
                    "Hook {$hook} should map back to entity {$entityType}"
                );
            }
        }
    }

    public function testEveryLegacyActionHookMapsBackToEntity(): void
    {
        foreach (EntityHooksConfig::LEGACY_ENTITIES as $entityType => $config) {
            foreach ($config['action_hooks'] as $hook) {
                $mappedEntity = EntityHooksConfig::getEntityByHook($hook);

                $this->assertSame(
                    $entityType,
                    $mappedEntity,
                    "Hook {$hook} should map back to entity {$entityType}"
                );
            }
        }
    }

    // =========================================================================
    // ENTITY GROUPING TESTS
    // =========================================================================

    public function testAllEntitiesAreGroupedByCategory(): void
    {
        $grouped = EntityHooksConfig::getEntitiesGroupedByCategory();

        $allEntities = array_merge(
            array_keys(EntityHooksConfig::SYMFONY_ENTITIES),
            array_keys(EntityHooksConfig::LEGACY_ENTITIES)
        );

        $groupedEntities = [];
        foreach ($grouped as $category => $entities) {
            $groupedEntities = array_merge($groupedEntities, array_keys($entities));
        }

        // Every entity should be in a group
        foreach ($allEntities as $entityType) {
            $this->assertContains(
                $entityType,
                $groupedEntities,
                "Entity {$entityType} should be in a category group"
            );
        }
    }

    public function testCategoriesAreWellDistributed(): void
    {
        $grouped = EntityHooksConfig::getEntitiesGroupedByCategory();

        // Each category should have at least 1 entity
        foreach ($grouped as $category => $entities) {
            $this->assertNotEmpty(
                $entities,
                "Category {$category} should have at least one entity"
            );
        }

        // Should have reasonable number of categories (5-15)
        $categoryCount = count($grouped);
        $this->assertGreaterThanOrEqual(5, $categoryCount);
        $this->assertLessThanOrEqual(15, $categoryCount);
    }

    // =========================================================================
    // PROVIDER CONTEXT BUILDING TESTS
    // =========================================================================

    public function testSymfonyProviderBuildsContextWithRequiredKeys(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('product');

        $context = $provider->buildContext(123);

        $this->assertArrayHasKey('entity_type', $context);
        $this->assertArrayHasKey('entity_id', $context);
        $this->assertSame('product', $context['entity_type']);
        $this->assertSame(123, $context['entity_id']);
    }

    public function testLegacyProviderBuildsContextWithRequiredKeys(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $context = $provider->buildContext(456);

        $this->assertArrayHasKey('entity_type', $context);
        $this->assertArrayHasKey('entity_id', $context);
        $this->assertSame('order', $context['entity_type']);
        $this->assertSame(456, $context['entity_id']);
    }

    // =========================================================================
    // HOOK REGISTRATION TESTS
    // =========================================================================

    public function testAllHooksAreRegistrable(): void
    {
        $hooks = EntityHooksConfig::getAllHooks();

        // All hooks should be non-empty strings
        foreach ($hooks as $hook) {
            $this->assertIsString($hook);
            $this->assertNotEmpty($hook);
        }
    }

    public function testHookCountMatchesExpected(): void
    {
        $hooks = EntityHooksConfig::getAllHooks();

        $symfonyCount = count(EntityHooksConfig::SYMFONY_ENTITIES);
        $legacyCount = count(EntityHooksConfig::LEGACY_ENTITIES);

        // Symfony: 1 form_builder + 2 form_handlers = 3 per entity
        // Some have display_hooks too
        // Legacy: action_hooks (typically 2) + display_hooks
        // Total should be reasonable
        $minExpected = ($symfonyCount * 3) + ($legacyCount * 2);

        $this->assertGreaterThanOrEqual(
            $minExpected,
            count($hooks),
            "Should have at least {$minExpected} hooks"
        );
    }

    // =========================================================================
    // DATA CONSISTENCY TESTS
    // =========================================================================

    public function testAllEntitiesHaveUniqueLabels(): void
    {
        $labels = [];

        foreach (EntityHooksConfig::SYMFONY_ENTITIES as $entityType => $config) {
            $label = $config['label'];
            $existingEntity = $labels[$label] ?? 'unknown';
            $this->assertArrayNotHasKey(
                $label,
                $labels,
                "Duplicate label '{$label}' found for {$entityType} (already used by {$existingEntity})"
            );
            $labels[$label] = $entityType;
        }

        foreach (EntityHooksConfig::LEGACY_ENTITIES as $entityType => $config) {
            $label = $config['label'];
            $existingEntity = $labels[$label] ?? 'unknown';
            $this->assertArrayNotHasKey(
                $label,
                $labels,
                "Duplicate label '{$label}' found for {$entityType} (already used by {$existingEntity})"
            );
            $labels[$label] = $entityType;
        }
    }

    public function testAllEntityTypesFollowNamingConvention(): void
    {
        // Entity types should be lowercase with underscores
        $pattern = '/^[a-z][a-z0-9_]*$/';

        foreach (EntityHooksConfig::SYMFONY_ENTITIES as $entityType => $config) {
            $this->assertMatchesRegularExpression(
                $pattern,
                $entityType,
                "Entity type {$entityType} should be snake_case"
            );
        }

        foreach (EntityHooksConfig::LEGACY_ENTITIES as $entityType => $config) {
            $this->assertMatchesRegularExpression(
                $pattern,
                $entityType,
                "Entity type {$entityType} should be snake_case"
            );
        }
    }

    /**
     * @dataProvider provideExpectedEntityTypes
     */
    public function testExpectedEntityTypesExist(string $entityType): void
    {
        $config = EntityHooksConfig::getEntityConfig($entityType);

        $this->assertNotNull($config, "Expected entity type '{$entityType}' should exist");
    }

    public static function provideExpectedEntityTypes(): array
    {
        return [
            // Core entities
            ['product'],
            ['category'],
            ['customer'],
            ['order'],

            // Catalog
            ['manufacturer'],
            ['feature'],

            // Customers
            ['customer_address'],
            ['customer_group'],

            // CMS
            ['cms_page'],
            ['cms_category'],

            // Localization
            ['language'],
            ['currency'],
            ['country'],
            ['zone'],
            ['tax'],

            // Configuration
            ['employee'],
            ['carrier'],
            ['contact'],
        ];
    }
}

