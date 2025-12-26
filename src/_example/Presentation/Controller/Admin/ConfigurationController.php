<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Presentation\Controller\Admin;

use WeprestaAcf\Example\Application\Form\ConfigurationType;
use WeprestaAcf\Example\Infrastructure\Adapter\ConfigurationAdapter;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur de configuration moderne (Symfony)
 * Routes définies dans config/routes.yml
 */
class ConfigurationController extends FrameworkBundleAdminController
{
    /**
     * Page de configuration principale
     */
    #[AdminSecurity("is_granted('read', 'AdminModules')", message: 'Access denied.', redirectRoute: 'admin_module_manage')]
    public function configuration(Request $request, ConfigurationAdapter $config): Response
    {
        $form = $this->createForm(ConfigurationType::class, [
            'active' => $config->getBool('WEPRESTA_ACF_ACTIVE'),
            'title' => $config->get('WEPRESTA_ACF_TITLE', 'Module Starter'),
            'description' => $config->get('WEPRESTA_ACF_DESCRIPTION', ''),
            'debug' => $config->getBool('WEPRESTA_ACF_DEBUG'),
            'cache_ttl' => $config->getInt('WEPRESTA_ACF_CACHE_TTL', 3600),
            'api_enabled' => $config->getBool('WEPRESTA_ACF_API_ENABLED'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $config->set('WEPRESTA_ACF_ACTIVE', $data['active']);
            $config->set('WEPRESTA_ACF_TITLE', $data['title']);
            $config->set('WEPRESTA_ACF_DESCRIPTION', $data['description']);
            $config->set('WEPRESTA_ACF_DEBUG', $data['debug']);
            $config->set('WEPRESTA_ACF_CACHE_TTL', $data['cache_ttl']);
            $config->set('WEPRESTA_ACF_API_ENABLED', $data['api_enabled']);

            $this->addFlash('success', $this->trans('Configuration saved successfully.', 'Admin.Notifications.Success'));

            return $this->redirectToRoute('wepresta_acf_configuration');
        }

        return $this->render('@Modules/wepresta_acf/views/templates/admin/configuration.html.twig', [
            'configurationForm' => $form->createView(),
            'moduleVersion' => \WeprestaAcf::VERSION ?? '1.0.0',
            'help_link' => false,
            'layoutTitle' => $this->trans('Module Starter Configuration', 'Modules.WeprestaAcf.Admin'),
        ]);
    }
}
