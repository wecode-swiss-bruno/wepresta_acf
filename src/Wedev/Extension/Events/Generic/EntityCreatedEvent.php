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

namespace WeprestaAcf\Wedev\Extension\Events\Generic;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Wedev\Extension\Events\AbstractDomainEvent;

/**
 * Generic event for entity creation.
 *
 * Use this for simple CRUD events instead of creating a dedicated event class.
 * For complex events with specific data, extend AbstractDomainEvent instead.
 *
 * @example
 * // When a group is created
 * $dispatcher->dispatch(new EntityCreatedEvent(
 *     entityType: 'group',
 *     entityId: $groupId,
 *     data: ['title' => $title, 'status' => 'active']
 * ));
 *
 * // Listening to all creation events
 * $dispatcher->addListener('entity.created', function (EntityCreatedEvent $event) {
 *     $this->auditLog->log("Created {$event->entityType} #{$event->entityId}");
 * });
 *
 * // Listening to specific entity type
 * $dispatcher->addListener('entity.created', function (EntityCreatedEvent $event) {
 *     if ($event->entityType === 'order') {
 *         // Handle order creation
 *     }
 * });
 */
final class EntityCreatedEvent extends AbstractDomainEvent
{
    /**
     * @param string $entityType The type of entity (e.g., 'group', 'product', 'order')
     * @param int|string $entityId The entity's identifier
     * @param array<string,mixed> $data Additional data about the created entity
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int|string $entityId,
        public readonly array $data = []
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'entity.created';
    }

    /**
     * Returns a more specific event name including the entity type.
     *
     * Useful for filtering: 'group.created', 'order.created'
     */
    public function getSpecificEventName(): string
    {
        return \sprintf('%s.created', $this->entityType);
    }
}
