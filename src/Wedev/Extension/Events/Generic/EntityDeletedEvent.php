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
 * Generic event for entity deletion.
 *
 * Includes the deleted entity's data for audit/recovery purposes.
 *
 * @example
 * // When a customer is deleted
 * $dispatcher->dispatch(new EntityDeletedEvent(
 *     entityType: 'customer',
 *     entityId: $customerId,
 *     data: ['email' => $email, 'name' => $name]
 * ));
 *
 * // Cleanup related data
 * $dispatcher->addListener('entity.deleted', function (EntityDeletedEvent $event) {
 *     if ($event->entityType === 'customer') {
 *         $this->cleanupCustomerData($event->entityId);
 *     }
 * });
 */
final class EntityDeletedEvent extends AbstractDomainEvent
{
    /**
     * @param string $entityType The type of entity that was deleted
     * @param int|string $entityId The entity's identifier
     * @param array<string,mixed> $data Snapshot of the entity's data before deletion
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
        return 'entity.deleted';
    }

    /**
     * Returns a more specific event name including the entity type.
     */
    public function getSpecificEventName(): string
    {
        return \sprintf('%s.deleted', $this->entityType);
    }

    /**
     * Checks if data was captured before deletion.
     */
    public function hasData(): bool
    {
        return ! empty($this->data);
    }

    /**
     * Gets a specific value from the deleted entity's data.
     */
    public function getValue(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }
}
