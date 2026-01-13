<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Installer;

use Configuration;
use Db;
use Exception;
use Module;
use Tab;
use WeprestaAcf;

/**
 * Gère la désinstallation du module.
 */
final class ModuleUninstaller
{
    public function __construct(
        private readonly Module $module,
        private readonly Db $db
    ) {
    }

    public function uninstall(): bool
    {
        // Execute in order: Tabs -> Database -> Configuration
        // Continue even if one step fails (best effort)
        $tabsResult = $this->uninstallTabs();
        $dbResult = $this->uninstallDatabase();
        $configResult = $this->uninstallConfiguration();
        
        // Log any failures but don't block uninstall
        if (!$tabsResult) {
            $this->module->log('Failed to uninstall some tabs during module uninstall', 2);
        }
        if (!$dbResult) {
            $this->module->log('Failed to uninstall database during module uninstall', 2);
        }
        if (!$configResult) {
            $this->module->log('Failed to uninstall configuration during module uninstall', 2);
        }
        
        // Return true if at least database and config were cleaned
        // Tabs can fail if already deleted manually
        return $dbResult && $configResult;
    }

    /**
     * Suppression des valeurs de configuration.
     */
    private function uninstallConfiguration(): bool
    {
        foreach (array_keys(WeprestaAcf::DEFAULT_CONFIG) as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    /**
     * Suppression des tables en base de données.
     */
    private function uninstallDatabase(): bool
    {
        $sqlFile = $this->module->getLocalPath() . 'sql/uninstall.sql';

        if (! file_exists($sqlFile)) {
            return true;
        }

        return $this->executeSqlFile($sqlFile);
    }

    /**
     * Suppression des onglets admin.
     */
    private function uninstallTabs(): bool
    {
        $tabClassNames = $this->getTabClassNames();
        $success = true;

        foreach ($tabClassNames as $className) {
            try {
                $tabId = (int) Tab::getIdFromClassName($className);

                if ($tabId > 0) {
                    $tab = new Tab($tabId);

                    if (! $tab->delete()) {
                        $this->module->log("Failed to delete tab: {$className}", 2);
                        $success = false;
                    }
                } else {
                    // Tab already deleted or doesn't exist - not an error
                    $this->module->log("Tab not found (already deleted?): {$className}", 1);
                }
            } catch (Exception $e) {
                $this->module->log("Error deleting tab {$className}: " . $e->getMessage(), 2);
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Liste des onglets à désinstaller (enfants avant parent).
     */
    private function getTabClassNames(): array
    {
        return [
            'AdminWeprestaAcfBuilder',
            'AdminWeprestaAcfConfiguration',
            'AdminWeprestaAcf', // Parent last
        ];
    }

    private function executeSqlFile(string $filePath): bool
    {
        $sql = file_get_contents($filePath);

        if ($sql === false) {
            return false;
        }

        $sql = str_replace(
            ['PREFIX_', 'ENGINE_TYPE'],
            [_DB_PREFIX_, _MYSQL_ENGINE_],
            $sql
        );

        $queries = preg_split('/;\s*[\r\n]+/', $sql);

        if ($queries === false) {
            return false;
        }

        foreach ($queries as $query) {
            $query = trim($query);

            if (! empty($query)) {
                // On ignore les erreurs de suppression (tables inexistantes)
                try {
                    $this->db->execute($query);
                } catch (Exception $e) {
                    // Silently ignore
                }
            }
        }

        return true;
    }
}
