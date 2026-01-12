<?php

/**
 * Smarty function: acf_group
 *
 * Render all fields from an ACF group.
 *
 * Usage:
 *   {acf_group id=1}
 *   {acf_group slug="product_specs"}
 *   {acf_group id=1 entity_type="product" entity_id=123}
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
function smarty_function_acf_group(array $params, Smarty_Internal_Template $template): string
{
    $groupId = $params['id'] ?? null;
    $groupSlug = $params['slug'] ?? null;

    if ($groupId === null && $groupSlug === null) {
        return '';
    }

    $groupIdOrSlug = $groupId !== null ? (int) $groupId : $groupSlug;
    $entityType = $params['entity_type'] ?? null;
    $entityId = isset($params['entity_id']) ? (int) $params['entity_id'] : null;

    try {
        $service = AcfServiceContainer::getFrontService();
        $renderer = AcfServiceContainer::getFieldRenderer();

        // Override context if specified
        if ($entityType !== null && $entityId !== null) {
            $service = $service->forEntity($entityType, $entityId);
        }

        $fields = $service->getGroupFields($groupIdOrSlug);

        if (empty($fields)) {
            return '';
        }

        return $renderer->renderGroup($fields);
    } catch (Throwable $e) {
        if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
            return '<!-- ACF Group Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
        }

        return '';
    }
}
