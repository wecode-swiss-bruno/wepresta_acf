<?php

/**
 * Smarty function: acf_render
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

use WeprestaAcf\Application\Service\AcfServiceContainer;

/**
 * @param array<string, mixed> $params
 * @param Smarty_Internal_Template $template
 *
 * @return string
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
