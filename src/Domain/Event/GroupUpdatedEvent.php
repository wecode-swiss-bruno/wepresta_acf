<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Domain\Event;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Wedev\Extension\Events\AbstractDomainEvent;

/**
 * Event dispatched when a field group is updated.
 */
final class GroupUpdatedEvent extends AbstractDomainEvent
{
    /**
     * @param int $groupId The group ID
     * @param array<string, mixed> $changes Changed fields (key => new value)
     * @param array<string, mixed> $oldData Previous values of changed fields
     */
    public function __construct(
        public readonly int $groupId,
        public readonly array $changes,
        public readonly array $oldData = []
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'acf.group.updated';
    }

    /**
     * Checks if a specific field was changed.
     */
    public function hasChanged(string $field): bool
    {
        return \array_key_exists($field, $this->changes);
    }

    /**
     * Gets the new value of a changed field.
     */
    public function getNewValue(string $field): mixed
    {
        return $this->changes[$field] ?? null;
    }

    /**
     * Gets the old value of a changed field.
     */
    public function getOldValue(string $field): mixed
    {
        return $this->oldData[$field] ?? null;
    }
}
