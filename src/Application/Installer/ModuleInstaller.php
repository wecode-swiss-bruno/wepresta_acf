<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Installer;

use Db;
use Configuration;
use Language;
use Tab;
use Module;
use WeprestaAcf;

/**
 * Gère l'installation du module
 */
final class ModuleInstaller
{
    public function __construct(
        private readonly Module $module,
        private readonly Db $db
    ) {
    }

    public function install(): bool
    {
        return $this->installConfiguration()
            && $this->installDatabase()
            && $this->installTabs();
    }

    /**
     * Installation des valeurs de configuration par défaut
     */
    private function installConfiguration(): bool
    {
        foreach (WeprestaAcf::DEFAULT_CONFIG as $key => $defaultValue) {
            if (Configuration::get($key) === false) {
                if (!Configuration::updateValue($key, $defaultValue)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Installation des tables en base de données
     */
    private function installDatabase(): bool
    {
        $sqlFile = $this->module->getLocalPath() . 'sql/install.sql';

        if (!file_exists($sqlFile)) {
            return true;
        }

        return $this->executeSqlFile($sqlFile);
    }

    /**
     * Installation des onglets admin (menu)
     */
    private function installTabs(): bool
    {
        $tabs = $this->getTabsToInstall();

        foreach ($tabs as $tabData) {
            if (!$this->installTab($tabData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Définition des onglets à installer
     */
    private function getTabsToInstall(): array
    {
        return [
            // Parent tab (top-level menu)
            [
                'class_name' => 'AdminWeprestaAcf',
                'route_name' => '',
                'name' => 'ACF',
                'parent' => 'SELL',
                'icon' => 'extension',
                'visible' => true,
            ],
            // Field Groups sub-tab
            [
                'class_name' => 'AdminWeprestaAcfBuilder',
                'route_name' => 'wepresta_acf_builder',
                'name' => 'Field Groups',
                'parent' => 'AdminWeprestaAcf',
                'icon' => 'view_list',
                'visible' => true,
            ],
            // Configuration sub-tab
            [
                'class_name' => 'AdminWeprestaAcfConfiguration',
                'route_name' => 'wepresta_acf_configuration',
                'name' => 'Configuration',
                'parent' => 'AdminWeprestaAcf',
                'icon' => 'settings',
                'visible' => true,
            ],
        ];
    }

    private function installTab(array $tabData): bool
    {
        $existingTabId = (int) Tab::getIdFromClassName($tabData['class_name']);

        if ($existingTabId > 0) {
            return true; // Déjà installé
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $tabData['class_name'];
        $tab->route_name = $tabData['route_name'] ?? null;
        $tab->name = [];

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabData['name'];
        }

        $tab->id_parent = (int) Tab::getIdFromClassName($tabData['parent']);
        $tab->module = $this->module->name;
        $tab->icon = $tabData['icon'] ?? '';
        $tab->enabled = true;
        $tab->hide_host_mode = false;

        return $tab->add();
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

            if (!empty($query) && !$this->db->execute($query)) {
                return false;
            }
        }

        return true;
    }
}

