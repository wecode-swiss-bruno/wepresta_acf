<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Repository;

interface AcfFieldValueRepositoryInterface
{
    // Legacy product-specific methods (backward compatibility)
    /** @return array<string, mixed> */
    public function findByProduct(int $productId, ?int $shopId = null, ?int $langId = null): array;

    /** @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null, config: array<string, mixed>, fo_options: array<string, mixed>}> */
    public function findByProductWithMeta(int $productId, ?int $shopId = null, ?int $langId = null): array;

    public function findByFieldAndProduct(int $fieldId, int $productId, ?int $shopId = null, ?int $langId = null): ?string;

    public function save(
        int $fieldId,
        int $productId,
        ?string $value,
        ?int $shopId = null,
        ?int $langId = null,
        ?bool $isTranslatable = null,
        ?string $indexValue = null
    ): bool;

    public function deleteByProduct(int $productId, ?int $shopId = null): bool;
    public function deleteByFieldAndProduct(int $fieldId, int $productId, ?int $shopId = null, ?int $langId = null): bool;

    /** @return array<int> */
    public function findProductsByFieldValue(int $fieldId, string $value, ?int $shopId = null): array;

    // New generic entity methods
    /** @return array<string, mixed> */
    public function findByEntity(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array;

    /** @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null, config: array<string, mixed>, fo_options: array<string, mixed>}> */
    public function findByEntityWithMeta(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array;

    public function findByFieldAndEntity(int $fieldId, string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): ?string;

    public function saveEntity(
        int $fieldId,
        string $entityType,
        int $entityId,
        ?string $value,
        ?int $shopId = null,
        ?int $langId = null,
        ?bool $isTranslatable = null,
        ?string $indexValue = null
    ): bool;

    public function deleteByEntity(string $entityType, int $entityId, ?int $shopId = null): bool;
    public function deleteByFieldAndEntity(int $fieldId, string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): bool;

    public function deleteByField(int $fieldId): bool;

    /**
     * Deletes all translatable values (id_lang IS NOT NULL) for a given field.
     * Used when a field is changed from translatable to non-translatable.
     */
    public function deleteTranslatableValuesByField(int $fieldId): bool;

    /** @return array<int> */
    public function findEntitiesByFieldValue(int $fieldId, string $value, string $entityType, ?int $shopId = null): array;
}

