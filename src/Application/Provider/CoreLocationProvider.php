<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider;

/**
 * Core PrestaShop location provider
 */
final class CoreLocationProvider implements LocationProviderInterface
{
    public function getIdentifier(): string { return 'prestashop_core'; }
    public function getName(): string { return 'PrestaShop'; }

    public function getLocations(): array
    {
        return [
            ['type' => 'entity_type', 'value' => 'product', 'label' => 'Products', 'group' => 'PrestaShop', 'icon' => 'inventory_2', 'description' => 'Display fields on product edit pages'],
            ['type' => 'entity_type', 'value' => 'category', 'label' => 'Categories', 'group' => 'PrestaShop', 'icon' => 'folder', 'description' => 'Display fields on category edit pages'],
            ['type' => 'entity_type', 'value' => 'manufacturer', 'label' => 'Manufacturers', 'group' => 'PrestaShop', 'icon' => 'business', 'description' => 'Display fields on manufacturer edit pages'],
            ['type' => 'entity_type', 'value' => 'supplier', 'label' => 'Suppliers', 'group' => 'PrestaShop', 'icon' => 'local_shipping', 'description' => 'Display fields on supplier edit pages'],
            ['type' => 'entity_type', 'value' => 'cms', 'label' => 'CMS Pages', 'group' => 'PrestaShop', 'icon' => 'article', 'description' => 'Display fields on CMS page edit pages'],
        ];
    }

    public function matchLocation(array $rule, array $context): bool
    {
        $ruleType = $rule['type'] ?? '';
        $ruleValue = $rule['value'] ?? '';
        $ruleOperator = $rule['operator'] ?? 'equals';

        if ($ruleType === 'entity_type') {
            $contextEntityType = $context['entity_type'] ?? '';
            return match ($ruleOperator) {
                'equals' => $ruleValue === $contextEntityType || $ruleValue === 'all',
                'not_equals' => $ruleValue !== $contextEntityType,
                default => false,
            };
        }

        if ($ruleType === 'product_category') {
            $contextCategoryIds = $context['category_ids'] ?? [];
            if (isset($context['category_id'])) { $contextCategoryIds[] = $context['category_id']; }
            $ruleValueInt = (int) $ruleValue;
            return match ($ruleOperator) {
                'equals' => in_array($ruleValueInt, array_map('intval', $contextCategoryIds), true),
                'not_equals' => !in_array($ruleValueInt, array_map('intval', $contextCategoryIds), true),
                default => false,
            };
        }

        if ($ruleType === 'product_type') {
            $contextProductType = $context['product_type'] ?? '';
            return match ($ruleOperator) {
                'equals' => $ruleValue === $contextProductType,
                'not_equals' => $ruleValue !== $contextProductType,
                default => false,
            };
        }

        return false;
    }

    public function getPriority(): int { return 0; }
}

