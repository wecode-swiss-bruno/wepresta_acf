<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\EventSubscriber;

use WeprestaAcf\Application\Service\EntityFieldService;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Subscriber to display ACF fields in category extra tab.
 * Implements PrestaShop's displayAdminCategoriesExtra hook.
 */
final class CategoryExtraFieldsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityFieldService $entityFieldService,
        private readonly ContextAdapter $context
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'displayAdminCategoriesExtra' => 'onDisplayAdminCategoriesExtra',
        ];
    }

    /**
     * Display ACF fields on category edit page (Extra tab).
     */
    public function onDisplayAdminCategoriesExtra(array $params): string
    {
        try {
            $idCategory = (int) ($params['id_category'] ?? 0);
            if (!$idCategory) {
                return '';
            }

            // Get the rendered fields for this category
            return $this->entityFieldService->renderFieldsForEntity(
                'category',
                $idCategory,
                (int) $this->context->getLangId(),
                (int) $this->context->getShopId()
            );
        } catch (\Exception $e) {
            // Silently fail - ACF is optional
            return '';
        }
    }
}
