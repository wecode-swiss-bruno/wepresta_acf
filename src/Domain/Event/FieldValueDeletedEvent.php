<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Event;

final class FieldValueDeletedEvent extends AbstractAcfEvent
{
    public function __construct(
        private readonly int $fieldId,
        private readonly int $productId,
        private readonly int $shopId,
        private readonly string $slug
    ) {
        parent::__construct();
    }

    public function getEventName(): string { return 'field_value_deleted'; }
    public function getFieldId(): int { return $this->fieldId; }
    public function getProductId(): int { return $this->productId; }
    public function getShopId(): int { return $this->shopId; }
    public function getSlug(): string { return $this->slug; }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'field_id' => $this->fieldId,
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'slug' => $this->slug,
        ]);
    }
}

