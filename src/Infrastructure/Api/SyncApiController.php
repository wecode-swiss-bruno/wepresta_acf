<?php
/**
 * SyncApiController - API endpoints for JSON sync operations
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeprestaAcf\Application\Service\SyncService;
use WeprestaAcf\Application\Service\SyncStatusResolver;

class SyncApiController extends FrameworkBundleAdminController
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

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'enabled' => $this->syncService->isEnabled(),
                    'sync_path' => $this->syncService->getSyncPath(),
                    'groups_path' => $this->syncService->getGroupsPath(),
                    'db_count' => $status['db_count'],
                    'theme_count' => $status['theme_count'],
                    'synced' => $status['synced'],
                    'need_push' => $status['need_push'],
                    'need_pull' => $status['need_pull'],
                    'groups' => $status['groups'],
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status for a single group.
     */
    public function groupStatus(int $groupId, Request $request): JsonResponse
    {
        try {
            $status = $this->statusResolver->resolveForGroup($groupId);

            return new JsonResponse([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Push a group to theme JSON.
     */
    public function push(int $groupId, Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pushGroup($groupId);

            return new JsonResponse([
                'success' => $result['success'],
                'data' => $result,
            ], $result['success'] ? 200 : 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pull a group from theme JSON.
     */
    public function pull(string $slug, Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pullGroup($slug);

            return new JsonResponse([
                'success' => $result['success'],
                'data' => $result,
            ], $result['success'] ? 200 : 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Push all groups to theme JSON.
     */
    public function pushAll(Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pushAllGroups();

            return new JsonResponse([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pull all groups from theme JSON.
     */
    public function pullAll(Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pullAllGroups();

            return new JsonResponse([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a group as JSON file.
     */
    public function export(int $groupId, Request $request): JsonResponse
    {
        try {
            $result = $this->syncService->pushGroup($groupId);

            if (!$result['success']) {
                return new JsonResponse([
                    'success' => false,
                    'error' => $result['error'] ?? 'Export failed',
                ], 400);
            }

            // Read the generated file
            $filePath = $result['path'];
            if (!file_exists($filePath)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'File not found after export',
                ], 500);
            }

            $content = file_get_contents($filePath);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'filename' => basename($filePath),
                    'content' => json_decode($content, true),
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

