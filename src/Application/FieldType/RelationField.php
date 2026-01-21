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

use Category;
use Context;
use Image;
use Product;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Tools;
use Validate;

/**
 * Relation field type.
 *
 * Allows selecting related products or categories.
 * Supports single and multiple selection with search and filters.
 */
final class RelationField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'relation';
    }

    public function getLabel(): string
    {
        return 'Relation';
    }

    public function getFormType(): string
    {
        // We use hidden type since the UI is custom JavaScript
        return HiddenType::class;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Custom data attributes for JavaScript initialization
        $options['attr']['data-relation-type'] = $this->getConfigValue($fieldConfig, 'entityType', 'product');
        $options['attr']['data-relation-multiple'] = $this->getConfigValue($fieldConfig, 'multiple', false) ? '1' : '0';

        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        // Handle empty values - return null for empty arrays, strings, or null values
        if ($value === null || $value === '' || (\is_array($value) && empty($value))) {
            return null;
        }

        $multiple = $this->getConfigValue($fieldConfig, 'multiple', false);

        // Already JSON string (from form submission)
        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Handle empty arrays from JSON
                if (\is_array($decoded) && empty($decoded)) {
                    return null;
                }

                if ($multiple) {
                    // Ensure array of integers
                    if (\is_array($decoded)) {
                        // If it's an array of objects with 'id' keys, extract IDs
                        $ids = array_map(function ($item) {
                            return \is_array($item) && isset($item['id']) ? (int) $item['id'] : (int) $item;
                        }, $decoded);
                    } else {
                        $ids = [(int) $decoded];
                    }

                    $ids = array_filter($ids);

                    // Return null if no valid IDs after filtering
                    return !empty($ids) ? json_encode(array_values($ids), JSON_THROW_ON_ERROR) : null;
                }

                // Single value
                if (\is_array($decoded)) {
                    // If it's an object with an 'id' key (from JS), extract the ID
                    if (isset($decoded['id'])) {
                        return (string) $decoded['id'];
                    }

                    // If it's a numeric array, take the first element
                    return (string) ($decoded[0] ?? 0);
                }

                return (string) $decoded;
            }

            // Plain numeric string
            if (is_numeric($value)) {
                return $multiple ? json_encode([(int) $value], JSON_THROW_ON_ERROR) : (string) $value;
            }
        }

        // Array of IDs or objects
        if (\is_array($value)) {
            // Extract IDs from array of objects or plain IDs
            $ids = array_map(function ($item) {
                if (\is_array($item) && isset($item['id'])) {
                    return (int) $item['id'];
                }

                return (int) $item;
            }, $value);

            $ids = array_filter($ids);

            // Return null if no valid IDs
            if (empty($ids)) {
                return null;
            }

            if ($multiple) {
                return json_encode(array_values($ids), JSON_THROW_ON_ERROR);
            }

            // Single value from array
            return (string) reset($ids);
        }

        // Single numeric value
        if (is_numeric($value)) {
            return $multiple ? json_encode([(int) $value], JSON_THROW_ON_ERROR) : (string) $value;
        }

        return null;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $multiple = $this->getConfigValue($fieldConfig, 'multiple', false);
        $entityType = $this->getConfigValue($fieldConfig, 'entityType', 'product');

        // Decode JSON if string
        $decoded = $value;

        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Plain numeric string
                $decoded = is_numeric($value) ? [(int) $value] : null;
            }
        }

        if ($decoded === null) {
            return null;
        }

        // Handle array of objects with 'id' key (from repeater/frontend)
        // Example: [{"id":14,"name":"..."},{"id":11,"name":"..."}]
        if (\is_array($decoded) && !empty($decoded)) {
            $firstItem = reset($decoded);

            if (\is_array($firstItem) && isset($firstItem['id'])) {
                // Already denormalized - extract IDs and reload with fresh data
                $ids = array_map(fn($item) => (int) ($item['id'] ?? 0), $decoded);
                $ids = array_filter($ids);
            } elseif (is_numeric($firstItem)) {
                // Array of IDs
                $ids = array_map('intval', $decoded);
            } else {
                $ids = [];
            }
        } elseif (is_numeric($decoded)) {
            $ids = [(int) $decoded];
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return $multiple ? [] : null;
        }

        // Load entity data for display
        $entities = $this->loadEntities($ids, $entityType, $fieldConfig);

        if ($multiple) {
            return $entities;
        }

        // Single selection - return first entity or null
        return \count($entities) > 0 ? $entities[0] : null;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '' || (\is_array($value) && empty($value))) {
            return '';
        }

        $displayFormat = $this->getConfigValue($fieldConfig, 'displayFormat', 'name');
        $multiple = $this->getConfigValue($fieldConfig, 'multiple', false);

        // Ensure we have array of entities
        $entities = $multiple ? $value : [$value];

        if (!\is_array($entities)) {
            return '';
        }

        $output = [];

        foreach ($entities as $entity) {
            if (!\is_array($entity) || !isset($entity['name'])) {
                continue;
            }

            $output[] = $this->renderEntityCard($entity, $displayFormat);
        }

        if (empty($output)) {
            return '';
        }

        // Wrap in container with styles
        $html = '<div class="acf-relation-items">' . implode('', $output) . '</div>';
        $html .= $this->getFrontendStyles();

        return $html;
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        // Decode if JSON
        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $ids = \is_array($decoded) ? $decoded : [$decoded];

                return implode(',', \array_slice($ids, 0, 10)); // First 10 IDs for index
            }

            return substr($value, 0, 255);
        }

        if (\is_array($value)) {
            $ids = array_column($value, 'id');

            return implode(',', \array_slice($ids, 0, 10));
        }

        return null;
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        // Skip further validation if empty (and not required)
        if ($this->isEmpty($value)) {
            return $errors;
        }

        $multiple = $this->getConfigValue($fieldConfig, 'multiple', false);

        // Get IDs from value
        $ids = [];

        if (\is_string($value)) {
            $decoded = json_decode($value, true);
            $ids = json_last_error() === JSON_ERROR_NONE ? (\is_array($decoded) ? $decoded : [$decoded]) : [];
        } elseif (\is_array($value)) {
            $ids = array_column($value, 'id') ?: $value;
        }

        // Validate min/max constraints
        $min = $this->getConfigValue($fieldConfig, 'min', 0);
        $max = $this->getConfigValue($fieldConfig, 'max', null);

        if ($multiple) {
            if ($min > 0 && \count($ids) < $min) {
                $errors[] = \sprintf('At least %d item(s) required.', $min);
            }

            if ($max !== null && \count($ids) > $max) {
                $errors[] = \sprintf('Maximum %d item(s) allowed.', $max);
            }
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'entityType' => 'product',
            'multiple' => false,
            'displayFormat' => 'name',
            'min' => 0,
            'max' => null,
            'placeholder' => 'Search...',
            'filters' => [
                'active' => true,
                'in_stock' => false,
                'exclude_current' => true,
                'categories' => [],
            ],
        ];
    }

    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'entityType' => [
                'type' => 'select',
                'label' => 'Entity Type',
                'help' => 'Type of entity to relate',
                'choices' => [
                    ['label' => 'Product', 'value' => 'product'],
                    ['label' => 'Category', 'value' => 'category'],
                ],
                'default' => 'product',
            ],
            'multiple' => [
                'type' => 'checkbox',
                'label' => 'Allow Multiple',
                'help' => 'Allow selecting multiple items',
                'default' => false,
            ],
            'displayFormat' => [
                'type' => 'select',
                'label' => 'Display Format',
                'help' => 'How to display selected items',
                'choices' => [
                    ['label' => 'Name only', 'value' => 'name'],
                    ['label' => 'Name + Reference', 'value' => 'name_reference'],
                    ['label' => 'Thumbnail + Name', 'value' => 'thumbnail_name'],
                ],
                'default' => 'name',
            ],
            'min' => [
                'type' => 'number',
                'label' => 'Minimum items',
                'help' => 'Minimum number of items required (0 = no minimum)',
                'default' => 0,
            ],
            'max' => [
                'type' => 'number',
                'label' => 'Maximum items',
                'help' => 'Maximum number of items allowed (empty = unlimited)',
                'default' => null,
            ],
            'filters' => [
                'type' => 'object',
                'label' => 'Filters',
                'help' => 'Filter options for the relation picker',
                'fields' => [
                    'active' => ['type' => 'checkbox', 'label' => 'Active only', 'default' => true],
                    'in_stock' => ['type' => 'checkbox', 'label' => 'In stock only', 'default' => false],
                    'exclude_current' => ['type' => 'checkbox', 'label' => 'Exclude current product', 'default' => true],
                    'categories' => ['type' => 'text', 'label' => 'Category IDs (comma-separated)', 'default' => ''],
                ],
            ],
        ]);
    }

    public function supportsTranslation(): bool
    {
        // Relations are not translatable - they reference entity IDs
        return false;
    }

    public function getCategory(): string
    {
        return 'relational';
    }

    public function getIcon(): string
    {
        return 'link';
    }



    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';
        $config = $this->getFieldConfig($field);
        $entityType = $this->getConfigValue($config, 'entityType', 'product');
        $multiple = (bool) $this->getConfigValue($config, 'multiple', false);
        $placeholder = $entityType === 'category' ? 'Search categories...' : 'Search products...';

        // Compact template for repeater table mode - matching full template structure
        $html = \sprintf(
            '<div class="acf-relation-field acf-relation-compact" data-slug="%s" data-entity-type="%s" data-multiple="%s">',
            $this->escapeAttr($slug),
            $this->escapeAttr($entityType),
            $multiple ? '1' : '0'
        );

        $html .= \sprintf(
            '<input type="hidden" class="acf-subfield-input acf-relation-value" data-subfield="%s" value="{value}">',
            $this->escapeAttr($slug)
        );

        $html .= '<div class="acf-relation-selected"></div>';

        // Use same structure as relation.tpl for consistent behavior
        $html .= '<div class="acf-relation-search position-relative">';
        $html .= '<div class="input-group">';
        $html .= '<span class="input-group-text"><span class="material-icons" style="font-size:18px;">search</span></span>';
        $html .= \sprintf(
            '<input type="text" class="form-control acf-relation-search-input" placeholder="%s" autocomplete="off">',
            $this->escapeAttr($placeholder)
        );
        $html .= '</div>';
        $html .= '<div class="acf-relation-dropdown list-group position-absolute w-100 shadow d-none" style="z-index:1050;max-height:200px;overflow-y:auto;"></div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Load entities by IDs with their display data.
     *
     * @param array<int> $ids Entity IDs
     * @param string $entityType 'product' or 'category'
     * @param array<string, mixed> $fieldConfig Field configuration
     *
     * @return array<array<string, mixed>> Entity data
     */
    private function loadEntities(array $ids, string $entityType, array $fieldConfig): array
    {
        if (empty($ids)) {
            return [];
        }

        $displayFormat = $this->getConfigValue($fieldConfig, 'displayFormat', 'name');
        $langId = (int) Context::getContext()->language->id;
        $entities = [];

        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id <= 0) {
                continue;
            }

            if ($entityType === 'category') {
                $category = new Category($id, $langId);

                if (Validate::isLoadedObject($category)) {
                    $entities[] = $this->formatCategoryData($category, $displayFormat);
                }
            } else {
                // Default: product
                $product = new Product($id, false, $langId);

                if (Validate::isLoadedObject($product)) {
                    $entities[] = $this->formatProductData($product, $displayFormat);
                }
            }
        }

        return $entities;
    }

    /**
     * Format product data for display.
     *
     * @return array<string, mixed>
     */
    private function formatProductData(Product $product, string $displayFormat): array
    {
        $data = [
            'id' => (int) $product->id,
            'type' => 'product',
            'name' => $product->name,
            'reference' => $product->reference,
            'link' => Context::getContext()->link->getProductLink($product),
        ];

        // Always load image for frontend display
        $cover = Image::getCover($product->id);

        if ($cover) {
            $imageLink = Context::getContext()->link->getImageLink(
                $product->link_rewrite,
                (string) $cover['id_image'],
                'small_default'
            );
            $data['image'] = $imageLink;
        }

        // Add price if full format
        if ($displayFormat === 'full') {
            $data['price'] = $product->getPrice(true, null, 2);
            $data['price_formatted'] = Tools::displayPrice($data['price']);
        }

        return $data;
    }

    /**
     * Format category data for display.
     *
     * @return array<string, mixed>
     */
    private function formatCategoryData(Category $category, string $displayFormat): array
    {
        $data = [
            'id' => (int) $category->id,
            'type' => 'category',
            'name' => $category->name,
            'link' => Context::getContext()->link->getCategoryLink($category),
        ];

        // Add image if needed
        if ($displayFormat === 'thumbnail_name' || $displayFormat === 'full') {
            $imageLink = Context::getContext()->link->getCatImageLink(
                $category->name,
                (int) $category->id,
                'category_default'
            );
            $data['image'] = $imageLink;
        }

        return $data;
    }

    /**
     * Render a single entity card for frontend display.
     *
     * @param array<string, mixed> $entity
     */
    private function renderEntityCard(array $entity, string $displayFormat): string
    {
        $name = htmlspecialchars($entity['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars($entity['reference'] ?? '', ENT_QUOTES, 'UTF-8');
        $link = htmlspecialchars($entity['link'] ?? '#', ENT_QUOTES, 'UTF-8');
        $image = $entity['image'] ?? '';

        $html = '<a href="' . $link . '" class="acf-relation-card" target="_blank">';

        // Image thumbnail
        if ($image) {
            $html .= '<img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="' . $name . '" class="acf-relation-card__image">';
        }

        // Content
        $html .= '<span class="acf-relation-card__content">';
        $html .= '<span class="acf-relation-card__name">' . $name . '</span>';

        if ($displayFormat === 'name_reference' && $reference) {
            $html .= '<span class="acf-relation-card__reference">(' . $reference . ')</span>';
        }

        $html .= '</span>';
        $html .= '</a>';

        return $html;
    }

    /**
     * Get CSS styles for frontend display.
     */
    private function getFrontendStyles(): string
    {
        return <<<'CSS'
            <style>
            .acf-relation-items {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .acf-relation-card {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.25rem 0.5rem;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                text-decoration: none;
                color: inherit;
                transition: all 0.2s ease;
            }
            .acf-relation-card:hover {
                background: #e9ecef;
                border-color: #adb5bd;
                text-decoration: none;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .acf-relation-card__image {
                width: 40px;
                height: 40px;
                object-fit: cover;
                border-radius: 3px;
            }
            .acf-relation-card__content {
                display: flex;
                flex-direction: column;
                line-height: 1.2;
            }
            .acf-relation-card__name {
                font-weight: 500;
                color: #212529;
                font-size: 0.9rem;
            }
            .acf-relation-card__reference {
                font-size: 0.8em;
                color: #6c757d;
            }
            </style>
            CSS;
    }

    /**
     * Get raw IDs from value (without loading entities).
     *
     * @param array<string, mixed> $config
     *
     * @return array<int>
     */
    private function getRawIds(mixed $value, array $config): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $multiple = $this->getConfigValue($config, 'multiple', false);

        // If already denormalized (array of entities)
        if (\is_array($value) && isset($value[0]['id'])) {
            return array_map(fn($e) => (int) $e['id'], $value);
        }

        // If single entity
        if (\is_array($value) && isset($value['id'])) {
            return [(int) $value['id']];
        }

        // If JSON string
        if (\is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                if (\is_array($decoded)) {
                    return array_map('intval', array_filter($decoded));
                }

                // Single value decoded from JSON
                return [(int) $decoded];
            }

            // Plain numeric string
            if (is_numeric($value)) {
                return [(int) $value];
            }
        }

        // If array of IDs (or array of numbers)
        if (\is_array($value)) {
            // Check if it's an array of numbers or array of arrays
            if (isset($value[0]) && is_numeric($value[0])) {
                return array_map('intval', array_filter($value));
            }

            // Empty array
            if (empty($value)) {
                return [];
            }
        }

        // If it's a single integer/numeric value (from database)
        if (is_numeric($value)) {
            return [(int) $value];
        }

        return [];
    }

    /**
     * Render a selected entity badge.
     */
    private function renderSelectedEntity(array $entity, string $displayFormat, bool $isCompact): string
    {
        $id = (int) ($entity['id'] ?? 0);
        $name = $this->escapeAttr($entity['name'] ?? '');
        $reference = $this->escapeAttr($entity['reference'] ?? '');
        $image = $entity['image'] ?? '';

        $html = \sprintf('<div class="acf-relation-item badge badge-secondary" data-id="%d">', $id);

        if (!$isCompact && $image && ($displayFormat === 'thumbnail_name' || $displayFormat === 'full')) {
            $html .= \sprintf('<img src="%s" alt="" class="acf-relation-thumb">', $this->escapeAttr($image));
        }

        $label = $name;

        if ($displayFormat === 'name_reference' && $reference) {
            $label .= ' (' . $reference . ')';
        }

        $html .= \sprintf('<span class="acf-relation-name">%s</span>', htmlspecialchars($label, ENT_QUOTES, 'UTF-8'));
        $html .= '<button type="button" class="acf-relation-remove" title="Remove"><span class="material-icons" style="font-size: 14px;">close</span></button>';
        $html .= '</div>';

        return $html;
    }
}
