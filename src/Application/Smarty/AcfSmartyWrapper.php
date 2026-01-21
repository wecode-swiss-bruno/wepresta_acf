<?php

/**
 * ACF Smarty Wrapper.
 *
 * This class wraps AcfFrontService to provide a clean API for Smarty templates.
 * It is assigned to the $acf variable in templates via hookDisplayHeader.
 *
 * Usage in Smarty:
 *   {$acf->field('brand')}
 *   {$acf->render('image')}
 *   {$acf->has('promo')}
 *   {foreach $acf->repeater('specs') as $row}...{/foreach}
 *   {$acf->forProduct(123)->field('brand')}
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Smarty;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Generator;
use WeprestaAcf\Application\Service\AcfFrontService;
use WeprestaAcf\Application\Service\AcfServiceContainer;

final class AcfSmartyWrapper
{
    private ?AcfFrontService $service = null;

    // =========================================================================
    // FIELD ACCESS
    // =========================================================================

    /**
     * Get field value (escaped).
     *
     * @param string $slug Field slug
     * @param mixed $default Default value if empty
     *
     * @return mixed Escaped field value
     */
    public function field(string $slug, mixed $default = ''): mixed
    {
        return $this->getService()->field($slug, $default);
    }

    /**
     * Get raw field value (not escaped).
     *
     * @param string $slug Field slug
     * @param mixed $default Default value if empty
     *
     * @return mixed Raw field value
     */
    public function raw(string $slug, mixed $default = ''): mixed
    {
        return $this->getService()->raw($slug, $default);
    }

    /**
     * Render field as HTML.
     *
     * @param string $slug Field slug
     * @param array<string, mixed> $options Render options
     *
     * @return string Rendered HTML
     */
    public function render(string $slug, array $options = []): string
    {
        return $this->getService()->render($slug, $options);
    }

    /**
     * Check if field has value.
     *
     * @param string $slug Field slug
     *
     * @return bool True if field has non-empty value
     */
    public function has(string $slug): bool
    {
        return $this->getService()->has($slug);
    }

    // =========================================================================
    // REPEATER ACCESS
    // =========================================================================

    /**
     * Get repeater rows as iterable.
     *
     * @param string $slug Repeater field slug
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function repeater(string $slug): Generator
    {
        return $this->getService()->repeater($slug);
    }

    /**
     * Get repeater rows as array.
     *
     * @param string $slug Repeater field slug
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRepeaterRows(string $slug): array
    {
        return $this->getService()->getRepeaterRows($slug);
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
        return $this->getService()->countRepeater($slug);
    }

    // =========================================================================
    // GROUP ACCESS
    // =========================================================================

    /**
     * Get all fields from a group.
     *
     * @param int|string $groupIdOrSlug Group ID or slug
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function group(int|string $groupIdOrSlug): Generator
    {
        return $this->getService()->group($groupIdOrSlug);
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
        return $this->getService()->getGroupFields($groupIdOrSlug);
    }

    /**
     * Get all active groups for the current context.
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function getActiveGroups(): Generator
    {
        return $this->getService()->getActiveGroups();
    }

    // =========================================================================
    // CONTEXT OVERRIDE
    // =========================================================================

    /**
     * Override context for a specific product.
     *
     * @return self New wrapper with overridden context
     */
    public function forProduct(int $productId): self
    {
        $wrapper = new self();
        $wrapper->service = $this->getService()->forProduct($productId);

        return $wrapper;
    }

    /**
     * Override context for a specific category.
     *
     * @return self New wrapper with overridden context
     */
    public function forCategory(int $categoryId): self
    {
        $wrapper = new self();
        $wrapper->service = $this->getService()->forCategory($categoryId);

        return $wrapper;
    }

    /**
     * Override context for a specific CMS page.
     *
     * @return self New wrapper with overridden context
     */
    public function forCms(int $cmsId): self
    {
        $wrapper = new self();
        $wrapper->service = $this->getService()->forCms($cmsId);

        return $wrapper;
    }

    /**
     * Override context for a specific customer.
     *
     * @return self New wrapper with overridden context
     */
    public function forCustomer(int $customerId): self
    {
        $wrapper = new self();
        $wrapper->service = $this->getService()->forCustomer($customerId);

        return $wrapper;
    }

    /**
     * Override context for any entity type.
     *
     * @return self New wrapper with overridden context
     */
    public function forEntity(string $entityType, int $entityId): self
    {
        $wrapper = new self();
        $wrapper->service = $this->getService()->forEntity($entityType, $entityId);

        return $wrapper;
    }

    /**
     * Override shop context.
     *
     * @return self New wrapper with overridden shop
     */
    public function forShop(int $shopId): self
    {
        $wrapper = new self();
        $wrapper->service = $this->getService()->forShop($shopId);

        return $wrapper;
    }

    /**
     * Override language context.
     *
     * @return self New wrapper with overridden language
     */
    public function forLang(int $langId): self
    {
        $wrapper = new self();
        $wrapper->service = $this->getService()->forLang($langId);

        return $wrapper;
    }

    // =========================================================================
    // UTILITY
    // =========================================================================

    /**
     * Get all field values for current entity.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->getService()->all();
    }

    /**
     * Get multiple field values at once.
     *
     * @param array<string> $slugs List of field slugs
     *
     * @return array<string, mixed>
     */
    public function fields(array $slugs): array
    {
        return $this->getService()->fields($slugs);
    }

    /**
     * Get current context info.
     *
     * @return array{entity_type: string|null, entity_id: int|null, shop_id: int|null, lang_id: int|null}
     */
    public function getContext(): array
    {
        return $this->getService()->getContext();
    }

    /**
     * Get or create the underlying service instance.
     */
    private function getService(): AcfFrontService
    {
        if ($this->service === null) {
            $this->service = AcfServiceContainer::getFrontService();
        }

        return $this->service;
    }

    /**
     * Allow conversion to string for safer template usage.
     */
    public function __toString(): string
    {
        return '';
    }
}
