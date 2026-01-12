<?php

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use Configuration;
use Exception;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use WeprestaAcf\Application\Form\SyncType;
use WeprestaAcf\Application\Service\AutoSyncService;
use WeprestaAcf\Application\Service\ExportImportService;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;

/**
 * Sync controller for Export/Import operations.
 */
class SyncController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly ExportImportService $exportImportService,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AutoSyncService $autoSyncService,
        private readonly TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    /**
     * Override trans() method for PS8/PS9 compatibility.
     * In PS8, translator is not available in the service locator.
     */
    protected function trans($key, $domain, array $parameters = [])
    {
        return $this->translator->trans($key, $parameters, $domain);
    }

    /**
     * Main sync page with Export/Import cards.
     */
    public function index(Request $request): Response
    {
        // Build form with current value
        $formData = [
            'auto_sync_enabled' => $this->autoSyncService->isEnabled(),
        ];
        $form = $this->createForm(SyncType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $enabled = ! empty($data['auto_sync_enabled']) ? 1 : 0;
            Configuration::updateValue('WEPRESTA_ACF_AUTO_SYNC_ENABLED', $enabled);

            $this->addFlash('success', $this->trans('Settings saved successfully.', 'Admin.Notifications.Success'));

            return $this->redirectToRoute('wepresta_acf_sync');
        }

        // Check sync status only if auto-sync is enabled
        $autoSyncEnabled = $this->autoSyncService->isEnabled();
        $fileInfo = null;
        $syncStatus = null;

        if ($autoSyncEnabled) {
            $fileInfo = $this->autoSyncService->getFileInfo();
            $syncStatus = $this->autoSyncService->getSyncStatus();
        }

        return $this->render('@Modules/wepresta_acf/views/templates/admin/sync/index.html.twig', [
            'layoutTitle' => $this->trans('Export / Import', 'Modules.Weprestaacf.Admin'),
            'syncForm' => $form->createView(),
            'autoSyncEnabled' => $autoSyncEnabled,
            'fileInfo' => $fileInfo,
            'syncStatus' => $syncStatus,
        ]);
    }

    /**
     * Export page with list of groups.
     */
    public function export(): Response
    {
        $groups = $this->groupRepository->findAll();
        $groupsWithCount = [];

        foreach ($groups as $group) {
            $groupId = (int) $group['id_wepresta_acf_group'];
            $fieldCount = $this->exportImportService->getFieldCount($groupId);
            $groupsWithCount[] = [
                'id' => $groupId,
                'title' => $group['title'],
                'slug' => $group['slug'],
                'field_count' => $fieldCount,
                'entity_type' => $this->extractEntityType($group['location_rules'] ?? '{}'),
            ];
        }

        return $this->render('@Modules/wepresta_acf/views/templates/admin/sync/export.html.twig', [
            'groups' => $groupsWithCount,
            'layoutTitle' => $this->trans('Export', 'Modules.Weprestaacf.Admin'),
        ]);
    }

    /**
     * Import page with drag & drop.
     */
    public function import(): Response
    {
        return $this->render('@Modules/wepresta_acf/views/templates/admin/sync/import.html.twig', [
            'layoutTitle' => $this->trans('Import', 'Modules.Weprestaacf.Admin'),
        ]);
    }

    /**
     * Export all groups as JSON download.
     */
    public function exportAll(Request $request): Response
    {
        try {
            $data = $this->exportImportService->exportAll();
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $filename = 'acf-export-all-' . date('Y-m-d') . '.json';

            $response = new Response($json);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

            return $response;
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export a single group as JSON download.
     */
    public function exportGroup(int $groupId, Request $request): Response
    {
        try {
            $data = $this->exportImportService->exportGroup($groupId);

            if ($data === null) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Group not found',
                ], 404);
            }

            $group = $this->groupRepository->findById($groupId);
            $slug = $group['slug'] ?? 'group-' . $groupId;
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $filename = 'acf-group-' . $slug . '.json';

            $response = new Response($json);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

            return $response;
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate import file before import.
     */
    public function validateFile(Request $request): JsonResponse
    {
        try {
            $uploadedFile = $request->files->get('file');

            if (! $uploadedFile) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'No file uploaded',
                ], 400);
            }

            // Check file extension
            if ($uploadedFile->getClientOriginalExtension() !== 'json') {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid file type. Only JSON files are allowed.',
                ], 400);
            }

            // Read and parse JSON
            $content = file_get_contents($uploadedFile->getPathname());

            if ($content === false) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Cannot read file',
                ], 400);
            }

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON: ' . json_last_error_msg(),
                ], 400);
            }

            // Validate structure
            $validation = $this->exportImportService->validateImportData($data);

            if (! $validation['valid']) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validation['errors'],
                ], 400);
            }

            // Count groups and fields for preview
            $groups = $data['groups'] ?? [];

            if (empty($groups) && isset($data['group'])) {
                $groups = [$data['group']];
            }

            $totalFields = 0;

            foreach ($groups as $group) {
                $totalFields += \count($group['fields'] ?? []);
            }

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'groups_count' => \count($groups),
                    'fields_count' => $totalFields,
                ],
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import groups from JSON file (API endpoint).
     */
    public function importFile(Request $request): JsonResponse
    {
        try {
            $uploadedFile = $request->files->get('file');

            if (! $uploadedFile) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'No file uploaded',
                ], 400);
            }

            $mode = $request->request->get('mode', 'merge');

            if (! \in_array($mode, ['replace', 'merge'], true)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid mode. Must be "replace" or "merge"',
                ], 400);
            }

            // Read and parse JSON
            $content = file_get_contents($uploadedFile->getPathname());

            if ($content === false) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Cannot read file',
                ], 400);
            }

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON: ' . json_last_error_msg(),
                ], 400);
            }

            // Import
            $result = $mode === 'replace'
                ? $this->exportImportService->importReplace($data)
                : $this->exportImportService->importMerge($data);

            return new JsonResponse($result->toArray());
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle auto-sync enabled/disabled.
     */
    public function toggleAutoSync(Request $request): JsonResponse
    {
        try {
            $enabled = (bool) $request->request->get('enabled', false);
            Configuration::updateValue('WEPRESTA_ACF_AUTO_SYNC_ENABLED', $enabled ? 1 : 0);

            return new JsonResponse([
                'success' => true,
                'enabled' => $enabled,
                'message' => $enabled
                    ? $this->trans('Auto-sync enabled', 'Modules.Weprestaacf.Admin')
                    : $this->trans('Auto-sync disabled', 'Modules.Weprestaacf.Admin'),
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export configuration immediately (manual trigger).
     */
    public function exportNow(): JsonResponse
    {
        try {
            $this->autoSyncService->exportNow();
            $fileInfo = $this->autoSyncService->getFileInfo();

            return new JsonResponse([
                'success' => true,
                'message' => $this->trans('Configuration exported successfully', 'Modules.Weprestaacf.Admin'),
                'fileInfo' => $fileInfo,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import configuration from sync file.
     */
    public function importFromSync(Request $request): JsonResponse
    {
        try {
            $merge = (bool) $request->request->get('merge', true);
            $result = $this->autoSyncService->importFromFile($merge);

            return new JsonResponse($result->toArray());
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dismiss sync notification.
     */
    public function dismissSyncNotification(): JsonResponse
    {
        try {
            $this->autoSyncService->dismissNotification();

            return new JsonResponse([
                'success' => true,
                'message' => $this->trans('Notification dismissed', 'Modules.Weprestaacf.Admin'),
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync now: intelligently import or export based on sync status.
     */
    public function syncNow(Request $request): JsonResponse
    {
        try {
            if (! $this->autoSyncService->isEnabled()) {
                return new JsonResponse([
                    'success' => false,
                    'error' => $this->trans('Auto-sync is disabled', 'Modules.Weprestaacf.Admin'),
                ], 400);
            }

            $result = $this->autoSyncService->syncNow();

            if ($result->isSuccess()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => $result->getMessage() ?: $this->trans('Synchronization completed', 'Modules.Weprestaacf.Admin'),
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => $result->getMessage() ?: $this->trans('Synchronization failed', 'Modules.Weprestaacf.Admin'),
            ], 500);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract entity type from location rules.
     */
    private function extractEntityType(string $locationRulesJson): string
    {
        try {
            $rules = json_decode($locationRulesJson, true);

            if (! \is_array($rules)) {
                return 'unknown';
            }

            // Try to find entity_type in location rules
            if (isset($rules['and']) && \is_array($rules['and'])) {
                foreach ($rules['and'] as $rule) {
                    if (isset($rule['==']) && \is_array($rule['=='])) {
                        $var = $rule['=='][0] ?? null;

                        if (isset($var['var']) && $var['var'] === 'entity_type') {
                            return $rule['=='][1] ?? 'unknown';
                        }
                    }
                }
            }

            return 'unknown';
        } catch (Exception $e) {
            return 'unknown';
        }
    }
}
