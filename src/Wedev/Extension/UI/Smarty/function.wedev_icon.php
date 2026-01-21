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
 * WEDEV UI - Smarty Function: wedev_icon
 * =============================================================================
 * Affiche une icône Material Icons.
 *
 * Usage:
 *   {wedev_icon name="check" size="md"}
 *   {wedev_icon name="shopping_cart" size="lg" class="text-primary"}
 *
 * Paramètres:
 *   - name (string, required): Nom de l'icône Material Icons
 *   - size (string): Taille - 'sm' (16px), 'md' (20px), 'lg' (24px), 'xl' (32px)
 *   - class (string): Classes CSS additionnelles
 *   - style (string): Styles CSS inline additionnels
 * =============================================================================
 */
if (! defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param array<string, mixed> $params
 */
function smarty_function_wedev_icon(array $params, Smarty_Internal_Template $template): string
{
    // Paramètres
    $name = $params['name'] ?? 'help';
    $size = $params['size'] ?? 'md';
    $class = $params['class'] ?? '';
    $style = $params['style'] ?? '';

    // Mapping des tailles
    $sizes = [
        'xs' => '14px',
        'sm' => '16px',
        'md' => '20px',
        'lg' => '24px',
        'xl' => '32px',
        'xxl' => '48px',
    ];

    $fontSize = $sizes[$size] ?? '20px';

    // Construction du style
    $styleAttr = "font-size: {$fontSize}; vertical-align: middle;";

    if ($style) {
        $styleAttr .= ' ' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8');
    }

    // Construction de la classe
    $classAttr = 'material-icons';

    if ($class) {
        $classAttr .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    }

    return sprintf(
        '<i class="%s" style="%s">%s</i>',
        $classAttr,
        $styleAttr,
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
    );
}
