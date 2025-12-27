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

use Symfony\Component\Form\Extension\Core\Type\ColorType;

/**
 * Color picker field type
 *
 * Stores colors as #RRGGBB hex values.
 */
final class ColorField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'color';
    }

    public function getLabel(): string
    {
        return 'Color Picker';
    }

    public function getFormType(): string
    {
        return ColorType::class;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $color = trim((string) $value);

        // Ensure # prefix
        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        // Validate hex format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            // Try to expand short hex (#RGB -> #RRGGBB)
            if (preg_match('/^#([0-9A-Fa-f])([0-9A-Fa-f])([0-9A-Fa-f])$/', $color, $matches)) {
                $color = '#' . $matches[1] . $matches[1] . $matches[2] . $matches[2] . $matches[3] . $matches[3];
            } else {
                return null;
            }
        }

        return strtoupper($color);
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $color = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $showHex = $this->getConfigValue($fieldConfig, 'showHex', true);

        $html = sprintf(
            '<span class="acf-color-swatch" style="display:inline-block;width:20px;height:20px;background-color:%s;border:1px solid #ccc;border-radius:3px;vertical-align:middle;"></span>',
            $color
        );

        if ($showHex) {
            $html .= sprintf(' <span class="acf-color-hex">%s</span>', $color);
        }

        return $html;
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        $color = trim((string) $value);
        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        // Validate hex format (6 or 3 digits)
        if (!preg_match('/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$/', $color)) {
            $errors[] = 'Please enter a valid hex color (e.g., #FF5733).';
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'defaultValue' => '#000000',
            'showHex' => true,
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'defaultValue' => [
                'type' => 'color',
                'label' => 'Default Color',
                'default' => '#000000',
            ],
            'showHex' => [
                'type' => 'boolean',
                'label' => 'Show Hex Value',
                'help' => 'Display the hex code alongside the color swatch on frontend',
                'default' => true,
            ],
        ];
    }

    public function supportsTranslation(): bool
    {
        return false;
    }

    public function getCategory(): string
    {
        return 'content';
    }

    public function getIcon(): string
    {
        return 'palette';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);
        $colorValue = $value ?: ($config['defaultValue'] ?? '#000000');

        return $this->renderPartial('color.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $colorValue,
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
        $defaultValue = $config['defaultValue'] ?? '#000000';

        return sprintf(
            '<input type="color" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="%s" style="width: 60px; padding: 2px;">',
            $this->escapeAttr($slug),
            $this->escapeAttr($defaultValue)
        );
    }
}
