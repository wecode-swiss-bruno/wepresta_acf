<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use Zone;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Zones.
 */
final class ZoneEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'zone';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminZones'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectZoneUpdateAfter', 'actionObjectZoneAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        return [
            'entity_type' => 'zone',
            'entity_id' => $entityId,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Zone';
    }
}

