<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Product;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Products.
 */
final class ProductEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'product';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminProductsExtra'];
    }

    public function getActionHooks(): array
    {
        return ['actionProductUpdate', 'actionProductAdd'];
    }

    public function buildContext(int $entityId): array
    {
        $product = new Product($entityId);
        $categoryIds = $product->getCategories();

        return [
            'entity_type' => 'product',
            'entity_id' => $entityId,
            'category_ids' => array_map('intval', $categoryIds),
            'category_id' => ! empty($categoryIds) ? (int) $categoryIds[0] : null,
            'product_type' => $product->getType(),
            'manufacturer_id' => (int) $product->id_manufacturer,
            'supplier_id' => (int) $product->id_supplier,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Product';
    }
}
