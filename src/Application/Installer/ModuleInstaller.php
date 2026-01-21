<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Installer;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Db;
use Language;
use Module;
use Tab;
use WeprestaAcf;

/**
 * Gère l'installation du module.
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
            && $this->installTabs()
            && $this->createThemeDirectories();
    }

    /**
     * Installation des valeurs de configuration par défaut.
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
     * Installation des tables en base de données.
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
     * Installation des onglets admin (menu).
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
     * Create theme directories for ACF CPT templates.
     * Creates: themes/{current_theme}/modules/wepresta_acf/views/templates/front/cpt/
     */
    private function createThemeDirectories(): bool
    {
        try {
            $themePath = _PS_THEME_DIR_;
            $moduleDir = $themePath . 'modules/wepresta_acf/views/templates/front/cpt';

            // Create directories if they don't exist
            if (!is_dir($moduleDir)) {
                if (!@mkdir($moduleDir, 0755, true)) {
                    return false;
                }
            }

            // Create an index.php file for security
            $indexFile = $moduleDir . '/index.php';
            if (!file_exists($indexFile)) {
                $this->createSecurityIndex($indexFile);
            }

            // Create a README file with instructions
            $readmeFile = $moduleDir . '/README.md';
            if (!file_exists($readmeFile)) {
                $this->createReadmeFile($readmeFile);
            }

            return true;
        } catch (Exception $e) {
            // Don't fail installation if directory creation fails
            return true;
        }
    }

    /**
     * Create a security index.php file in the directory.
     */
    private function createSecurityIndex(string $filePath): void
    {
        file_put_contents($filePath, "<?php\nheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\nheader('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');\nheader('Cache-Control: no-store, no-cache, must-revalidate');\nheader('Cache-Control: post-check=0, pre-check=0', false);\nheader('Pragma: no-cache');\nheader('Location: ../../../');\nexit;\n");
    }

    /**
     * Create a README file with instructions for custom templates.
     */
    private function createReadmeFile(string $filePath): void
    {
        $readme = <<<'EOF'
# WePresta ACF - Custom CPT Templates

This directory is for custom template overrides for Custom Post Types (CPT).

## Template Hierarchy

WePresta ACF uses a template hierarchy to find the correct template for each CPT:

1. **single-{TYPE}.tpl** ← Custom per CPT type (THIS DIRECTORY)
2. **single.tpl** ← Generic for all CPT (optional, this directory)
3. **module/views/templates/front/cpt/single.tpl** ← Module default (fallback)

## How to Use

### For a Blog CPT (slug: blog)

Create: `themes/{YOUR_THEME}/modules/wepresta_acf/views/templates/front/cpt/single-blog.tpl`

### For a Portfolio CPT (slug: portfolio)

Create: `themes/{YOUR_THEME}/modules/wepresta_acf/views/templates/front/cpt/single-portfolio.tpl`

### For all other CPT (generic)

Create: `themes/{YOUR_THEME}/modules/wepresta_acf/views/templates/front/cpt/single.tpl`

## Template Variables

Your template has access to:

- `$cpt_type` - CPT type info: `['id', 'slug', 'name', 'url']`
- `$cpt_post` - Post data: `['id', 'slug', 'title', 'date_add', 'date_upd']`
- `$acf` - ACF Service instance
- `$cpt` - CPT Service instance

## Example Template

```smarty
{extends file='page.tpl'}

{block name='page_title'}
<h1>{$cpt_post.title}</h1>
{/block}

{block name='page_content'}
<article class="blog-post">
    {* Featured Image *}
    {if $acf->has('featured_image')}
        <div class="featured-image">
            {$acf->render('featured_image')}
        </div>
    {/if}

    {* Content *}
    {if $acf->has('content')}
        <div class="post-content">
            {$acf->render('content')}
        </div>
    {/if}

    {* All ACF Groups *}
    {assign var="groups" value=$acf->getActiveGroupsArray()}
    {foreach $groups as $group}
        <section class="group-{$group.slug}">
            <h2>{$group.title}</h2>
            {foreach $group.fields as $field}
                {if $field.has_value && $field.slug != 'featured_image' && $field.slug != 'content'}
                    <div class="field field-{$field.type}">
                        <strong>{$field.title}</strong>
                        {$field.rendered nofilter}
                    </div>
                {/if}
            {/foreach}
        </section>
    {/foreach}
</article>
{/block}
```

## Documentation

For complete ACF documentation, see: `ACF_FRONT_OFFICE_GUIDE.md` in the module root.

---

**Created by:** WePresta ACF Module Installation
EOF;

        file_put_contents($filePath, $readme);
    }

    /**
     * Définition des onglets à installer.
     */
    private function getTabsToInstall(): array
    {
        return [
            [
                'class_name' => 'AdminWepresta',
                'route_name' => '',
                'name' => 'WePresta',
                'parent' => 'DEFAULT',
                'icon' => 'extension',
                'visible' => true,
            ],
            [
                'class_name' => 'AdminWeprestaAcf',
                'route_name' => '',
                'name' => 'Advanced Custom Fields / Custom Post Type',
                'parent' => 'AdminWepresta',
                'icon' => 'view_list',
                'visible' => true,
            ],
            [
                'class_name' => 'AdminWeprestaAcfBuilder',
                'route_name' => 'wepresta_acf_builder',
                'name' => 'Builder',
                'parent' => 'AdminWeprestaAcf',
                'icon' => 'view_list',
                'visible' => true,
            ],
            [
                'class_name' => 'AdminWeprestaAcfConfiguration',
                'route_name' => 'wepresta_acf_configuration',
                'name' => 'Configuration',
                'parent' => 'AdminWeprestaAcf',
                'icon' => 'settings',
                'visible' => true,
            ],
            [
                'class_name' => 'AdminWeprestaAcfSync',
                'route_name' => 'wepresta_acf_sync',
                'name' => 'Sync',
                'parent' => 'AdminWeprestaAcf',
                'icon' => 'sync',
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
