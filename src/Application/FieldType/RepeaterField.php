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

use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Repeater field type - Group repeater with unlimited nesting.
 *
 * Stores array of rows as JSON:
 * [
 *   {
 *     "row_id": "uuid-1",
 *     "collapsed": false,
 *     "values": {
 *       "subfield_slug": "value1",
 *       "another_field": "value2",
 *       "nested_repeater": [...]
 *     }
 *   },
 *   ...
 * ]
 *
 * Subfields are defined in the admin builder and stored with id_parent = this field's ID.
 */
final class RepeaterField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'repeater';
    }

    public function getLabel(): string
    {
        return 'Repeater';
    }

    public function getFormType(): string
    {
        return HiddenType::class;
    }

    public function getCategory(): string
    {
        return 'layout';
    }

    public function getIcon(): string
    {
        return 'repeat';
    }

    public function supportsTranslation(): bool
    {
        // Repeater itself is not translatable, but subfields may be
        return false;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === '[]') {
            return null;
        }

        // If already JSON string, decode
        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (!\is_array($decoded)) {
                return null;
            }
            $value = $decoded;
        }

        if (!\is_array($value)) {
            return null;
        }

        // Check if this is a valid repeater structure
        // Valid format: [{"row_id":"...","values":{...}}, ...]
        // Invalid format: {"1":"...","2":"..."} (translations object)
        if (!empty($value)) {
            $firstItem = reset($value);
            $isValidRepeaterStructure = \is_array($firstItem) && (isset($firstItem['row_id']) || isset($firstItem['values']));

            // If invalid structure (like a translations object), return null
            // This prevents errors and allows proper handling elsewhere
            if (!$isValidRepeaterStructure) {
                return null;
            }
        }

        // Normalize each row
        $normalized = [];

        foreach ($value as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $rowData = [
                'row_id' => $row['row_id'] ?? Uuid::uuid4()->toString(),
                'collapsed' => (bool) ($row['collapsed'] ?? false),
                'values' => [],
            ];

            // Copy values as-is (subfield normalization happens in ValueHandler)
            if (isset($row['values']) && \is_array($row['values'])) {
                $rowData['values'] = $row['values'];
            }

            $normalized[] = $rowData;
        }

        if (\count($normalized) === 0) {
            return null;
        }

        return json_encode($normalized);
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === '[]') {
            return [];
        }

        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (\is_array($decoded)) {
                // Check if this is a valid repeater structure (array of rows)
                // Valid structure: [{"row_id":"...","values":{...}}]
                // If not, it might be malformed data - return empty array to avoid errors
                $isValidRepeaterStructure = false;

                if (!empty($decoded)) {
                    $firstItem = reset($decoded);
                    $isValidRepeaterStructure = \is_array($firstItem) && (isset($firstItem['row_id']) || isset($firstItem['values']));
                }

                return $isValidRepeaterStructure ? $decoded : [];
            }

            return [];
        }

        if (\is_array($value)) {
            // Same validation for array values
            if (!empty($value)) {
                $firstItem = reset($value);
                $isValidRepeaterStructure = \is_array($firstItem) && (isset($firstItem['row_id']) || isset($firstItem['values']));

                return $isValidRepeaterStructure ? $value : [];
            }

            return [];
        }

        return [];
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        $rows = $this->denormalizeValue($value, $fieldConfig);

        if (\count($rows) === 0) {
            return '';
        }

        // Subfields and registry for proper rendering
        $subfields = $renderOptions['subfields'] ?? [];
        $registry = $renderOptions['fieldTypeRegistry'] ?? null;

        // Index subfields by slug for easy lookup
        $subfieldsBySlug = [];

        foreach ($subfields as $subfield) {
            $subfieldsBySlug[$subfield['slug']] = $subfield;
        }

        $html = '<table class="acf-repeater">';

        foreach ($rows as $index => $row) {
            $html .= '<tr class="acf-repeater-row" data-row-id="' . htmlspecialchars($row['row_id'] ?? '', ENT_QUOTES, 'UTF-8') . '">';

            if (!empty($row['values']) && \is_array($row['values'])) {
                foreach ($row['values'] as $slug => $subfieldValue) {
                    $html .= '<td class="acf-repeater-field" data-field="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '">';

                    // Try to render using the appropriate field type
                    $rendered = false;

                    if ($registry !== null && isset($subfieldsBySlug[$slug])) {
                        $subfield = $subfieldsBySlug[$slug];
                        $subfieldType = $registry->getOrNull($subfield['type'] ?? '');

                        if ($subfieldType !== null) {
                            $subfieldConfig = json_decode($subfield['config'] ?? '{}', true) ?: [];
                            $denormalizedValue = $subfieldType->denormalizeValue($subfieldValue, $subfieldConfig);
                            $html .= $subfieldType->renderValue($denormalizedValue, $subfieldConfig, $renderOptions);
                            $rendered = true;
                        }
                    }

                    // Fallback to simple rendering
                    if (!$rendered) {
                        $html .= htmlspecialchars(\is_string($subfieldValue) ? $subfieldValue : json_encode($subfieldValue), ENT_QUOTES, 'UTF-8');
                    }

                    $html .= '</td>';
                }
            }

            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $rows = $this->denormalizeValue($value, $fieldConfig);

        if (\count($rows) === 0) {
            return null;
        }

        // Index: count of rows
        return \sprintf('%d rows', \count($rows));
    }

    public function getDefaultConfig(): array
    {
        return [
            'min' => 0,
            'max' => 0, // 0 = unlimited
            'collapsed' => false, // Default collapsed state for new rows
            'rowTitle' => '', // Template for row title, e.g., "{title}" or "Row {#}"
            'buttonLabel' => 'Add Row',
            'displayMode' => 'table', // 'table' or 'cards'
        ];
    }

    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'min' => [
                'type' => 'number',
                'label' => 'Minimum Rows',
                'default' => 0,
            ],
            'max' => [
                'type' => 'number',
                'label' => 'Maximum Rows',
                'default' => 0,
                'help' => '0 = unlimited',
            ],
            'collapsed' => [
                'type' => 'boolean',
                'label' => 'Collapsed by Default',
                'default' => false,
            ],
            'rowTitle' => [
                'type' => 'text',
                'label' => 'Row Title Template',
                'default' => '',
                'help' => 'Use {field_slug} to display a field value, or {#} for row number',
            ],
            'buttonLabel' => [
                'type' => 'text',
                'label' => 'Add Button Label',
                'default' => 'Add Row',
            ],
            'displayMode' => [
                'type' => 'select',
                'label' => 'Display Mode',
                'default' => 'table',
                'options' => [
                    'table' => 'Table (rows)',
                    'cards' => 'Cards',
                ],
            ],
        ]);
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = [];

        $rows = $this->denormalizeValue($value, $fieldConfig);
        $count = \count($rows);

        // Check required
        if (!empty($validation['required']) && $count === 0) {
            $errors[] = 'This field is required.';

            return $errors;
        }

        // Check min
        $min = (int) $this->getConfigValue($fieldConfig, 'min', 0);

        if ($min > 0 && $count < $min) {
            $errors[] = \sprintf('At least %d row(s) required.', $min);
        }

        // Check max
        $max = (int) $this->getConfigValue($fieldConfig, 'max', 0);

        if ($max > 0 && $count > $max) {
            $errors[] = \sprintf('Maximum %d row(s) allowed.', $max);
        }

        // Note: Subfield validation is handled by ValueHandler

        return $errors;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        return [
            'required' => false, // Handled by custom validation
            'attr' => [
                'class' => 'acf-repeater-value',
                'data-min' => $this->getConfigValue($fieldConfig, 'min', 0),
                'data-max' => $this->getConfigValue($fieldConfig, 'max', 0),
                'data-collapsed' => $this->getConfigValue($fieldConfig, 'collapsed', false) ? '1' : '0',
                'data-row-title' => $this->getConfigValue($fieldConfig, 'rowTitle', ''),
                'data-button-label' => $this->getConfigValue($fieldConfig, 'buttonLabel', 'Add Row'),
            ],
        ];
    }

    /**
     * Create an empty row structure.
     *
     * @return array<string, mixed>
     */
    public static function createEmptyRow(): array
    {
        return [
            'row_id' => Uuid::uuid4()->toString(),
            'collapsed' => false,
            'values' => [],
        ];
    }

    /**
     * Generate row title from template.
     *
     * @param string $template Title template (e.g., "{title}" or "Row {#}")
     * @param array<string, mixed> $values Row values
     * @param int $rowIndex Row index (1-based)
     *
     * @return string Generated title
     */
    public static function generateRowTitle(string $template, array $values, int $rowIndex): string
    {
        if (empty($template)) {
            return \sprintf('Row %d', $rowIndex);
        }

        $title = $template;

        // Replace {#} with row number
        $title = str_replace('{#}', (string) $rowIndex, $title);

        // Replace {field_slug} with field values
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $template, $matches);

        foreach ($matches[1] as $fieldSlug) {
            if (isset($values[$fieldSlug])) {
                $value = $values[$fieldSlug];

                if (\is_string($value)) {
                    $title = str_replace('{' . $fieldSlug . '}', $value, $title);
                } elseif (\is_array($value) && isset($value['text'])) {
                    // For list items, use first text
                    $title = str_replace('{' . $fieldSlug . '}', $value['text'], $title);
                }
            } else {
                // Remove unfilled placeholders
                $title = str_replace('{' . $fieldSlug . '}', '', $title);
            }
        }

        return trim($title) ?: \sprintf('Row %d', $rowIndex);
    }



    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);
        $buttonLabel = $config['buttonLabel'] ?? 'Add Row';

        // Nested repeaters in table mode are simplified
        return \sprintf(
            '<div class="acf-repeater-field acf-repeater-compact" data-slug="%s">' .
            '<input type="hidden" class="acf-subfield-input acf-repeater-value" data-subfield="%s" value="{value}">' .
            '<div class="acf-repeater-rows"></div>' .
            '<button type="button" class="btn btn-outline-secondary btn-sm acf-repeater-add"><i class="material-icons">add</i> %s</button>' .
            '</div>',
            $this->escapeAttr($slug),
            $this->escapeAttr($slug),
            $this->escapeAttr($buttonLabel)
        );
    }
}
