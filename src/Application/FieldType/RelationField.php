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

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Relation field type
 *
 * Allows selecting related products or categories.
 * Supports single and multiple selection with search and filters.
 */
final class RelationField extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'relation';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Relation';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType(): string
    {
        // We use hidden type since the UI is custom JavaScript
        return HiddenType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Custom data attributes for JavaScript initialization
        $options['attr']['data-relation-type'] = $this->getConfigValue($fieldConfig, 'entityType', 'product');
        $options['attr']['data-relation-multiple'] = $this->getConfigValue($fieldConfig, 'multiple', false) ? '1' : '0';

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $multiple = $this->getConfigValue($fieldConfig, 'multiple', false);

        // Already JSON string (from form submission)
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if ($multiple) {
                    // Ensure array of integers
                    $ids = array_map('intval', is_array($decoded) ? $decoded : [$decoded]);

                    return json_encode(array_values(array_filter($ids)), JSON_THROW_ON_ERROR);
                }

                // Single value
                return (string) (is_array($decoded) ? ($decoded[0] ?? 0) : $decoded);
            }

            // Plain numeric string
            if (is_numeric($value)) {
                return $multiple ? json_encode([(int) $value], JSON_THROW_ON_ERROR) : (string) $value;
            }
        }

        // Array of IDs
        if (is_array($value)) {
            $ids = array_map('intval', $value);
            $ids = array_filter($ids);

            if ($multiple) {
                return json_encode(array_values($ids), JSON_THROW_ON_ERROR);
            }

            // Single value from array
            return count($ids) > 0 ? (string) $ids[0] : null;
        }

        // Single numeric value
        if (is_numeric($value)) {
            return $multiple ? json_encode([(int) $value], JSON_THROW_ON_ERROR) : (string) $value;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $multiple = $this->getConfigValue($fieldConfig, 'multiple', false);
        $entityType = $this->getConfigValue($fieldConfig, 'entityType', 'product');

        // Decode JSON if string
        $ids = $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $ids = json_last_error() === JSON_ERROR_NONE ? $decoded : [(int) $value];
        }

        if (!is_array($ids)) {
            $ids = [(int) $ids];
        }

        // Load entity data for display
        $entities = $this->loadEntities($ids, $entityType, $fieldConfig);

        if ($multiple) {
            return $entities;
        }

        // Single selection - return first entity or null
        return count($entities) > 0 ? $entities[0] : null;
    }

    /**
     * Load entities by IDs with their display data
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
        $langId = (int) \Context::getContext()->language->id;
        $entities = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            if ($entityType === 'category') {
                $category = new \Category($id, $langId);
                if (\Validate::isLoadedObject($category)) {
                    $entities[] = $this->formatCategoryData($category, $displayFormat);
                }
            } else {
                // Default: product
                $product = new \Product($id, false, $langId);
                if (\Validate::isLoadedObject($product)) {
                    $entities[] = $this->formatProductData($product, $displayFormat);
                }
            }
        }

        return $entities;
    }

    /**
     * Format product data for display
     *
     * @param \Product $product
     * @param string $displayFormat
     *
     * @return array<string, mixed>
     */
    private function formatProductData(\Product $product, string $displayFormat): array
    {
        $data = [
            'id' => (int) $product->id,
            'type' => 'product',
            'name' => $product->name,
            'reference' => $product->reference,
            'link' => \Context::getContext()->link->getProductLink($product),
        ];

        // Add image if needed
        if ($displayFormat === 'thumbnail_name' || $displayFormat === 'full') {
            $cover = \Image::getCover($product->id);
            if ($cover) {
                $imageLink = \Context::getContext()->link->getImageLink(
                    $product->link_rewrite,
                    (string) $cover['id_image'],
                    'small_default'
                );
                $data['image'] = $imageLink;
            }
        }

        // Add price if full format
        if ($displayFormat === 'full') {
            $data['price'] = $product->getPrice(true, null, 2);
            $data['price_formatted'] = \Tools::displayPrice($data['price']);
        }

        return $data;
    }

    /**
     * Format category data for display
     *
     * @param \Category $category
     * @param string $displayFormat
     *
     * @return array<string, mixed>
     */
    private function formatCategoryData(\Category $category, string $displayFormat): array
    {
        $data = [
            'id' => (int) $category->id,
            'type' => 'category',
            'name' => $category->name,
            'link' => \Context::getContext()->link->getCategoryLink($category),
        ];

        // Add image if needed
        if ($displayFormat === 'thumbnail_name' || $displayFormat === 'full') {
            $imageLink = \Context::getContext()->link->getCatImageLink(
                $category->name,
                (int) $category->id,
                'category_default'
            );
            $data['image'] = $imageLink;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || (is_array($value) && empty($value))) {
            return '';
        }

        $displayFormat = $this->getConfigValue($fieldConfig, 'displayFormat', 'name');
        $multiple = $this->getConfigValue($fieldConfig, 'multiple', false);

        // Ensure we have array of entities
        $entities = $multiple ? $value : [$value];

        if (!is_array($entities)) {
            return '';
        }

        $output = [];
        foreach ($entities as $entity) {
            if (!is_array($entity) || !isset($entity['name'])) {
                continue;
            }

            $label = htmlspecialchars($entity['name'], ENT_QUOTES, 'UTF-8');

            if ($displayFormat === 'name_reference' && !empty($entity['reference'])) {
                $label .= ' (' . htmlspecialchars($entity['reference'], ENT_QUOTES, 'UTF-8') . ')';
            }

            $output[] = $label;
        }

        $separator = $renderOptions['separator'] ?? ', ';

        return implode($separator, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        // Decode if JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $ids = is_array($decoded) ? $decoded : [$decoded];

                return implode(',', array_slice($ids, 0, 10)); // First 10 IDs for index
            }

            return substr($value, 0, 255);
        }

        if (is_array($value)) {
            $ids = array_column($value, 'id');

            return implode(',', array_slice($ids, 0, 10));
        }

        return null;
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

        $multiple = $this->getConfigValue($fieldConfig, 'multiple', false);

        // Get IDs from value
        $ids = [];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $ids = json_last_error() === JSON_ERROR_NONE ? (is_array($decoded) ? $decoded : [$decoded]) : [];
        } elseif (is_array($value)) {
            $ids = array_column($value, 'id') ?: $value;
        }

        // Validate min/max constraints
        $min = $this->getConfigValue($fieldConfig, 'min', 0);
        $max = $this->getConfigValue($fieldConfig, 'max', null);

        if ($multiple) {
            if ($min > 0 && count($ids) < $min) {
                $errors[] = sprintf('At least %d item(s) required.', $min);
            }

            if ($max !== null && count($ids) > $max) {
                $errors[] = sprintf('Maximum %d item(s) allowed.', $max);
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function supportsTranslation(): bool
    {
        // Relations are not translatable - they reference entity IDs
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'relational';
    }

    /**
     * {@inheritdoc}
     */
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

        // Get raw IDs and load entities for display
        $rawIds = $this->getRawIds($value, $config);
        $entityType = $this->getConfigValue($config, 'entityType', 'product');
        $entities = !empty($rawIds) ? $this->loadEntities($rawIds, $entityType, $config) : [];

        return $this->renderPartial('relation.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $rawIds,
            'entities' => $entities,
            'context' => $context,
        ]);
    }

    /**
     * Get raw IDs from value (without loading entities)
     *
     * @param mixed $value
     * @param array<string, mixed> $config
     *
     * @return array<int>
     */
    private function getRawIds(mixed $value, array $config): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        // If already denormalized (array of entities)
        if (is_array($value) && isset($value[0]['id'])) {
            return array_map(fn($e) => (int) $e['id'], $value);
        }

        // If single entity
        if (is_array($value) && isset($value['id'])) {
            return [(int) $value['id']];
        }

        // If JSON string
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($decoded)) {
                    return array_map('intval', $decoded);
                }

                return [(int) $decoded];
            }
            if (is_numeric($value)) {
                return [(int) $value];
            }
        }

        // If array of IDs
        if (is_array($value)) {
            return array_map('intval', array_filter($value));
        }

        return [];
    }

    /**
     * Render a selected entity badge
     */
    private function renderSelectedEntity(array $entity, string $displayFormat, bool $isCompact): string
    {
        $id = (int) ($entity['id'] ?? 0);
        $name = $this->escapeAttr($entity['name'] ?? '');
        $reference = $this->escapeAttr($entity['reference'] ?? '');
        $image = $entity['image'] ?? '';

        $html = sprintf('<div class="acf-relation-item badge badge-secondary" data-id="%d">', $id);

        if (!$isCompact && $image && ($displayFormat === 'thumbnail_name' || $displayFormat === 'full')) {
            $html .= sprintf('<img src="%s" alt="" class="acf-relation-thumb">', $this->escapeAttr($image));
        }

        $label = $name;
        if ($displayFormat === 'name_reference' && $reference) {
            $label .= ' (' . $reference . ')';
        }

        $html .= sprintf('<span class="acf-relation-name">%s</span>', htmlspecialchars($label, ENT_QUOTES, 'UTF-8'));
        $html .= '<button type="button" class="acf-relation-remove" title="Remove"><span class="material-icons" style="font-size: 14px;">close</span></button>';
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
        $entityType = $this->getConfigValue($config, 'entityType', 'product');
        $multiple = (bool) $this->getConfigValue($config, 'multiple', false);
        $placeholder = addslashes($config['placeholder'] ?? 'Search...');

        // Compact template for repeater table mode
        $html = sprintf(
            '<div class="acf-relation-field acf-relation-compact" data-slug="%s" data-entity-type="%s" data-multiple="%s">',
            $this->escapeAttr($slug),
            $this->escapeAttr($entityType),
            $multiple ? '1' : '0'
        );

        $html .= sprintf(
            '<input type="hidden" class="acf-subfield-input acf-relation-value" data-subfield="%s" value="{value}">',
            $this->escapeAttr($slug)
        );

        $html .= '<div class="acf-relation-selected"></div>';
        $html .= sprintf(
            '<div class="acf-relation-search"><input type="text" class="form-control form-control-sm acf-relation-search-input" placeholder="%s"><div class="acf-relation-dropdown"></div></div>',
            $placeholder
        );
        $html .= '</div>';

        return $html;
    }
}
