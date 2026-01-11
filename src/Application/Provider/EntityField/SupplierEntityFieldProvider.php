<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Suppliers.
 */
final class SupplierEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'supplier';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminSuppliers'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectSupplierUpdateAfter', 'actionObjectSupplierAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        return [
            'entity_type' => 'supplier',
            'entity_id' => $entityId,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Supplier';
    }
}
