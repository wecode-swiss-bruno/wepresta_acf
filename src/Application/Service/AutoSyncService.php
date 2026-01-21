<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

use DateTime;
use Exception;
use RuntimeException;
use WeprestaAcf\Application\Template\ImportResult;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Trait\LoggerTrait;

/**
 * Auto-Sync service for automatic export/import of ACF configuration.
 * Uses a "dirty flag" pattern with shutdown function to debounce exports.
 */
final class AutoSyncService
{
    use LoggerTrait;

    private const CONFIG_FILENAME = 'acf-config.json';

    private static bool $isDirty = false;

    private static bool $shutdownRegistered = false;

    public function __construct(
        private readonly ExportImportService $exportImportService,
        private readonly ConfigurationAdapter $config,
        private readonly string $modulePath
    ) {
    }

    /**
     * Check if auto-sync is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->config->getBool('WEPRESTA_ACF_AUTO_SYNC_ENABLED', false);
    }

    /**
     * Get the config file path.
     */
    public function getConfigFilePath(): string
    {
        return $this->modulePath . '/sync/' . self::CONFIG_FILENAME;
    }

    /**
     * Mark configuration as dirty (needs export).
     * Registers shutdown function on first call.
     */
    public function markDirty(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        self::$isDirty = true;

        // Register shutdown function only once
        if (! self::$shutdownRegistered) {
            $service = $this;
            register_shutdown_function(function () use ($service): void {
                $service->exportIfDirty();
            });
            self::$shutdownRegistered = true;
        }
    }

