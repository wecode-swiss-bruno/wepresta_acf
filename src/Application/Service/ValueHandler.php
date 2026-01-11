<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Wedev\Core\Trait\LoggerTrait;

/**
 * Handles saving, validating and deleting product field values.
 */
final class ValueHandler
{
    use LoggerTrait;

    public function __construct(
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfFieldValueRepositoryInterface $valueRepository,
        private readonly FieldTypeRegistry $fieldTypeRegistry
    ) {
    }

    /**
     * @param array<string, mixed> $values
     */
    public function saveProductFieldValues(int $productId, array $values, ?int $shopId = null, ?int $langId = null): void
    {
        $this->saveEntityFieldValues('product', $productId, $values, $shopId, $langId);
    }

    /**
     * Saves field values for any entity type.
     *
     * @param array<string, mixed> $values
     */
    public function saveEntityFieldValues(string $entityType, int $entityId, array $values, ?int $shopId = null, ?int $langId = null): void
    {
        $this->logInfo('Saving entity field values', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'field_count' => \count($values),
            'shop_id' => $shopId,
            'lang_id' => $langId,
        ]);

        foreach ($values as $slug => $value) {
            $this->saveEntityFieldValue($entityType, $entityId, $slug, $value, $shopId, $langId);
        }
    }

    public function saveFieldValue(int $productId, string $slug, mixed $value, ?int $shopId = null, ?int $langId = null): bool
    {
        return $this->saveEntityFieldValue('product', $productId, $slug, $value, $shopId, $langId);
    }

    /**
     * Saves a field value for any entity type.
     */
    public function saveEntityFieldValue(string $entityType, int $entityId, string $slug, mixed $value, ?int $shopId = null, ?int $langId = null): bool
    {
        $field = $this->fieldRepository->findBySlug($slug);

        if (! $field) {
            $this->logWarning('Field not found for slug', ['slug' => $slug, 'entity_type' => $entityType, 'entity_id' => $entityId]);

            return false;
        }

        $fieldId = (int) $field['id_wepresta_acf_field'];
        $fieldType = $field['type'];
        $isTranslatable = (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);
        $config = $this->parseJsonConfig($field['config'] ?? '{}');

        // For translatable fields, value is an array of {langId: value}
        if ($isTranslatable && \is_array($value) && $this->isLangValueArray($value)) {
            $allSuccess = true;

            foreach ($value as $langId => $langValue) {
                $normalizedValue = $this->fieldTypeRegistry->normalizeValue($fieldType, $langValue, $config);
                $storableValue = $this->toStorableValue($normalizedValue);
                $indexValue = $this->fieldTypeRegistry->getIndexValue($fieldType, $normalizedValue, $config);

                $result = $this->valueRepository->saveEntity($fieldId, $entityType, $entityId, $storableValue, $shopId, (int) $langId, $isTranslatable, $indexValue);

                if (! $result) {
                    $allSuccess = false;
                }
            }

            return $allSuccess;
        }

        // Non-translatable field: single value
        $normalizedValue = $this->fieldTypeRegistry->normalizeValue($fieldType, $value, $config);
        $storableValue = $this->toStorableValue($normalizedValue);
        $indexValue = $this->fieldTypeRegistry->getIndexValue($fieldType, $normalizedValue, $config);

        $result = $this->valueRepository->saveEntity($fieldId, $entityType, $entityId, $storableValue, $shopId, $langId, $isTranslatable, $indexValue);

        $this->logDebug('Field value saved', [
            'field_id' => $fieldId,
            'slug' => $slug,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'success' => $result,
        ]);

        return $result;
    }

    public function deleteProductFieldValues(int $productId, ?int $shopId = null): bool
    {
        $this->logInfo('Deleting all product field values', ['product_id' => $productId, 'shop_id' => $shopId]);

        return $this->valueRepository->deleteByProduct($productId, $shopId);
    }

    public function deleteFieldValue(int $productId, string $slug, ?int $shopId = null, ?int $langId = null): bool
    {
        $field = $this->fieldRepository->findBySlug($slug);

        if (! $field) {
            return false;
        }

        return $this->valueRepository->deleteByFieldAndProduct((int) $field['id_wepresta_acf_field'], $productId, $shopId, $langId);
    }

    /**
     * @param array<string, mixed> $values @return array<string, array<string>>
     */
    public function validateProductFieldValues(array $values): array
    {
        $errors = [];

        foreach ($values as $slug => $value) {
            $field = $this->fieldRepository->findBySlug($slug);

            if (! $field) {
                continue;
            }

            $isTranslatable = (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);
            $config = $this->parseJsonConfig($field['config'] ?? '{}');
            $validation = $this->parseJsonConfig($field['validation'] ?? '{}');

            // For translatable fields, validate each language value
            if ($isTranslatable && \is_array($value) && $this->isLangValueArray($value)) {
                $fieldErrors = [];

                foreach ($value as $langId => $langValue) {
                    $langErrors = $this->fieldTypeRegistry->validate($field['type'], $langValue, $config, $validation);

                    if (! empty($langErrors)) {
                        $fieldErrors[$langId] = $langErrors;
                    }
                }

                if (! empty($fieldErrors)) {
                    $errors[$slug] = $fieldErrors;
                }
            } else {
                // Non-translatable field: validate single value
                $fieldErrors = $this->fieldTypeRegistry->validate($field['type'], $value, $config, $validation);

                if (! empty($fieldErrors)) {
                    $errors[$slug] = $fieldErrors;
                }
            }
        }

        return $errors;
    }

    /**
     * Checks if an array is a language-value mapping (keys are numeric language IDs).
     */
    private function isLangValueArray(mixed $value): bool
    {
        if (! \is_array($value) || empty($value)) {
            return false;
        }

        // Check if all keys are numeric (language IDs)
        foreach (array_keys($value) as $key) {
            if (! is_numeric($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonConfig(string|array|null $config): array
    {
        if (\is_array($config)) {
            return $config;
        }

        if ($config === null || $config === '' || $config === '{}') {
            return [];
        }
        $decoded = json_decode($config, true);

        return \is_array($decoded) ? $decoded : [];
    }

    private function toStorableValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (\is_string($value)) {
            return $value;
        }

        if (\is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (\is_array($value) || \is_object($value)) {
            // Convert empty objects/arrays to null (they should have been normalized already)
            if (\is_array($value) && empty($value)) {
                return null;
            }

            if (\is_object($value) && (array) $value === []) {
                return null;
            }

            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }
}
