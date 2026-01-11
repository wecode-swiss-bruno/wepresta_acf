<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WeprestaAcf\Application\Config\EntityHooksConfig;

/**
 * Tests for EntityHooksConfig - centralized entity hooks configuration.
 */
class EntityHooksConfigTest extends TestCase
{
    // =========================================================================
    // SYMFONY ENTITIES TESTS
    // =========================================================================

    public function testSymfonyEntitiesContainsExpectedCount(): void
    {
        $count = \count(EntityHooksConfig::SYMFONY_ENTITIES);

        $this->assertGreaterThanOrEqual(35, $count, 'Should have at least 35 Symfony entities');
        $this->assertLessThanOrEqual(45, $count, 'Should have at most 45 Symfony entities');
    }

    /**
     * @dataProvider provideSymfonyEntityTypes
     */
    public function testSymfonyEntityHasRequiredFields(string $entityType): void
    {
        $entity = EntityHooksConfig::SYMFONY_ENTITIES[$entityType];

        $this->assertArrayHasKey('label', $entity, "Entity {$entityType} should have label");
        $this->assertArrayHasKey('category', $entity, "Entity {$entityType} should have category");
        $this->assertArrayHasKey('form_builder_hook', $entity, "Entity {$entityType} should have form_builder_hook");
        $this->assertArrayHasKey('form_handler_hooks', $entity, "Entity {$entityType} should have form_handler_hooks");
        $this->assertArrayHasKey('id_param', $entity, "Entity {$entityType} should have id_param");

        $this->assertNotEmpty($entity['label']);
        $this->assertNotEmpty($entity['form_builder_hook']);
        $this->assertIsArray($entity['form_handler_hooks']);
        $this->assertCount(2, $entity['form_handler_hooks'], 'Should have create and update handlers');
    }

    public static function provideSymfonyEntityTypes(): array
    {
        return [
            'product' => ['product'],
            'category' => ['category'],
            'customer' => ['customer'],
            'manufacturer' => ['manufacturer'],
            'cms_page' => ['cms_page'],
            'language' => ['language'],
            'currency' => ['currency'],
            'employee' => ['employee'],
            'carrier' => ['carrier'],
        ];
    }

    public function testSymfonyEntityFormBuilderHookFollowsPattern(): void
    {
        foreach (EntityHooksConfig::SYMFONY_ENTITIES as $entityType => $entity) {
            $hook = $entity['form_builder_hook'];

            $this->assertMatchesRegularExpression(
                '/^action[A-Z][a-zA-Z]*FormBuilderModifier$/',
                $hook,
                "Entity {$entityType} hook should follow pattern action{EntityName}FormBuilderModifier"
            );
        }
    }

    public function testSymfonyEntityFormHandlerHooksFollowPattern(): void
    {
        foreach (EntityHooksConfig::SYMFONY_ENTITIES as $entityType => $entity) {
            foreach ($entity['form_handler_hooks'] as $hook) {
                $this->assertMatchesRegularExpression(
                    '/^actionAfter(Create|Update)[A-Z][a-zA-Z]*FormHandler$/',
                    $hook,
                    "Entity {$entityType} handler hook should follow pattern"
                );
            }
        }
    }

    // =========================================================================
    // LEGACY ENTITIES TESTS
    // =========================================================================

    public function testLegacyEntitiesContainsExpectedCount(): void
    {
        $count = \count(EntityHooksConfig::LEGACY_ENTITIES);

        $this->assertGreaterThanOrEqual(5, $count, 'Should have at least 5 legacy entities');
        $this->assertLessThanOrEqual(15, $count, 'Should have at most 15 legacy entities');
    }

    /**
     * @dataProvider provideLegacyEntityTypes
     */
    public function testLegacyEntityHasRequiredFields(string $entityType): void
    {
        $entity = EntityHooksConfig::LEGACY_ENTITIES[$entityType];

        $this->assertArrayHasKey('label', $entity, "Entity {$entityType} should have label");
        $this->assertArrayHasKey('category', $entity, "Entity {$entityType} should have category");
        $this->assertArrayHasKey('object_class', $entity, "Entity {$entityType} should have object_class");
        $this->assertArrayHasKey('action_hooks', $entity, "Entity {$entityType} should have action_hooks");

        $this->assertNotEmpty($entity['label']);
        $this->assertNotEmpty($entity['object_class']);
        $this->assertIsArray($entity['action_hooks']);
        $this->assertNotEmpty($entity['action_hooks'], 'Should have at least one action hook');
    }

