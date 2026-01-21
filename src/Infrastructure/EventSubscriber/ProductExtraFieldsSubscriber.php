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
 * Subscriber to display ACF fields in product extra tab.
 * Implements PrestaShop's displayAdminProductsExtra hook.
 */
final class ProductExtraFieldsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityFieldService $entityFieldService,
        private readonly ContextAdapter $context
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'displayAdminProductsExtra' => 'onDisplayAdminProductsExtra',
        ];
    }

    /**
     * Display ACF fields on product edit page (Extra tab).
     */
    public function onDisplayAdminProductsExtra(array $params): string
    {
        try {
            $idProduct = (int) ($params['id_product'] ?? 0);
            if (!$idProduct) {
                return '';
            }

            // Get the rendered fields for this product
            return $this->entityFieldService->renderFieldsForEntity(
                'product',
                $idProduct,
                (int) $this->context->getLangId(),
                (int) $this->context->getShopId()
            );
        } catch (\Exception $e) {
            // Silently fail - ACF is optional
            return '';
        }
    }
}
