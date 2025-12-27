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

use Symfony\Component\Form\Extension\Core\Type\NumberType;

/**
 * Number field type
 *
 * Numeric input field with optional min/max, step, and formatting.
 */
final class NumberField extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'number';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Number';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType(): string
    {
        return NumberType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Scale (decimal places)
        $decimals = $this->getConfigValue($fieldConfig, 'decimals', 2);
        $options['scale'] = (int) $decimals;

        // HTML5 number input attributes
        $options['html5'] = true;

        if (isset($validation['min'])) {
            $options['attr']['min'] = $validation['min'];
        }

        if (isset($validation['max'])) {
            $options['attr']['max'] = $validation['max'];
        }

        if (!empty($fieldConfig['step'])) {
            $options['attr']['step'] = $fieldConfig['step'];
        } elseif ($decimals > 0) {
            // Calculate step based on decimals
            $options['attr']['step'] = 1 / pow(10, $decimals);
        }

        // Unit as suffix
        if (!empty($fieldConfig['unit'])) {
            $options['attr']['data-unit'] = $fieldConfig['unit'];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Convert to float
        $numericValue = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($numericValue === false) {
            return null;
        }

        // Round to configured decimals
        $decimals = $this->getConfigValue($fieldConfig, 'decimals', 2);

        return round($numericValue, (int) $decimals);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $numericValue = (float) $value;
        $decimals = $this->getConfigValue($fieldConfig, 'decimals', 2);

        // Format number
        $locale = $renderOptions['locale'] ?? 'en_US';
        $formatted = number_format($numericValue, (int) $decimals, '.', ' ');

        // Add unit if configured
        $unit = $this->getConfigValue($fieldConfig, 'unit', '');
        if ($unit !== '') {
            $formatted .= ' ' . htmlspecialchars($unit, ENT_QUOTES, 'UTF-8');
        }

        return $formatted;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Store as padded string for proper sorting
        $numericValue = (float) $value;

        // Format: sign + 10 digits integer + . + 6 decimals
        return sprintf('%+017.6f', $numericValue);
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

        // Numeric validation
        $numericValue = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($numericValue === false) {
            $errors[] = 'Value must be a valid number.';

            return $errors;
        }

        // Min validation
        if (isset($validation['min']) && $numericValue < (float) $validation['min']) {
            $errors[] = sprintf('Value must be at least %s.', $validation['min']);
        }

        // Max validation
        if (isset($validation['max']) && $numericValue > (float) $validation['max']) {
            $errors[] = sprintf('Value must not exceed %s.', $validation['max']);
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfig(): array
    {
        return [
            'decimals' => 2,
            'step' => null,
            'prepend' => '',
            'unit' => '',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'decimals' => [
                'type' => 'number',
                'label' => 'Decimal Places',
                'help' => 'Number of decimal places to display',
                'default' => 2,
                'min' => 0,
                'max' => 10,
            ],
            'step' => [
                'type' => 'number',
                'label' => 'Step',
                'help' => 'Increment/decrement step (leave empty for auto)',
                'default' => null,
            ],
            'prepend' => [
                'type' => 'text',
                'label' => 'Prepend',
                'help' => 'Text to display before the input (e.g., currency symbol)',
                'default' => '',
            ],
            'unit' => [
                'type' => 'text',
                'label' => 'Unit / Append',
                'help' => 'Unit to display after the value (e.g., kg, €, %)',
                'default' => '',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTranslation(): bool
    {
        // Numbers don't need translation
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        return 'numbers';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);

        return $this->renderPartial('number.tpl', [
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

        $min = isset($config['min']) ? sprintf('min="%s"', $this->escapeAttr((string) $config['min'])) : '';
        $max = isset($config['max']) ? sprintf('max="%s"', $this->escapeAttr((string) $config['max'])) : '';
        $step = isset($config['step']) ? sprintf('step="%s"', $this->escapeAttr((string) $config['step'])) : 'step="any"';

        return sprintf(
            '<input type="number" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}" %s %s %s>',
            $this->escapeAttr($slug),
            $min,
            $max,
            $step
        );
    }
}
