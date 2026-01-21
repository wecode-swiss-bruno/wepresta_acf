<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

/**
 * ACF Twig Extension.
 *
 * Provides Twig functions for accessing ACF fields in templates.
 *
 * Usage in Twig:
 *   {{ acf_field('brand') }}
 *   {{ acf_render('image') }}
 *   {{ acf_has('promo') }}
 *   {% for row in acf_repeater('specs') %}...{% endfor %}
 *   {% for field in acf_group('product_info') %}...{% endfor %}
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Twig;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Generator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WeprestaAcf\Application\Service\AcfFrontService;
use WeprestaAcf\Application\Service\AcfServiceContainer;
use WeprestaAcf\Application\Service\FieldRenderer;

final class AcfTwigExtension extends AbstractExtension
{
    private ?AcfFrontService $frontService = null;

    private ?FieldRenderer $fieldRenderer = null;

    public function __construct(
        ?AcfFrontService $frontService = null,
        ?FieldRenderer $fieldRenderer = null
    ) {
        $this->frontService = $frontService;
        $this->fieldRenderer = $fieldRenderer;
    }

    public function getName(): string
    {
        return 'acf';
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            // Field access
            new TwigFunction('acf_field', [$this, 'field']),
            new TwigFunction('acf_raw', [$this, 'raw']),
            new TwigFunction('acf_render', [$this, 'render'], ['is_safe' => ['html']]),
            new TwigFunction('acf_has', [$this, 'has']),

            // Repeater
            new TwigFunction('acf_repeater', [$this, 'repeater']),
            new TwigFunction('acf_count_repeater', [$this, 'countRepeater']),

            // Group
            new TwigFunction('acf_group', [$this, 'group']),
            new TwigFunction('acf_render_group', [$this, 'renderGroup'], ['is_safe' => ['html']]),

            // Context
            new TwigFunction('acf_for_product', [$this, 'forProduct']),
            new TwigFunction('acf_for_category', [$this, 'forCategory']),
            new TwigFunction('acf_for_entity', [$this, 'forEntity']),
            new TwigFunction('acf_context', [$this, 'getContext']),

            // Utility
            new TwigFunction('acf_all', [$this, 'all']),
            new TwigFunction('acf_fields', [$this, 'fields']),
        ];
    }

    // =========================================================================
    // FIELD ACCESS
    // =========================================================================

    /**
     * Get field value (escaped).
     *
     * @param string $slug Field slug
     * @param mixed $default Default value if empty
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return mixed Escaped field value
     */
    public function field(string $slug, mixed $default = '', ?string $entityType = null, ?int $entityId = null): mixed
    {
        $service = $this->getService($entityType, $entityId);

        return $service->field($slug, $default);
    }

    /**
     * Get raw field value (not escaped).
     *
     * @param string $slug Field slug
     * @param mixed $default Default value if empty
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return mixed Raw field value
     */
    public function raw(string $slug, mixed $default = '', ?string $entityType = null, ?int $entityId = null): mixed
    {
        $service = $this->getService($entityType, $entityId);

        return $service->raw($slug, $default);
    }

    /**
     * Render field as HTML.
     *
     * @param string $slug Field slug
     * @param array<string, mixed> $options Render options
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return string Rendered HTML
     */
    public function render(string $slug, array $options = [], ?string $entityType = null, ?int $entityId = null): string
    {
        $service = $this->getService($entityType, $entityId);

        return $service->render($slug, $options);
    }

    /**
     * Check if field has value.
     *
     * @param string $slug Field slug
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return bool True if field has non-empty value
     */
    public function has(string $slug, ?string $entityType = null, ?int $entityId = null): bool
    {
        $service = $this->getService($entityType, $entityId);

        return $service->has($slug);
    }

    // =========================================================================
    // REPEATER ACCESS
    // =========================================================================

    /**
     * Get repeater rows.
     *
     * @param string $slug Repeater field slug
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return array<int, array<string, mixed>> Repeater rows
     */
    public function repeater(string $slug, ?string $entityType = null, ?int $entityId = null): array
    {
        $service = $this->getService($entityType, $entityId);

        // Return array instead of generator for Twig compatibility
        return $service->getRepeaterRows($slug);
    }

    /**
     * Count repeater rows.
     *
     * @param string $slug Repeater field slug
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return int Number of rows
     */
    public function countRepeater(string $slug, ?string $entityType = null, ?int $entityId = null): int
    {
        $service = $this->getService($entityType, $entityId);

        return $service->countRepeater($slug);
    }

    // =========================================================================
    // GROUP ACCESS
    // =========================================================================

    /**
     * Get all fields from a group.
     *
     * @param int|string $groupIdOrSlug Group ID or slug
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return array<int, array<string, mixed>> Group fields
     */
    public function group(int|string $groupIdOrSlug, ?string $entityType = null, ?int $entityId = null): array
    {
        $service = $this->getService($entityType, $entityId);

        return $service->getGroupFields($groupIdOrSlug);
    }

    /**
     * Render all fields from a group as HTML.
     *
     * @param int|string $groupIdOrSlug Group ID or slug
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return string Rendered HTML
     */
    public function renderGroup(int|string $groupIdOrSlug, ?string $entityType = null, ?int $entityId = null): string
    {
        $service = $this->getService($entityType, $entityId);
        $renderer = $this->getRenderer();

        $fields = $service->getGroupFields($groupIdOrSlug);

        if (empty($fields)) {
            return '';
        }

        return $renderer->renderGroup($fields);
    }

    // =========================================================================
    // CONTEXT HELPERS
    // =========================================================================

    /**
     * Get service for a specific product.
     *
     * @param int $productId Product ID
     *
     * @return AcfFrontService Service with product context
     */
    public function forProduct(int $productId): AcfFrontService
    {
        return $this->getFrontService()->forProduct($productId);
    }

    /**
     * Get service for a specific category.
     *
     * @param int $categoryId Category ID
     *
     * @return AcfFrontService Service with category context
     */
    public function forCategory(int $categoryId): AcfFrontService
    {
        return $this->getFrontService()->forCategory($categoryId);
    }

    /**
     * Get service for a specific entity.
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     *
     * @return AcfFrontService Service with entity context
     */
    public function forEntity(string $entityType, int $entityId): AcfFrontService
    {
        return $this->getFrontService()->forEntity($entityType, $entityId);
    }

    /**
     * Get current context info.
     *
     * @return array{entity_type: string|null, entity_id: int|null, shop_id: int|null, lang_id: int|null}
     */
    public function getContext(): array
    {
        return $this->getFrontService()->getContext();
    }

    // =========================================================================
    // UTILITY
    // =========================================================================

    /**
     * Get all field values for current entity.
     *
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return array<string, mixed>
     */
    public function all(?string $entityType = null, ?int $entityId = null): array
    {
        $service = $this->getService($entityType, $entityId);

        return $service->all();
    }

    /**
     * Get multiple field values at once.
     *
     * @param array<string> $slugs List of field slugs
     * @param string|null $entityType Optional entity type override
     * @param int|null $entityId Optional entity ID override
     *
     * @return array<string, mixed>
     */
    public function fields(array $slugs, ?string $entityType = null, ?int $entityId = null): array
    {
        $service = $this->getService($entityType, $entityId);

        return $service->fields($slugs);
    }

    // =========================================================================
    // PRIVATE
    // =========================================================================

    /**
     * Get service with optional context override.
     */
    private function getService(?string $entityType, ?int $entityId): AcfFrontService
    {
        $service = $this->getFrontService();

        if ($entityType !== null && $entityId !== null) {
            return $service->forEntity($entityType, $entityId);
        }

        return $service;
    }

    private function getFrontService(): AcfFrontService
    {
        if ($this->frontService === null) {
            $this->frontService = AcfServiceContainer::getFrontService();
        }

        return $this->frontService;
    }

    private function getRenderer(): FieldRenderer
    {
        if ($this->fieldRenderer === null) {
            $this->fieldRenderer = AcfServiceContainer::getFieldRenderer();
        }

        return $this->fieldRenderer;
    }
}
