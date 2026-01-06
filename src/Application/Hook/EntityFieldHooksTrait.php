<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Hook;

use WeprestaAcf\Application\Config\EntityHooksConfig;
use WeprestaAcf\Application\Service\EntityFieldService;
use WeprestaAcf\Application\Service\FormModifierService;

/**
 * Trait for handling entity field hooks.
 * Centralized hook management to keep main module file clean.
 */
trait EntityFieldHooksTrait
{
    // =========================================================================
    // V1 PRIORITY HOOKS - Explicitly defined for core entities
    // =========================================================================

    /**
     * Display hook for Product entity.
     */
    public function hookDisplayAdminProductsExtra(array $params): string
    {
        return $this->handleDisplayHook('product', $params, 'id_product');
    }

    public function hookActionProductUpdate(array $params): void
    {
        $this->handleActionHook('product', $params, 'id_product');
    }

    public function hookActionProductAdd(array $params): void
    {
        $this->handleActionHook('product', $params, 'id_product');
    }

    /**
     * Display hook for Category entity.
     */
    public function hookDisplayAdminCategoriesExtra(array $params): string
    {
        return $this->handleDisplayHook('category', $params, 'id_category');
    }

    public function hookActionCategoryUpdate(array $params): void
    {
        $this->handleActionHook('category', $params, 'id_category');
    }

    public function hookActionCategoryAdd(array $params): void
    {
        $this->handleActionHook('category', $params, 'id_category');
    }

    /**
     * Display hook for Customer entity.
     */
    public function hookDisplayAdminCustomers(array $params): string
    {
        return $this->handleDisplayHook('customer', $params, 'id_customer');
    }

    public function hookActionCustomerAccountUpdate(array $params): void
    {
        $this->handleActionHook('customer', $params, 'id_customer');
    }

    public function hookActionObjectCustomerUpdateAfter(array $params): void
    {
        $object = $params['object'] ?? null;
        if ($object instanceof \Customer) {
            $this->handleActionHook('customer', ['id_customer' => (int) $object->id], 'id_customer');
        }
    }

    /**
     * Display hook for Order entity.
     */
    public function hookDisplayAdminOrderMain(array $params): string
    {
        return $this->handleDisplayHook('order', $params, 'id_order');
    }

    public function hookActionObjectOrderUpdateAfter(array $params): void
    {
        $this->handleActionHook('order', $params, 'id_order');
    }