    /**
     * Export configuration if marked as dirty.
     * Called automatically at end of request via shutdown function.
     */
    public function exportIfDirty(): void
    {
        if (! self::$isDirty || ! $this->isEnabled()) {
            return;
        }

        try {
            $this->exportNow();
            self::$isDirty = false;
            $this->logInfo('Auto-sync: Configuration exported on shutdown');
        } catch (Exception $e) {
            $this->logError('Auto-sync: Export failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Export configuration immediately to sync file.
     */
    public function exportNow(): bool
    {
        try {
            // Ensure sync directory exists
            $syncDir = \dirname($this->getConfigFilePath());

            if (! is_dir($syncDir)) {
                if (! mkdir($syncDir, 0o755, true)) {
                    throw new RuntimeException('Cannot create sync directory: ' . $syncDir);
                }

                // Create security files
                $this->createSecurityFiles($syncDir);
            }

            // Export configuration
            $data = $this->exportImportService->exportAll();

            // Safety check: don't export if database is empty
            $groupsCount = \count($data['groups'] ?? []);

            if ($groupsCount === 0) {
                $this->logError('Auto-sync: Cannot export - database is empty');

                throw new RuntimeException('Cannot export: database is empty. Import data first.');
            }

            // Add timestamp for comparison
            $data['timestamp'] = time();
            $data['updated_at'] = date('c');

            // Write to file
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $filePath = $this->getConfigFilePath();

            if (file_put_contents($filePath, $json, LOCK_EX) === false) {
                throw new RuntimeException('Cannot write to sync file: ' . $filePath);
            }

            // Update last sync timestamp
            $this->config->set('WEPRESTA_ACF_SYNC_LAST_UPDATE', time());

            $this->logInfo('Auto-sync: Configuration exported', [
                'file' => $filePath,
                'groups_count' => $groupsCount,
            ]);

            return true;
        } catch (Exception $e) {
            $this->logError('Auto-sync: Export failed', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Get information about the sync file.
     *
     * @return array{exists: bool, path: string, timestamp?: int, updated_at?: string, groups_count?: int, fields_count?: int, size?: int}|null
     */
    public function getFileInfo(): ?array
    {
        $filePath = $this->getConfigFilePath();

        if (! file_exists($filePath)) {
            return [
                'exists' => false,
                'path' => $filePath,
            ];
        }

        try {
            $content = file_get_contents($filePath);

            if ($content === false) {
                return null;
            }

            $data = json_decode($content, true);

            if (! \is_array($data)) {
                return null;
            }

            $groupsCount = \count($data['groups'] ?? []);
            $fieldsCount = 0;

            foreach ($data['groups'] ?? [] as $group) {
                $fieldsCount += \count($group['fields'] ?? []);
            }

            $timestamp = $data['timestamp'] ?? filemtime($filePath);
            $updatedAtStr = $data['updated_at'] ?? date('c', filemtime($filePath));

            // Format date for display (d/m/Y H:i)
            try {
                $dateTime = new DateTime($updatedAtStr);
                $updatedAtFormatted = $dateTime->format('d/m/Y H:i');
            } catch (Exception $e) {
                $updatedAtFormatted = date('d/m/Y H:i', $timestamp);
            }

            return [
                'exists' => true,
                'path' => $filePath,
                'timestamp' => $timestamp,
                'updated_at' => $updatedAtFormatted, // Formatted date string
                'groups_count' => $groupsCount,
                'fields_count' => $fieldsCount,
                'size' => filesize($filePath),
            ];
        } catch (Exception $e) {
            $this->logError('Auto-sync: Cannot read file info', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Check if sync file has newer configuration than database.
     * Optimized: Uses filemtime() first (fast) before parsing JSON (slow).
     */
    public function hasNewerConfig(): bool
    {
        $syncStatus = $this->getSyncStatus();

        return $syncStatus['status'] === 'file_newer';
    }

    /**
     * Get synchronization status between file and database.
     *
     * @return array{
     *     status: 'synced'|'file_newer'|'db_newer'|'no_file',
     *     fileTimestamp: int|null,
     *     dbTimestamp: int,
     *     message: string
     * }
     */
    public function getSyncStatus(): array
    {
        $filePath = $this->getConfigFilePath();
        $fileInfo = $this->getFileInfo();

        // Check if database has any groups (more reliable than timestamp)
        $dbData = $this->exportImportService->exportAll();
        $dbHasGroups = \count($dbData['groups'] ?? []) > 0;

        // Get DB timestamp (last sync update or 0 if never synced)
        $dbTimestamp = $this->config->getInt('WEPRESTA_ACF_SYNC_LAST_UPDATE', 0);

        // If no file exists
        if (! $fileInfo || ! $fileInfo['exists']) {
            if ($dbHasGroups) {
                return [
                    'status' => 'db_newer',
                    'fileTimestamp' => null,
                    'dbTimestamp' => $dbTimestamp,
                    'message' => 'No sync file found. Export configuration to create it.',
                ];
            }

            return [
                'status' => 'no_file',
                'fileTimestamp' => null,
                'dbTimestamp' => $dbTimestamp,
                'message' => 'No sync file found and database is empty. Export configuration to create it.',
            ];
        }

        $fileTimestamp = $fileInfo['timestamp'] ?? filemtime($filePath);
        $fileHasGroups = ($fileInfo['groups_count'] ?? 0) > 0;

        // If file has groups but DB is empty, file is definitely newer
        if ($fileHasGroups && ! $dbHasGroups) {
            return [
                'status' => 'file_newer',
                'fileTimestamp' => $fileTimestamp,
                'dbTimestamp' => $dbTimestamp,
                'message' => 'The sync file contains data but database is empty. Import to update.',
            ];
        }

        // If DB has groups but file is empty, DB is newer
        if ($dbHasGroups && ! $fileHasGroups) {
            return [
                'status' => 'db_newer',
                'fileTimestamp' => $fileTimestamp,
                'dbTimestamp' => $dbTimestamp,
                'message' => 'The database contains data but sync file is empty. Export to update.',
            ];
        }

        // Both have groups or both are empty - compare timestamps
        // Compare timestamps (with 2 seconds tolerance for clock differences)
        $diff = abs($fileTimestamp - $dbTimestamp);

        if ($diff <= 2) {
            // Synchronized (within 2 seconds tolerance)
            return [
                'status' => 'synced',
                'fileTimestamp' => $fileTimestamp,
                'dbTimestamp' => $dbTimestamp,
                'message' => 'Configuration is synchronized.',
            ];
        }

        if ($fileTimestamp > $dbTimestamp) {
            // File is newer
            return [
                'status' => 'file_newer',
                'fileTimestamp' => $fileTimestamp,
                'dbTimestamp' => $dbTimestamp,
                'message' => 'The sync file is newer than the database. Import to update.',
            ];
        }

        // DB is newer
        return [
            'status' => 'db_newer',
            'fileTimestamp' => $fileTimestamp,
            'dbTimestamp' => $dbTimestamp,
            'message' => 'The database is newer than the sync file. Export to update.',
        ];
    }

    /**
     * Import configuration from sync file.
     */
    public function importFromFile(bool $merge = true): ImportResult
    {
        $filePath = $this->getConfigFilePath();

        if (! file_exists($filePath)) {
            return new ImportResult(false, 'Sync file not found');
        }

        try {
            $content = file_get_contents($filePath);

            if ($content === false) {
                return new ImportResult(false, 'Cannot read sync file');
            }

            $data = json_decode($content, true);

            if (! \is_array($data)) {
                return new ImportResult(false, 'Invalid JSON in sync file');
            }

            // Temporarily disable auto-sync during import to prevent immediate re-export
            $wasEnabled = $this->isEnabled();
            $this->config->set('WEPRESTA_ACF_AUTO_SYNC_ENABLED', 0);

            // Import using appropriate mode
            $result = $merge
                ? $this->exportImportService->importMerge($data)
                : $this->exportImportService->importReplace($data);

            // Re-enable auto-sync if it was enabled
            if ($wasEnabled) {
                $this->config->set('WEPRESTA_ACF_AUTO_SYNC_ENABLED', 1);
            }

            // Update last sync timestamp if successful
            if ($result->isSuccess()) {
                // Use current timestamp to prevent immediate re-export
                // This ensures the file is considered "synchronized" after import
                $this->config->set('WEPRESTA_ACF_SYNC_LAST_UPDATE', time());
                $this->logInfo('Auto-sync: Configuration imported from file', [
                    'created' => count($result->getCreated()),
                    'updated' => count($result->getUpdated()),
                ]);
            }

            return $result;
        } catch (Exception $e) {
            $this->logError('Auto-sync: Import failed', ['error' => $e->getMessage()]);

            // Re-enable auto-sync if it was enabled
            if (isset($wasEnabled) && $wasEnabled) {
                $this->config->set('WEPRESTA_ACF_AUTO_SYNC_ENABLED', 1);
            }

            return new ImportResult(false, 'Import error: ' . $e->getMessage());
        }
    }

    /**
     * Dismiss sync notification by updating DB timestamp to match file.
     */
    public function dismissNotification(): void
    {
        $fileInfo = $this->getFileInfo();

        if ($fileInfo && $fileInfo['exists'] && isset($fileInfo['timestamp'])) {
            $this->config->set('WEPRESTA_ACF_SYNC_LAST_UPDATE', $fileInfo['timestamp']);
            $this->logInfo('Auto-sync: Notification dismissed');
        }
    }

    /**
     * Sync now: intelligently import or export based on sync status.
     * - If file is newer: import from file
     * - If DB is newer: export to file
     * - If synced: do nothing (already synced).
     *
     * @return ImportResult Result of the sync operation
     */
    public function syncNow(): ImportResult
    {
        $syncStatus = $this->getSyncStatus();

        if ($syncStatus['status'] === 'synced') {
            return new ImportResult(true, 'Already synchronized');
        }

        if ($syncStatus['status'] === 'file_newer') {
            // File is newer: import from file
            // Use REPLACE mode to ensure clean import (not merge which might skip existing groups)
            $result = $this->importFromFile(false); // Replace mode for clean sync

            if (! $result->isSuccess()) {
                $this->logError('Auto-sync: Import failed', [
                    'errors' => $result->getErrors(),
                    'message' => $result->getMessage(),
                ]);

                return $result;
            }

            // Verify import was successful by checking if groups exist in DB
            $importedGroups = $this->exportImportService->exportAll();
            $groupsCount = \count($importedGroups['groups'] ?? []);

            if ($groupsCount === 0) {
                $this->logError('Auto-sync: Import reported success but no groups found in DB');

                // Don't update timestamp if import failed
                return new ImportResult(false, 'Import completed but no groups found in database. Import may have failed silently.');
            }

            $this->logInfo('Auto-sync: Synchronized by importing from file', [
                'created' => \count($result->getCreated()),
                'updated' => \count($result->getUpdated()),
                'groups_in_db' => $groupsCount,
            ]);

            return $result;
        }

        if ($syncStatus['status'] === 'db_newer') {
            // DB is newer: export to file
            try {
                $this->exportNow();
                $this->logInfo('Auto-sync: Synchronized by exporting to file');

                return new ImportResult(true, 'Configuration exported successfully');
            } catch (Exception $e) {
                $this->logError('Auto-sync: Export failed during sync', ['error' => $e->getMessage()]);

                return new ImportResult(false, 'Export failed: ' . $e->getMessage());
            }
        }

        if ($syncStatus['status'] === 'no_file') {
            // No file exists: check if DB has data before exporting
            $dbData = $this->exportImportService->exportAll();
            $dbHasGroups = \count($dbData['groups'] ?? []) > 0;

            if (! $dbHasGroups) {
                return new ImportResult(false, 'Cannot sync: database is empty and no sync file exists. Import data first or create groups manually.');
            }

            // DB has data but no file: export to create file
            try {
                $this->exportNow();
                $this->logInfo('Auto-sync: Synchronized by exporting to file');

                return new ImportResult(true, 'Configuration exported successfully');
            } catch (Exception $e) {
                $this->logError('Auto-sync: Export failed during sync', ['error' => $e->getMessage()]);

                return new ImportResult(false, 'Export failed: ' . $e->getMessage());
            }
        }

        return new ImportResult(false, 'Unknown sync status: ' . $syncStatus['status']);
    }

    /**
     * Create security files in sync directory.
     */
    private function createSecurityFiles(string $syncDir): void
    {
        // .htaccess - Deny all access
        $htaccess = <<<'HTACCESS'
            # Deny all access to sync files
            <IfModule mod_authz_core.c>
                Require all denied
            </IfModule>
            <IfModule !mod_authz_core.c>
                Order deny,allow
                Deny from all
            </IfModule>
            HTACCESS;
        file_put_contents($syncDir . '/.htaccess', $htaccess);

        // index.php - Redirect to root
        $indexPhp = <<<'PHP'
            <?php
            header('Location: /');
            exit;
            PHP;
        file_put_contents($syncDir . '/index.php', $indexPhp);

        $this->logInfo('Auto-sync: Security files created', ['dir' => $syncDir]);
    }
}
