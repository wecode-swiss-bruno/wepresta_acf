<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use Manufacturer;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Manufacturers (Brands).
 */
final class BrandEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'brand';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminManufacturers'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectManufacturerUpdateAfter', 'actionObjectManufacturerAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        return [
            'entity_type' => 'brand',
            'entity_id' => $entityId,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Brand';
    }
}

