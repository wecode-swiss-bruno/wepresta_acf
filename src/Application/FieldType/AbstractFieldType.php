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

/**
 * Base class for all field types
 *
 * Provides default implementations for common functionality.
 * Extend this class to create new field types.
 */
abstract class AbstractFieldType implements FieldTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = [
            'required' => $validation['required'] ?? false,
        ];

        // Add placeholder if configured
        if (!empty($fieldConfig['placeholder'])) {
            $options['attr']['placeholder'] = $fieldConfig['placeholder'];
        }

        // Add CSS class if configured
        if (!empty($fieldConfig['class'])) {
            $options['attr']['class'] = $fieldConfig['class'];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        // Default: return value as-is
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        // Default: return value as-is
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Default: escape and return as string
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Default: first 255 chars of string representation
        $stringValue = is_string($value) ? $value : (string) $value;

        return substr($stringValue, 0, 255);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfig(): array
    {
        return [];
    }

    /**
     * Parses field config from a field array.
     *
     * Config may be stored as JSON string in database, this ensures it's always an array.
     *
     * @param array $field The field array containing 'config' key
     *
     * @return array The parsed config array
     */
    protected function getFieldConfig(array $field): array
    {
        $config = $field['config'] ?? [];

        if (\is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        if (!\is_array($config)) {
            return [];
        }

        // Parse nested JSON strings (e.g., choices, options)
        foreach ($config as $key => $value) {
            if (\is_string($value)) {
                $firstChar = $value[0] ?? '';
                if ($firstChar === '[' || $firstChar === '{') {
                    $decoded = json_decode($value, true);
                    if (\is_array($decoded)) {
                        $config[$key] = $decoded;
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Renders a Smarty partial template for this field type.
     *
     * @param string $template The template filename (e.g., 'video.tpl')
     * @param array $vars Variables to assign to the template
     *
     * @return string The rendered HTML
     */
    protected function renderPartial(string $template, array $vars = []): string
    {
        $smarty = \Context::getContext()->smarty;

        // Save current vars to restore after
        $savedVars = [];
        foreach ($vars as $key => $value) {
            if ($smarty->getTemplateVars($key) !== null) {
                $savedVars[$key] = $smarty->getTemplateVars($key);
            }
            $smarty->assign($key, $value);
        }

        $templatePath = _PS_MODULE_DIR_ . 'wepresta_acf/views/templates/admin/fields/' . $template;

        if (!file_exists($templatePath)) {
            return sprintf('<!-- Template not found: %s -->', htmlspecialchars($template));
        }

        $html = $smarty->fetch($templatePath);

        // Restore previous vars
        foreach ($savedVars as $key => $value) {
            $smarty->assign($key, $value);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigSchema(): array
    {
        return [
            'placeholder' => [
                'type' => 'text',
                'label' => 'Placeholder',
                'default' => '',
            ],
            'class' => [
                'type' => 'text',
                'label' => 'CSS Class',
                'default' => '',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = [];

        // Check required
        if (!empty($validation['required']) && ($value === null || $value === '')) {
            $errors[] = 'This field is required.';
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTranslation(): bool
    {
        // Default: text-based fields support translation
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'basic';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        return 'text_fields';
    }

    /**
     * Helper: Get a config value with default fallback
     *
     * @param array<string, mixed> $config Configuration array
     * @param string $key Key to retrieve
     * @param mixed $default Default value if not set
     *
     * @return mixed The config value or default
     */
    protected function getConfigValue(array $config, string $key, mixed $default = null): mixed
    {
        return $config[$key] ?? $default;
    }

    /**
     * Helper: Check if value is empty (null, empty string, or empty array)
     */
    protected function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value) && count($value) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Helper: Validate string length
     *
     * @param string $value Value to check
     * @param array<string, mixed> $validation Validation rules
     *
     * @return array<string> Error messages
     */
    protected function validateStringLength(string $value, array $validation): array
    {
        $errors = [];

        if (isset($validation['minLength']) && strlen($value) < (int) $validation['minLength']) {
            $errors[] = sprintf('Value must be at least %d characters.', $validation['minLength']);
        }

        if (isset($validation['maxLength']) && strlen($value) > (int) $validation['maxLength']) {
            $errors[] = sprintf('Value must not exceed %d characters.', $validation['maxLength']);
        }

        return $errors;
    }

    /**
     * Helper: Validate against a regex pattern
     *
     * @param string $value Value to check
     * @param string $pattern Regex pattern
     * @param string $message Error message if pattern doesn't match
     *
     * @return array<string> Error messages
     */
    protected function validatePattern(string $value, string $pattern, string $message = 'Invalid format.'): array
    {
        if ($value !== '' && !preg_match($pattern, $value)) {
            return [$message];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);

        // Context options
        $size = $context['size'] ?? '';
        $prefix = $context['prefix'] ?? 'acf_';
        $dataSubfield = !empty($context['dataSubfield']);
        $idPrefix = $context['idPrefix'] ?? 'acf_';

        // Build attributes
        $sizeClass = $size ? "form-control-{$size}" : '';
        $escapedSlug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
        $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars($config['placeholder'] ?? '', ENT_QUOTES, 'UTF-8');

        // Name or data-subfield attribute
        $nameAttr = $dataSubfield
            ? ''
            : sprintf('name="%s%s"', $prefix, $escapedSlug);
        $dataAttr = $dataSubfield
            ? sprintf('data-subfield="%s"', $escapedSlug)
            : '';
        $inputClass = $dataSubfield ? 'acf-subfield-input' : '';

        return sprintf(
            '<input type="text" class="form-control %s %s" id="%s%s" %s %s value="%s" placeholder="%s">',
            $sizeClass,
            $inputClass,
            $idPrefix,
            $escapedSlug,
            $nameAttr,
            $dataAttr,
            $escapedValue,
            $placeholder
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);
        $placeholder = addslashes($config['placeholder'] ?? '');
        $escapedSlug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<input type="text" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}" placeholder="%s">',
            $escapedSlug,
            $placeholder
        );
    }

    /**
     * Helper: Escape HTML attribute value
     */
    protected function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Helper: Build common input attributes
     *
     * @param array<string, mixed> $field Field data
     * @param array<string, mixed> $context Rendering context
     *
     * @return array{slug: string, sizeClass: string, nameAttr: string, dataAttr: string, inputClass: string, idPrefix: string}
     */
    protected function buildInputAttrs(array $field, array $context = []): array
    {
        $slug = $field['slug'] ?? '';
        $size = $context['size'] ?? '';
        $prefix = $context['prefix'] ?? 'acf_';
        $dataSubfield = !empty($context['dataSubfield']);
        $idPrefix = $context['idPrefix'] ?? 'acf_';

        $escapedSlug = $this->escapeAttr($slug);
        $sizeClass = $size ? "form-control-{$size}" : '';
        $nameAttr = $dataSubfield ? '' : sprintf('name="%s%s"', $prefix, $escapedSlug);
        $dataAttr = $dataSubfield ? sprintf('data-subfield="%s"', $escapedSlug) : '';
        $inputClass = $dataSubfield ? 'acf-subfield-input' : '';

        return [
            'slug' => $escapedSlug,
            'sizeClass' => $sizeClass,
            'nameAttr' => $nameAttr,
            'dataAttr' => $dataAttr,
            'inputClass' => $inputClass,
            'idPrefix' => $idPrefix,
        ];
    }
}
