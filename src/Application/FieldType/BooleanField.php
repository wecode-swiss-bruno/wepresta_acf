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

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * Boolean field type
 *
 * Simple true/false toggle switch.
 */
final class BooleanField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'boolean';
    }

    public function getLabel(): string
    {
        return 'True/False';
    }

    public function getFormType(): string
    {
        return CheckboxType::class;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Checkbox is never required in Symfony forms (unchecked = false)
        $options['required'] = false;

        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        // Convert to 0/1 for storage
        return $value ? '1' : '0';
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        // Convert to boolean - handle various formats
        if ($value === null || $value === '' || $value === false) {
            return false;
        }
        
        if ($value === true || $value === '1' || $value === 1) {
            return true;
        }
        
        if ($value === '0' || $value === 0) {
            return false;
        }
        
        // Default: convert to boolean
        return (bool) $value;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        // Extract value for current language if translatable
        $actualValue = $this->extractTranslatableValue($value);

        $boolValue = $this->denormalizeValue($actualValue, $fieldConfig);

        $trueLabel = $this->getConfigValue($fieldConfig, 'trueLabel', 'Yes');
        $falseLabel = $this->getConfigValue($fieldConfig, 'falseLabel', 'No');

        $label = $boolValue ? $trueLabel : $falseLabel;

        return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        return $this->denormalizeValue($value, $fieldConfig) ? '1' : '0';
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        // Boolean fields don't really have validation beyond required
        // And "required" for boolean means "must be true"
        $errors = [];

        if (!empty($validation['required']) && !$this->denormalizeValue($value, $fieldConfig)) {
            $errors[] = 'This field must be checked.';
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'defaultValue' => false,
            'trueLabel' => 'Yes',
            'falseLabel' => 'No',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'defaultValue' => [
                'type' => 'boolean',
                'label' => 'Default Value',
                'default' => false,
            ],
            'trueLabel' => [
                'type' => 'text',
                'label' => 'True Label',
                'help' => 'Text to display when value is true',
                'default' => 'Yes',
            ],
            'falseLabel' => [
                'type' => 'text',
                'label' => 'False Label',
                'help' => 'Text to display when value is false',
                'default' => 'No',
            ],
        ];
    }

    public function supportsTranslation(): bool
    {
        // Boolean values don't need translation
        return false;
    }

    public function getCategory(): string
    {
        return 'choice';
    }

    public function getIcon(): string
    {
        return 'toggle_on';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);
        $boolValue = $this->denormalizeValue($value, $config);
        
        // Pass as integer (1 or 0) for Smarty compatibility
        $smartyValue = $boolValue ? 1 : 0;

        return $this->renderPartial('boolean.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $smartyValue,
            'context' => $context,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';

        return sprintf(
            '<div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input acf-subfield-input" data-subfield="%s" id="rep_{rowId}_%s"><label class="custom-control-label" for="rep_{rowId}_%s"></label></div>',
            $this->escapeAttr($slug),
            $slug,
            $slug
        );
    }
}
