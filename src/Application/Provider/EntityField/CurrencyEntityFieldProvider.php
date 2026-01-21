<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Currencies.
 */
final class CurrencyEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'currency';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminCurrencies'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectCurrencyUpdateAfter', 'actionObjectCurrencyAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        return [
            'entity_type' => 'currency',
            'entity_id' => $entityId,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Currency';
    }
}
