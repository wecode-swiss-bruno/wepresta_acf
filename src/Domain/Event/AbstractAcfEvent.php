<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Event;

/**
 * Base class for all ACF events
 */
abstract class AbstractAcfEvent
{
    protected float $occurredAt;
    protected string $eventId;

    public function __construct()
    {
        $this->occurredAt = microtime(true);
        $this->eventId = uniqid('evt_', true);
    }

    abstract public function getEventName(): string;

    public function getFullEventName(): string
    {
        return 'wepresta_acf.' . $this->getEventName();
    }

    public function getEventId(): string { return $this->eventId; }
    public function getOccurredAt(): float { return $this->occurredAt; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt,
        ];
    }
}

