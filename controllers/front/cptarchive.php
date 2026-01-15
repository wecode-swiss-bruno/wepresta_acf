<?php

declare(strict_types=1);

use WeprestaAcf\Application\Service\AcfServiceContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Archive Controller - Display list of posts for a CPT type
 */
class Wepresta_AcfCptarchiveModuleFrontController extends ModuleFrontController
{
    public function init(): void
    {
        parent::init();

        // Get type slug from URL
        $typeSlug = Tools::getValue('type');

        if (!$typeSlug) {
            Tools::redirect('index.php');
        }

        $cptTypeService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptTypeService');
        $cptFrontService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptFrontService');
        $cptUrlService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptUrlService');
        $cptSeoService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptSeoService');

        // Get type
        $type = $cptTypeService->getTypeBySlug($typeSlug);

        if (!$type || !$type->isActive()) {
            Tools::redirect('404');
        }

        // Check if archive is enabled
        if (!$type->hasArchive()) {
            Tools::redirect('404');
        }

        // Pagination
        $page = (int) Tools::getValue('p', 1);
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Get posts
        $cptFrontService->forType($typeSlug);
        $posts = $cptFrontService->getArchivePosts($limit, $offset);
        $total = $cptFrontService->getTotalPosts();
        $totalPages = ceil($total / $limit);

        // Prepare post data with URLs
        $postsData = array_map(function ($post) use ($type, $cptUrlService) {
            return [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'slug' => $post->getSlug(),
                'url' => $cptUrlService->getFriendlyUrl($type, $post),
                'date_add' => $post->getDateAdd()?->format('Y-m-d H:i:s'),
                'date_upd' => $post->getDateUpd()?->format('Y-m-d H:i:s'),
            ];
        }, $posts);

        // SEO
        $langId = (int) $this->context->language->id;
        $this->context->smarty->assign([
            'meta_title' => $type->getName($langId) . ' - ' . Configuration::get('PS_SHOP_NAME'),
            'meta_description' => $type->getDescription($langId) ?: '',
        ]);

        // Assign to template
        $this->context->smarty->assign([
            'cpt_type' => [
                'id' => $type->getId(),
                'slug' => $type->getSlug(),
                'name' => $type->getName($langId),
                'description' => $type->getDescription($langId),
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
        $this->setTemplate($this->getArchiveTemplate($typeSlug));
    }

    private function getArchiveTemplate(string $typeSlug): string
    {
        // Template hierarchy (PS8 & PS9 compatible):
        // PrestaShop automatically handles theme overrides for module templates
        // Theme override path: themes/{theme}/modules/wepresta_acf/views/templates/front/cpt/archive.tpl
        //
        // 1. module:wepresta_acf/views/templates/front/cpt/archive-{type}.tpl (type-specific)
        // 2. module:wepresta_acf/views/templates/front/cpt/archive.tpl (generic fallback)

        $moduleDir = $this->module->getLocalPath() . 'views/templates/front/cpt/';

        // 1. Check for specific CPT template in MODULE (or theme override)
        $specificModuleTemplate = $moduleDir . 'archive-' . $typeSlug . '.tpl';
        if (file_exists($specificModuleTemplate)) {
            return 'module:wepresta_acf/views/templates/front/cpt/archive-' . $typeSlug . '.tpl';
        }

        // 2. Fallback to generic MODULE template (or theme override)
        return 'module:wepresta_acf/views/templates/front/cpt/archive.tpl';
    }
}
