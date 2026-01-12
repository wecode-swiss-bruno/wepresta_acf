<?php

/**
 * Copyright since 2024 WeCode.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 *
 * @author    Wecode <prestashop@wecode.swiss>
 * @copyright Since 2024 WeCode
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * Gallery field type - Multiple images with reorderable gallery.
 *
 * Stores array of image metadata as JSON:
 * [
 *   {
 *     "id": "uuid-1",
 *     "filename": "1_42_1_0.jpg",
 *     "path": "images/1_42_1_0.jpg",
 *     "url": "/modules/acfps/uploads/images/1_42_1_0.jpg",
 *     "size": 123456,
 *     "mime": "image/jpeg",
 *     "original_name": "photo1.jpg",
 *     "title": "Image Title",
 *     "description": "Image description",
 *     "position": 0
 *   },
 *   ...
 * ]
 */
final class GalleryField extends AbstractFieldType
{
    /** Allowed MIME types for image uploads */
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function getType(): string
    {
        return 'gallery';
    }

    public function getLabel(): string
    {
        return 'Gallery';
    }

    public function getFormType(): string
    {
        return FileType::class;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === '[]') {
            return null;
        }

        // If already JSON string, validate and return
        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (\is_array($decoded)) {
                // Ensure it's an array of items (not a single item)
                if (\count($decoded) === 0) {
                    return null;
                }

                // Check if it's already a list (numeric array of arrays)
                if (isset($decoded[0])) {
                    return $value;
                }

                // Single item - wrap in array
                return json_encode([$decoded]);
            }

            return null;
        }

        // If array, encode to JSON
        if (\is_array($value)) {
            if (empty($value)) {
                return null;
            }

            return json_encode(array_values($value));
        }

        return null;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === '[]') {
            return [];
        }

        // Parse JSON to array
        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (\is_array($decoded)) {
                // Sort by position
                usort($decoded, fn ($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

                return $decoded;
            }

            // Handle corrupted JSON - if it's truncated array/object, return empty
            $value = trim($value);
            if ((str_starts_with($value, '[') && !str_ends_with($value, ']')) ||
                (str_starts_with($value, '{') && !str_ends_with($value, '}'))) {
                return [];
            }
        }

        // Already an array
        if (\is_array($value)) {
            usort($value, fn ($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

            return $value;
        }

        return [];
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        $items = $this->denormalizeValue($value, $fieldConfig);

        if (empty($items)) {
            return '';
        }

        $html = '<div class="acf-gallery">';

        foreach ($items as $item) {
            if (! isset($item['url'])) {
                continue;
            }

            $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($item['title'] ?? $item['original_name'] ?? 'Image', ENT_QUOTES, 'UTF-8');

            $html .= \sprintf(
                '<figure class="acf-gallery-item"><img src="%s" alt="%s" class="img-fluid" loading="lazy"></figure>',
                $url,
                $alt
            );
        }

        $html .= '</div>';

        return $html;
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $items = $this->denormalizeValue($value, $fieldConfig);

        if (empty($items)) {
            return null;
        }

        // Return comma-separated titles/names
        $names = [];

        foreach ($items as $item) {
            $names[] = $item['title'] ?? $item['original_name'] ?? '';
        }

        return implode(', ', array_filter($names));
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        $items = $this->denormalizeValue($value, $fieldConfig);

        $minItems = $fieldConfig['minItems'] ?? null;
        $maxItems = $fieldConfig['maxItems'] ?? null;

        if ($minItems !== null && \count($items) < $minItems) {
            $errors[] = \sprintf('Minimum %d images required.', $minItems);
        }

        if ($maxItems !== null && \count($items) > $maxItems) {
            $errors[] = \sprintf('Maximum %d images allowed.', $maxItems);
        }

        return $errors;
    }

    public function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === '[]') {
            return true;
        }

        $items = $this->denormalizeValue($value, []);

        return empty($items);
    }

    public function getDefaultConfig(): array
    {
        return [
            'allowedFormats' => ['jpg', 'png', 'gif', 'webp'],
            'maxSizeMB' => 5,
            'minItems' => null,
            'maxItems' => null,
            'enableTitle' => true,
            'enableDescription' => false,
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'allowedFormats' => [
                'type' => 'multiselect',
                'label' => 'Allowed Formats',
                'options' => [
                    ['value' => 'jpg', 'label' => 'JPEG'],
                    ['value' => 'png', 'label' => 'PNG'],
                    ['value' => 'gif', 'label' => 'GIF'],
                    ['value' => 'webp', 'label' => 'WebP'],
                ],
                'default' => ['jpg', 'png', 'gif', 'webp'],
            ],
            'maxSizeMB' => [
                'type' => 'number',
                'label' => 'Max File Size (MB)',
                'default' => 5,
                'min' => 1,
                'max' => 20,
            ],
            'minItems' => [
                'type' => 'number',
                'label' => 'Minimum Images',
                'help' => 'Leave empty for no minimum',
                'default' => null,
                'min' => 0,
            ],
            'maxItems' => [
                'type' => 'number',
                'label' => 'Maximum Images',
                'help' => 'Leave empty for no limit',
                'default' => null,
                'min' => 1,
            ],
            'enableTitle' => [
                'type' => 'checkbox',
                'label' => 'Enable Title Field',
                'default' => true,
            ],
            'enableDescription' => [
                'type' => 'checkbox',
                'label' => 'Enable Description Field',
                'default' => false,
            ],
        ];
    }

    public function supportsTranslation(): bool
    {
        return false;
    }

    public function getCategory(): string
    {
        return 'media';
    }

    public function getIcon(): string
    {
        return 'photo_library';
    }

    /**
     * Get allowed MIME types from config.
     *
     * @return array<string>
     */
    public function getAllowedMimes(array $fieldConfig): array
    {
        $formats = $fieldConfig['allowedFormats'] ?? ['jpg', 'png', 'gif', 'webp'];
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        $mimes = [];

        foreach ($formats as $format) {
            $format = strtolower($format);

            if (isset($mimeMap[$format])) {
                $mimes[] = $mimeMap[$format];
            }
        }

        return array_unique($mimes) ?: self::ALLOWED_IMAGE_MIMES;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);
        $items = $this->denormalizeValue($value, $config);

        return $this->renderPartial('gallery.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $items,
            'context' => $context,
        ]);
    }

    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';

        return \sprintf(
            '<div class="acf-gallery-field acf-gallery-compact" data-slug="%s">' .
            '<input type="hidden" class="acf-subfield-input acf-gallery-value" data-subfield="%s" value="{value}">' .
            '<div class="acf-gallery-items"></div>' .
            '<div class="acf-gallery-add">' .
            '<button type="button" class="btn btn-outline-secondary btn-sm acf-gallery-add-btn"><i class="material-icons">add_photo_alternate</i></button>' .
            '<input type="file" class="acf-gallery-input" accept="image/*" multiple style="display: none;">' .
            '</div>' .
            '</div>',
            $this->escapeAttr($slug),
            $this->escapeAttr($slug)
        );
    }
}
