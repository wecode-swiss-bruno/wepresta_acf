<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Languages.
 */
final class LanguageEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'language';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminLanguages'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectLanguageUpdateAfter', 'actionObjectLanguageAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        return [
            'entity_type' => 'language',
            'entity_id' => $entityId,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Language';
    }
}
