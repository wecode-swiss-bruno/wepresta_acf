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

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

final class SlugGenerator
{
    public function generate(string $input): string
    {
        $slug = mb_strtolower($input, 'UTF-8');
        $slug = preg_replace('/[àáâãäåāăą]/u', 'a', $slug) ?? $slug;
        $slug = preg_replace('/[èéêëēėę]/u', 'e', $slug) ?? $slug;
        $slug = preg_replace('/[ìíîïīį]/u', 'i', $slug) ?? $slug;
        $slug = preg_replace('/[òóôõöøōő]/u', 'o', $slug) ?? $slug;
        $slug = preg_replace('/[ùúûüūůűų]/u', 'u', $slug) ?? $slug;
        $slug = preg_replace('/[ýÿ]/u', 'y', $slug) ?? $slug;
        $slug = preg_replace('/[ñń]/u', 'n', $slug) ?? $slug;
        $slug = preg_replace('/[çćč]/u', 'c', $slug) ?? $slug;
        $slug = preg_replace('/[ß]/u', 'ss', $slug) ?? $slug;
        $slug = preg_replace('/[æ]/u', 'ae', $slug) ?? $slug;
        $slug = preg_replace('/[œ]/u', 'oe', $slug) ?? $slug;
        $slug = preg_replace('/[^a-z0-9\-_\s]/u', '', $slug) ?? $slug;
        $slug = preg_replace('/[\s\-]+/', '_', $slug) ?? $slug;
        $slug = preg_replace('/^_+|_+$/', '', $slug) ?? $slug;

        return substr($slug, 0, 255) ?: 'field';
    }

    public function generateUnique(string $input, callable $existsChecker, ?int $excludeId = null): string
    {
        $baseSlug = $this->generate($input);

        if (! $existsChecker($baseSlug, $excludeId)) {
            return $baseSlug;
        }
        $counter = 2;

        while ($counter < 100) {
            $testSlug = $baseSlug . '_' . $counter;

            if (! $existsChecker($testSlug, $excludeId)) {
                return $testSlug;
            }
            ++$counter;
        }

        return $baseSlug . '_' . time();
    }
}