    public function hookActionOrderStatusUpdate(array $params): void
    {
        $this->handleActionHook('order', $params, 'id_order');
    }

    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        $this->handleActionHook('order', $params, 'id_order');
    }

    // =========================================================================
    // MAGIC HOOK HANDLER - Handles all other entities dynamically
    // =========================================================================

    /**
     * Magic method to handle all entity hooks dynamically.
     *
     * Intercepts hook calls for non-V1 entities and routes them appropriately.
     * Supports 40+ additional entity types via EntityHooksConfig.
     *
     * @param string $method Method name (e.g., 'hookActionCmsFormBuilderModifier')
     * @param array $args Method arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        if (!str_starts_with($method, 'hook')) {
            return null;
        }

        $hookName = substr($method, 4);
        $params = $args[0] ?? [];

        // Pattern 1: action{EntityName}FormBuilderModifier
        if (preg_match('/^action(\w+)FormBuilderModifier$/i', $hookName)) {
            $entityType = EntityHooksConfig::getEntityByHook($hookName);
            if ($entityType !== null && $this->isEntityEnabled($entityType)) {
                $this->handleFormBuilderModifierHook($entityType, $params);
            }

            return null;
        }

        // Pattern 2: actionAfter(Create|Update){EntityName}FormHandler
        if (preg_match('/^actionAfter(Create|Update)(\w+)FormHandler$/i', $hookName, $matches)) {
            $entityType = EntityHooksConfig::getEntityByHook($hookName);
            if ($entityType !== null && $this->isEntityEnabled($entityType)) {
                $this->handleFormHandlerHook($entityType, strtolower($matches[1]), $params);
            }

            return null;
        }

        // Pattern 3: actionObject{ClassName}(Add|Update)After
        if (preg_match('/^actionObject(\w+)(Add|Update)After$/i', $hookName)) {
            $entityType = EntityHooksConfig::getEntityByHook($hookName);
            if ($entityType !== null && $this->isEntityEnabled($entityType)) {
                $this->handleObjectModelHook($entityType, $params);
            }

            return null;
        }

        // Pattern 4: All display hooks (admin + front-office)
        // Admin hooks: displayAdmin{Xxx}
        // Front-office hooks: display{Xxx} (not starting with displayAdmin)
        if (preg_match('/^display/i', $hookName)) {
            $entityType = EntityHooksConfig::getEntityByHook($hookName);
            if ($entityType !== null && $this->isEntityEnabled($entityType)) {
                // Check if it's an admin hook (displayAdminXxx) or front-office hook
                if (preg_match('/^displayAdmin/i', $hookName)) {
                    // Back-office display hook
                    return $this->handleGenericDisplayHook($entityType, $params);
                } else {
                    // Front-office display hook
                    return $this->handleFrontOfficeHook($entityType, $hookName, $params);
                }
            }
        }

        return null;
    }

    // =========================================================================
    // PRIVATE HOOK HANDLERS
    // =========================================================================

    /**
     * Handle display hook - injects ACF fields HTML.
     */
    private function handleDisplayHook(string $entityType, array $params, string $idKey): string
    {
        if (!$this->isActive()) {
            return '';
        }

        try {
            $entityId = (int) ($params[$idKey] ?? $params['id'] ?? 0);
            if ($entityId <= 0) {
                return '';
            }

            $service = $this->getService(EntityFieldService::class);
            if ($service === null) {
                return '';
            }

            return $service->renderFieldsForEntity($this, $entityType, $entityId);
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">ACF Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    /**
     * Handle action hook - saves ACF field values.
     */
    private function handleActionHook(string $entityType, array $params, string $idKey): void
    {
        if (!$this->isActive()) {
            return;
        }

        try {
            $entityId = (int) ($params[$idKey] ?? $params['id'] ?? 0);
            if ($entityId <= 0) {
                return;
            }

            $service = $this->getService(EntityFieldService::class);
            if ($service === null) {
                return;
            }

            $service->saveFieldsFromRequest($this, $entityType, $entityId);
        } catch (\Exception $e) {
            $this->log("Error saving ACF fields for {$entityType}: " . $e->getMessage(), 3);
        }
    }

    /**
     * Handle FormBuilderModifier hook - adds ACF fields to Symfony forms.
     */
    private function handleFormBuilderModifierHook(string $entityType, array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        try {
            $formModifierService = $this->getService(FormModifierService::class);
            if ($formModifierService === null) {
                return;
            }

            $formBuilder = $params['form_builder'] ?? null;
            if (!$formBuilder instanceof \Symfony\Component\Form\FormBuilderInterface) {
                return;
            }

            $entityId = $formModifierService->getEntityIdFromParams($entityType, $params);
            $data = &$params['data'];

            $formModifierService->modifyForm($formBuilder, $entityType, $entityId, $data);
        } catch (\Exception $e) {
            $this->log("Error in FormBuilderModifier for {$entityType}: " . $e->getMessage(), 3);
        }
    }

    /**
     * Handle FormHandler hook - saves ACF field values after form submission.
     */
    private function handleFormHandlerHook(string $entityType, string $operation, array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        try {
            $formModifierService = $this->getService(FormModifierService::class);
            if ($formModifierService === null) {
                return;
            }

            $entityId = $formModifierService->getEntityIdFromParams($entityType, $params);
            if ($entityId === null || $entityId <= 0) {
                return;
            }

            $formData = $params['form_data'] ?? [];
            $formModifierService->saveAcfData($entityType, $entityId, $formData);
        } catch (\Exception $e) {
            $this->log("Error in FormHandler for {$entityType}: " . $e->getMessage(), 3);
        }
    }

    /**
     * Handle ObjectModel hook - saves ACF field values for legacy entities.
     */
    private function handleObjectModelHook(string $entityType, array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        $object = $params['object'] ?? null;
        if ($object === null || !method_exists($object, 'id')) {
            return;
        }

        $entityId = (int) $object->id;
        if ($entityId <= 0) {
            return;
        }

        $idKey = 'id_' . $entityType;
        $this->handleActionHook($entityType, [$idKey => $entityId], $idKey);
    }

    /**
     * Handle generic display hook for entities without explicit hook methods.
     */
    private function handleGenericDisplayHook(string $entityType, array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        $idKey = 'id_' . $entityType;
        $entityId = (int) ($params[$idKey] ?? $params['id'] ?? 0);

        if ($entityId <= 0) {
            return '';
        }

        return $this->handleDisplayHook($entityType, $params, $idKey);
    }

    /**
     * Handle front-office display hooks for entities.
     */
    private function handleFrontOfficeHook(string $entityType, string $hookName, array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        try {
            // Extract entity ID based on entity type and hook context
            $entityId = $this->extractEntityIdFromFrontOfficeHook($entityType, $hookName, $params);
            
            if ($entityId <= 0) {
                // Debug: log when entity ID is not found
                if (method_exists($this, 'log') && defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
                    $this->log("ACF Front-office: No entity ID found for {$entityType} in hook {$hookName}. Params: " . json_encode(array_keys($params)), 1);
                }
                return '';
            }

            // Use the module's renderEntityFieldsForDisplay method
            if (method_exists($this, 'renderEntityFieldsForDisplay')) {
                $result = $this->renderEntityFieldsForDisplay($entityType, $entityId);
                
                // Debug: log when no fields are found
                if (empty($result) && method_exists($this, 'log') && defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
                    $this->log("ACF Front-office: No fields found for {$entityType} #{$entityId} in hook {$hookName}", 1);
                }
                
                return $result;
            }

            return '';
        } catch (\Exception $e) {
            if (method_exists($this, 'log')) {
                $this->log("Error in front-office hook {$hookName}: " . $e->getMessage(), 3);
            }
            return '';
        }
    }

    /**
     * Extract entity ID from front-office hook parameters.
     */
    private function extractEntityIdFromFrontOfficeHook(string $entityType, string $hookName, array $params): int
    {
        // Product hooks
        if ($entityType === 'product') {
            $product = $params['product'] ?? null;
            return (int) ($product['id_product'] ?? ($product->id ?? 0));
        }

        // Category hooks
        if ($entityType === 'category') {
            // Try multiple ways to get category ID
            $categoryId = 0;
            
            // From params array
            if (isset($params['category'])) {
                if (is_array($params['category'])) {
                    $categoryId = (int) ($params['category']['id_category'] ?? $params['category']['id'] ?? 0);
                } elseif (is_object($params['category'])) {
                    $categoryId = (int) ($params['category']->id ?? $params['category']->id_category ?? 0);
                }
            }
            
            // Fallback: from context controller
            if ($categoryId <= 0 && isset($this->context->controller)) {
                $controller = $this->context->controller;
                if (isset($controller->category) && is_object($controller->category)) {
                    $categoryId = (int) ($controller->category->id ?? 0);
                }
            }
            
            // Fallback: from Tools::getValue (URL parameter)
            if ($categoryId <= 0) {
                $categoryId = (int) \Tools::getValue('id_category', 0);
            }
            
            return $categoryId;
        }

        // Customer hooks
        if ($entityType === 'customer') {
            if (isset($this->context->customer)) {
                return (int) ($this->context->customer->id ?? 0);
            }
            return 0;
        }

        // Order hooks
        if ($entityType === 'order') {
            return (int) ($params['order']['id_order'] ?? $params['order']->id ?? 0);
        }

        // Generic fallback
        $idKey = 'id_' . $entityType;
        return (int) ($params[$idKey] ?? $params['id'] ?? 0);
    }

    // =========================================================================
    // ENTITY FILTER CONFIGURATION
    // =========================================================================

    /**
     * Check if an entity type is enabled in current version.
     * 
     * V1: Core entities (product, category, customer, order)
     * Future: Add more entities progressively
     */
    private function isEntityEnabled(string $entityType): bool
    {
        return EntityHooksConfig::isEntityEnabled($entityType);
    }
}


