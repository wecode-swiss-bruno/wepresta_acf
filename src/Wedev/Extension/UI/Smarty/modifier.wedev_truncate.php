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

if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * =============================================================================
 * WEDEV UI - Smarty Modifier: wedev_truncate
 * =============================================================================
 * Tronque un texte à une longueur donnée avec ellipsis.
 *
 * Usage:
 *   {$text|wedev_truncate:50}
 *   {$description|wedev_truncate:100:'...'}
 *   {$title|wedev_truncate:30:'…':true}
 *
 * Paramètres:
 *   - length (int): Longueur maximale (défaut: 50)
 *   - suffix (string): Suffixe à ajouter (défaut: '...')
 *   - breakWord (bool): Couper au milieu d'un mot (défaut: false)
 * =============================================================================
 */
if (! defined('_PS_VERSION_')) {
    exit;
}

function smarty_modifier_wedev_truncate(
    string $string,
    int $length = 50,
    string $suffix = '...',
    bool $breakWord = false
): string {
    if (mb_strlen($string, 'UTF-8') <= $length) {
        return $string;
    }

    $length -= mb_strlen($suffix, 'UTF-8');

    if ($length <= 0) {
        return $suffix;
    }

    $truncated = mb_substr($string, 0, $length, 'UTF-8');

    // Si on ne coupe pas les mots, on cherche le dernier espace
    if (! $breakWord) {
        $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');

        if ($lastSpace !== false && $lastSpace > $length * 0.5) {
            $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
        }
    }

    return rtrim($truncated) . $suffix;
}
