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

use CMS;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop CMS Pages.
 */
final class CmsPageEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'cms_page';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminCmsContent'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectCmsUpdateAfter', 'actionObjectCmsAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        $cms = new CMS($entityId);

        return [
            'entity_type' => 'cms_page',
            'entity_id' => $entityId,
            'cms_category_id' => (int) $cms->id_cms_category,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'CMS Page';
    }
}
