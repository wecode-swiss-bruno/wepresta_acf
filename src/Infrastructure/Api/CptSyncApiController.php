<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Service\CptSyncService;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * API Controller for CPT Sync (Export/Import)
 */
final class CptSyncApiController extends AbstractApiController
{
    private CptSyncService $syncService;

    public function __construct(
        CptSyncService $syncService,
        ConfigurationAdapter $config,
        ContextAdapter $context
    ) {
        parent::__construct($config, $context);
        $this->syncService = $syncService;
    }

    /**
     * GET /api/cpt/sync/export/{typeId} - Export single type
     */
    public function exportType(int $typeId): JsonResponse
    {
        try {
            $export = $this->syncService->exportType($typeId);

            return $this->jsonSuccess($export);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/cpt/sync/export-all - Export all types
     */
    public function exportAll(): JsonResponse
    {
        try {
            $export = $this->syncService->exportAll();

            return $this->jsonSuccess($export);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/cpt/sync/export-file/{typeId} - Export to file
     */
    public function exportToFile(int $typeId): JsonResponse
    {
        try {
            $export = $this->syncService->exportType($typeId);
            $filepath = $this->syncService->saveToFile($export, $export['slug']);

            return $this->jsonSuccess([
                'filepath' => $filepath,
                'filename' => basename($filepath),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/cpt/sync/import - Import from JSON
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->jsonError('Invalid JSON', Response::HTTP_BAD_REQUEST);
            }

            $overwrite = (bool) $request->query->get('overwrite', false);

            $typeId = $this->syncService->importType($data, $overwrite);

            return $this->jsonSuccess([
                'success' => true,
                'type_id' => $typeId,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/cpt/sync/import-file - Import from uploaded file
     */
    public function importFile(Request $request): JsonResponse
    {
        try {
            $file = $request->files->get('file');

            if (!$file) {
                return $this->jsonError('No file uploaded', Response::HTTP_BAD_REQUEST);
            }

            $content = file_get_contents($file->getPathname());
            $data = json_decode($content, true);

            if (!$data) {
                return $this->jsonError('Invalid JSON file', Response::HTTP_BAD_REQUEST);
            }

            $overwrite = (bool) $request->request->get('overwrite', false);

            $typeId = $this->syncService->importType($data, $overwrite);

            return $this->jsonSuccess([
                'success' => true,
                'type_id' => $typeId,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
