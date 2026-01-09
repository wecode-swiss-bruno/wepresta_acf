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

use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * URL field type
 *
 * URL input with validation and link rendering.
 */
final class UrlField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'url';
    }

    public function getLabel(): string
    {
        return 'URL';
    }

    public function getFormType(): string
    {
        return UrlType::class;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Handle arrays/objects (empty objects from JS become empty arrays)
        if (is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // Add protocol if missing
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }

        return $value;
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

        $url = htmlspecialchars($stringValue, ENT_QUOTES, 'UTF-8');
        $target = $this->getConfigValue($fieldConfig, 'target', '_blank');
        $linkText = $this->getConfigValue($fieldConfig, 'linkText', '');

        // Use custom link text or display URL
        $displayText = $linkText !== '' ? htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8') : $url;

        // Add prefix/suffix for display
        $prefix = $fieldConfig['prefix'] ?? '';
        $suffix = $fieldConfig['suffix'] ?? '';

        if ($prefix !== '') {
            $displayText = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . ' ' . $displayText;
        }

        if ($suffix !== '') {
            $displayText .= ' ' . htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8');
        }

        // Render as clickable link
        return sprintf(
            '<a href="%s" target="%s" rel="noopener noreferrer">%s</a>',
            $url,
            htmlspecialchars($target, ENT_QUOTES, 'UTF-8'),
            $displayText
        );
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        $stringValue = (string) $value;

        // Add protocol for validation if missing
        if (!preg_match('#^https?://#i', $stringValue)) {
            $stringValue = 'https://' . $stringValue;
        }

        // URL format validation
        if (!filter_var($stringValue, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid URL.';
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'placeholder' => 'https://example.com',
            'target' => '_blank',
            'linkText' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'target' => [
                'type' => 'select',
                'label' => 'Link Target',
                'options' => [
                    ['value' => '_blank', 'label' => 'New Tab (_blank)'],
                    ['value' => '_self', 'label' => 'Same Tab (_self)'],
                ],
                'default' => '_blank',
            ],
            'linkText' => [
                'type' => 'text',
                'label' => 'Link Text',
                'help' => 'Custom text to display instead of URL (optional)',
                'default' => '',
            ],
        ]);
    }

    public function supportsTranslation(): bool
    {
        // URLs are typically not translated
        return false;
    }

    public function getIcon(): string
    {
        return 'link';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);

        return $this->renderPartial('url.tpl', [
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
        $placeholder = addslashes($config['placeholder'] ?? 'https://example.com');

        return sprintf(
            '<input type="url" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}" placeholder="%s">',
            $this->escapeAttr($slug),
            $placeholder
        );
    }
}
