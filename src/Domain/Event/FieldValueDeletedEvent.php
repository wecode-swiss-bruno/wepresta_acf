<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Event;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Wedev\Extension\Events\AbstractDomainEvent;

/**
 * Event dispatched when a field value is deleted.
 */
final class FieldValueDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $fieldId,
        public readonly string $fieldSlug,
        public readonly int $objectId,
        public readonly string $objectType,
        public readonly int $langId,
        public readonly mixed $deletedValue = null
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'acf.field_value.deleted';
    }
}
