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
 * Select field type
 *
 * Dropdown/select field with static or dynamic choices.
 * Supports single and allowMultiple selection.
 */
final class SelectField extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Select';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType(): string
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Build choices from config
        $options['choices'] = $this->buildChoices($fieldConfig);

        // Multiple selection
        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);
        $options['allowMultiple'] = (bool) $allowMultiple;

        // Allow empty selection
        $options['placeholder'] = $this->getConfigValue($fieldConfig, 'placeholder', 'Choose an option...');

        // Expanded (radio buttons or checkboxes) vs collapsed (dropdown)
        $options['expanded'] = (bool) $this->getConfigValue($fieldConfig, 'expanded', false);

        // Allow custom value (freeform entry)
        if ($this->getConfigValue($fieldConfig, 'allowCustom', false)) {
            $options['attr']['data-allow-custom'] = 'true';
        }

        return $options;
    }

    /**
     * Build choices array from field config
     *
     * @param array<string, mixed> $fieldConfig
     *
     * @return array<string, string>
     */
    private function buildChoices(array $fieldConfig): array
    {
        $choices = [];
        $rawChoices = $this->getConfigValue($fieldConfig, 'choices', []);

        if (!is_array($rawChoices)) {
            return $choices;
        }

        foreach ($rawChoices as $choice) {
            if (is_array($choice) && isset($choice['label'], $choice['value'])) {
                // Format: [{label: 'Label', value: 'value'}, ...]
                $choices[$choice['label']] = $choice['value'];
            } elseif (is_string($choice)) {
                // Format: ['value1', 'value2', ...] - use value as label
                $choices[$choice] = $choice;
            }
        }

        return $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

        if ($allowMultiple) {
            // Store allowMultiple values as JSON array
            if (is_array($value)) {
                return json_encode(array_values($value), JSON_THROW_ON_ERROR);
            }

            return json_encode([$value], JSON_THROW_ON_ERROR);
        }

        // Single value: store as string
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

        if ($allowMultiple) {
            // Decode JSON array
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : [$value];
            }

            return is_array($value) ? $value : [$value];
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        // Get the choices map for label lookup
        $choicesMap = $this->buildValueToLabelMap($fieldConfig);
        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

        if ($allowMultiple) {
            // Handle allowMultiple values
            $values = is_array($value) ? $value : json_decode((string) $value, true) ?? [];
            $labels = [];

            foreach ($values as $val) {
                $labels[] = htmlspecialchars($choicesMap[$val] ?? (string) $val, ENT_QUOTES, 'UTF-8');
            }

            $separator = $renderOptions['separator'] ?? ', ';

            return implode($separator, $labels);
        }

        // Single value
        $label = $choicesMap[$value] ?? (string) $value;

        return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Build value => label map for rendering
     *
     * @param array<string, mixed> $fieldConfig
     *
     * @return array<string, string>
     */
    private function buildValueToLabelMap(array $fieldConfig): array
    {
        $map = [];
        $rawChoices = $this->getConfigValue($fieldConfig, 'choices', []);

        if (!is_array($rawChoices)) {
            return $map;
        }

        foreach ($rawChoices as $choice) {
            if (is_array($choice) && isset($choice['label'], $choice['value'])) {
                $map[$choice['value']] = $choice['label'];
            } elseif (is_string($choice)) {
                $map[$choice] = $choice;
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

        if ($allowMultiple) {
            $values = is_array($value) ? $value : json_decode((string) $value, true) ?? [];

            return implode(',', array_slice($values, 0, 5)); // First 5 values for index
        }

        return substr((string) $value, 0, 255);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        // Skip further validation if empty (and not required)
        if ($this->isEmpty($value)) {
            return $errors;
        }

        // Validate against allowed choices (unless allowCustom is enabled)
        if (!$this->getConfigValue($fieldConfig, 'allowCustom', false)) {
            $validValues = array_values($this->buildChoices($fieldConfig));
            $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

            if ($allowMultiple) {
                $values = is_array($value) ? $value : [$value];
                foreach ($values as $val) {
                    if (!in_array($val, $validValues, true)) {
                        $errors[] = sprintf('Invalid choice: %s', $val);
                    }
                }
            } else {
                if (!in_array($value, $validValues, true)) {
                    $errors[] = 'Invalid choice selected.';
                }
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfig(): array
    {
        return [
            'choices' => [],
            'allowMultiple' => false,
            'expanded' => false,
            'allowCustom' => false,
            'placeholder' => 'Choose an option...',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'choices' => [
                'type' => 'repeater',
                'label' => 'Choices',
                'help' => 'Available options for selection',
                'fields' => [
                    'label' => ['type' => 'text', 'label' => 'Label'],
                    'value' => ['type' => 'text', 'label' => 'Value'],
                ],
                'default' => [],
            ],
            'allowMultiple' => [
                'type' => 'checkbox',
                'label' => 'Allow Multiple',
                'help' => 'Allow selecting allowMultiple options',
                'default' => false,
            ],
            'expanded' => [
                'type' => 'checkbox',
                'label' => 'Expanded',
                'help' => 'Show as radio buttons (single) or checkboxes (allowMultiple)',
                'default' => false,
            ],
            'allowCustom' => [
                'type' => 'checkbox',
                'label' => 'Allow Custom Values',
                'help' => 'Allow entering values not in the list',
                'default' => false,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        return 'list';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);

        return $this->renderPartial('select.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $value,
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

        $html = sprintf(
            '<select class="form-control form-control-sm acf-subfield-input" data-subfield="%s">',
            $this->escapeAttr($slug)
        );

        $html .= '<option value="">-- Select --</option>';

        foreach ($choices as $choice) {
            $choiceValue = $this->escapeAttr($choice['value'] ?? '');
            $choiceLabel = addslashes($choice['label'] ?? '');
            $html .= sprintf('<option value="%s">%s</option>', $choiceValue, $choiceLabel);
        }

        $html .= '</select>';

        return $html;
    }
}
