<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WeprestaAcf\Application\Config\EntityHooksConfig;
use WeprestaAcf\Application\Provider\EntityField\GenericLegacyEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\GenericSymfonyEntityFieldProvider;

/**
 * Tests for GenericSymfonyEntityFieldProvider and GenericLegacyEntityFieldProvider.
 */
class GenericEntityFieldProviderTest extends TestCase
{
    // =========================================================================
    // GenericSymfonyEntityFieldProvider TESTS
    // =========================================================================

    public function testSymfonyProviderCreateFromConfigReturnsProvider(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('product');

        $this->assertInstanceOf(GenericSymfonyEntityFieldProvider::class, $provider);
    }

    public function testSymfonyProviderCreateFromConfigReturnsNullForLegacy(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('order');

        $this->assertNull($provider, 'Should return null for legacy entities');
    }

    public function testSymfonyProviderCreateFromConfigReturnsNullForUnknown(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('unknown_entity');

        $this->assertNull($provider);
    }

    public function testSymfonyProviderGetEntityTypeReturnsCorrectValue(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('product');

        $this->assertSame('product', $provider->getEntityType());
    }

    public function testSymfonyProviderGetFormBuilderHookReturnsCorrectHook(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('customer');

        $this->assertSame('actionCustomerFormBuilderModifier', $provider->getFormBuilderHook());
    }

    public function testSymfonyProviderGetFormHandlerHooksReturnsCorrectHooks(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('category');

        $hooks = $provider->getFormHandlerHooks();

        $this->assertIsArray($hooks);
        $this->assertCount(2, $hooks);
        $this->assertContains('actionAfterCreateCategoryFormHandler', $hooks);
        $this->assertContains('actionAfterUpdateCategoryFormHandler', $hooks);
    }

    public function testSymfonyProviderGetActionHooksReturnsFormHandlerHooks(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('manufacturer');

        // getActionHooks() should return the same as getFormHandlerHooks()
        $this->assertSame($provider->getFormHandlerHooks(), $provider->getActionHooks());
    }

    public function testSymfonyProviderGetDisplayHooksReturnsArray(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('product');

        $hooks = $provider->getDisplayHooks();

        $this->assertIsArray($hooks);
        $this->assertContains('displayAdminProductsExtra', $hooks);
    }

    public function testSymfonyProviderGetCategoryReturnsCorrectCategory(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('customer');

        $this->assertSame('Customers', $provider->getCategory());
    }

    public function testSymfonyProviderGetIdParamReturnsCorrectParam(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('product');

        $this->assertSame('id', $provider->getIdParam());
    }

    public function testSymfonyProviderBuildContextReturnsMinimalContext(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('product');

        $context = $provider->buildContext(123);

        $this->assertIsArray($context);
        $this->assertArrayHasKey('entity_type', $context);
        $this->assertArrayHasKey('entity_id', $context);
        $this->assertSame('product', $context['entity_type']);
        $this->assertSame(123, $context['entity_id']);
    }

    public function testSymfonyProviderGetEntityLabelReturnsLabel(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('customer');

        $label = $provider->getEntityLabel(1);

        $this->assertSame('Customer', $label);
    }

    public function testSymfonyProviderUsesSymfonyFormsReturnsTrue(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('product');

        $this->assertTrue($provider->usesSymfonyForms());
    }

    public function testSymfonyProviderGetAllHooksReturnsAllHooks(): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig('product');

        $hooks = $provider->getAllHooks();

