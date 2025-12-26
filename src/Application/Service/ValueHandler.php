<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;

final class ValueHandler
{
    public function __construct(
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfFieldValueRepositoryInterface $valueRepository,
        private readonly FieldTypeRegistry $fieldTypeRegistry
    ) {}

    /** @param array<string, mixed> $values */
    public function saveProductFieldValues(int $productId, array $values, ?int $shopId = null, ?int $langId = null): void
    {
        foreach ($values as $slug => $value) {
            $this->saveFieldValue($productId, $slug, $value, $shopId, $langId);
        }
    }

    public function saveFieldValue(int $productId, string $slug, mixed $value, ?int $shopId = null, ?int $langId = null): bool
    {
        $field = $this->fieldRepository->findBySlug($slug);
        if (!$field) { return false; }

        $fieldId = (int) $field['id_wepresta_acf_field'];
        $fieldType = $field['type'];
        $isTranslatable = (bool) ($field['translatable'] ?? false);
        $config = $this->parseJsonConfig($field['config'] ?? '{}');

        $normalizedValue = $this->fieldTypeRegistry->normalizeValue($fieldType, $value, $config);
        $storableValue = $this->toStorableValue($normalizedValue);
        $indexValue = $this->fieldTypeRegistry->getIndexValue($fieldType, $normalizedValue, $config);

        return $this->valueRepository->save($fieldId, $productId, $storableValue, $shopId, $langId, $isTranslatable, $indexValue);
    }

    public function deleteProductFieldValues(int $productId, ?int $shopId = null): bool
    {
        return $this->valueRepository->deleteByProduct($productId, $shopId);
    }

    public function deleteFieldValue(int $productId, string $slug, ?int $shopId = null, ?int $langId = null): bool
    {
        $field = $this->fieldRepository->findBySlug($slug);
        if (!$field) { return false; }
        return $this->valueRepository->deleteByFieldAndProduct((int) $field['id_wepresta_acf_field'], $productId, $shopId, $langId);
    }

    /** @param array<string, mixed> $values @return array<string, array<string>> */
    public function validateProductFieldValues(array $values): array
    {
        $errors = [];
        foreach ($values as $slug => $value) {
            $field = $this->fieldRepository->findBySlug($slug);
            if (!$field) { continue; }
            $fieldErrors = $this->fieldTypeRegistry->validate($field['type'], $value, $this->parseJsonConfig($field['config'] ?? '{}'), $this->parseJsonConfig($field['validation'] ?? '{}'));
            if (!empty($fieldErrors)) { $errors[$slug] = $fieldErrors; }
        }
        return $errors;
    }

    /** @return array<string, mixed> */
    private function parseJsonConfig(string|array|null $config): array
    {
        if (is_array($config)) { return $config; }
        if ($config === null || $config === '' || $config === '{}') { return []; }
        $decoded = json_decode($config, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function toStorableValue(mixed $value): ?string
    {
        if ($value === null) { return null; }
        if (is_string($value)) { return $value; }
        if (is_bool($value)) { return $value ? '1' : '0'; }
        if (is_numeric($value)) { return (string) $value; }
        if (is_array($value) || is_object($value)) { return json_encode($value, JSON_THROW_ON_ERROR); }
        return (string) $value;
    }
}

