<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Event;

use WeprestaAcf\Wedev\Extension\Events\AbstractDomainEvent;

/**
 * Event dispatched when a field group is deleted.
 */
final class GroupDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $groupId,
        public readonly string $slug
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'acf.group.deleted';
    }
}
