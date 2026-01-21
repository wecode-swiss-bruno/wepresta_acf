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
 * Smarty block: acf_foreach.
 *
 * Iterate over ACF repeater rows.
 *
 * Usage:
 *   {acf_foreach repeater="specifications" item="spec"}
 *       <tr>
 *           <td>{$spec.label}</td>
 *           <td>{$spec.value}</td>
 *       </tr>
 *   {/acf_foreach}
 *
 *   {* With entity override *}
 *   {acf_foreach repeater="features" item="f" entity_type="product" entity_id=123}
 *       <div>{$f.title}</div>
 *   {/acf_foreach}
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
function smarty_block_acf_foreach(array $params, ?string $content, Smarty_Internal_Template $template, bool &$repeat): string
{
    $repeaterSlug = $params['repeater'] ?? '';
    $itemVar = $params['item'] ?? 'row';
    $indexVar = $params['index'] ?? null;

    if ($repeaterSlug === '') {
        $repeat = false;

        return '';
    }

    // Get the data storage from template
    $dataKey = '_acf_foreach_' . $repeaterSlug;
    $indexKey = '_acf_foreach_index_' . $repeaterSlug;

    // First call (opening tag)
    if ($content === null) {
        try {
            $service = AcfServiceContainer::getFrontService();

            // Override context if specified
            $entityType = $params['entity_type'] ?? null;
            $entityId = isset($params['entity_id']) ? (int) $params['entity_id'] : null;

            if ($entityType !== null && $entityId !== null) {
                $service = $service->forEntity($entityType, $entityId);
            }

            // Get repeater rows as array
            $rows = $service->getRepeaterRows($repeaterSlug);

            if (empty($rows)) {
                $repeat = false;

                return '';
            }

            // Store rows and index in template
            $template->assign($dataKey, $rows);
            $template->assign($indexKey, 0);

            // Assign first row
            $template->assign($itemVar, $rows[0]);

            if ($indexVar !== null) {
                $template->assign($indexVar, 0);
            }

            $repeat = count($rows) > 1;

            return '';
        } catch (Throwable $e) {
            $repeat = false;

            if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
                return '<!-- ACF Foreach Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
            }

            return '';
        }
    }

    // Subsequent calls (closing tag, looping)
    $rows = $template->getTemplateVars($dataKey);
    $currentIndex = $template->getTemplateVars($indexKey);

    $output = $content;
    $nextIndex = $currentIndex + 1;

    if ($nextIndex < count($rows)) {
        // More rows to process
        $template->assign($indexKey, $nextIndex);
        $template->assign($itemVar, $rows[$nextIndex]);

        if ($indexVar !== null) {
            $template->assign($indexVar, $nextIndex);
        }

        $repeat = true;
    } else {
        // No more rows
        $repeat = false;

        // Clean up
        $template->clearAssign($dataKey);
        $template->clearAssign($indexKey);
    }

    return $output;
}
