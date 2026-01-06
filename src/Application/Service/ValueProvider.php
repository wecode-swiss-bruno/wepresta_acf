<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;

final class ValueProvider
{
    public function __construct(
        private readonly AcfFieldValueRepositoryInterface $valueRepository
    ) {}

    /** @return array<string, mixed> */
    public function getProductFieldValues(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->getEntityFieldValues('product', $productId, $shopId, $langId);
    }

    /** @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null}> */
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
     * Gets field values with metadata for any entity type WITH hook filtering.
     * Only returns fields from groups configured to display in the specified hook.
     *
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null, config: array<string, mixed>, fo_options: array<string, mixed>}>
     */
    public function getEntityFieldValuesWithMetaForHook(string $entityType, int $entityId, string $hookName, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->valueRepository->findByEntityWithMetaForHook($entityType, $entityId, $hookName, $shopId, $langId);
    }

    /**
     * Gets field values with metadata for any entity type (legacy - no hook filtering).
     *
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null, config: array<string, mixed>, fo_options: array<string, mixed>}>
     */
    public function getEntityFieldValuesWithMeta(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        // Fallback: get all fields without hook filtering
        return $this->getEntityFieldValuesWithMetaForHook($entityType, $entityId, '', $shopId, $langId);
    }
}

