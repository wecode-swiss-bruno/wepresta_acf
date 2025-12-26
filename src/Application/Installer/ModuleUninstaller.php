<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Installer;

use Db;
use Configuration;
use Tab;
use Module;
use WeprestaAcf;

/**
 * Gère la désinstallation du module
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
        return $this->uninstallTabs()
            && $this->uninstallDatabase()
            && $this->uninstallConfiguration();
    }

    /**
     * Suppression des valeurs de configuration
     */
    private function uninstallConfiguration(): bool
    {
        foreach (array_keys(WeprestaAcf::DEFAULT_CONFIG) as $key) {
            Configuration::deleteByName($key);
        }
        return true;
    }

    /**
     * Suppression des tables en base de données
     */
    private function uninstallDatabase(): bool
    {
        $sqlFile = $this->module->getLocalPath() . 'sql/uninstall.sql';

        if (!file_exists($sqlFile)) {
            return true;
        }

        return $this->executeSqlFile($sqlFile);
    }

    /**
     * Suppression des onglets admin
     */
    private function uninstallTabs(): bool
    {
        $tabClassNames = $this->getTabClassNames();

        foreach ($tabClassNames as $className) {
            $tabId = (int) Tab::getIdFromClassName($className);

            if ($tabId > 0) {
                $tab = new Tab($tabId);
                if (!$tab->delete()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Liste des onglets à désinstaller (enfants avant parent)
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

            if (!empty($query)) {
                // On ignore les erreurs de suppression (tables inexistantes)
                try {
                    $this->db->execute($query);
                } catch (\Exception $e) {
                    // Silently ignore
                }
            }
        }

        return true;
    }
}

