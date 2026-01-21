<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Event;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Wedev\Extension\Events\AbstractDomainEvent;

/**
 * Event dispatched when a field value is saved.
 */
final class FieldValueSavedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $fieldId,
        public readonly string $fieldSlug,
        public readonly int $objectId,
        public readonly string $objectType,
        public readonly mixed $oldValue,
        public readonly mixed $newValue,
        public readonly int $langId
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'acf.field_value.saved';
    }

    /**
     * Checks if the value actually changed.
     */
    public function hasChanged(): bool
    {
        return $this->oldValue !== $this->newValue;
    }

    /**
     * Checks if this is a new value (no previous value).
     */
    public function isNew(): bool
    {
        return $this->oldValue === null && $this->newValue !== null;
    }

    /**
     * Checks if the value was deleted (set to null).
     */
    public function isDeleted(): bool
    {
        return $this->oldValue !== null && $this->newValue === null;
    }
}
