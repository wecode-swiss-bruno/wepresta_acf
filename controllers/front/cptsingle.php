<?php

declare(strict_types=1);

use WeprestaAcf\Application\Service\AcfServiceContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Single Controller - Display single CPT post
 */
class Wepresta_AcfCptsingleModuleFrontController extends ModuleFrontController
{
    public function init(): void
    {
        parent::init();

        // Get parameters from URL
        $typeSlug = Tools::getValue('type');
        $postSlug = Tools::getValue('slug');

        if (!$typeSlug || !$postSlug) {
            Tools::redirect('404');
        }

        $cptTypeService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptTypeService');
        $cptPostService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptPostService');
        $cptFrontService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptFrontService');
        $cptUrlService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptUrlService');
        $cptSeoService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptSeoService');
        $acfFrontService = AcfServiceContainer::getFrontService();

        // Get type
        $type = $cptTypeService->getTypeBySlug($typeSlug);

        if (!$type || !$type->isActive()) {
            Tools::redirect('404');
        }

        // Get post
        $post = $cptPostService->getPostBySlug($postSlug, $type->getId());

        if (!$post || !$post->isPublished()) {
            Tools::redirect('404');
        }

        // Set context for ACF and CPT
        $cptFrontService->forPost($post->getId());
        $acfFrontService = $acfFrontService->forCpt($type->getSlug(), $post->getId());

        // Generate SEO
        $seoMeta = $cptSeoService->generateMetaTags($post, $type);
        $schemaOrg = $cptSeoService->generateSchemaOrg($post, $type);

        // Assign meta tags
        $this->context->smarty->assign([
            'meta_title' => $seoMeta['title'],
            'meta_description' => $seoMeta['description'],
        ]);

        // Add meta tags to head
        $this->addMetaTags($seoMeta);
        $this->addSchemaOrg($schemaOrg);

        // Assign to template
        $this->context->smarty->assign([
            'cpt_type' => [
                'id' => $type->getId(),
                'slug' => $type->getSlug(),
                'name' => $type->getName(),
                'url' => $cptUrlService->getArchiveUrl($type),
            ],
            'cpt_post' => [
                'id' => $post->getId(),
                'slug' => $post->getSlug(),
                'title' => $post->getTitle(),
                'date_add' => $post->getDateAdd()?->format('Y-m-d H:i:s'),
                'date_upd' => $post->getDateUpd()?->format('Y-m-d H:i:s'),
            ],
            'cpt' => $cptFrontService,
            'acf' => $acfFrontService,
        ]);

        // Set template
        $this->setTemplate($this->getSingleTemplate($typeSlug));
    }

    private function getSingleTemplate(string $typeSlug): string
    {
        // Template hierarchy:
        // 1. theme/modules/wepresta_acf/cpt/single-{type}.tpl
        // 2. theme/modules/wepresta_acf/cpt/single.tpl
        // 3. module/views/templates/front/cpt/single.tpl

        $themeDir = _PS_THEME_DIR_ . 'modules/wepresta_acf/cpt/';

        if (file_exists($themeDir . 'single-' . $typeSlug . '.tpl')) {
            return 'module:wepresta_acf/views/templates/front/cpt/single-' . $typeSlug . '.tpl';
        }

        if (file_exists($themeDir . 'single.tpl')) {
            return 'module:wepresta_acf/views/templates/front/cpt/single.tpl';
        }

        return 'module:wepresta_acf/views/templates/front/cpt/single.tpl';
    }

    private function addMetaTags(array $meta): void
    {
        $socialMeta = [];
        foreach ($meta as $property => $content) {
            if (strpos($property, 'og:') === 0 || strpos($property, 'twitter:') === 0) {
                $socialMeta[$property] = $content;
            }
        }
        $this->context->smarty->assign('acf_social_meta', $socialMeta);
    }

    private function addSchemaOrg(array $schema): void
    {
        $this->context->smarty->assign('schema_org', json_encode($schema));
    }
}