    public static function provideLegacyEntityTypes(): array
    {
        return [
            'customer_address' => ['customer_address'],
            'customer_group' => ['customer_group'],
            'supplier' => ['supplier'],
            'order' => ['order'],
            'cart' => ['cart'],
        ];
    }

    public function testLegacyEntityActionHooksFollowPattern(): void
    {
        foreach (EntityHooksConfig::LEGACY_ENTITIES as $entityType => $entity) {
            foreach ($entity['action_hooks'] as $hook) {
                $this->assertMatchesRegularExpression(
                    '/^actionObject[A-Z][a-zA-Z]*(Add|Update)After$/',
                    $hook,
                    "Entity {$entityType} hook should follow pattern actionObject{ClassName}(Add|Update)After"
                );
            }
        }
    }

    // =========================================================================
    // getAllHooks() TESTS
    // =========================================================================

    public function testGetAllHooksReturnsNonEmptyArray(): void
    {
        $hooks = EntityHooksConfig::getAllHooks();

        $this->assertIsArray($hooks);
        $this->assertNotEmpty($hooks);
    }

    public function testGetAllHooksContainsFormBuilderModifierHooks(): void
    {
        $hooks = EntityHooksConfig::getAllHooks();

        $this->assertContains('actionProductFormBuilderModifier', $hooks);
        $this->assertContains('actionCustomerFormBuilderModifier', $hooks);
        $this->assertContains('actionCategoryFormBuilderModifier', $hooks);
    }

    public function testGetAllHooksContainsFormHandlerHooks(): void
    {
        $hooks = EntityHooksConfig::getAllHooks();

        $this->assertContains('actionAfterCreateProductFormHandler', $hooks);
        $this->assertContains('actionAfterUpdateProductFormHandler', $hooks);
        $this->assertContains('actionAfterCreateCustomerFormHandler', $hooks);
    }

    public function testGetAllHooksContainsLegacyObjectModelHooks(): void
    {
        $hooks = EntityHooksConfig::getAllHooks();

        $this->assertContains('actionObjectAddressAddAfter', $hooks);
        $this->assertContains('actionObjectAddressUpdateAfter', $hooks);
        $this->assertContains('actionObjectSupplierAddAfter', $hooks);
    }

    public function testGetAllHooksReturnsUniqueHooks(): void
    {
        $hooks = EntityHooksConfig::getAllHooks();
        $uniqueHooks = array_unique($hooks);

        $this->assertCount(\count($hooks), $uniqueHooks, 'All hooks should be unique');
    }

    public function testGetAllHooksReturnsExpectedCount(): void
    {
        $hooks = EntityHooksConfig::getAllHooks();

        // 41 Symfony entities × 3 hooks each (1 builder + 2 handlers) = 123
        // + 8 Legacy entities × 2 hooks each = 16
        // + some display hooks
        // Total should be around 100-150
        $this->assertGreaterThan(100, \count($hooks), 'Should have more than 100 hooks');
        $this->assertLessThan(200, \count($hooks), 'Should have less than 200 hooks');
    }

    // =========================================================================
    // getEntityByHook() TESTS
    // =========================================================================

    /**
     * @dataProvider provideHookToEntityMapping
     */
    public function testGetEntityByHookReturnsCorrectEntity(string $hookName, string $expectedEntityType): void
    {
        $entityType = EntityHooksConfig::getEntityByHook($hookName);

        $this->assertSame($expectedEntityType, $entityType);
    }

    public static function provideHookToEntityMapping(): array
    {
        return [
            // Symfony FormBuilder hooks
            ['actionProductFormBuilderModifier', 'product'],
            ['actionCustomerFormBuilderModifier', 'customer'],
            ['actionCategoryFormBuilderModifier', 'category'],
            ['actionManufacturerFormBuilderModifier', 'manufacturer'],
            ['actionCmsPageFormBuilderModifier', 'cms_page'],

            // Symfony FormHandler hooks
            ['actionAfterCreateProductFormHandler', 'product'],
            ['actionAfterUpdateProductFormHandler', 'product'],
            ['actionAfterCreateCustomerFormHandler', 'customer'],

            // Legacy ObjectModel hooks
            ['actionObjectAddressAddAfter', 'customer_address'],
            ['actionObjectAddressUpdateAfter', 'customer_address'],
            ['actionObjectSupplierAddAfter', 'supplier'],
            ['actionObjectOrderAddAfter', 'order'],
        ];
    }

