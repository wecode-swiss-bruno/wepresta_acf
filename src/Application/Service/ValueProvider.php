<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;

final class ValueProvider
{
    public function __construct(
        private readonly AcfFieldValueRepositoryInterface $valueRepository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductFieldValues(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->getEntityFieldValues('product', $productId, $shopId, $langId);
    }

    /**
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null}>
     */
    public function getProductFieldValuesWithMeta(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->getEntityFieldValuesWithMeta('product', $productId, $shopId, $langId);
    }

    /**
     * Gets field values for any entity type.
     *
     * @return array<string, mixed>
     */
    public function getEntityFieldValues(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->valueRepository->findByEntity($entityType, $entityId, $shopId, $langId);
    }

    /**
     * Gets all field values for any entity type, including ALL languages for translatable fields.
     * Returns: [slug => value] for non-translatable, [slug => [langId => value]] for translatable.
     *
     * @return array<string, mixed>
     */
    public function getEntityFieldValuesAllLanguages(string $entityType, int $entityId, ?int $shopId = null): array
    {
        return $this->valueRepository->findByEntityAllLanguages($entityType, $entityId, $shopId);
    }

    /**
     * Gets all field values indexed by Field ID (for Admin Builders).
     *
     * @return array<int, mixed>
     */
    public function getEntityFieldValuesAllLanguagesIndexedById(string $entityType, int $entityId, ?int $shopId = null): array
    {
        return $this->valueRepository->findByEntityAllLanguagesIndexedById($entityType, $entityId, $shopId);
    }
}