        $this->assertIsArray($hooks);
        $this->assertContains('actionProductFormBuilderModifier', $hooks);
        $this->assertContains('actionAfterCreateProductFormHandler', $hooks);
        $this->assertContains('actionAfterUpdateProductFormHandler', $hooks);
        $this->assertContains('displayAdminProductsExtra', $hooks);
    }

    // =========================================================================
    // GenericLegacyEntityFieldProvider TESTS
    // =========================================================================

    public function testLegacyProviderCreateFromConfigReturnsProvider(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $this->assertInstanceOf(GenericLegacyEntityFieldProvider::class, $provider);
    }

    public function testLegacyProviderCreateFromConfigReturnsNullForSymfony(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('product');

        $this->assertNull($provider, 'Should return null for Symfony entities');
    }

    public function testLegacyProviderCreateFromConfigReturnsNullForUnknown(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('unknown_entity');

        $this->assertNull($provider);
    }

    public function testLegacyProviderGetEntityTypeReturnsCorrectValue(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $this->assertSame('order', $provider->getEntityType());
    }

    public function testLegacyProviderGetObjectClassReturnsCorrectClass(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $this->assertSame('Order', $provider->getObjectClass());
    }

    public function testLegacyProviderGetActionHooksReturnsCorrectHooks(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('supplier');

        $hooks = $provider->getActionHooks();

        $this->assertIsArray($hooks);
        $this->assertContains('actionObjectSupplierAddAfter', $hooks);
        $this->assertContains('actionObjectSupplierUpdateAfter', $hooks);
    }

    public function testLegacyProviderGetDisplayHooksReturnsArrayMaybeEmpty(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('supplier');

        $hooks = $provider->getDisplayHooks();

        $this->assertIsArray($hooks);
        // Supplier doesn't have display hooks
    }

    public function testLegacyProviderGetDisplayHooksReturnsHooksWhenDefined(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $hooks = $provider->getDisplayHooks();

        $this->assertIsArray($hooks);
        $this->assertContains('displayAdminOrderMain', $hooks);
    }

    public function testLegacyProviderGetCategoryReturnsCorrectCategory(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $this->assertSame('Orders', $provider->getCategory());
    }

    public function testLegacyProviderBuildContextReturnsMinimalContext(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $context = $provider->buildContext(456);

        $this->assertIsArray($context);
        $this->assertArrayHasKey('entity_type', $context);
        $this->assertArrayHasKey('entity_id', $context);
        $this->assertSame('order', $context['entity_type']);
        $this->assertSame(456, $context['entity_id']);
    }

    public function testLegacyProviderGetEntityLabelReturnsLabel(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('supplier');

        $label = $provider->getEntityLabel(1);

        $this->assertSame('Supplier', $label);
    }

    public function testLegacyProviderUsesSymfonyFormsReturnsFalse(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $this->assertFalse($provider->usesSymfonyForms());
    }

    public function testLegacyProviderGetAllHooksReturnsAllHooks(): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig('order');

        $hooks = $provider->getAllHooks();

        $this->assertIsArray($hooks);
        $this->assertContains('actionObjectOrderAddAfter', $hooks);
        $this->assertContains('actionObjectOrderUpdateAfter', $hooks);
        $this->assertContains('displayAdminOrderMain', $hooks);
    }

    // =========================================================================
    // CROSS-PROVIDER TESTS
    // =========================================================================

    /**
     * @dataProvider provideAllSymfonyEntities
     */
    public function testAllSymfonyEntitiesCanCreateProvider(string $entityType): void
    {
        $provider = GenericSymfonyEntityFieldProvider::createFromConfig($entityType);

        $this->assertInstanceOf(
            GenericSymfonyEntityFieldProvider::class,
            $provider,
            "Should create provider for Symfony entity: {$entityType}"
        );
        $this->assertSame($entityType, $provider->getEntityType());
        $this->assertTrue($provider->usesSymfonyForms());
    }

    public static function provideAllSymfonyEntities(): array
    {
        $entities = [];

        foreach (array_keys(EntityHooksConfig::SYMFONY_ENTITIES) as $entityType) {
            $entities[$entityType] = [$entityType];
        }

        return $entities;
    }

    /**
     * @dataProvider provideAllLegacyEntities
     */
    public function testAllLegacyEntitiesCanCreateProvider(string $entityType): void
    {
        $provider = GenericLegacyEntityFieldProvider::createFromConfig($entityType);

        $this->assertInstanceOf(
            GenericLegacyEntityFieldProvider::class,
            $provider,
            "Should create provider for Legacy entity: {$entityType}"
        );
        $this->assertSame($entityType, $provider->getEntityType());
        $this->assertFalse($provider->usesSymfonyForms());
    }

    public static function provideAllLegacyEntities(): array
    {
        $entities = [];

        foreach (array_keys(EntityHooksConfig::LEGACY_ENTITIES) as $entityType) {
            $entities[$entityType] = [$entityType];
        }

        return $entities;
    }
}
