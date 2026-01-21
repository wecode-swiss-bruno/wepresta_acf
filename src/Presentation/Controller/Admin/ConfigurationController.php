<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

/**
 * ConfigurationController - Module configuration admin page.
 */

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Module;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use WeprestaAcf\Application\Form\ConfigurationType;
use WeprestaAcf\Application\Service\FieldTypeLoader;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;

class ConfigurationController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly ConfigurationAdapter $config,
        private readonly FieldTypeLoader $fieldTypeLoader,
        private readonly TranslatorInterface $translator
    ) {
        // Note: parent::__construct() is NOT called for PS8/PS9 compatibility
        // In PS9 (Symfony 6.x), calling parent constructor causes "Cannot call constructor" error
    }

    public function configuration(Request $request): Response
    {
        // Load custom field types
        $this->fieldTypeLoader->loadAllCustomTypes();

        // Build form with current values
        $formData = $this->getFormData();
        $form = $this->createForm(ConfigurationType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->saveFormData($data);

            $this->addFlash('success', $this->trans('Settings saved successfully.', 'Admin.Notifications.Success'));

            return $this->redirectToRoute('wepresta_acf_configuration');
        }

        // Get field types info
        $fieldTypes = $this->fieldTypeLoader->getLoadedTypesInfo();
        $discoveryPaths = $this->fieldTypeLoader->getDiscoveryPaths();

        // Get module version
        $module = Module::getInstanceByName('wepresta_acf');
        $moduleVersion = $module ? $module::VERSION : '1.0.0';

        return $this->render('@Modules/wepresta_acf/views/templates/admin/configuration.html.twig', [
            'configurationForm' => $form->createView(),
            'moduleVersion' => $moduleVersion,
            'fieldTypes' => $fieldTypes,
            'discoveryPaths' => $discoveryPaths,
            'layoutTitle' => $this->trans('ACF Configuration', 'Modules.Weprestaacf.Admin'),
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
     * Get form data from configuration.
     */
    private function getFormData(): array
    {
        return [
            'debug' => $this->config->getBool('WEPRESTA_ACF_DEBUG', false),
            'max_file_size' => (int) ($this->config->getInt('WEPRESTA_ACF_MAX_FILE_SIZE') / 1048576) ?: 10, // Convert bytes to MB
        ];
    }

    /**
     * Save form data to configuration.
     */
    private function saveFormData(array $data): void
    {
        // PrestaShop Configuration expects integers for boolean values (1 or 0)
        Configuration::updateValue('WEPRESTA_ACF_DEBUG', ! empty($data['debug']) ? 1 : 0);
        Configuration::updateValue('WEPRESTA_ACF_MAX_FILE_SIZE', ((int) ($data['max_file_size'] ?? 10)) * 1048576);
    }
}
