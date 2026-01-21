<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
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

use Symfony\Component\Form\Extension\Core\Type\NumberType;

/**
 * Number field type.
 *
 * Numeric input field with optional min/max, step, and formatting.
 */
final class NumberField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'number';
    }

    public function getLabel(): string
    {
        return 'Number';
    }

    public function getFormType(): string
    {
        return NumberType::class;
    }

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

        // Suffix
        if (!empty($fieldConfig['suffix'])) {
            $options['attr']['data-suffix'] = $fieldConfig['suffix'];
        }

        return $options;
    }

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

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Extract value for current language if translatable
        $actualValue = $this->extractTranslatableValue($value);

        // Convert to string and handle empty values
        $stringValue = (string) $actualValue;

        if ($stringValue === '') {
            return '';
        }

        $numericValue = (float) $stringValue;
        $decimals = $this->getConfigValue($fieldConfig, 'decimals', 2);

        // Format number
        $locale = $renderOptions['locale'] ?? 'en_US';
        $formatted = number_format($numericValue, (int) $decimals, '.', ' ');

        // Add prefix if configured
        $prefix = $this->getConfigValue($fieldConfig, 'prefix', '');

        if ($prefix !== '') {
            $formatted = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . ' ' . $formatted;
        }

        // Add suffix if configured
        $suffix = $this->getConfigValue($fieldConfig, 'suffix', '');

        if ($suffix !== '') {
            $formatted .= ' ' . htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8');
        }

        return $formatted;
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Store as padded string for proper sorting
        $numericValue = (float) $value;

        // Format: sign + 10 digits integer + . + 6 decimals
        return \sprintf('%+017.6f', $numericValue);
    }

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

        // Min validation - check both validation and config for backwards compatibility
        $min = $fieldConfig['min'] ?? $validation['min'] ?? null;

        if ($min !== null && $numericValue < (float) $min) {
            $errors[] = \sprintf('Value must be at least %s.', $min);
        }

        // Max validation - check both validation and config for backwards compatibility
        $max = $fieldConfig['max'] ?? $validation['max'] ?? null;

        if ($max !== null && $numericValue > (float) $max) {
            $errors[] = \sprintf('Value must not exceed %s.', $max);
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'decimals' => 2,
            'step' => null,
            'prepend' => '',
            'unit' => '',
        ];
    }

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

    public function supportsTranslation(): bool
    {
        // Numbers don't need translation
        return false;
    }

    public function getIcon(): string
    {
        return 'numbers';
    }



    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);

        $min = isset($config['min']) ? \sprintf('min="%s"', $this->escapeAttr((string) $config['min'])) : '';
        $max = isset($config['max']) ? \sprintf('max="%s"', $this->escapeAttr((string) $config['max'])) : '';
        $step = isset($config['step']) ? \sprintf('step="%s"', $this->escapeAttr((string) $config['step'])) : 'step="any"';

        return \sprintf(
            '<input type="number" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}" %s %s %s>',
            $this->escapeAttr($slug),
            $min,
            $max,
            $step
        );
    }
}
