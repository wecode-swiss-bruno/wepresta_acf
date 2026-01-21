<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

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

        // Get preview token
        $previewToken = Tools::getValue('preview_token');

        // Check availability
        $isPublished = $post && $post->isPublished();
        $isValidPreview = false;

        if (!$isPublished && $post && $previewToken) {
            $expectedToken = $cptUrlService->getPreviewToken($post);
            if (hash_equals($expectedToken, $previewToken)) {
                $isValidPreview = true;
            }
        }

        if (!$post || (!$isPublished && !$isValidPreview)) {
            Tools::redirect('404');
        }

        // Set context for ACF and CPT
        $cptFrontService->forPost($post->getId());
        $acfFrontService = $acfFrontService->forCpt($type->getSlug(), $post->getId());

        // Generate SEO
        $seoMeta = $cptSeoService->generateMetaTags($post, $type);
        $schemaOrg = $cptSeoService->generateSchemaOrg($post, $type);

        // Store SEO data for getTemplateVarPage()
        $this->seoMeta = $seoMeta;

        // Add social meta tags to head
        $this->addMetaTags($seoMeta);
        $this->addSchemaOrg($schemaOrg);

        // Assign to template
        $this->context->smarty->assign([
            'cpt_type' => [
                'id' => $type->getId(),
                'slug' => $type->getSlug(),
                'name' => $type->getName($this->context->language->id),
                'url' => $cptUrlService->getFriendlyUrl($type),
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

    /**
     * Override to set proper page meta (PrestaShop 8/9 way)
     */
    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();

        if (isset($this->seoMeta)) {
            $page['meta']['title'] = $this->seoMeta['title'] ?? $page['meta']['title'];
            $page['meta']['description'] = $this->seoMeta['description'] ?? $page['meta']['description'];
            $page['meta']['robots'] = 'index, follow';
        }

        // Set page name for CSS targeting
        $page['page_name'] = 'module-wepresta_acf-cptsingle';

        return $page;
    }

    /** @var array<string, string>|null */
    private ?array $seoMeta = null;

    private function getSingleTemplate(string $typeSlug): string
    {
        // Template hierarchy (PS8 & PS9 compatible):
        // 1. themes/{active_theme}/modules/wepresta_acf/views/templates/front/cpt/single-{type}.tpl
        // 2. modules/wepresta_acf/views/templates/front/cpt/single-{type}.tpl
        // 3. themes/{active_theme}/modules/wepresta_acf/views/templates/front/cpt/single.tpl
        // 4. modules/wepresta_acf/views/templates/front/cpt/single.tpl

        $theme = $this->context->shop->theme_name ?? '';
        $moduleDir = $this->module->getLocalPath() . 'views/templates/front/cpt/';
        $themeDir = _PS_THEME_DIR_ . 'modules/wepresta_acf/views/templates/front/cpt/';

        // 1. Check type-specific template in THEME
        $specificThemeTemplate = $themeDir . 'single-' . $typeSlug . '.tpl';
        if (file_exists($specificThemeTemplate)) {
            return 'module:wepresta_acf/views/templates/front/cpt/single-' . $typeSlug . '.tpl';
        }

        // 2. Check type-specific template in MODULE
        $specificModuleTemplate = $moduleDir . 'single-' . $typeSlug . '.tpl';
        if (file_exists($specificModuleTemplate)) {
            return 'module:wepresta_acf/views/templates/front/cpt/single-' . $typeSlug . '.tpl';
        }

        // 3. Fallback to generic template (theme override automatically handled by PrestaShop)
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
