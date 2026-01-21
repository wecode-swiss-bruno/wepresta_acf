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

use Configuration;
use Context;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Radio field type.
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

        // Handle arrays/objects (empty objects from JS become empty arrays)
        if (\is_array($value) || \is_object($value)) {
            return null;
        }

        return (string) $value;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Extract value for current language if translatable
        $actualValue = $this->extractTranslatableValue($value);

        if ($actualValue === null || $actualValue === '') {
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
            if (($choice['value'] ?? '') === $actualValue) {
                return htmlspecialchars($choice['label'] ?? $actualValue, ENT_QUOTES, 'UTF-8');
            }
        }

        // Fallback to value if no label found
        return htmlspecialchars((string) $actualValue, ENT_QUOTES, 'UTF-8');
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        // Validate that selected value exists in choices
        $validValues = array_column($fieldConfig['choices'] ?? [], 'value');

        if (!\in_array($value, $validValues, true)) {
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
            // Use same label resolution as buildChoices
            $choiceLabel = $this->getChoiceLabelForValidation($choice);
            $choiceLabel = addslashes($choiceLabel);
            $html .= \sprintf(
                '<label class="acf-radio-label"><input type="radio" class="acf-subfield-input" data-subfield="%s" value="%s"> %s</label>',
                $this->escapeAttr($slug),
                $choiceValue,
                $choiceLabel
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get current language ID from context.
     */
    private function getCurrentLanguageId(): string
    {
        if (isset($this->context) && method_exists($this->context, 'getLanguage')) {
            $language = $this->context->getLanguage();

            if ($language && isset($language->id)) {
                return (string) $language->id;
            }
        }

        // Try global Context
        if (class_exists('Context')) {
            $ctx = Context::getContext();

            if ($ctx && isset($ctx->language) && isset($ctx->language->id)) {
                return (string) $ctx->language->id;
            }
        }

        // Fallback to default language
        return $this->getDefaultLanguageId();
    }

    /**
     * Build Symfony choices array from config.
     *
     * @param array<int, array{value: string, label: string, translations?: array}> $choices
     *
     * @return array<string, string> Label => Value format for Symfony
     */
    private function buildChoices(array $choices): array
    {
        $result = [];

        foreach ($choices as $choice) {
            $value = $choice['value'] ?? '';

            if ($value === '') {
                continue;
            }

            // Get label: try translations first, then label, then value
            $label = $this->getChoiceLabelForValidation($choice);
            $result[$label] = $value;
        }

        return $result;
    }

    /**
     * Get the best available label for a choice (for Symfony validation).
     * Priority: translation of default language > main label > value.
     */
    private function getChoiceLabelForValidation(array $choice): string
    {
        // 1. Try translations (default language first, then any available)
        if (!empty($choice['translations']) && \is_array($choice['translations'])) {
            // Try default language
            $defaultLangId = $this->getDefaultLanguageId();

            if (isset($choice['translations'][$defaultLangId]) && !empty($choice['translations'][$defaultLangId])) {
                return $choice['translations'][$defaultLangId];
            }

            // Try any available translation
            foreach ($choice['translations'] as $langId => $label) {
                if (!empty($label)) {
                    return $label;
                }
            }
        }

        // 2. Fallback to main label
        if (!empty($choice['label'])) {
            return $choice['label'];
        }

        // 3. Last resort: use value as label
        return $choice['value'] ?? '';
    }

    /**
     * Get the default language ID.
     */
    private function getDefaultLanguageId(): string
    {
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');

        return (string) $defaultLangId;
    }
}
