<?php
/**
 * ConfigurationController - Module configuration admin page
 */

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use Module;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Form\ConfigurationType;
use WeprestaAcf\Application\Service\FieldTypeLoader;
use WeprestaAcf\Application\Service\SyncService;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;

class ConfigurationController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly ConfigurationAdapter $config,
        private readonly SyncService $syncService,
        private readonly FieldTypeLoader $fieldTypeLoader
    ) {
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

        // Get sync status
        $syncStatus = null;
        if ($this->syncService->isEnabled()) {
            $syncStatus = $this->syncService->getSyncStatus();
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
            'syncEnabled' => $this->syncService->isEnabled(),
            'syncPath' => $this->syncService->getSyncPath(),
            'syncStatus' => $syncStatus,
            'fieldTypes' => $fieldTypes,
            'discoveryPaths' => $discoveryPaths,
            'layoutTitle' => $this->trans('ACF Configuration', 'Modules.Weprestaacf.Admin'),
        ]);
    }

    /**
     * Get form data from configuration.
     */
    private function getFormData(): array
    {
        return [
            'active' => $this->config->getBool('WEPRESTA_ACF_ACTIVE', true),
            'debug' => $this->config->getBool('WEPRESTA_ACF_DEBUG', false),
            'max_file_size' => (int) ($this->config->getInt('WEPRESTA_ACF_MAX_FILE_SIZE') / 1048576) ?: 10, // Convert bytes to MB
            'sync_enabled' => $this->config->getBool('WEPRESTA_ACF_SYNC_ENABLED', false),
            'auto_sync_on_save' => $this->config->getBool('WEPRESTA_ACF_AUTO_SYNC_ON_SAVE', false),
            'sync_on_install' => $this->config->getBool('WEPRESTA_ACF_SYNC_ON_INSTALL', true),
            'sync_path_type' => $this->config->getString('WEPRESTA_ACF_SYNC_PATH_TYPE') ?: 'theme',
            'sync_custom_path' => $this->config->getString('WEPRESTA_ACF_SYNC_CUSTOM_PATH'),
        ];
    }

    /**
     * Save form data to configuration.
     */
    private function saveFormData(array $data): void
    {
        // PrestaShop Configuration expects integers for boolean values (1 or 0)
        \Configuration::updateValue('WEPRESTA_ACF_ACTIVE', !empty($data['active']) ? 1 : 0);
        \Configuration::updateValue('WEPRESTA_ACF_DEBUG', !empty($data['debug']) ? 1 : 0);
        \Configuration::updateValue('WEPRESTA_ACF_MAX_FILE_SIZE', ((int) ($data['max_file_size'] ?? 10)) * 1048576);
        \Configuration::updateValue('WEPRESTA_ACF_SYNC_ENABLED', !empty($data['sync_enabled']) ? 1 : 0);
        \Configuration::updateValue('WEPRESTA_ACF_AUTO_SYNC_ON_SAVE', !empty($data['auto_sync_on_save']) ? 1 : 0);
        \Configuration::updateValue('WEPRESTA_ACF_SYNC_ON_INSTALL', !empty($data['sync_on_install']) ? 1 : 0);
        \Configuration::updateValue('WEPRESTA_ACF_SYNC_PATH_TYPE', $data['sync_path_type'] ?? 'theme');
        \Configuration::updateValue('WEPRESTA_ACF_SYNC_CUSTOM_PATH', $data['sync_custom_path'] ?? '');
    }
}

