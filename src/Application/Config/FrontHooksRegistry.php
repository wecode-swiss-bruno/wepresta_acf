<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Config;

/**
 * Registry of all available front-office display hooks by entity type.
 * 
 * This registry provides a complete list of hooks where custom fields can be displayed
 * in the front-office, organized by entity type (product, category, etc.).
 * 
 * Each hook includes:
 * - value: Hook name (technical)
 * - label: Human-readable label
 * - description: Detailed description of where the hook displays
 * - ps_version: Minimum PrestaShop version (8 or 9)
 */
final class FrontHooksRegistry
{
    /**
     * All available front hooks for Product entity.
     * 
     * @return array<array{value: string, label: string, description: string, ps_version: int}>
     */
    public static function getProductHooks(): array
    {
        return [
            // Product page - Main content
            [
                'value' => 'displayProductAdditionalInfo',
                'label' => 'Product Additional Info',
                'description' => 'Product page - Below main product information, ideal for specifications or custom attributes',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayProductExtraContent',
                'label' => 'Product Extra Content (Tabs)',
                'description' => 'Product page - Creates additional tabs in the product details section',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayProductButtons',
                'label' => 'Product Action Buttons',
                'description' => 'Product page - Near the "Add to Cart" button area',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayProductActions',
                'label' => 'Product Actions Area',
                'description' => 'Product page - After the add to cart button',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayProductPriceBlock',
                'label' => 'Product Price Block',
                'description' => 'Product page - Near the price display area',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayAfterProductThumbs',
                'label' => 'After Product Thumbnails',
                'description' => 'Product page - Below the product image thumbnails',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayReassurance',
                'label' => 'Reassurance Block',
                'description' => 'Product page - Reassurance information area (trust badges, guarantees)',
                'ps_version' => 8,
            ],
            
            // Product listing
            [
                'value' => 'displayProductListReviews',
                'label' => 'Product List Reviews',
                'description' => 'Product listing - In the product card, near reviews area',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayProductListFunctionalButtons',
                'label' => 'Product List Functional Buttons',
                'description' => 'Product listing - Quick view, wishlist, compare buttons area',
                'ps_version' => 8,
            ],
            
            // Footer areas
            [
                'value' => 'displayFooterProduct',
                'label' => 'Product Footer',
                'description' => 'Product page - At the bottom of the product page',
                'ps_version' => 8,
            ],
        ];
    }

    /**
     * All available front hooks for Category entity.
     *
     * @return array<array{value: string, label: string, description: string, ps_version: int}>
     */
    public static function getCategoryHooks(): array
    {
        return [
            [
                'value' => 'displayFooterCategory',
                'label' => 'Category Footer',
                'description' => 'Category page - At the bottom of the category page',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayHeaderCategory',
                'label' => 'Category Header',
                'description' => 'Category page - At the top of the category page',
                'ps_version' => 8,
            ],
        ];
    }

    /**
     * All available front hooks for Customer entity.
     *
     * @return array<array{value: string, label: string, description: string, ps_version: int}>
     */
    public static function getCustomerHooks(): array
    {
        return [
            [
                'value' => 'displayCustomerAccount',
                'label' => 'Customer Account Page',
                'description' => 'My Account page - Main content area where customer info is displayed',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayCustomerAccountForm',
                'label' => 'Customer Account Form',
                'description' => 'Account edit page - After the customer information form',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayCustomerAccountFormTop',
                'label' => 'Customer Account Form Top',
                'description' => 'Account edit page - Before the customer information form',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayCustomerAccountTop',
                'label' => 'Customer Account Top',
                'description' => 'Account edit page - At the top of the customer information form',
                'ps_version' => 8,
            ],
            [
                'value' => 'displayCustomerLoginFormAfter',
                'label' => 'Customer Login Form After',
                'description' => 'Login page - After the customer login form',
                'ps_version' => 8,
            ],
        ];
    }

    /**
     * Get all hooks for a specific entity type.
     * 
     * @param string $entityType Entity type (product, category, etc.)
     * @return array<array{value: string, label: string, description: string, ps_version: int}>
     */
    public static function getHooksForEntity(string $entityType): array
    {
        return match (strtolower($entityType)) {
            'product' => self::getProductHooks(),
            'category' => self::getCategoryHooks(),
            'customer' => self::getCustomerHooks(),
            default => [],
        };
    }

    /**
     * Get default hook for an entity type.
     * Returns the recommended default hook to use when none is specified.
     * 
     * @param string $entityType Entity type
     * @return string Default hook name
     */
    public static function getDefaultHook(string $entityType): string
    {
        return match (strtolower($entityType)) {
            'product' => 'displayProductAdditionalInfo',
            'category' => 'displayHeaderCategory',
            'customer' => 'displayCustomerAccount',
            default => '',
        };
    }

    /**
     * Get all unique hook names (for module registration).
     * 
     * @return string[] Array of hook names
     */
    public static function getAllHookNames(): array
    {
        $hooks = [];

        foreach (self::getProductHooks() as $hook) {
            $hooks[] = $hook['value'];
        }

        foreach (self::getCategoryHooks() as $hook) {
            $hooks[] = $hook['value'];
        }

        foreach (self::getCustomerHooks() as $hook) {
            $hooks[] = $hook['value'];
        }

        return array_unique($hooks);
    }

    /**
     * Check if a hook is valid for an entity type.
     * 
     * @param string $entityType Entity type
     * @param string $hookName Hook name
     * @return bool True if valid
     */
    public static function isValidHook(string $entityType, string $hookName): bool
    {
        $hooks = self::getHooksForEntity($entityType);
        
        foreach ($hooks as $hook) {
            if ($hook['value'] === $hookName) {
                return true;
            }
        }
        
        return false;
    }
}

