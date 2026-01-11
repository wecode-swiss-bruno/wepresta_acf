<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider;

use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldRegistry;

/**
 * Core PrestaShop location provider.
 */
final class CoreLocationProvider implements LocationProviderInterface
{
    public function __construct(
        private readonly ?EntityFieldRegistry $entityFieldRegistry = null
    ) {
    }

    public function getIdentifier(): string
    {
        return 'prestashop_core';
    }

    public function getName(): string
    {
        return 'PrestaShop';
    }

    public function getLocations(): array
    {
        // No additional locations - only entity types from EntityHooksConfig
        return [];
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

            if (isset($context['category_id'])) {
                $contextCategoryIds[] = $context['category_id'];
            }
            $ruleValueInt = (int) $ruleValue;

            return match ($ruleOperator) {
                'equals' => \in_array($ruleValueInt, array_map('intval', $contextCategoryIds), true),
                'not_equals' => ! \in_array($ruleValueInt, array_map('intval', $contextCategoryIds), true),
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

    public function getPriority(): int
    {
        return 0;
    }
}
