<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Event;

final class FieldValueSavedEvent extends AbstractAcfEvent
{
    public function __construct(
        private readonly int $fieldId,
        private readonly int $productId,
        private readonly int $shopId,
        private readonly ?int $langId,
        private readonly mixed $value,
        private readonly string $slug
    ) {
        parent::__construct();
    }

    public function getEventName(): string { return 'field_value_saved'; }
    public function getFieldId(): int { return $this->fieldId; }
    public function getProductId(): int { return $this->productId; }
    public function getShopId(): int { return $this->shopId; }
    public function getLangId(): ?int { return $this->langId; }
    public function getValue(): mixed { return $this->value; }
    public function getSlug(): string { return $this->slug; }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'field_id' => $this->fieldId,
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'lang_id' => $this->langId,
            'slug' => $this->slug,
        ]);
    }
}

