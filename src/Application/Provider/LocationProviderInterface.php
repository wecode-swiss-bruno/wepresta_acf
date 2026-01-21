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

/**
 * Interface for location providers.
 */
interface LocationProviderInterface
{
    public function getIdentifier(): string;

    public function getName(): string;

    /**
     * @return array<array{type: string, value: string, label: string, group: string, icon?: string, description?: string}>
     */
    public function getLocations(): array;

    /**
     * @param array<string, mixed> $rule @param array<string, mixed> $context
     */
    public function matchLocation(array $rule, array $context): bool;

    public function getPriority(): int;
}
