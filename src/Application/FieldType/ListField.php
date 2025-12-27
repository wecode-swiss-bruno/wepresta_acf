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

use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * List field type - Simple repeater for text items with optional metadata
 *
 * Stores array of items as JSON:
 * [
 *   {
 *     "id": "uuid-1",
 *     "text": "Feature 1",
 *     "icon": "check",
 *     "link": "",
 *     "position": 0
 *   },
 *   ...
 * ]
 */
final class ListField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'list';
    }

    public function getLabel(): string
    {
        return 'List';
    }

    public function getFormType(): string
    {
        return HiddenType::class;
    }

    public function getCategory(): string
    {
        return 'layout';
    }

    public function getIcon(): string
    {
        return 'format_list_bulleted';
    }

    public function supportsTranslation(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === '[]') {
            return null;
        }

        // If already JSON string, decode and validate
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return null;
            }
            $value = $decoded;
        }

        if (!is_array($value)) {
            return null;
        }

        // Normalize each item
        $normalized = [];
        $position = 0;

        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Skip empty text items
            $text = trim((string) ($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $normalized[] = [
                'id' => $item['id'] ?? Uuid::uuid4()->toString(),
                'text' => $text,
                'icon' => $item['icon'] ?? '',
                'link' => $item['link'] ?? '',
                'position' => $position++,
            ];
        }

        if (count($normalized) === 0) {
            return null;
        }

        return json_encode($normalized);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === '[]') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                // Sort by position
                usort($decoded, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

                return $decoded;
            }

            return [];
        }

        if (is_array($value)) {
            usort($value, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

            return $value;
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        $items = $this->denormalizeValue($value, $fieldConfig);

        if (count($items) === 0) {
            return '';
        }

        $showIcon = $this->getConfigValue($fieldConfig, 'showIcon', false);
        $showLink = $this->getConfigValue($fieldConfig, 'showLink', false);

        $html = '<ul class="acf-list">';

        foreach ($items as $item) {
            $html .= '<li class="acf-list-item">';

            if ($showIcon && !empty($item['icon'])) {
                $html .= '<span class="acf-list-icon material-icons">' . htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') . '</span>';
            }

            $text = htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8');

            if ($showLink && !empty($item['link'])) {
                $link = htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8');
                $html .= '<a href="' . $link . '" class="acf-list-link">' . $text . '</a>';
            } else {
                $html .= '<span class="acf-list-text">' . $text . '</span>';
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $items = $this->denormalizeValue($value, $fieldConfig);

        if (count($items) === 0) {
            return null;
        }

        // Index: concatenate all text values
        $texts = array_map(fn($item) => $item['text'] ?? '', $items);

        return substr(implode(' | ', $texts), 0, 255);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfig(): array
    {
        return [
            'min' => 0,
            'max' => 0, // 0 = unlimited
            'showIcon' => false,
            'showLink' => false,
            'iconSet' => 'material', // material, fontawesome, custom
            'placeholder' => '',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'min' => [
                'type' => 'number',
                'label' => 'Minimum Items',
                'default' => 0,
            ],
            'max' => [
                'type' => 'number',
                'label' => 'Maximum Items',
                'default' => 0,
                'help' => '0 = unlimited',
            ],
            'showIcon' => [
                'type' => 'boolean',
                'label' => 'Show Icon Field',
                'default' => false,
            ],
            'showLink' => [
                'type' => 'boolean',
                'label' => 'Show Link Field',
                'default' => false,
            ],
            'iconSet' => [
                'type' => 'select',
                'label' => 'Icon Set',
                'default' => 'material',
                'options' => [
                    'material' => 'Material Icons',
                    'fontawesome' => 'Font Awesome',
                    'custom' => 'Custom (text input)',
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = [];

        $items = $this->denormalizeValue($value, $fieldConfig);
        $count = count($items);

        // Check required
        if (!empty($validation['required']) && $count === 0) {
            $errors[] = 'This field is required.';

            return $errors;
        }

        // Check min
        $min = (int) $this->getConfigValue($fieldConfig, 'min', 0);
        if ($min > 0 && $count < $min) {
            $errors[] = sprintf('At least %d item(s) required.', $min);
        }

        // Check max
        $max = (int) $this->getConfigValue($fieldConfig, 'max', 0);
        if ($max > 0 && $count > $max) {
            $errors[] = sprintf('Maximum %d item(s) allowed.', $max);
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        return [
            'required' => false, // Handled by custom validation
            'attr' => [
                'class' => 'acf-list-value',
                'data-min' => $this->getConfigValue($fieldConfig, 'min', 0),
                'data-max' => $this->getConfigValue($fieldConfig, 'max', 0),
                'data-show-icon' => $this->getConfigValue($fieldConfig, 'showIcon', false) ? '1' : '0',
                'data-show-link' => $this->getConfigValue($fieldConfig, 'showLink', false) ? '1' : '0',
                'data-icon-set' => $this->getConfigValue($fieldConfig, 'iconSet', 'material'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);
        $items = $this->denormalizeValue($value, $config);

        return $this->renderPartial('list.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $items,
            'context' => $context,
        ]);
    }

    /**
     * Render a single list item
     */
    private function renderListItem(array $item, string $slug, bool $showIcon, bool $showLink, bool $isCompact, string $placeholder): string
    {
        $itemId = $this->escapeAttr($item['id'] ?? '');
        $text = $this->escapeAttr($item['text'] ?? '');
        $icon = $this->escapeAttr($item['icon'] ?? '');
        $link = $this->escapeAttr($item['link'] ?? '');
        $sizeClass = $isCompact ? 'form-control-sm' : '';

        $html = sprintf('<div class="acf-list-item" data-id="%s">', $itemId);
        $html .= '<span class="acf-list-drag material-icons">drag_indicator</span>';

        if ($showIcon) {
            $html .= sprintf(
                '<input type="text" class="form-control %s acf-list-icon-input" value="%s" placeholder="Icon" style="width: 80px;">',
                $sizeClass,
                $icon
            );
        }

        $html .= sprintf(
            '<input type="text" class="form-control %s acf-list-text-input flex-grow-1" value="%s" placeholder="%s">',
            $sizeClass,
            $text,
            $placeholder ?: 'Enter text...'
        );

        if ($showLink) {
            $html .= sprintf(
                '<input type="url" class="form-control %s acf-list-link-input" value="%s" placeholder="https://..." style="width: 150px;">',
                $sizeClass,
                $link
            );
        }

        $html .= '<button type="button" class="btn btn-link text-danger acf-list-remove p-1" title="Remove"><span class="material-icons" style="font-size: 18px;">close</span></button>';
        $html .= '</div>';

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);
        $showIcon = (bool) ($config['showIcon'] ?? false);
        $showLink = (bool) ($config['showLink'] ?? false);
        $placeholder = addslashes($config['placeholder'] ?? 'Enter text...');

        // Compact template for repeater table mode
        $html = sprintf(
            '<div class="acf-list-field acf-list-compact" data-slug="%s" data-show-icon="%s" data-show-link="%s">',
            $this->escapeAttr($slug),
            $showIcon ? '1' : '0',
            $showLink ? '1' : '0'
        );

        $html .= sprintf(
            '<input type="hidden" class="acf-subfield-input acf-list-value" data-subfield="%s" value="{value}">',
            $this->escapeAttr($slug)
        );

        $html .= '<div class="acf-list-items"></div>';
        $html .= '<button type="button" class="btn btn-outline-secondary btn-sm acf-list-add"><span class="material-icons" style="font-size: 14px;">add</span></button>';
        $html .= '</div>';

        return $html;
    }
}
