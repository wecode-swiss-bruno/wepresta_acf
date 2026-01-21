<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
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
