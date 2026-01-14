<?php

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use Configuration;
use Context;
use Language;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Application\Service\FieldTypeLoader;
use WeprestaAcf\Application\Service\FieldTypeRegistry;

/**
 * Builder Controller - Single Page Application Entry Point
 * Compatible PrestaShop 8.x and 9.x
 */
final class BuilderController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly FieldTypeLoader $fieldTypeLoader,
        private readonly LocationProviderRegistry $locationProviderRegistry,
        private readonly TranslatorInterface $translator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RequestStack $requestStack
    ) {
        // Note: parent::__construct() is NOT called for PS8/PS9 compatibility
        // In PS9 (Symfony 6.x), calling parent constructor causes "Cannot call constructor" error
    }

    public function index(): Response
    {
        // Load custom field types from theme/uploads
        $this->fieldTypeLoader->loadAllCustomTypes();

        // Get loaded types info to know which are custom
        $loadedTypesInfo = $this->fieldTypeLoader->getLoadedTypesInfo();

        $fieldTypes = [];

        foreach ($this->fieldTypeRegistry->getAll() as $type => $fieldType) {
            // Check if this is a custom type (from theme or uploaded)
            $source = $loadedTypesInfo[$type]['source'] ?? 'core';
            $category = $fieldType->getCategory();

            // Override category to 'custom' for non-core types
            if ($source !== 'core') {
                $category = 'custom';
            }

            $fieldTypes[] = [
                'type' => $type,
                'label' => $fieldType->getLabel(),
                'icon' => $fieldType->getIcon(),
                'category' => $category,
                'source' => $source,
                'supportsTranslation' => $fieldType->supportsTranslation(),
            ];
        }

        // Get all available locations (entity types, product categories, etc.)
        $locations = $this->locationProviderRegistry->getLocationsGrouped();

        // Get available languages for multilingual fields
        $languages = [];

        foreach (Language::getLanguages(true) as $lang) {
            $name = $lang['name'];

            if (preg_match('/^(.+)\s*\([^)]+\)$/', $name, $matches)) {
                $name = trim($matches[1]);
            }

            $languages[] = [
                'id' => (int) $lang['id_lang'],
                'id_lang' => (int) $lang['id_lang'],
                'code' => $lang['iso_code'],
                'iso_code' => $lang['iso_code'],
                'name' => $name,
                'is_default' => (int) $lang['id_lang'] === (int) Configuration::get('PS_LANG_DEFAULT'),
            ];
        }

        // Generate CSRF token compatible PS8/PS9
        $csrfToken = $this->generateCsrfToken();

        return $this->render('@Modules/wepresta_acf/views/templates/admin/builder.html.twig', [
            'layoutTitle' => $this->trans('ACF Field Builder', 'Modules.Weprestaacf.Admin'),
            'fieldTypes' => $fieldTypes,
            'locations' => $locations,
            'languages' => $languages,
            'currentLangId' => (int) Configuration::get('PS_LANG_DEFAULT'),
            'csrfToken' => $csrfToken,
            'currentShopId' => (int) Context::getContext()->shop->id,
            // Toolbar buttons are now handled dynamically via Vue.js and DOM manipulation
            // The buttons are injected via header_toolbar_btn block and controlled by Vue state
            'layoutHeaderToolbarBtn' => [],
        ]);
    }

    /**
     * Override trans() method for PS8/PS9 compatibility.
     * In PS8, translator is not available in the service locator.
     */
    protected function trans($key, $domain, array $parameters = [])
    {
        return $this->translator->trans($key, $parameters, $domain);
    }

    /**
     * Generate CSRF token compatible with PS8 and PS9.
     * 
     * For PS8 with "Protection des jetons" enabled, we need to use the same
     * token that PrestaShop generates for the current page URL.
     * This token is validated by the LegacyAdminTokenValidator.
     */
    private function generateCsrfToken(): string
    {
        // Try to get the current request's _token (this is the token PS8 validates)
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->query->has('_token')) {
            return $request->query->get('_token');
        }

        // PS9 method (UserTokenManager)
        if (class_exists('PrestaShopBundle\Security\Admin\UserTokenManager')) {
            try {
                $userTokenManager = $this->container->get('prestashop.security.admin.user_token_manager');
                if ($userTokenManager && method_exists($userTokenManager, 'getSymfonyToken')) {
                    return $userTokenManager->getSymfonyToken();
                }
            } catch (\Exception $e) {
                // Fallback
            }
        }

        // PS8 fallback: Generate CSRF token with employee email
        try {
            $context = Context::getContext();
            if (isset($context->employee) && $context->employee->id && $context->employee->email) {
                $tokenId = $context->employee->email;
                return $this->csrfTokenManager->getToken($tokenId)->getValue();
            }
        } catch (\Exception $e) {
            // Continue
        }

        // Last resort
        return '';
    }
}
