<?php

declare(strict_types=1);

use WeprestaAcf\Application\Service\AcfServiceContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Taxonomy Controller - Display posts filtered by taxonomy term
 */
class Wepresta_AcfCpttaxonomyModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();

        // Get parameters from URL
        $typeSlug = Tools::getValue('type');
        $termSlug = Tools::getValue('term');
        $taxonomyId = (int) Tools::getValue('taxonomy');

        if (!$typeSlug || !$termSlug || !$taxonomyId) {
            Tools::redirect('404');
        }

        $cptTypeService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptTypeService');
        $cptPostService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptPostService');
        $cptTaxonomyService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptTaxonomyService');
        $cptUrlService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptUrlService');

        // Get type
        $type = $cptTypeService->getTypeBySlug($typeSlug);

        if (!$type || !$type->isActive()) {
            Tools::redirect('404');
        }

        // Get term
        $term = $cptTaxonomyService->getTermBySlug($termSlug, $taxonomyId);

        if (!$term || !$term->isActive()) {
            Tools::redirect('404');
        }

        // Get taxonomy
        $taxonomy = $cptTaxonomyService->getTaxonomyById($taxonomyId);

        // Pagination
        $page = (int) Tools::getValue('p', 1);
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Get posts
        $posts = $cptPostService->getPostsByTerm($term->getId(), $limit, $offset);
        $total = AcfServiceContainer::get('WeprestaAcf\Domain\Repository\CptPostRepositoryInterface')->countByTerm($term->getId());
        $totalPages = ceil($total / $limit);

        // Prepare post data
        $postsData = array_map(function ($post) use ($type, $cptUrlService) {
            return [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'slug' => $post->getSlug(),
                'url' => $cptUrlService->getPostUrl($post, $type),
                'date_add' => $post->getDateAdd()?->format('Y-m-d H:i:s'),
                'date_upd' => $post->getDateUpd()?->format('Y-m-d H:i:s'),
            ];
        }, $posts);

        // SEO
        $this->context->smarty->assign([
            'meta_title' => $term->getName() . ' - ' . $type->getName() . ' - ' . Configuration::get('PS_SHOP_NAME'),
            'meta_description' => $term->getDescription() ?: '',
        ]);

        // Assign to template
        $this->context->smarty->assign([
            'cpt_type' => [
                'id' => $type->getId(),
                'slug' => $type->getSlug(),
                'name' => $type->getName(),
                'url' => $cptUrlService->getArchiveUrl($type),
            ],
            'cpt_taxonomy' => [
                'id' => $taxonomy->getId(),
                'slug' => $taxonomy->getSlug(),
                'name' => $taxonomy->getName(),
            ],
            'cpt_term' => [
                'id' => $term->getId(),
                'slug' => $term->getSlug(),
                'name' => $term->getName(),
                'description' => $term->getDescription(),
            ],
            'cpt_posts' => $postsData,
            'cpt_pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
                'items_per_page' => $limit,
            ],
        ]);

        // Set template
        $this->setTemplate($this->getTaxonomyTemplate($typeSlug, $taxonomy->getSlug()));
    }

    private function getTaxonomyTemplate(string $typeSlug, string $taxonomySlug): string
    {
        // Template hierarchy (PS8 & PS9 compatible):
        // 1. themes/{active_theme}/modules/wepresta_acf/views/templates/front/cpt/taxonomy-{type}-{taxonomy}.tpl
        // 2. modules/wepresta_acf/views/templates/front/cpt/taxonomy-{type}-{taxonomy}.tpl
        // 3. themes/{active_theme}/modules/wepresta_acf/views/templates/front/cpt/taxonomy-{type}.tpl
        // 4. modules/wepresta_acf/views/templates/front/cpt/taxonomy-{type}.tpl
        // 5. themes/{active_theme}/modules/wepresta_acf/views/templates/front/cpt/taxonomy.tpl
        // 6. modules/wepresta_acf/views/templates/front/cpt/taxonomy.tpl

        $theme = $this->context->shop->theme_name ?? '';
        $moduleDir = $this->module->getLocalPath() . 'views/templates/front/cpt/';
        $themeDir = _PS_THEME_DIR_ . 'modules/wepresta_acf/views/templates/front/cpt/';

        // 1. Check specific type-taxonomy template in THEME
        $specificThemeTemplate = $themeDir . 'taxonomy-' . $typeSlug . '-' . $taxonomySlug . '.tpl';
        if (file_exists($specificThemeTemplate)) {
            return 'module:wepresta_acf/views/templates/front/cpt/taxonomy-' . $typeSlug . '-' . $taxonomySlug . '.tpl';
        }

        // 2. Check specific type-taxonomy template in MODULE
        $specificModuleTemplate = $moduleDir . 'taxonomy-' . $typeSlug . '-' . $taxonomySlug . '.tpl';
        if (file_exists($specificModuleTemplate)) {
            return 'module:wepresta_acf/views/templates/front/cpt/taxonomy-' . $typeSlug . '-' . $taxonomySlug . '.tpl';
        }

        // 3. Check type template in THEME
        $typeThemeTemplate = $themeDir . 'taxonomy-' . $typeSlug . '.tpl';
        if (file_exists($typeThemeTemplate)) {
            return 'module:wepresta_acf/views/templates/front/cpt/taxonomy-' . $typeSlug . '.tpl';
        }

        // 4. Check type template in MODULE
        $typeModuleTemplate = $moduleDir . 'taxonomy-' . $typeSlug . '.tpl';
        if (file_exists($typeModuleTemplate)) {
            return 'module:wepresta_acf/views/templates/front/cpt/taxonomy-' . $typeSlug . '.tpl';
        }

        // 5. Fallback to generic template (theme override automatically handled by PrestaShop)
        return 'module:wepresta_acf/views/templates/front/cpt/taxonomy.tpl';
    }
}
