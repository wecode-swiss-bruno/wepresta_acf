<?php

/**
 * Copyright since 2024 WeCode
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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Checkbox field type
 *
 * Multiple checkboxes for multi-select options.
 */
final class CheckboxField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'checkbox';
    }

    public function getLabel(): string
    {
        return 'Checkbox';
    }

    public function getFormType(): string
    {
        return ChoiceType::class;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Build choices from config
        $choices = $this->buildChoices($fieldConfig['choices'] ?? []);

        $options['choices'] = $choices;
        $options['multiple'] = true;
        $options['expanded'] = true; // Renders as checkboxes

        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        // Ensure it's an array
        if (!is_array($value)) {
            $value = [$value];
        }

        // Filter out empty values
        $value = array_filter($value, fn($v) => $v !== null && $v !== '');

        if (empty($value)) {
            return null;
        }

        // Store as JSON array
        return json_encode(array_values($value), JSON_THROW_ON_ERROR);
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return [];
        }

        // Parse JSON if stored as string
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $result = is_array($decoded) ? $decoded : [];
        } else {
            $result = is_array($value) ? $value : [];
        }

        // Remove duplicates (can happen if form submitted values twice)
        return array_values(array_unique($result));
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        // Extract value for current language if translatable
        $actualValue = $this->extractTranslatableValue($value);

        $values = $this->denormalizeValue($actualValue, $fieldConfig);

        if (empty($values)) {
            return '';
        }

        // Get labels for selected values
        $choices = $fieldConfig['choices'] ?? [];
        if (\is_string($choices)) {
            $choices = json_decode($choices, true) ?: [];
        }
        if (!\is_array($choices)) {
            $choices = [];
        }
        $labels = [];

        foreach ($values as $val) {
            foreach ($choices as $choice) {
                if (($choice['value'] ?? '') === $val) {
                    $labels[] = htmlspecialchars($choice['label'] ?? $val, ENT_QUOTES, 'UTF-8');
                    break;
                }
            }
        }

        // Render as comma-separated list or ul
        $format = $this->getConfigValue($renderOptions, 'format', 'list');

        if ($format === 'list') {
            return '<ul><li>' . implode('</li><li>', $labels) . '</li></ul>';
        }

        return implode(', ', $labels);
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $values = $this->denormalizeValue($value, $fieldConfig);

        if (empty($values)) {
            return null;
        }

        return implode(',', $values);
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        // For checkbox, empty array is "empty"
        $values = $this->denormalizeValue($value, $fieldConfig);

        if (!empty($validation['required']) && empty($values)) {
            // Remove the default required error and add our own
            $errors = array_filter($errors, fn($e) => $e !== 'This field is required.');
            $errors[] = 'Please select at least one option.';
        }

        // Validate that selected values exist in choices
        if (!empty($values)) {
            $validValues = array_column($fieldConfig['choices'] ?? [], 'value');
            foreach ($values as $val) {
                if (!in_array($val, $validValues, true)) {
                    $errors[] = sprintf('Invalid option: %s', $val);
                }
            }
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'choices' => [],
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'choices' => [
                'type' => 'repeater',
                'label' => 'Choices',
                'fields' => [
                    'value' => ['type' => 'text', 'label' => 'Value'],
                    'label' => ['type' => 'text', 'label' => 'Label'],
                ],
            ],
        ];
    }

    public function supportsTranslation(): bool
    {
        // Labels could be translated but values typically not
        return false;
    }

    public function getCategory(): string
    {
        return 'choice';
    }

    public function getIcon(): string
    {
        return 'check_box';
    }

    /**
     * Build Symfony choices array from config
     *
     * @param array<int, array{value: string, label: string}> $choices
     *
     * @return array<string, string> Label => Value format for Symfony
     */
    private function buildChoices(array $choices): array
    {
        $result = [];
        foreach ($choices as $choice) {
            $label = $choice['label'] ?? $choice['value'] ?? '';
            $value = $choice['value'] ?? '';
            if ($value !== '') {
                $result[$label] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);
        $selectedValues = $this->denormalizeValue($value, $config);

        return $this->renderPartial('checkbox.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $selectedValues,
            'context' => $context,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);
        $choices = $config['choices'] ?? [];
        if (!\is_array($choices)) {
            $choices = [];
        }

        $html = '<div class="acf-checkbox-group-inline">';

        foreach ($choices as $choice) {
            $choiceValue = $this->escapeAttr($choice['value'] ?? '');
            $choiceLabel = addslashes($choice['label'] ?? '');
            $html .= sprintf(
                '<label class="acf-checkbox-label"><input type="checkbox" class="acf-subfield-checkbox" data-subfield="%s" value="%s"> %s</label>',
                $this->escapeAttr($slug),
                $choiceValue,
                $choiceLabel
            );
        }

        $html .= '</div>';

        return $html;
    }
}
