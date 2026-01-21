<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

/**
 * Smarty function: acf_field.
 *
 * Get an ACF field value (escaped).
 *
 * Usage:
 *   {acf_field slug="brand"}
 *   {acf_field slug="brand" default="N/A"}
 *   {acf_field slug="brand" entity_type="product" entity_id=123}
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

if (! defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Application\Service\AcfServiceContainer;

/**
 * @param array<string, mixed> $params
 */
function smarty_function_acf_field(array $params, Smarty_Internal_Template $template): string
{
    $slug = $params['slug'] ?? '';

    if ($slug === '') {
        return '';
    }

    $default = $params['default'] ?? '';
    $entityType = $params['entity_type'] ?? null;
    $entityId = isset($params['entity_id']) ? (int) $params['entity_id'] : null;

    try {
        $service = AcfServiceContainer::getFrontService();

        // Override context if specified
        if ($entityType !== null && $entityId !== null) {
            $service = $service->forEntity($entityType, $entityId);
        }

        $value = $service->field($slug, $default);

        // Convert to string for output
        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    } catch (Throwable $e) {
        if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
            return '<!-- ACF Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
        }

        return (string) $default;
    }
}
