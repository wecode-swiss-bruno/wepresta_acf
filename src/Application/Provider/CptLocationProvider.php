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

use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Location provider for Custom Post Types.
 * Dynamically loads CPT types from database and exposes them as ACF locations.
 */
final class CptLocationProvider implements LocationProviderInterface
{
    public function __construct(
        private readonly ?CptTypeRepositoryInterface $typeRepository = null
    ) {
    }

    public function getIdentifier(): string
    {
        return 'cpt';
    }

    public function getName(): string
    {
        return 'Custom Post Types';
    }

    public function getLocations(): array
    {
        $locations = [];

        try {
            $types = $this->typeRepository?->findAll() ?? [];
            $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');

            foreach ($types as $type) {
                if (!$type->isActive()) {
                    continue;
                }

                // Get name for current language (getName() can return array or string)
                $name = $type->getName($defaultLangId);
                if (is_array($name)) {
                    $name = reset($name) ?: $type->getSlug();
                }

                // Add each CPT type as a location option
                // Format: cpt_post:slug (e.g., cpt_post:blog)
                $locations[] = [
                    'type' => 'entity_type',
                    'value' => 'cpt_post:' . $type->getSlug(),
                    'label' => $name,
                    'group' => 'Custom Post Types',
                    'icon' => $type->getIcon() ?: 'article',
                    'description' => sprintf('Display fields on %s posts', $name),
                    'enabled' => true,
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if CPT tables don't exist yet
        }

        return $locations;
    }

    public function matchLocation(array $rule, array $context): bool
    {
        $ruleType = $rule['type'] ?? '';
        $ruleValue = $rule['value'] ?? '';
        $ruleOperator = $rule['operator'] ?? 'equals';

        // Handle cpt_post:slug format
        if ($ruleType === 'entity_type' && str_starts_with($ruleValue, 'cpt_post:')) {
        $contextEntityType = $context['entity_type'] ?? '';

            // First check if we're dealing with a cpt_post entity
        if ($contextEntityType !== 'cpt_post') {
                return $ruleOperator === 'not_equals';
            }

            // Extract the slug from the rule value
            $ruleSlug = substr($ruleValue, strlen('cpt_post:'));
            $contextSlug = $context['cpt_type_slug'] ?? '';

            return match ($ruleOperator) {
                'equals' => $ruleSlug === $contextSlug,
                'not_equals' => $ruleSlug !== $contextSlug,
                default => false,
            };
        }

            return false;
        }

    public function getPriority(): int
    {
        return 10; // Higher priority than core provider
    }
}
