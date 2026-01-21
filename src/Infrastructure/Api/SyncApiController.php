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

namespace WeprestaAcf\Infrastructure\Api;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use WeprestaAcf\Application\Service\SyncService;
use WeprestaAcf\Application\Service\SyncStatusResolver;

/**
 * Sync API Controller - Handles JSON sync operations.
 */
final class SyncApiController extends AbstractApiController
{
    public function __construct(
        private readonly SyncService $syncService,
        private readonly SyncStatusResolver $statusResolver
    ) {
    }

    /**
     * Get sync status for all groups.
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $status = $this->syncService->getSyncStatus();

            return $this->jsonSuccess([
                'enabled' => $this->syncService->isEnabled(),
                'sync_path' => $this->syncService->getSyncPath(),
                'groups_path' => $this->syncService->getGroupsPath(),
                'db_count' => $status['db_count'],
                'theme_count' => $status['theme_count'],
                'synced' => $status['synced'],
                'need_push' => $status['need_push'],
                'need_pull' => $status['need_pull'],
                'groups' => $status['groups'],
            ]);
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get sync status for a single group.
     */
    public function groupStatus(int $groupId, Request $request): JsonResponse
    {
        try {
            $status = $this->statusResolver->resolveForGroup($groupId);

            return $this->jsonSuccess($status);
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Push a group to theme JSON.
     */
    public function push(int $groupId, Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pushGroup($groupId);

            return $this->json(
                ['success' => $result['success'], 'data' => $result],
                $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST
            );
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Pull a group from theme JSON.
     */
    public function pull(string $slug, Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pullGroup($slug);

            return $this->json(
                ['success' => $result['success'], 'data' => $result],
                $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST
            );
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Push all groups to theme JSON.
     */
    public function pushAll(Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pushAllGroups();

            return $this->jsonSuccess($result);
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Pull all groups from theme JSON.
     */
    public function pullAll(Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pullAllGroups();

            return $this->jsonSuccess($result);
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Download a group as JSON file.
     */
    public function export(int $groupId, Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pushGroup($groupId);

            if (! $result['success']) {
                return $this->jsonError($result['error'] ?? 'Export failed', Response::HTTP_BAD_REQUEST);
            }

            // Read the generated file
            $filePath = $result['path'];

            if (! file_exists($filePath)) {
                return $this->jsonError('File not found after export', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $content = file_get_contents($filePath);

            return $this->jsonSuccess([
                'filename' => basename($filePath),
                'content' => json_decode($content, true),
            ]);
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }
}
