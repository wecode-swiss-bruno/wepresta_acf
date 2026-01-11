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

use Configuration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Select field type.
 *
 * Dropdown/select field with static or dynamic choices.
 * Supports single and allowMultiple selection.
 */
final class SelectField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'select';
    }

    public function getLabel(): string
    {
        return 'Select';
    }

    public function getFormType(): string
    {
        return ChoiceType::class;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Build choices from config - IMPORTANT: Use original choices for validation
        $originalChoices = $this->getConfigValue($fieldConfig, 'choices', []);
        $options['choices'] = $this->buildChoicesFromRaw($originalChoices);

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
     * Get sanitized choices for admin display.
     * Ensures labels are properly set from translations.
     *
     * @param array<string, mixed> $fieldConfig
     *
     * @return array<array<string, mixed>>
     */
    public function getSanitizedChoicesForAdmin(array $fieldConfig): array
    {
        $rawChoices = $this->getConfigValue($fieldConfig, 'choices', []);
        $defaultLangId = $this->getDefaultLanguageId();

        return array_map(function ($choice) use ($defaultLangId) {
            if (! \is_array($choice) || ! isset($choice['value'])) {
                return $choice;
            }

            // Ensure translations array exists
            if (! isset($choice['translations']) || ! \is_array($choice['translations'])) {
                $choice['translations'] = [];
            }

            // Fix: If main label is empty, try to get it from default language translation
            if (empty($choice['label']) && isset($choice['translations'][$defaultLangId]) && ! empty($choice['translations'][$defaultLangId])) {
                $choice['label'] = $choice['translations'][$defaultLangId];
            }

            // Fix: If default language translation is empty but main label exists, sync it
            if (! empty($choice['label']) && empty($choice['translations'][$defaultLangId] ?? '')) {
                $choice['translations'][$defaultLangId] = $choice['label'];
            }

            return $choice;
        }, $rawChoices);
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

        if ($allowMultiple) {
            // Store allowMultiple values as JSON array
            if (\is_array($value)) {
                return json_encode(array_values($value), JSON_THROW_ON_ERROR);
            }

            return json_encode([$value], JSON_THROW_ON_ERROR);
        }

        // Single value: store as string
        return (string) $value;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

        if ($allowMultiple) {
            // Decode JSON array
            if (\is_string($value)) {
                $decoded = json_decode($value, true);

                return \is_array($decoded) ? $decoded : [$value];
            }

            return \is_array($value) ? $value : [$value];
        }

        return $value;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        // Extract value for current language if translatable
        $actualValue = $this->extractTranslatableValue($value);

        if ($actualValue === null || $actualValue === '' || $actualValue === []) {
            return '';
        }

        // Get the choices map for label lookup
        $choicesMap = $this->buildValueToLabelMap($fieldConfig);
        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

        if ($allowMultiple) {
            // Handle allowMultiple values
            $values = \is_array($actualValue) ? $actualValue : json_decode((string) $actualValue, true) ?? [];
            $labels = [];

            foreach ($values as $val) {
                $labels[] = htmlspecialchars($choicesMap[$val] ?? (string) $val, ENT_QUOTES, 'UTF-8');
            }

            $separator = $renderOptions['separator'] ?? ', ';

            return implode($separator, $labels);
        }

        // Single value
        $label = $choicesMap[$actualValue] ?? (string) $actualValue;

        return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

        if ($allowMultiple) {
            $values = \is_array($value) ? $value : json_decode((string) $value, true) ?? [];

            return implode(',', \array_slice($values, 0, 5)); // First 5 values for index
        }

        return substr((string) $value, 0, 255);
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        // Skip further validation if empty (and not required)
        if ($this->isEmpty($value)) {
            return $errors;
        }

        // Validate against allowed choices (unless allowCustom is enabled)
        if (! $this->getConfigValue($fieldConfig, 'allowCustom', false)) {
            $validValues = array_values($this->buildChoices($fieldConfig));
            $allowMultiple = $this->getConfigValue($fieldConfig, 'allowMultiple', false);

            if ($allowMultiple) {
                $values = \is_array($value) ? $value : [$value];

                foreach ($values as $val) {
                    if (! \in_array($val, $validValues, true)) {
                        $errors[] = \sprintf('Invalid choice: %s', $val);
                    }
                }
            } else {
                if (! \in_array($value, $validValues, true)) {
                    $errors[] = 'Invalid choice selected.';
                }
            }
        }

        return $errors;
    }

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

    public function getCategory(): string
    {
        return 'choice';
    }

    public function getIcon(): string
    {
        return 'list';
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);

        // Sanitize choices to ensure proper label handling
        $sanitizedConfig = $config;
        $sanitizedConfig['choices'] = $this->getSanitizedChoicesForAdmin($config);

        // Pass current language ID for template translation
        $currentLangId = $this->getCurrentLanguageId();

        return $this->renderPartial('select.tpl', [
            'field' => $field,
            'fieldConfig' => $sanitizedConfig,  // Sanitized choices for display
            'currentLangId' => $currentLangId,  // For template translation
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $value,
            'context' => $context,
        ]);
    }

    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);

        // Sanitize choices for consistent label display
        $choices = $this->getSanitizedChoicesForAdmin($config);

        if (! \is_array($choices)) {
            $choices = [];
        }

        $html = \sprintf(
            '<select class="form-control form-control-sm acf-subfield-input" data-subfield="%s">',
            $this->escapeAttr($slug)
        );

        $html .= '<option value="">-- Select --</option>';

        foreach ($choices as $choice) {
            $choiceValue = $this->escapeAttr($choice['value'] ?? '');
            // Use same label resolution as buildChoices
            $choiceLabel = $this->getChoiceLabelForValidation($choice);
            $choiceLabel = addslashes($choiceLabel);
            $html .= \sprintf('<option value="%s">%s</option>', $choiceValue, $choiceLabel);
        }

        $html .= '</select>';

        return $html;
    }

    public function supportsTranslation(): bool
    {
        // Choice labels can be translated
        return false;
    }

    /**
     * Build choices array from field config.
     *
     * @param array<string, mixed> $fieldConfig
     *
     * @return array<string, string>
     */
    private function buildChoices(array $fieldConfig): array
    {
        $rawChoices = $this->getConfigValue($fieldConfig, 'choices', []);

        return $this->buildChoicesFromRaw($rawChoices);
    }

    private function buildChoicesFromRaw(array $rawChoices): array
    {
        $choices = [];

        if (! \is_array($rawChoices)) {
            return $choices;
        }

        foreach ($rawChoices as $index => $choice) {
            if (\is_array($choice) && isset($choice['value'])) {
                // Format: [{label: 'Label', value: 'value', translations: {...}}, ...]
                $value = $choice['value'];

                // Skip choices with empty values
                if (empty($value)) {
                    continue;
                }

                // Get label: try translations first, then label, then value
                $label = $this->getChoiceLabelForValidation($choice);

                $choices[$label] = $value;
            } elseif (\is_string($choice)) {
                // Format: ['value1', 'value2', ...] - use value as label
                if (! empty($choice)) {
                    $choices[$choice] = $choice;
                }
            }
        }

        return $choices;
    }

    /**
     * Get the best available label for a choice (for Symfony validation).
     * Priority: translation of default language > main label > value.
     */
    private function getChoiceLabelForValidation(array $choice): string
    {
        // 1. Try translations (default language first, then any available)
        if (! empty($choice['translations']) && \is_array($choice['translations'])) {
            // Try default language
            $defaultLangId = $this->getDefaultLanguageId();

            if (isset($choice['translations'][$defaultLangId]) && ! empty($choice['translations'][$defaultLangId])) {
                return $choice['translations'][$defaultLangId];
            }

            // Try any available translation
            foreach ($choice['translations'] as $langId => $label) {
                if (! empty($label)) {
                    return $label;
                }
            }
        }

        // 2. Fallback to main label
        if (! empty($choice['label'])) {
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

    /**
     * Build value => label map for rendering.
     *
     * @return array<string, string>
     */
    /**
     * Get translated label for a choice based on current language.
     */
    private function getTranslatedChoiceLabel(array $choice): string
    {
        // Get current language ID from context
        $currentLangId = $this->getCurrentLanguageId();

        // Check if choice has translations and current language translation exists
        if (isset($choice['translations']) && isset($choice['translations'][$currentLangId])) {
            $translated = $choice['translations'][$currentLangId];

            if (! empty($translated)) {
                return $translated;
            }
        }

        // Fallback to default label
        return $choice['label'] ?? '';
    }

    /**
     * Get current language ID from context.
     */
    private function getCurrentLanguageId(): string
    {
        // Try to get from PrestaShop context
        if (isset($this->context) && method_exists($this->context, 'getLanguage')) {
            $language = $this->context->getLanguage();

            if ($language && isset($language->id)) {
                return (string) $language->id;
            }
        }

        // Fallback to default language (usually 1 for English)
        return '1';
    }

    private function buildValueToLabelMap(array $fieldConfig): array
    {
        $map = [];
        $rawChoices = $this->getConfigValue($fieldConfig, 'choices', []);

        if (! \is_array($rawChoices)) {
            return $map;
        }

        foreach ($rawChoices as $choice) {
            if (\is_array($choice) && isset($choice['label'], $choice['value'])) {
                $translatedLabel = $this->getTranslatedChoiceLabel($choice);
                $map[$choice['value']] = $translatedLabel ?: $choice['label'];
            } elseif (\is_string($choice)) {
                $map[$choice] = $choice;
            }
        }

        return $map;
    }

    /**
     * Translate choices for admin display using back-office language.
     */
    private function translateChoicesForAdmin(array $choices): array
    {
        $currentLangId = $this->getCurrentLanguageId();

        return array_map(function ($choice) use ($currentLangId) {
            if (! \is_array($choice) || ! isset($choice['label'])) {
                return $choice;
            }

            // Check if choice has translations for current back-office language
            if (isset($choice['translations']) && isset($choice['translations'][$currentLangId])) {
                $translated = $choice['translations'][$currentLangId];

                if (! empty($translated)) {
                    $choice['label'] = $translated;
                }
            }

            return $choice;
        }, $choices);
    }
}
