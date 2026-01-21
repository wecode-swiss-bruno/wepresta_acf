<?php

/**
 * Entity Context Detector.
 *
 * Automatically detects the current entity context from PrestaShop's
 * front-office controllers and request parameters.
 *
 * Supports:
 * - ProductController -> product
 * - CategoryController -> category
 * - CmsController -> cms_page
 * - ManufacturerController -> manufacturer
 * - SupplierController -> supplier
 * - Custom entity types via hooks
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Context;
use Hook;
use Tools;

final class EntityContextDetector
{
    /**
     * Controller to entity type mapping.
     *
     * @var array<string, array{entity_type: string, id_param: string}>
     */
    private const CONTROLLER_MAP = [
        'product' => [
            'entity_type' => 'product',
            'id_param' => 'id_product',
        ],
        'category' => [
            'entity_type' => 'category',
            'id_param' => 'id_category',
        ],
        'cms' => [
            'entity_type' => 'cms_page',
            'id_param' => 'id_cms',
        ],
        'page' => [
            'entity_type' => 'cms_page',
            'id_param' => 'id_cms',
        ],
        'manufacturer' => [
            'entity_type' => 'manufacturer',
            'id_param' => 'id_manufacturer',
        ],
        'supplier' => [
            'entity_type' => 'supplier',
            'id_param' => 'id_supplier',
        ],
        'order' => [
            'entity_type' => 'order',
            'id_param' => 'id_order',
        ],
        'cart' => [
            'entity_type' => 'cart',
            'id_param' => 'id_cart',
        ],
        // Customer account pages (all use logged-in customer)

        'my-account' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer
        ],
        'identity' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer
        ],
        'history' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer
        ],
        'order-detail' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer (order context available via id_order)
        ],
        'order-follow' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer
        ],
        'order-return' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer
        ],
        'order-slip' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer
        ],
        'addresses' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer
        ],
        'discount' => [
            'entity_type' => 'customer',
            'id_param' => '', // Use logged-in customer
        ],
        'address' => [
            'entity_type' => 'customer_address',
            'id_param' => 'id_address',
        ],
    ];

    /**
     * Detect current entity context.
     *
     * @return array{entity_type: string|null, entity_id: int|null, shop_id: int|null, lang_id: int|null}
     */
    public function detect(): array
    {
        $context = Context::getContext();

        $result = [
            'entity_type' => null,
            'entity_id' => null,
            'shop_id' => $this->getShopId($context),
            'lang_id' => $this->getLangId($context),
        ];

        // Try to detect from controller
        $controllerName = $this->getControllerName($context);

        if ($controllerName !== null && isset(self::CONTROLLER_MAP[$controllerName])) {
            $mapping = self::CONTROLLER_MAP[$controllerName];
            $result['entity_type'] = $mapping['entity_type'];
            $result['entity_id'] = $this->getEntityId($mapping, $context);
        }

        // Allow other modules to override/extend detection
        $hookResult = Hook::exec('actionAcfDetectContext', [
            'context' => $context,
            'detected' => &$result,
        ], null, true);

        if (\is_array($hookResult)) {
            foreach ($hookResult as $moduleResult) {
                if (\is_array($moduleResult) && !empty($moduleResult['entity_type'])) {
                    $result = array_merge($result, $moduleResult);

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Detect context for a specific controller name.
     *
     * @return array{entity_type: string|null, entity_id: int|null}
     */
    public function detectForController(string $controllerName): array
    {
        $result = [
            'entity_type' => null,
            'entity_id' => null,
        ];

        $controllerName = strtolower($controllerName);

        if (isset(self::CONTROLLER_MAP[$controllerName])) {
            $mapping = self::CONTROLLER_MAP[$controllerName];
            $result['entity_type'] = $mapping['entity_type'];
            $result['entity_id'] = $this->getEntityIdFromParam($mapping['id_param']);
        }

        return $result;
    }

    /**
     * Check if current page is an entity page.
     */
    public function isEntityPage(): bool
    {
        $detected = $this->detect();

        return $detected['entity_type'] !== null && $detected['entity_id'] !== null;
    }

    /**
     * Get entity type for current page.
     */
    public function getEntityType(): ?string
    {
        return $this->detect()['entity_type'];
    }

    /**
     * Get entity ID for current page.
     */
    public function getCurrentEntityId(): ?int
    {
        return $this->detect()['entity_id'];
    }

    /**
     * Check if current page is a product page.
     */
    public function isProductPage(): bool
    {
        return $this->getControllerName(Context::getContext()) === 'product';
    }

    /**
     * Check if current page is a category page.
     */
    public function isCategoryPage(): bool
    {
        return $this->getControllerName(Context::getContext()) === 'category';
    }

    /**
     * Check if current page is a CMS page.
     */
    public function isCmsPage(): bool
    {
        return $this->getControllerName(Context::getContext()) === 'cms';
    }

    /**
     * Get available entity types.
     *
     * @return array<string, string> Map of controller => entity_type
     */
    public function getAvailableEntityTypes(): array
    {
        $types = [];

        foreach (self::CONTROLLER_MAP as $controller => $mapping) {
            $types[$controller] = $mapping['entity_type'];
        }

        return $types;
    }

    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================

    /**
     * Get current controller name.
     */
    private function getControllerName(?Context $context): ?string
    {
        // Try from context controller
        if ($context !== null && isset($context->controller)) {
            $controller = $context->controller;

            if (\is_object($controller) && isset($controller->php_self)) {
                return strtolower($controller->php_self);
            }
        }

        // Fallback to Tools::getValue
        $controller = Tools::getValue('controller');

        if (\is_string($controller) && $controller !== '') {
            return strtolower($controller);
        }

        return null;
    }

    /**
     * Get entity ID from mapping.
     *
     * @param array{entity_type: string, id_param: string} $mapping
     */
    private function getEntityId(array $mapping, ?Context $context): ?int
    {
        $idParam = $mapping['id_param'];
        $entityType = $mapping['entity_type'];

        // Special case: customer from session
        if ($idParam === '' && $entityType === 'customer') {
            return $this->getLoggedInCustomerId($context);
        }

        // Try URL parameter first
        $id = $this->getEntityIdFromParam($idParam);
        if ($id !== null) {
            return $id;
        }

        // Fallback: try to get ID from controller's loaded object
        if ($context !== null && isset($context->controller)) {
            $controller = $context->controller;

            // CMS pages: $controller->cms->id
            if ($entityType === 'cms_page' && isset($controller->cms) && is_object($controller->cms)) {
                $cmsId = (int) ($controller->cms->id ?? 0);
                if ($cmsId > 0) {
                    return $cmsId;
                }
            }

            // Products: $controller->product->id
            if ($entityType === 'product' && isset($controller->product) && is_object($controller->product)) {
                $productId = (int) ($controller->product->id ?? 0);
                if ($productId > 0) {
                    return $productId;
                }
            }

            // Categories: $controller->category->id
            if ($entityType === 'category' && isset($controller->category) && is_object($controller->category)) {
                $categoryId = (int) ($controller->category->id ?? 0);
                if ($categoryId > 0) {
                    return $categoryId;
                }
            }
        }

        return null;
    }

    /**
     * Get entity ID from request parameter.
     */
    private function getEntityIdFromParam(string $param): ?int
    {
        if ($param === '') {
            return null;
        }

        $id = (int) Tools::getValue($param, 0);

        return $id > 0 ? $id : null;
    }

    /**
     * Get logged-in customer ID.
     */
    private function getLoggedInCustomerId(?Context $context): ?int
    {
        if ($context === null || !isset($context->customer)) {
            return null;
        }

        $customerId = (int) $context->customer->id;

        return $customerId > 0 ? $customerId : null;
    }

    /**
     * Get current shop ID.
     */
    private function getShopId(?Context $context): ?int
    {
        if ($context === null || !isset($context->shop)) {
            return null;
        }

        $shopId = (int) $context->shop->id;

        return $shopId > 0 ? $shopId : null;
    }

    /**
     * Get current language ID.
     */
    private function getLangId(?Context $context): ?int
    {
        if ($context === null || !isset($context->language)) {
            return null;
        }

        $langId = (int) $context->language->id;

        return $langId > 0 ? $langId : null;
    }
}
