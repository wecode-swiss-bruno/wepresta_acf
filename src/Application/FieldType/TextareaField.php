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

use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Textarea field type.
 *
 * Multi-line text input field.
 */
final class TextareaField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'textarea';
    }

    public function getLabel(): string
    {
        return 'Textarea';
    }

    public function getFormType(): string
    {
        return TextareaType::class;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Set rows attribute
        $rows = $this->getConfigValue($fieldConfig, 'rows', 4);
        $options['attr']['rows'] = (int) $rows;

        // Add maxlength if configured
        if (!empty($validation['maxLength'])) {
            $options['attr']['maxlength'] = (int) $validation['maxLength'];
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

        // Trim whitespace but preserve internal line breaks
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

        // Escape HTML and convert newlines to <br>
        $output = htmlspecialchars($stringValue, ENT_QUOTES, 'UTF-8');
        $output = nl2br($output);

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

        if ($this->isEmpty($value)) {
            return $errors;
        }

        $stringValue = (string) $value;

        // Length validation
        return array_merge($errors, $this->validateStringLength($stringValue, $validation));
    }

    public function getDefaultConfig(): array
    {
        return [
            'placeholder' => '',
            'rows' => 4,
        ];
    }

    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'rows' => [
                'type' => 'number',
                'label' => 'Rows',
                'help' => 'Number of visible text lines',
                'default' => 4,
                'min' => 2,
                'max' => 20,
            ],
        ]);
    }

    public function getIcon(): string
    {
        return 'notes';
    }



    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);
        $placeholder = addslashes($config['placeholder'] ?? '');

        return \sprintf(
            '<textarea class="form-control form-control-sm acf-subfield-input" data-subfield="%s" rows="2" placeholder="%s">{value}</textarea>',
            $this->escapeAttr($slug),
            $placeholder
        );
    }
}
