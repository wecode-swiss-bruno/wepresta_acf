<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

/**
 * ACF Front-Office Service.
 *
 * Main service for rendering ACF fields in front-office templates.
 * Provides a fluent API similar to WordPress ACF.
 *
 * Usage in Smarty:
 *   {$acf->field('brand')}           - Get escaped value
 *   {$acf->render('image')}          - Get rendered HTML
 *   {$acf->has('promo')}             - Check if field exists and has value
 *   {foreach $acf->repeater('specs') as $row}...{/foreach}
 *   {$acf->forProduct(123)->field('brand')} - Override context
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Category;
use CMS;
use Context;
use Generator;
use Hook;
use Image;
use ImageType;
use PrestaShopLogger;
use Product;
use Throwable;
use Validate;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;

final class AcfFrontService
{
    private ?string $entityType = null;

    private ?int $entityId = null;

    private ?int $shopId = null;

    private ?int $langId = null;

    /** @var array<string, mixed>|null Cached field values for current entity */
    private ?array $cachedValues = null;

    /** @var array<string, array<string, mixed>>|null Cached field definitions */
    private ?array $cachedFields = null;

    private bool $contextOverridden = false;
    private array $extraContext = [];

    // =========================================================================
    // REPEATER ACCESS
    // =========================================================================

    /** @var array<int, array<string, array<string, mixed>>>|null Cached sub-fields by parent ID */
    private ?array $cachedSubFields = null;

    public function __construct(
        private readonly EntityContextDetector $contextDetector,
        private readonly FieldRenderer $fieldRenderer,
        private readonly ValueProvider $valueProvider,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly LocationProviderRegistry $locationProviderRegistry
    ) {
    }

    // =========================================================================
    // CONTEXT MANAGEMENT
    // =========================================================================

    /**
     * Override context for a specific product.
     *
     * @return self New instance with overridden context
     */
    public function forProduct(int $productId): self
    {
        return $this->forEntity('product', $productId);
    }

    /**
     * Override context for a specific category.
     *
     * @return self New instance with overridden context
     */
    public function forCategory(int $categoryId): self
    {
        return $this->forEntity('category', $categoryId);
    }

    /**
     * Override context for a specific CMS page.
     *
     * @return self New instance with overridden context
     */
    public function forCms(int $cmsId): self
    {
        return $this->forEntity('cms_page', $cmsId);
    }

    /**
     * Override context for a specific customer.
     *
     * @return self New instance with overridden context
     */
    public function forCustomer(int $customerId): self
    {
        return $this->forEntity('customer', $customerId);
    }

    /**
     * Override context for any entity type.
     *
     * @return self New instance with overridden context
     */
    public function forEntity(string $entityType, int $entityId): self
    {
        $clone = clone $this;
        $clone->entityType = $entityType;
        $clone->entityId = $entityId;
        $clone->contextOverridden = true;
        // Reset cache for new context
        $clone->cachedValues = null;
        $clone->cachedFields = null;

        return $clone;
    }

    /**
     * Override context for a CPT post (with type slug).
     *
     * @return self New instance with overridden context and CPT type slug
     */
    public function forCpt(string $cptSlug, int $postId): self
    {
        $clone = $this->forEntity('cpt_post', $postId);
        $clone->extraContext['cpt_type_slug'] = $cptSlug;

        return $clone;
    }

    /**
     * Set shop context.
     *
     * @return self New instance with overridden shop
     */
    public function forShop(int $shopId): self
    {
        $clone = clone $this;
        $clone->shopId = $shopId;
        $clone->cachedValues = null;

        return $clone;
    }

    /**
     * Set language context.
     *
     * @return self New instance with overridden language
     */
    public function forLang(int $langId): self
    {
        $clone = clone $this;
        $clone->langId = $langId;
        $clone->cachedValues = null;

        return $clone;
    }

    // =========================================================================
    // FIELD ACCESS
    // =========================================================================

    /**
     * Get field value (escaped for XSS protection).
     *
     * @param string $slug Field slug
     * @param mixed $default Default value if field is empty
     *
     * @return mixed Escaped field value
     */
    public function field(string $slug, mixed $default = ''): mixed
    {
        $this->ensureContext();

        $value = $this->getFieldValue($slug);

        if ($value === null || $value === '') {
            return $default;
        }

        // Escape string values for XSS protection
        if (\is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }

    /**
     * Get raw field value (not escaped, use with caution).
     *
     * @param string $slug Field slug
     * @param mixed $default Default value if field is empty
     *
     * @return mixed Raw field value
     */
    public function raw(string $slug, mixed $default = ''): mixed
    {
        $this->ensureContext();

        $value = $this->getFieldValue($slug);

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    /**
     * Get translated label for select/radio/checkbox fields.
     *
     * Returns the human-readable label instead of the stored value.
     * For other field types, returns the escaped value.
     *
     * @param string $slug Field slug
     * @param mixed $default Default value if field is empty
     *
     * @return string Translated label or escaped value
     */
    public function label(string $slug, mixed $default = ''): string
    {
        $this->ensureContext();

        $value = $this->getFieldValue($slug);

        if ($value === null || $value === '') {
            return (string) $default;
        }

        $fieldDef = $this->getFieldDefinition($slug);

        if ($fieldDef === null) {
            return \is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : (string) $value;
        }

        $fieldType = $fieldDef['type'] ?? '';

        // Only resolve labels for choice fields
        if (!\in_array($fieldType, ['select', 'radio', 'checkbox'], true)) {
            return \is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : (string) $value;
        }

        // Get config and choices
        $config = $fieldDef['config'] ?? [];

        if (\is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        $choices = $config['choices'] ?? [];

        if (empty($choices)) {
            return (string) $value;
        }

        $langId = $this->langId ?? $this->contextDetector->detect()['lang_id'] ?? 1;

        // Handle checkbox (multiple values)
        if ($fieldType === 'checkbox' && \is_array($value)) {
            $labels = array_map(
                fn($v) => $this->resolveChoiceLabel($v, $choices, $langId),
                $value
            );

            return implode(', ', $labels);
        }

        // Single value for select/radio
        return $this->resolveChoiceLabel($value, $choices, $langId);
    }

    /**
     * Get array of labels for select/radio/checkbox fields.
     *
     * Returns an array of human-readable labels instead of values.
     * For checkbox fields with multiple selections, returns array of labels.
     * For other field types, returns array with single escaped value.
     *
     * @param string $slug Field slug
     *
     * @return array<int, string> Array of labels
     */
    public function labels(string $slug): array
    {
        $this->ensureContext();

        $value = $this->getFieldValue($slug);

        if ($value === null || $value === '') {
            return [];
        }

        $fieldDef = $this->getFieldDefinition($slug);

        if ($fieldDef === null) {
            // Field definition not found, return escaped value as array
            if (\is_string($value)) {
                return [htmlspecialchars($value, ENT_QUOTES, 'UTF-8')];
            }
            return [\is_array($value) ? implode(', ', $value) : (string) $value];
        }

        $fieldType = $fieldDef['type'] ?? '';

        // Only resolve labels for choice fields
        if (!\in_array($fieldType, ['select', 'radio', 'checkbox'], true)) {
            return \is_string($value) ? [htmlspecialchars($value, ENT_QUOTES, 'UTF-8')] : [(string) $value];
        }

        // Get config and choices
        $config = $fieldDef['config'] ?? [];

        if (\is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        $choices = $config['choices'] ?? [];

        if (empty($choices)) {
            return \is_array($value) ? $value : [$value];
        }

        $langId = $this->langId ?? $this->contextDetector->detect()['lang_id'] ?? 1;

        // Handle checkbox (multiple values) or single value
        if (\is_array($value)) {
            return array_map(
                fn($v) => $this->resolveChoiceLabel($v, $choices, $langId),
                $value
            );
        }

        // Single value for select/radio
        return [$this->resolveChoiceLabel($value, $choices, $langId)];
    }

    /**
     *
     * @param string $slug Field slug
     * @param array<string, mixed> $options Render options
     *
     * @return string Rendered HTML (not escaped, contains HTML)
     */
    public function render(string $slug, array $options = []): string
    {
        $this->ensureContext();

        $value = $this->getFieldValue($slug);

        if ($value === null || $value === '') {
            return '';
        }

        $fieldDef = $this->getFieldDefinition($slug);

        if ($fieldDef === null) {
            // Field definition not found, return escaped value
            return \is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
        }

        try {
            // Execute before render hook
            $hookParams = [
                'field_slug' => $slug,
                'entity_type' => $this->getEntityType(),
                'entity_id' => $this->getEntityId(),
                'value' => &$value,
            ];
            Hook::exec('actionAcfBeforeRender', $hookParams);
            $value = $hookParams['value'];

            // Render the field
            $html = $this->fieldRenderer->render($fieldDef, $value, $options);

            // Execute after render hook
            $afterHookParams = [
                'field_slug' => $slug,
                'rendered_html' => &$html,
            ];
            Hook::exec('actionAcfAfterRender', $afterHookParams);

            return $afterHookParams['rendered_html'];
        } catch (Throwable $e) {
            $this->logError("Error rendering field '{$slug}': " . $e->getMessage());

            return '';
        }
    }

    /**
     * Check if field exists and has a non-empty value.
     *
     * @param string $slug Field slug
     *
     * @return bool True if field has value
     */
    public function has(string $slug): bool
    {
        $this->ensureContext();

        $value = $this->getFieldValue($slug);

        if ($value === null || $value === '') {
            return false;
        }

        return !(\is_array($value) && \count($value) === 0);
    }

    /**
     * Get repeater field rows as iterable.
     *
     * Automatically resolves:
     * - Multilang fields (text, textarea, richtext) to current language
     * - Select/radio/checkbox labels
     * - Relation fields (product, category, cms) to enriched data
     *
     * @param string $slug Repeater field slug
     * @param bool $resolveAll Whether to resolve labels, multilang and relations (default: true)
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function repeater(string $slug, bool $resolveAll = true): Generator
    {
        $this->ensureContext();

        $value = $this->getFieldValue($slug);

        if (!\is_array($value) || empty($value)) {
            return;
        }

        // Get sub-fields definitions for resolution
        $subFields = $resolveAll ? $this->getRepeaterSubFields($slug) : [];
        $langId = $this->langId ?? $this->contextDetector->detect()['lang_id'] ?? 1;

        // Repeater structure: [{"row_id": "...", "values": {...}}, ...]
        foreach ($value as $index => $row) {
            if (!\is_array($row)) {
                continue;
            }

            // Extract values from row structure
            $rowValues = $row['values'] ?? $row;

            if (!\is_array($rowValues)) {
                continue;
            }

            // Process each field value
            if ($resolveAll) {
                $rowValues = $this->processRepeaterRowValues($rowValues, $subFields, $langId);
            }

            // Add row metadata
            $rowValues['_index'] = $index;
            $rowValues['_row_id'] = $row['row_id'] ?? $index;

            yield $index => $rowValues;
        }
    }

    /**
     * Process all values in a repeater row.
     *
     * @param array<string, mixed> $rowValues Row values
     * @param array<string, array<string, mixed>> $subFields Sub-field definitions
     * @param int $langId Current language ID
     *
     * @return array<string, mixed> Processed row values
     */
    private function processRepeaterRowValues(array $rowValues, array $subFields, int $langId): array
    {
        foreach ($rowValues as $slug => $value) {
            // Skip metadata keys
            if (str_starts_with($slug, '_')) {
                continue;
            }

            $fieldDef = $subFields[$slug] ?? null;

            // 1. Resolve multilang arrays first (for any field type)
            if (\is_array($value) && $this->isMultilangArray($value)) {
                $value = $this->resolveMultilangValue($value, $langId);
                $rowValues[$slug] = $value;
            }

            if ($fieldDef === null) {
                continue;
            }

            $fieldType = $fieldDef['type'] ?? '';

            // 2. Resolve choice field labels (select, radio, checkbox)
            if (\in_array($fieldType, ['select', 'radio', 'checkbox'], true)) {
                $rowValues[$slug] = $this->resolveSubFieldChoiceValue($value, $fieldDef, $langId);
                continue;
            }

            // 3. Resolve relation fields
            if ($this->isRelationField($fieldDef)) {
                $rowValues[$slug] = $this->resolveRelation($value, $fieldDef, $langId);
                continue;
            }
        }

        return $rowValues;
    }

    /**
     * Check if array is a multilang structure (keys are language IDs).
     *
     * Multilang arrays have numeric string keys like "1", "2", etc.
     * Example: {"1": "English text", "2": "French text", "3": "German text"}
     *
     * @param array<mixed> $array Array to check
     *
     * @return bool True if multilang array
     */
    private function isMultilangArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        // Check if all keys are numeric (language IDs)
        foreach (array_keys($array) as $key) {
            if (!is_numeric($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve multilang value to current language.
     *
     * @param array<int|string, mixed> $multilangValue Multilang array
     * @param int $langId Current language ID
     *
     * @return mixed Resolved value for current language
     */
    private function resolveMultilangValue(array $multilangValue, int $langId): mixed
    {
        // Try current language
        $langKey = (string) $langId;
        if (isset($multilangValue[$langKey]) && $multilangValue[$langKey] !== '') {
            return $multilangValue[$langKey];
        }

        // Try integer key
        if (isset($multilangValue[$langId]) && $multilangValue[$langId] !== '') {
            return $multilangValue[$langId];
        }

        // Fallback to language ID 1 (default) or context default lang
        // Ideally we should use Configuration::get('PS_LANG_DEFAULT') but checking keys 1 (common default) first
        if (isset($multilangValue['1']) && $multilangValue['1'] !== '') {
            return $multilangValue['1'];
        }
        if (isset($multilangValue[1]) && $multilangValue[1] !== '') {
            return $multilangValue[1];
        }

        // Last resort: first non-empty value
        foreach ($multilangValue as $val) {
            if ($val !== null && $val !== '') {
                return $val;
            }
        }

        return '';
    }

    /**
     * Check if field is a relation type.
     *
     * @param array<string, mixed> $fieldDef Field definition
     *
     * @return bool True if relation field
     */
    private function isRelationField(array $fieldDef): bool
    {
        $fieldType = $fieldDef['type'] ?? '';

        // Direct relation types
        if (\in_array($fieldType, ['relation', 'product', 'category', 'cms', 'cpt_post'], true)) {
            return true;
        }

        // Check config for relation_type
        $config = $fieldDef['config'] ?? [];
        if (\is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        return isset($config['relation_type']) || isset($config['relationType']);
    }

    /**
     * Get relation type from field definition.
     *
     * @param array<string, mixed> $fieldDef Field definition
     *
     * @return string Relation type (product, category, cms, cpt_post)
     */
    private function getRelationType(array $fieldDef): string
    {
        $fieldType = $fieldDef['type'] ?? '';

        // Direct types
        if (\in_array($fieldType, ['product', 'category', 'cms', 'cpt_post'], true)) {
            return $fieldType;
        }

        // Check config
        $config = $fieldDef['config'] ?? [];
        if (\is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        return $config['relation_type'] ?? $config['relationType'] ?? 'product';
    }

    /**
     * Resolve relation field to enriched data.
     *
     * @param mixed $value Field value (ID or comma-separated IDs)
     * @param array<string, mixed> $fieldDef Field definition
     * @param int $langId Language ID
     *
     * @return array<int, array<string, mixed>> Enriched relation data
     */
    private function resolveRelation(mixed $value, array $fieldDef, int $langId): array
    {
        if (empty($value)) {
            return [];
        }

        $relationType = $this->getRelationType($fieldDef);

        // Handle single or multiple IDs
        $ids = \is_array($value) ? $value : explode(',', (string) $value);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return [];
        }

        try {
            return match ($relationType) {
                'product' => $this->resolveProducts($ids, $langId),
                'category' => $this->resolveCategories($ids, $langId),
                'cms' => $this->resolveCmsPages($ids, $langId),
                'cpt_post' => $this->resolveCptPosts($ids, $langId),
                default => array_map(fn($id) => ['id' => $id], $ids),
            };
        } catch (Throwable $e) {
            $this->logError("Error resolving relation ({$relationType}): " . $e->getMessage());
            return array_map(fn($id) => ['id' => $id], $ids);
        }
    }

    /**
     * Resolve product IDs to enriched product data.
     *
     * @param array<int> $ids Product IDs
     * @param int $langId Language ID
     *
     * @return array<int, array<string, mixed>> Enriched product data
     */
    private function resolveProducts(array $ids, int $langId): array
    {
        $products = [];
        $context = Context::getContext();

        foreach ($ids as $idProduct) {
            try {
                $product = new Product($idProduct, false, $langId);

                if (!Validate::isLoadedObject($product) || !$product->active) {
                    continue;
                }

                // Get product image
                $imageUrl = '';
                $cover = Product::getCover($idProduct);
                if ($cover && isset($cover['id_image'])) {
                    $imageUrl = $context->link->getImageLink(
                        $product->link_rewrite,
                        (int) $cover['id_image'],
                        ImageType::getFormattedName('home')
                    );
                }

                // Get product prices
                $priceWithTax = Product::getPriceStatic($idProduct, true);
                $priceWithoutReduction = Product::getPriceStatic($idProduct, true, null, 6, null, false, false);
                $hasDiscount = $priceWithoutReduction > $priceWithTax;

                $products[] = [
                    'id' => $idProduct,
                    'name' => $product->name,
                    'description_short' => $product->description_short,
                    'link' => $context->link->getProductLink($product),
                    'image' => $imageUrl,
                    'price' => $priceWithTax,
                    'price_formatted' => $context->currentLocale->formatPrice($priceWithTax, $context->currency->iso_code),
                    'price_without_reduction' => $hasDiscount ? $priceWithoutReduction : null,
                    'price_without_reduction_formatted' => $hasDiscount
                        ? $context->currentLocale->formatPrice($priceWithoutReduction, $context->currency->iso_code)
                        : null,
                    'has_discount' => $hasDiscount,
                    'discount_percentage' => $hasDiscount
                        ? round((1 - $priceWithTax / $priceWithoutReduction) * 100)
                        : 0,
                    'reference' => $product->reference,
                    'available' => $product->checkQty(1),
                    'quantity' => Product::getQuantity($idProduct),
                ];
            } catch (Throwable $e) {
                $this->logError("Error loading product #{$idProduct}: " . $e->getMessage());
                continue;
            }
        }

        return $products;
    }

    /**
     * Resolve category IDs to enriched category data.
     *
     * @param array<int> $ids Category IDs
     * @param int $langId Language ID
     *
     * @return array<int, array<string, mixed>> Enriched category data
     */
    private function resolveCategories(array $ids, int $langId): array
    {
        $categories = [];
        $context = Context::getContext();

        foreach ($ids as $idCategory) {
            try {
                $category = new Category($idCategory, $langId);

                if (!Validate::isLoadedObject($category) || !$category->active) {
                    continue;
                }

                // Get category image
                $imageUrl = '';
                if (file_exists(_PS_CAT_IMG_DIR_ . $idCategory . '.jpg')) {
                    $imageUrl = $context->link->getCatImageLink(
                        $category->link_rewrite,
                        $idCategory,
                        ImageType::getFormattedName('category')
                    );
                }

                $categories[] = [
                    'id' => $idCategory,
                    'name' => $category->name,
                    'description' => $category->description,
                    'link' => $context->link->getCategoryLink($category),
                    'image' => $imageUrl,
                    'products_count' => $category->getProducts($langId, 1, 1, null, null, true),
                ];
            } catch (Throwable $e) {
                $this->logError("Error loading category #{$idCategory}: " . $e->getMessage());
                continue;
            }
        }

        return $categories;
    }

    /**
     * Resolve CMS page IDs to enriched page data.
     *
     * @param array<int> $ids CMS page IDs
     * @param int $langId Language ID
     *
     * @return array<int, array<string, mixed>> Enriched CMS page data
     */
    private function resolveCmsPages(array $ids, int $langId): array
    {
        $pages = [];
        $context = Context::getContext();

        foreach ($ids as $idCms) {
            try {
                $cms = new CMS($idCms, $langId);

                if (!Validate::isLoadedObject($cms) || !$cms->active) {
                    continue;
                }

                $pages[] = [
                    'id' => $idCms,
                    'title' => $cms->meta_title,
                    'description' => $cms->meta_description,
                    'content' => $cms->content,
                    'link' => $context->link->getCMSLink($cms),
                ];
            } catch (Throwable $e) {
                $this->logError("Error loading CMS page #{$idCms}: " . $e->getMessage());
                continue;
            }
        }

        return $pages;
    }

    /**
     * Resolve CPT post IDs to enriched post data.
     *
     * @param array<int> $ids CPT post IDs
     * @param int $langId Language ID
     *
     * @return array<int, array<string, mixed>> Enriched CPT post data
     */
    private function resolveCptPosts(array $ids, int $langId): array
    {
        // This would require access to CPT repository
        // For now, return basic structure
        return array_map(fn($id) => [
            'id' => $id,
            // Additional fields would be loaded from CPT repository
        ], $ids);
    }

    /**
     * Resolve choice field value to label.
     *
     * @param mixed $value Field value
     * @param array<string, mixed> $fieldDef Field definition
     * @param int $langId Language ID
     *
     * @return mixed Resolved label(s) or original value
     */
    private function resolveSubFieldChoiceValue(mixed $value, array $fieldDef, int $langId): mixed
    {
        $config = $fieldDef['config'] ?? [];
        if (\is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        $choices = $config['choices'] ?? [];

        if (empty($choices)) {
            return $value;
        }

        $fieldType = $fieldDef['type'] ?? '';

        // Handle checkbox (multiple values)
        if ($fieldType === 'checkbox' && \is_array($value)) {
            return array_map(
                fn($v) => $this->resolveChoiceLabel($v, $choices, $langId),
                $value
            );
        }

        // Single value for select/radio
        return $this->resolveChoiceLabel($value, $choices, $langId);
    }

    /**
     * Get repeater rows as array (alternative to generator).
     *
     * @param string $slug Repeater field slug
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRepeaterRows(string $slug): array
    {
        return iterator_to_array($this->repeater($slug));
    }

    /**
     * Count repeater rows.
     *
     * @param string $slug Repeater field slug
     *
     * @return int Number of rows
     */
    public function countRepeater(string $slug): int
    {
        $this->ensureContext();

        $value = $this->getFieldValue($slug);

        if (!\is_array($value)) {
            return 0;
        }

        return \count($value);
    }

    // =========================================================================
    // GROUP ACCESS
    // =========================================================================

    /**
     * Get all fields from a group.
     *
     * @param int|string $groupIdOrSlug Group ID or slug
     *
     * @return Generator<int, array<string, mixed>> Field data with rendered values
     */
    public function group(int|string $groupIdOrSlug): Generator
    {
        $this->ensureContext();

        // Find group
        $group = \is_int($groupIdOrSlug)
            ? $this->groupRepository->findOneBy(['id_wepresta_acf_group' => $groupIdOrSlug])
            : $this->groupRepository->findBySlug($groupIdOrSlug);

        if ($group === null) {
            $this->logError("Group not found: {$groupIdOrSlug}");

            return;
        }

        $groupId = (int) $group['id_wepresta_acf_group'];

        // Get fields from repository
        $fields = $this->fieldRepository->findByGroup($groupId);

        foreach ($fields as $fieldDef) {
            $slug = $fieldDef['slug'] ?? '';

            if ($slug === '') {
                continue;
            }

            $value = $this->getFieldValue($slug);

            yield [
                'slug' => $slug,
                'type' => $fieldDef['type'] ?? 'text',
                'title' => $fieldDef['title'] ?? $slug,
                'instructions' => $fieldDef['instructions'] ?? '',
                'value' => $value,
                'rendered' => $this->render($slug),
                'has_value' => $this->has($slug),
            ];
        }
    }

    /**
     * Get group fields as array.
     *
     * @param int|string $groupIdOrSlug Group ID or slug
     *
     * @return array<int, array<string, mixed>>
     */
    public function getGroupFields(int|string $groupIdOrSlug): array
    {
        return iterator_to_array($this->group($groupIdOrSlug));
    }

    /**
     * Get all active groups for the current context.
     *
     * Note: Returns a Generator for memory efficiency. For Smarty templates,
     * use getActiveGroupsArray() instead, as Smarty templates work better with arrays.
     *
     * @return Generator<int, array<string, mixed>> Group data with fields
     */
    public function getActiveGroups(): Generator
    {
        $this->ensureContext();

        $entityType = $this->getEntityType();
        $entityId = $this->getEntityId();

        // Get all active groups and filter them
        // This mirrors FormModifierService logic but optimized for read-only
        $groups = $this->groupRepository->findActiveGroups($this->shopId);

        foreach ($groups as $group) {
            // Skip if location rules don't match
            if (!$this->matchLocationRules($group, $entityType, $entityId)) {
                continue;
            }

            $groupId = (int) $group['id_wepresta_acf_group'];
            $fieldsGen = $this->group($groupId);

            // Check if group has fields
            $fields = iterator_to_array($fieldsGen);

            if (empty($fields)) {
                continue;
            }

            yield [
                'id' => $groupId,
                'title' => $group['title'],
                'slug' => $group['slug'] ?? '',
                'fields' => $fields,
            ];
        }
    }

    /**
     * Get all active groups as array (for template compatibility).
     *
     * @return array<int, array<string, mixed>> Group data with fields
     */
    public function getActiveGroupsArray(): array
    {
        return iterator_to_array($this->getActiveGroups());
    }

    /**
     * Check if group matches current context location rules.
     *
     * Location rules are stored in JsonLogic format:
     * [{"==": [{"var": "entity_type"}, "cpt_post:blog"]}, ...]
     * where each element is a separate rule to match (OR logic between them).
     */
    private function matchLocationRules(array $group, ?string $entityType, ?int $entityId): bool
    {
        $locationRules = json_decode($group['location_rules'] ?? '[]', true) ?: [];

        if (empty($locationRules)) {
            return false;
        }

        // Location rules use OR logic: any rule matching = group is shown
        return $this->locationProviderRegistry->matchLocation($locationRules, array_merge($this->extraContext, [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]));
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Get current entity context info.
     *
     * @return array{entity_type: string|null, entity_id: int|null, shop_id: int|null, lang_id: int|null}
     */
    public function getContext(): array
    {
        $this->ensureContext();

        return [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'shop_id' => $this->shopId,
            'lang_id' => $this->langId,
        ];
    }

    /**
     * Get all field values for current entity.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->ensureContext();
        $this->loadValues();

        return $this->cachedValues ?? [];
    }

    /**
     * Get multiple field values at once.
     *
     * @param array<string> $slugs List of field slugs
     *
     * @return array<string, mixed> Map of slug => value
     */
    public function fields(array $slugs): array
    {
        $result = [];

        foreach ($slugs as $slug) {
            $result[$slug] = $this->field($slug);
        }

        return $result;
    }

    /**
     * Get sub-fields definitions for a repeater.
     *
     * @return array<string, array<string, mixed>> Map of slug => field definition
     */
    private function getRepeaterSubFields(string $repeaterSlug): array
    {
        // Get repeater field definition
        $repeaterField = $this->getFieldDefinition($repeaterSlug);

        if ($repeaterField === null) {
            return [];
        }

        $repeaterId = (int) ($repeaterField['id_wepresta_acf_field'] ?? 0);

        if ($repeaterId === 0) {
            return [];
        }

        // Check cache
        if ($this->cachedSubFields === null) {
            $this->cachedSubFields = [];
        }

        if (isset($this->cachedSubFields[$repeaterId])) {
            return $this->cachedSubFields[$repeaterId];
        }

        // Load sub-fields from repository
        $subFieldsRaw = $this->fieldRepository->findByParent($repeaterId);
        $subFields = [];

        foreach ($subFieldsRaw as $field) {
            $fieldSlug = $field['slug'] ?? '';

            if ($fieldSlug !== '') {
                $subFields[$fieldSlug] = $field;
            }
        }

        $this->cachedSubFields[$repeaterId] = $subFields;

        return $subFields;
    }

    /**
     * Resolve labels for select/radio/checkbox fields in a row.
     *
     * @deprecated Use processRepeaterRowValues() instead
     *
     * @param array<string, mixed> $rowValues Row values
     * @param array<string, array<string, mixed>> $subFields Sub-field definitions
     *
     * @return array<string, mixed> Row values with resolved labels
     */
    private function resolveSubFieldLabels(array $rowValues, array $subFields): array
    {
        $langId = $this->langId ?? $this->contextDetector->detect()['lang_id'] ?? 1;

        foreach ($rowValues as $slug => $value) {
            // Skip metadata keys
            if (str_starts_with($slug, '_')) {
                continue;
            }

            $fieldDef = $subFields[$slug] ?? null;

            if ($fieldDef === null) {
                continue;
            }

            $fieldType = $fieldDef['type'] ?? '';

            // Only resolve for choice fields
            if (!\in_array($fieldType, ['select', 'radio', 'checkbox'], true)) {
                continue;
            }

            // Get config and choices
            $config = $fieldDef['config'] ?? [];

            if (\is_string($config)) {
                $config = json_decode($config, true) ?: [];
            }

            $choices = $config['choices'] ?? [];

            if (empty($choices)) {
                continue;
            }

            // Resolve value(s) to label(s)
            if ($fieldType === 'checkbox' && \is_array($value)) {
                // Multiple values for checkbox
                $rowValues[$slug] = array_map(
                    fn($v) => $this->resolveChoiceLabel($v, $choices, $langId),
                    $value
                );
            } else {
                // Single value for select/radio
                $rowValues[$slug] = $this->resolveChoiceLabel($value, $choices, $langId);
            }
        }

        return $rowValues;
    }

    /**
     * Resolve a single choice value to its label.
     *
     * @param mixed $value Choice value
     * @param array<int, array<string, mixed>> $choices Available choices
     * @param int $langId Language ID
     *
     * @return string Resolved label or original value
     */
    private function resolveChoiceLabel(mixed $value, array $choices, int $langId): string
    {
        if (!\is_string($value) && !is_numeric($value)) {
            return (string) $value;
        }

        foreach ($choices as $choice) {
            if (($choice['value'] ?? '') === $value) {
                // Check for translation
                $translations = $choice['translations'] ?? [];

                // Keys in translations are strings ("1", "2"), not integers
                $langKey = (string) $langId;

                if (isset($translations[$langKey]) && $translations[$langKey] !== '') {
                    return (string) $translations[$langKey];
                }

                // Fallback to label
                if (!empty($choice['label'])) {
                    return (string) $choice['label'];
                }

                // Fallback to value
                return (string) $value;
            }
        }

        return (string) $value;
    }

    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================

    /**
     * Ensure context is set (auto-detect if not).
     */
    private function ensureContext(): void
    {
        if ($this->entityType !== null && $this->entityId !== null) {
            return;
        }

        if (!$this->contextOverridden) {
            $detected = $this->contextDetector->detect();
            $this->entityType = $detected['entity_type'];
            $this->entityId = $detected['entity_id'];
            $this->shopId = $detected['shop_id'];
            $this->langId = $detected['lang_id'];
        }
    }

    /**
     * Get field value from cache or load all values.
     */
    private function getFieldValue(string $slug): mixed
    {
        $this->loadValues();

        $value = $this->cachedValues[$slug] ?? null;

        // If no value found, try to get default value from field config
        if ($value === null || $value === '') {
            $fieldDef = $this->getFieldDefinition($slug);
            if ($fieldDef !== null) {
                $config = $fieldDef['config'] ?? [];
                if (is_string($config)) {
                    $config = json_decode($config, true) ?: [];
                }
                $value = $config['defaultValue'] ?? null;
            }
        }

        return $value;
    }

    /**
     * Get field definition from cache or repository.
     *
     * @return array<string, mixed>|null
     */
    private function getFieldDefinition(string $slug): ?array
    {
        if ($this->cachedFields === null) {
            $this->cachedFields = [];
        }

        if (!isset($this->cachedFields[$slug])) {
            $field = $this->fieldRepository->findBySlug($slug);
            $this->cachedFields[$slug] = $field ?: [];
        }

        return $this->cachedFields[$slug] ?: null;
    }

    /**
     * Load all field values for current entity (eager loading).
     */
    private function loadValues(): void
    {
        if ($this->cachedValues !== null) {
            return;
        }

        $entityType = $this->getEntityType();
        $entityId = $this->getEntityId();

        if ($entityType === null || $entityId === null) {
            $this->cachedValues = [];

            return;
        }

        try {
            $this->cachedValues = $this->valueProvider->getEntityFieldValues(
                $entityType,
                $entityId,
                $this->shopId,
                $this->langId
            );
        } catch (Throwable $e) {
            $this->logError("Error loading values for {$entityType}#{$entityId}: " . $e->getMessage());
            $this->cachedValues = [];
        }
    }

    private function getEntityType(): ?string
    {
        return $this->entityType;
    }

    private function getEntityId(): ?int
    {
        return $this->entityId;
    }

    /**
     * Log error if debug mode is enabled.
     */
    private function logError(string $message): void
    {
        if (\defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
            PrestaShopLogger::addLog(
                '[ACF Front] ' . $message,
                2,
                null,
                'AcfFrontService',
                0
            );
        }
    }
}
