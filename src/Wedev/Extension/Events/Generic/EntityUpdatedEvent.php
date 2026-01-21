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
 * Generic event for entity updates.
 *
 * Includes both old and new data to allow subscribers to see what changed.
 *
 * @example
 * // When a product price is updated
 * $dispatcher->dispatch(new EntityUpdatedEvent(
 *     entityType: 'product',
 *     entityId: $productId,
 *     oldData: ['price' => 19.99],
 *     newData: ['price' => 24.99]
 * ));
 *
 * // Detect specific changes
 * $dispatcher->addListener('entity.updated', function (EntityUpdatedEvent $event) {
 *     if ($event->hasChanged('price')) {
 *         $this->notifyPriceChange($event->entityId, $event->getOldValue('price'), $event->getNewValue('price'));
 *     }
 * });
 */
final class EntityUpdatedEvent extends AbstractDomainEvent
{
    /**
     * @param string $entityType The type of entity
     * @param int|string $entityId The entity's identifier
     * @param array<string,mixed> $oldData Data before the update
     * @param array<string,mixed> $newData Data after the update
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int|string $entityId,
        public readonly array $oldData = [],
        public readonly array $newData = []
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'entity.updated';
    }

    /**
     * Returns a more specific event name including the entity type.
     */
    public function getSpecificEventName(): string
    {
        return \sprintf('%s.updated', $this->entityType);
    }

    /**
     * Checks if a specific field has changed.
     */
    public function hasChanged(string $field): bool
    {
        $oldValue = $this->oldData[$field] ?? null;
        $newValue = $this->newData[$field] ?? null;

        return $oldValue !== $newValue;
    }

    /**
     * Gets the old value of a field.
     */
    public function getOldValue(string $field): mixed
    {
        return $this->oldData[$field] ?? null;
    }

    /**
     * Gets the new value of a field.
     */
    public function getNewValue(string $field): mixed
    {
        return $this->newData[$field] ?? null;
    }

    /**
     * Gets all fields that have changed.
     *
     * @return array<string>
     */
    public function getChangedFields(): array
    {
        $changed = [];
        $allFields = array_unique(array_merge(array_keys($this->oldData), array_keys($this->newData)));

        foreach ($allFields as $field) {
            if ($this->hasChanged($field)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /**
     * Gets a diff of all changes.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getDiff(): array
    {
        $diff = [];

        foreach ($this->getChangedFields() as $field) {
            $diff[$field] = [
                'old' => $this->getOldValue($field),
                'new' => $this->getNewValue($field),
            ];
        }

        return $diff;
    }
}