    public function testGetEntityByHookReturnsNullForUnknownHook(): void
    {
        $entityType = EntityHooksConfig::getEntityByHook('actionUnknownHook');

        $this->assertNull($entityType);
    }

    // =========================================================================
    // getEntitiesGroupedByCategory() TESTS
    // =========================================================================

    public function testGetEntitiesGroupedByCategoryReturnsNonEmptyArray(): void
    {
        $grouped = EntityHooksConfig::getEntitiesGroupedByCategory();

        $this->assertIsArray($grouped);
        $this->assertNotEmpty($grouped);
    }

    public function testGetEntitiesGroupedByCategoryContainsExpectedCategories(): void
    {
        $grouped = EntityHooksConfig::getEntitiesGroupedByCategory();

        $this->assertArrayHasKey('Catalog', $grouped);
        $this->assertArrayHasKey('Customers', $grouped);
        $this->assertArrayHasKey('Orders', $grouped);
        $this->assertArrayHasKey('CMS', $grouped);
        $this->assertArrayHasKey('Localization', $grouped);
        $this->assertArrayHasKey('Configuration', $grouped);
    }

    public function testGetEntitiesGroupedByCategoryContainsProductInCatalog(): void
    {
        $grouped = EntityHooksConfig::getEntitiesGroupedByCategory();

        $this->assertArrayHasKey('product', $grouped['Catalog']);
        $this->assertSame('Product', $grouped['Catalog']['product']['label']);
        $this->assertSame('symfony', $grouped['Catalog']['product']['type']);
    }

    public function testGetEntitiesGroupedByCategoryContainsOrderInOrders(): void
    {
        $grouped = EntityHooksConfig::getEntitiesGroupedByCategory();

        $this->assertArrayHasKey('order', $grouped['Orders']);
        $this->assertSame('Order', $grouped['Orders']['order']['label']);
        $this->assertSame('legacy', $grouped['Orders']['order']['type']);
    }

    // =========================================================================
    // getEntityConfig() TESTS
    // =========================================================================

    public function testGetEntityConfigReturnsSymfonyEntityWithIntegrationType(): void
    {
        $config = EntityHooksConfig::getEntityConfig('product');

        $this->assertIsArray($config);
        $this->assertSame('symfony', $config['integration_type']);
        $this->assertSame('Product', $config['label']);
        $this->assertSame('Catalog', $config['category']);
        $this->assertArrayHasKey('form_builder_hook', $config);
    }

    public function testGetEntityConfigReturnsLegacyEntityWithIntegrationType(): void
    {
        $config = EntityHooksConfig::getEntityConfig('order');

        $this->assertIsArray($config);
        $this->assertSame('legacy', $config['integration_type']);
        $this->assertSame('Order', $config['label']);
        $this->assertArrayHasKey('object_class', $config);
    }

    public function testGetEntityConfigReturnsNullForUnknownEntity(): void
    {
        $config = EntityHooksConfig::getEntityConfig('unknown_entity');

        $this->assertNull($config);
    }

    // =========================================================================
    // isSymfonyEntity() / isLegacyEntity() TESTS
    // =========================================================================

    public function testIsSymfonyEntityReturnsTrue(): void
    {
        $this->assertTrue(EntityHooksConfig::isSymfonyEntity('product'));
        $this->assertTrue(EntityHooksConfig::isSymfonyEntity('customer'));
        $this->assertTrue(EntityHooksConfig::isSymfonyEntity('category'));
    }

    public function testIsSymfonyEntityReturnsFalseForLegacy(): void
    {
        $this->assertFalse(EntityHooksConfig::isSymfonyEntity('order'));
        $this->assertFalse(EntityHooksConfig::isSymfonyEntity('supplier'));
        $this->assertFalse(EntityHooksConfig::isSymfonyEntity('customer_address'));
    }

    public function testIsLegacyEntityReturnsTrue(): void
    {
        $this->assertTrue(EntityHooksConfig::isLegacyEntity('order'));
        $this->assertTrue(EntityHooksConfig::isLegacyEntity('supplier'));
        $this->assertTrue(EntityHooksConfig::isLegacyEntity('customer_address'));
    }

    public function testIsLegacyEntityReturnsFalseForSymfony(): void
    {
        $this->assertFalse(EntityHooksConfig::isLegacyEntity('product'));
        $this->assertFalse(EntityHooksConfig::isLegacyEntity('customer'));
        $this->assertFalse(EntityHooksConfig::isLegacyEntity('category'));
    }
}
