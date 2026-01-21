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
 * SyncStatusResolver - Resolve sync status per group.
 *
 * Lightweight class to check individual group sync status.
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Sync status constants and resolver for individual groups.
 */
final class SyncStatusResolver
{
    public const STATUS_SYNCED = 'synced';

    public const STATUS_MODIFIED = 'modified';

    public const STATUS_NEED_PUSH = 'need_push';

    public const STATUS_NEED_PULL = 'need_pull';

    public const STATUS_THEME_ONLY = 'theme_only';

    public const STATUS_CONFLICT = 'conflict';

    public function __construct(
        private readonly SyncService $syncService
    ) {
    }

    /**
     * Get status for a database group.
     */
    public function resolveForGroup(int $groupId): array
    {
        if (! $this->syncService->isEnabled()) {
            return [
                'status' => 'disabled',
                'label' => 'Sync disabled',
                'icon' => 'sync_disabled',
                'color' => 'secondary',
                'actions' => [],
            ];
        }

        $status = $this->syncService->getGroupSyncStatus($groupId);

        if ($status === null) {
            return [
                'status' => 'error',
                'label' => 'Group not found',
                'icon' => 'error',
                'color' => 'danger',
                'actions' => [],
            ];
        }

        return $this->mapStatusToDisplay($status['status'], 'database');
    }

    /**
     * Get status for a theme-only group.
     */
    public function resolveForThemeGroup(string $slug): array
    {
        if (! $this->syncService->isEnabled()) {
            return [
                'status' => 'disabled',
                'label' => 'Sync disabled',
                'icon' => 'sync_disabled',
                'color' => 'secondary',
                'actions' => [],
            ];
        }

        return $this->mapStatusToDisplay(self::STATUS_THEME_ONLY, 'theme');
    }

    /**
     * Get all available statuses for reference.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_SYNCED => 'Group is in sync with theme JSON',
            self::STATUS_MODIFIED => 'Group differs from theme JSON',
            self::STATUS_NEED_PUSH => 'Group exists in DB but not in theme',
            self::STATUS_NEED_PULL => 'Group exists in theme but not in DB',
            self::STATUS_THEME_ONLY => 'Group only exists in theme',
            self::STATUS_CONFLICT => 'Both DB and theme have different changes',
        ];
    }

    /**
     * Map status to display data.
     */
    private function mapStatusToDisplay(string $status, string $source): array
    {
        return match ($status) {
            self::STATUS_SYNCED => [
                'status' => $status,
                'label' => 'Synced',
                'icon' => 'check_circle',
                'color' => 'success',
                'actions' => ['push', 'export'],
            ],
            self::STATUS_MODIFIED => [
                'status' => $status,
                'label' => 'Modified',
                'icon' => 'edit',
                'color' => 'warning',
                'actions' => ['push', 'pull', 'diff', 'export'],
            ],
            self::STATUS_NEED_PUSH => [
                'status' => $status,
                'label' => 'Not in theme',
                'icon' => 'cloud_upload',
                'color' => 'info',
                'actions' => ['push', 'export'],
            ],
            self::STATUS_THEME_ONLY => [
                'status' => $status,
                'label' => 'Theme only',
                'icon' => 'cloud_download',
                'color' => 'primary',
                'actions' => ['pull'],
            ],
            self::STATUS_CONFLICT => [
                'status' => $status,
                'label' => 'Conflict',
                'icon' => 'warning',
                'color' => 'danger',
                'actions' => ['push', 'pull', 'diff'],
            ],
            default => [
                'status' => 'unknown',
                'label' => 'Unknown',
                'icon' => 'help',
                'color' => 'secondary',
                'actions' => [],
            ],
        };
    }
}
