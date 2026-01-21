<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider;


if (!defined('_PS_VERSION_')) {
    exit;
}

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
