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
    public function init()
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
                'url' => $cptUrlService->getPostUrl($post, $type),
                'date_add' => $post->getDateAdd()?->format('Y-m-d H:i:s'),
                'date_upd' => $post->getDateUpd()?->format('Y-m-d H:i:s'),
            ];
        }, $posts);

        // SEO
        $this->context->smarty->assign([
            'meta_title' => $type->getName() . ' - ' . Configuration::get('PS_SHOP_NAME'),
            'meta_description' => $type->getDescription() ?: '',
        ]);

        // Assign to template
        $this->context->smarty->assign([
            'cpt_type' => [
                'id' => $type->getId(),
                'slug' => $type->getSlug(),
                'name' => $type->getName(),
                'description' => $type->getDescription(),
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
        // Template hierarchy:
        // 1. theme/modules/wepresta_acf/cpt/archive-{type}.tpl
        // 2. theme/modules/wepresta_acf/cpt/archive.tpl
        // 3. module/views/templates/front/cpt/archive.tpl

        $themeDir = _PS_THEME_DIR_ . 'modules/wepresta_acf/cpt/';

        if (file_exists($themeDir . 'archive-' . $typeSlug . '.tpl')) {
            return 'module:wepresta_acf/views/templates/front/cpt/archive-' . $typeSlug . '.tpl';
        }

        if (file_exists($themeDir . 'archive.tpl')) {
            return 'module:wepresta_acf/views/templates/front/cpt/archive.tpl';
        }

        return 'module:wepresta_acf/views/templates/front/cpt/archive.tpl';
    }
}
