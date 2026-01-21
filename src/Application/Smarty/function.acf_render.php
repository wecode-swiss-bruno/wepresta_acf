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

/**
 * Smarty function: acf_render.
 *
 * Render an ACF field as HTML.
 *
 * Usage:
 *   {acf_render slug="product_image"}
 *   {acf_render slug="gallery" class="my-gallery"}
 *   {acf_render slug="video" entity_type="product" entity_id=123}
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}


if (! defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Application\Service\AcfServiceContainer;

/**
 * @param array<string, mixed> $params
 */
function smarty_function_acf_render(array $params, Smarty_Internal_Template $template): string
{
    $slug = $params['slug'] ?? '';

    if ($slug === '') {
        return '';
    }

    $entityType = $params['entity_type'] ?? null;
    $entityId = isset($params['entity_id']) ? (int) $params['entity_id'] : null;

    // Pass remaining params as render options
    $options = array_diff_key($params, array_flip(['slug', 'entity_type', 'entity_id']));

    try {
        $service = AcfServiceContainer::getFrontService();

        // Override context if specified
        if ($entityType !== null && $entityId !== null) {
            $service = $service->forEntity($entityType, $entityId);
        }

        return $service->render($slug, $options);
    } catch (Throwable $e) {
        if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
            return '<!-- ACF Render Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
        }

        return '';
    }
}
