<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

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
 * File upload field type.
 *
 * Stores file metadata as JSON:
 * {
 *   "filename": "1_42_1.pdf",
 *   "path": "files/1_42_1.pdf",
 *   "url": "/modules/acfps/uploads/files/1_42_1.pdf",
 *   "size": 123456,
 *   "mime": "application/pdf",
 *   "original_name": "document.pdf"
 * }
 */
final class FileField extends AbstractFieldType
{
    /** Default allowed MIME types for file uploads */
    private const DEFAULT_ALLOWED_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-zip-compressed',
    ];

    public function getType(): string
    {
        return 'file';
    }

    public function getLabel(): string
    {
        return 'File Upload';
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

            // Valid file data has either filename (uploaded) or url (external link)
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

        $filename = htmlspecialchars($data['original_name'] ?? $data['filename'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8');
        $size = isset($data['size']) ? $this->formatFileSize((int) $data['size']) : '';

        return \sprintf(
            '<a href="%s" class="acf-file-download" target="_blank" download>%s</a>%s',
            $url,
            $filename,
            $size ? ' <span class="acf-file-size">(' . $size . ')</span>' : ''
        );
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $data = $this->denormalizeValue($value, $fieldConfig);

        if (!\is_array($data)) {
            return null;
        }

        // Index the original filename for search
        return $data['original_name'] ?? $data['filename'] ?? null;
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        // File validation is handled during upload by FileUploadService
        // This validates the stored metadata

        if ($this->isEmpty($value)) {
            return $errors;
        }

        $data = $this->denormalizeValue($value, $fieldConfig);

        // Valid file data has either filename (uploaded) or url (external link)
        if (!\is_array($data) || (!isset($data['filename']) && !isset($data['url']))) {
            $errors[] = 'Invalid file data.';
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'allowedMimes' => self::DEFAULT_ALLOWED_MIMES,
            'maxSizeMB' => 10,
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'allowedMimes' => [
                'type' => 'multiselect',
                'label' => 'Allowed File Types',
                'options' => [
                    ['value' => 'application/pdf', 'label' => 'PDF'],
                    ['value' => 'application/msword', 'label' => 'Word (.doc)'],
                    ['value' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'label' => 'Word (.docx)'],
                    ['value' => 'application/vnd.ms-excel', 'label' => 'Excel (.xls)'],
                    ['value' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'label' => 'Excel (.xlsx)'],
                    ['value' => 'text/plain', 'label' => 'Text (.txt)'],
                    ['value' => 'text/csv', 'label' => 'CSV'],
                    ['value' => 'application/zip', 'label' => 'ZIP'],
                ],
                'default' => self::DEFAULT_ALLOWED_MIMES,
            ],
            'maxSizeMB' => [
                'type' => 'number',
                'label' => 'Max File Size (MB)',
                'default' => 10,
                'min' => 1,
                'max' => 50,
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
        return 'attach_file';
    }

    /**
     * Get allowed MIME types from config.
     *
     * @return array<string>
     */
    public function getAllowedMimes(array $fieldConfig): array
    {
        return $fieldConfig['allowedMimes'] ?? self::DEFAULT_ALLOWED_MIMES;
    }



    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';

        return \sprintf(
            '<div class="acf-file-field acf-file-compact" data-slug="%s">' .
            '<input type="hidden" class="acf-subfield-input acf-file-value" data-subfield="%s" value="{value}">' .
            '<div class="acf-file-preview" style="display: none;"><div class="acf-file-info"><i class="material-icons">insert_drive_file</i><span class="acf-file-name"></span></div><button type="button" class="btn btn-sm btn-link text-danger acf-file-remove"><i class="material-icons">delete</i></button></div>' .
            '<div class="acf-file-upload"><button type="button" class="btn btn-outline-secondary btn-sm acf-file-select"><i class="material-icons">attach_file</i></button><input type="file" class="acf-file-input" style="display: none;"></div>' .
            '</div>',
            $this->escapeAttr($slug),
            $this->escapeAttr($slug)
        );
    }

    /**
     * Format file size for display.
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < \count($units) - 1) {
            $bytes /= 1024;
            ++$i;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
