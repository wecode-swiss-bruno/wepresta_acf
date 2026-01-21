<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Country;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Countries.
 */
final class CountryEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'country';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminCountries'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectCountryUpdateAfter', 'actionObjectCountryAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        $country = new Country($entityId);

        return [
            'entity_type' => 'country',
            'entity_id' => $entityId,
            'zone_id' => (int) $country->id_zone,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Country';
    }
}
