<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use State;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop States.
 */
final class StateEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'state';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminStates'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectStateUpdateAfter', 'actionObjectStateAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        $state = new State($entityId);

        return [
            'entity_type' => 'state',
            'entity_id' => $entityId,
            'country_id' => (int) $state->id_country,
            'zone_id' => (int) $state->id_zone,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'State';
    }
}
