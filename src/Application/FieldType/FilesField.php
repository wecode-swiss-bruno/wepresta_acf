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

/**
 * Files field type - Multiple file uploads with reordering.
 *
 * Stores array of file metadata as JSON:
 * [
 *   {
 *     "id": "uuid-1",
 *     "filename": "1_42_1_0.pdf",
 *     "path": "files/1_42_1_0.pdf",
 *     "url": "/modules/acfps/uploads/files/1_42_1_0.pdf",
 *     "size": 123456,
 *     "mime": "application/pdf",
 *     "original_name": "document.pdf",
 *     "title": "Document Title",
 *     "description": "Document description",
 *     "position": 0
 *   },
 *   ...
 * ]
 */
final class FilesField extends AbstractFieldType
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
        return 'files';
    }

    public function getLabel(): string
    {
        return 'Files';
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
                if (\count($decoded) === 0) {
                    return null;
                }

                // Check if it's already a list (numeric array)
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
                usort($decoded, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

                return $decoded;
            }
        }

        // Already an array
        if (\is_array($value)) {
            usort($value, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

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

        $html = '<ul class="acf-files-list">';

        foreach ($items as $item) {
            if (!isset($item['url'])) {
                continue;
            }

            $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars($item['title'] ?? $item['original_name'] ?? 'File', ENT_QUOTES, 'UTF-8');
            $size = isset($item['size']) ? $this->formatFileSize((int) $item['size']) : '';

            $html .= \sprintf(
                '<li class="acf-file-item"><a href="%s" target="_blank" download>%s</a>%s</li>',
                $url,
                $name,
                $size ? ' <span class="acf-file-size">(' . $size . ')</span>' : ''
            );
        }

        $html .= '</ul>';

        return $html;
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $items = $this->denormalizeValue($value, $fieldConfig);

        if (empty($items)) {
            return null;
        }

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
            $errors[] = \sprintf('Minimum %d files required.', $minItems);
        }

        if ($maxItems !== null && \count($items) > $maxItems) {
            $errors[] = \sprintf('Maximum %d files allowed.', $maxItems);
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
            'allowedMimes' => self::DEFAULT_ALLOWED_MIMES,
            'maxSizeMB' => 10,
            'minItems' => null,
            'maxItems' => null,
            'enableTitle' => true,
            'enableDescription' => true,
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
            'minItems' => [
                'type' => 'number',
                'label' => 'Minimum Files',
                'help' => 'Leave empty for no minimum',
                'default' => null,
                'min' => 0,
            ],
            'maxItems' => [
                'type' => 'number',
                'label' => 'Maximum Files',
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
        return 'folder';
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
            '<div class="acf-files-field acf-files-compact" data-slug="%s">' .
            '<input type="hidden" class="acf-subfield-input acf-files-value" data-subfield="%s" value="{value}">' .
            '<div class="acf-files-list"></div>' .
            '<div class="acf-files-add">' .
            '<button type="button" class="btn btn-outline-secondary btn-sm acf-files-add-btn"><i class="material-icons">attach_file</i></button>' .
            '<input type="file" class="acf-files-input" multiple style="display: none;">' .
            '</div>' .
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

    /**
     * Get file icon based on mime type.
     */
    private function getFileIcon(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'pdf') => 'picture_as_pdf',
            str_contains($mime, 'word') || str_contains($mime, 'document') => 'description',
            str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet') => 'table_chart',
            str_contains($mime, 'zip') || str_contains($mime, 'archive') => 'folder_zip',
            str_contains($mime, 'text') => 'article',
            default => 'insert_drive_file',
        };
    }
}
