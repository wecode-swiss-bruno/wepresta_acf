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
 * Radio field type
 *
 * Radio buttons for single selection from multiple options.
 */
final class RadioField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'radio';
    }

    public function getLabel(): string
    {
        return 'Radio Buttons';
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
        $options['multiple'] = false;
        $options['expanded'] = true; // Renders as radio buttons

        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Get label for selected value
        $choices = $fieldConfig['choices'] ?? [];
        if (\is_string($choices)) {
            $choices = json_decode($choices, true) ?: [];
        }
        if (!\is_array($choices)) {
            $choices = [];
        }

        foreach ($choices as $choice) {
            if (($choice['value'] ?? '') === $value) {
                return htmlspecialchars($choice['label'] ?? $value, ENT_QUOTES, 'UTF-8');
            }
        }

        // Fallback to value if no label found
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        // Validate that selected value exists in choices
        $validValues = array_column($fieldConfig['choices'] ?? [], 'value');
        if (!in_array($value, $validValues, true)) {
            $errors[] = 'Invalid option selected.';
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'choices' => [],
            'layout' => 'vertical',
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
            'layout' => [
                'type' => 'select',
                'label' => 'Layout',
                'options' => [
                    ['value' => 'vertical', 'label' => 'Vertical'],
                    ['value' => 'horizontal', 'label' => 'Horizontal'],
                ],
                'default' => 'vertical',
            ],
        ];
    }

    public function supportsTranslation(): bool
    {
        return false;
    }

    public function getCategory(): string
    {
        return 'choice';
    }

    public function getIcon(): string
    {
        return 'radio_button_checked';
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

        return $this->renderPartial('radio.tpl', [
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

        $html = '<div class="acf-radio-group-inline">';

        foreach ($choices as $choice) {
            $choiceValue = $this->escapeAttr($choice['value'] ?? '');
            $choiceLabel = addslashes($choice['label'] ?? '');
            $html .= sprintf(
                '<label class="acf-radio-label"><input type="radio" class="acf-subfield-input" data-subfield="%s" value="%s"> %s</label>',
                $this->escapeAttr($slug),
                $choiceValue,
                $choiceLabel
            );
        }

        $html .= '</div>';

        return $html;
    }
}
