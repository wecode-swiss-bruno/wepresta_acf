<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Infrastructure\Repository\AcfFieldRepository;

/**
 * Service responsible for preparing and rendering field values for frontend display.
 */
final class FieldRenderService
{
    public function __construct(
        private readonly ValueProvider $valueProvider,
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly AcfFieldRepository $fieldRepository
    ) {}

    /**
     * Get product fields ready for frontend display.
     *
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, rendered: string, fo_options: array, wrapper: array}>
     */
    public function getProductFieldsForDisplay(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->getEntityFieldsForDisplay('product', $productId, $shopId, $langId);
    }

    /**
     * Get entity fields ready for frontend display.
     *
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, rendered: string, fo_options: array, wrapper: array}>
     */
    public function getEntityFieldsForDisplay(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        $fields = $this->valueProvider->getEntityFieldValuesWithMeta($entityType, $entityId, $shopId, $langId);

        if (empty($fields)) {
            return [];
        }

        $displayFields = [];

        foreach ($fields as $field) {
            $prepared = $this->prepareFieldForDisplay($field);

            if ($prepared !== null) {
                $displayFields[] = $prepared;
            }
        }

        return $displayFields;
    }

    /**
     * Prepare a single field for display.
     *
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>|null Returns null if field should not be displayed
     */
    private function prepareFieldForDisplay(array $field): ?array
    {
        $foOptions = $this->decodeJson($field['fo_options'] ?? []);

        // Skip if not visible on frontend
        if (!($foOptions['show_on_front'] ?? true)) {
            return null;
        }

        // Skip empty values
        if ($field['value'] === null || $field['value'] === '') {
            return null;
        }

        $fieldConfig = $this->decodeJson($field['config'] ?? []);
        $wrapper = $this->decodeJson($field['wrapper'] ?? []);

        // Get translated title/instructions for current language
        $langId = (int) \Context::getContext()->language->id;
        $fieldId = (int) ($field['id_wepresta_acf_field'] ?? 0);
        $translatedMetadata = $fieldId > 0 ? $this->getTranslatedMetadata($fieldId, $langId) : [];

        // Use translated title if available, otherwise fallback to default
        $displayTitle = !empty($translatedMetadata['title']) 
            ? $translatedMetadata['title'] 
            : ($field['title'] ?? '');

        // Render the value
        $renderedValue = $this->renderFieldValue($field['type'], $field['value'], $fieldConfig, $foOptions);

        return [
            'slug' => $field['slug'],
            'title' => $displayTitle,
            'instructions' => $translatedMetadata['instructions'] ?? $field['instructions'] ?? '',
            'type' => $field['type'],
            'value' => $field['value'],
            'rendered' => $renderedValue,
            'fo_options' => $foOptions,
            'wrapper' => $wrapper,
        ];
    }

    /**
     * Render a field value to string.
     *
     * @param array<string, mixed> $fieldConfig
     * @param array<string, mixed> $foOptions
     */
    private function renderFieldValue(string $type, mixed $value, array $fieldConfig, array $foOptions): string
    {
        $fieldType = $this->fieldTypeRegistry->getOrNull($type);

        if ($fieldType === null) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        // Denormalize value first (important for relations, files, etc.)
        $denormalizedValue = $fieldType->denormalizeValue($value, $fieldConfig);

        return $fieldType->renderValue($denormalizedValue, $fieldConfig, $foOptions);
    }

    /**
     * Get translated metadata for a field in current language.
     *
     * @return array{title?: string, instructions?: string, placeholder?: string}
     */
    private function getTranslatedMetadata(int $fieldId, int $langId): array
    {
        if ($fieldId <= 0) {
            return [];
        }

        // Get translations from repository (handles table prefix correctly)
        $translations = $this->fieldRepository->getFieldTranslations($fieldId);

        // Find translation for current language by ISO code
        $languages = \Language::getLanguages(true);
        $langCode = '';
        foreach ($languages as $lang) {
            if ((int) $lang['id_lang'] === $langId) {
                $langCode = $lang['iso_code'] ?? '';
                break;
            }
        }

        if (empty($langCode) || !isset($translations[$langCode])) {
            return [];
        }

        return [
            'title' => $translations[$langCode]['title'] ?? '',
            'instructions' => $translations[$langCode]['instructions'] ?? '',
            'placeholder' => $translations[$langCode]['placeholder'] ?? null,
        ];
    }

    /**
     * Decode JSON string to array if needed.
     *
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}

