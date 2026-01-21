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


if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Tools;

/**
 * Image upload field type.
 *
 * Stores image metadata as JSON (same format as FileField)
 */
final class ImageField extends AbstractFieldType
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
        return 'image';
    }

    public function getLabel(): string
    {
        return 'Image';
    }

    public function getFormType(): string
    {
        return FileType::class;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If already JSON string, validate and return
        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            // Valid image data has either filename (uploaded) or url (external link)
            if (\is_array($decoded) && (isset($decoded['filename']) || isset($decoded['url']))) {
                return $value;
            }

            return null;
        }

        // If array (from upload or external link), encode to JSON
        if (\is_array($value) && (isset($value['filename']) || isset($value['url']))) {
            return json_encode($value);
        }

        return null;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Parse JSON to array
        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (\is_array($decoded)) {
                return $decoded;
            }
        }

        // Already an array
        if (\is_array($value)) {
            return $value;
        }

        return null;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        $data = $this->denormalizeValue($value, $fieldConfig);

        if (!\is_array($data) || !isset($data['url'])) {
            return '';
        }

        $url = htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($data['original_name'] ?? 'Image', ENT_QUOTES, 'UTF-8');

        return \sprintf(
            '<img src="%s" alt="%s" class="acf-image" loading="lazy">',
            $url,
            $alt
        );
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $data = $this->denormalizeValue($value, $fieldConfig);

        if (!\is_array($data)) {
            return null;
        }

        return $data['original_name'] ?? $data['filename'] ?? null;
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        $data = $this->denormalizeValue($value, $fieldConfig);

        // Valid image data has either filename (uploaded) or url (external link)
        if (!\is_array($data) || (!isset($data['filename']) && !isset($data['url']))) {
            $errors[] = 'Invalid image data.';
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'allowedFormats' => ['jpg', 'png', 'gif', 'webp'],
            'maxWidth' => null,
            'maxHeight' => null,
            'maxSizeMB' => 5,
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
            'maxWidth' => [
                'type' => 'number',
                'label' => 'Max Width (px)',
                'help' => 'Leave empty for no resize',
                'default' => null,
            ],
            'maxHeight' => [
                'type' => 'number',
                'label' => 'Max Height (px)',
                'help' => 'Leave empty for no resize',
                'default' => null,
            ],
            'maxSizeMB' => [
                'type' => 'number',
                'label' => 'Max File Size (MB)',
                'default' => 5,
                'min' => 1,
                'max' => 20,
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
        return 'image';
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



    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';

        return \sprintf(
            '<div class="acf-image-field acf-image-compact" data-slug="%s">' .
            '<input type="hidden" class="acf-subfield-input acf-image-value" data-subfield="%s" value="{value}">' .
            '<div class="acf-image-preview" style="display: none;"><img src="" alt="" style="width: 50px; height: 50px; object-fit: contain;"><div class="acf-image-actions"><button type="button" class="btn btn-sm btn-link text-danger acf-image-remove"><i class="material-icons">delete</i></button></div></div>' .
            '<div class="acf-dropzone acf-dropzone-sm"><div class="acf-dropzone-content"><i class="material-icons">add_photo_alternate</i></div><input type="file" class="acf-image-input" accept="image/*" style="display: none;"></div>' .
            '</div>',
            $this->escapeAttr($slug),
            $this->escapeAttr($slug)
        );
    }
}
