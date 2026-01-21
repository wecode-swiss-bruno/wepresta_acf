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

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Text field type.
 *
 * Basic single-line text input field.
 */
final class TextField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'text';
    }

    public function getLabel(): string
    {
        return 'Text';
    }

    public function getFormType(): string
    {
        return TextType::class;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Add maxlength attribute if configured
        if (!empty($validation['maxLength'])) {
            $options['attr']['maxlength'] = (int) $validation['maxLength'];
        }

        // Add prefix/suffix if configured
        if (!empty($fieldConfig['prefix'])) {
            $options['attr']['data-prefix'] = $fieldConfig['prefix'];
        }

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

        // Handle arrays/objects (empty objects from JS become empty arrays)
        if (\is_array($value) || \is_object($value)) {
            return null;
        }

        // Trim whitespace
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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

        $output = htmlspecialchars($stringValue, ENT_QUOTES, 'UTF-8');

        // Add prefix/suffix for display
        $prefix = $fieldConfig['prefix'] ?? '';
        $suffix = $fieldConfig['suffix'] ?? '';

        if ($prefix !== '') {
            $output = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . ' ' . $output;
        }

        if ($suffix !== '') {
            $output .= ' ' . htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8');
        }

        return $output;
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        // Skip further validation if empty (and not required)
        if ($this->isEmpty($value)) {
            return $errors;
        }

        $stringValue = (string) $value;

        // Length validation
        $errors = array_merge($errors, $this->validateStringLength($stringValue, $validation));

        // Pattern validation
        if (!empty($validation['pattern'])) {
            $errors = array_merge(
                $errors,
                $this->validatePattern($stringValue, $validation['pattern'], $validation['patternMessage'] ?? 'Invalid format.')
            );
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'prepend' => [
                'type' => 'text',
                'label' => 'Prepend',
                'help' => 'Text to display before the value',
                'default' => '',
            ],
            'append' => [
                'type' => 'text',
                'label' => 'Append',
                'help' => 'Text to display after the value',
                'default' => '',
            ],
        ]);
    }

    public function getIcon(): string
    {
        return 'text_fields';
    }



    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);
        $placeholder = addslashes($config['placeholder'] ?? '');

        return \sprintf(
            '<input type="text" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}" placeholder="%s">',
            $this->escapeAttr($slug),
            $placeholder
        );
    }
}
