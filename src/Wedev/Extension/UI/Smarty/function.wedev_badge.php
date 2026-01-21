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
 * WEDEV UI - Smarty Function: wedev_badge
 * =============================================================================
 * Affiche un badge Bootstrap.
 *
 * Usage:
 *   {wedev_badge text="New" variant="success"}
 *   {wedev_badge text="Sale" variant="danger" pill=true}
 *   {wedev_badge text="5" variant="primary" pill=true class="ml-2"}
 *
 * Paramètres:
 *   - text (string, required): Texte du badge
 *   - variant (string): Variante Bootstrap - 'primary', 'secondary', 'success', 'danger', etc.
 *   - pill (bool): Utiliser le style pill (arrondi)
 *   - class (string): Classes CSS additionnelles
 * =============================================================================
 */
if (! defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param array<string, mixed> $params
 */
function smarty_function_wedev_badge(array $params, Smarty_Internal_Template $template): string
{
    // Paramètres
    $text = $params['text'] ?? '';
    $variant = $params['variant'] ?? 'primary';
    $pill = isset($params['pill']) && $params['pill'];
    $class = $params['class'] ?? '';

    if (empty($text)) {
        return '';
    }

    // Validation de la variante
    $validVariants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

    if (! in_array($variant, $validVariants, true)) {
        $variant = 'primary';
    }

    // Construction des classes
    $badgeClass = 'badge badge-' . htmlspecialchars($variant, ENT_QUOTES, 'UTF-8');

    if ($pill) {
        $badgeClass .= ' badge-pill';
    }

    if ($class) {
        $badgeClass .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    }

    return sprintf(
        '<span class="%s">%s</span>',
        $badgeClass,
        htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
    );
}
