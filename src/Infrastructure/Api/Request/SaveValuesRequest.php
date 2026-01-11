<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Request;

/**
 * Save values request DTO.
 */
final class SaveValuesRequest
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly array $values,
        public readonly ?int $shopId = null,
        public readonly ?int $langId = null
    ) {
    }

    /**
     * Create from array (request data).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityType: $data['entityType'] ?? 'product',
            entityId: (int) ($data['productId'] ?? $data['entityId'] ?? 0),
            values: \is_array($data['values'] ?? null) ? $data['values'] : [],
            shopId: isset($data['shopId']) ? (int) $data['shopId'] : null,
            langId: isset($data['langId']) ? (int) $data['langId'] : null
        );
    }

    /**
     * Validate the request.
     *
     * @return array<string, string> Field-level errors
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->entityId <= 0) {
            $errors['entityId'] = 'Entity ID is required and must be positive';
        }

        if (empty($this->values)) {
            $errors['values'] = 'Values are required';
        }

        return $errors;
    }
}
