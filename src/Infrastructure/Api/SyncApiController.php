<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

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
