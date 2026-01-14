<?php

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use Context;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Builder Controller - Vue.js SPA for managing CPT types
 */
final class CptBuilderController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RequestStack $requestStack
    ) {
        // Note: parent::__construct() is NOT called for PS8/PS9 compatibility
    }
    /**
     * CPT Builder main page (Vue.js SPA)
     *
     * @return Response
     */
    #[AdminSecurity("is_granted('read', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function index(): Response
    {
        // Generate CSRF token compatible PS8/PS9
        $csrfToken = $this->generateCsrfToken();

        // Fetch active languages
        $languages = array_values(\Language::getLanguages(true, Context::getContext()->shop->id));
        $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');

        return $this->render('@Modules/wepresta_acf/views/templates/admin/cpt-builder.html.twig', [
            'layoutTitle' => $this->trans('CPT Builder', 'Modules.Weprestaacf.Admin'),
            'requireBulkActions' => false,
            'showContentHeader' => true,
            'enableSidebar' => false,
            'help_link' => false,
            'csrfToken' => $csrfToken,
            'layoutHeaderToolbarBtn' => [],
            'languages' => $languages,
            'defaultLangId' => $defaultLangId,
        ]);
    }

    /**
     * Generate CSRF token compatible with PS8 and PS9.
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
