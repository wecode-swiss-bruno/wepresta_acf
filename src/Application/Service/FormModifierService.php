<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Context;
use Exception;
use Language;
use PrestaShopLogger;
use Symfony\Component\Form\FormBuilderInterface;
use Twig\Environment;
use WeprestaAcf\Application\Form\Type\AcfContainerType;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldContext;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldRegistry;

/**
 * Service for integrating ACF fields into Symfony forms.
 *
 * This service handles:
 * - Adding ACF fields to Symfony FormBuilder (via FormBuilderModifier hooks)
 * - Extracting ACF data from submitted forms
 * - Saving ACF data after form submission (via FormHandler hooks)
 *
 * Uses Twig rendering to provide full ACF field support including complex types
 * (image, file, gallery, repeater, list, relation).
 *
 * @see https://devdocs.prestashop-project.org/9/modules/sample-modules/grid-and-identifiable-object-form-hooks-usage-example/
 */
final class FormModifierService
{
    public function __construct(
        private readonly EntityFieldRegistry $entityFieldRegistry,
        private readonly LocationProviderRegistry $locationProviderRegistry,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly ValueProvider $valueProvider,
        private readonly ValueHandler $valueHandler,
        private readonly Environment $twig
    ) {
    }

    /**
     * Modifies a Symfony form to include ACF fields.
     *
     * Called by FormBuilderModifier hooks (e.g., actionCustomerFormBuilderModifier).
     *
     * @param FormBuilderInterface $formBuilder The form builder to modify
     * @param string $entityType Entity type (e.g., 'customer', 'category')
     * @param int|null $entityId Entity ID (null for new entities)
     * @param array $data Form data array (passed by reference)
     */
    public function modifyForm(
        FormBuilderInterface $formBuilder,
        string $entityType,
        ?int $entityId,
        array &$data
    ): void {
        try {
            // Load custom field types
            AcfServiceContainer::loadCustomFieldTypes();

            // Build context for location rule matching
            $context = [
                'entity_type' => $entityType,
                'entity_id' => $entityId ?? 0,
            ];

            // If entity exists, try to build full context from provider
            if ($entityId !== null && $entityId > 0) {
                $contextFromProvider = EntityFieldContext::buildFromProvider(
                    $this->entityFieldRegistry,
                    $entityType,
                    $entityId
                );
                $context = array_merge($context, $contextFromProvider);
            }

            // Get active groups
            $shopId = (int) Context::getContext()->shop->id;
            $groups = $this->groupRepository->findActiveGroups($shopId);

            if (empty($groups)) {
                return;
            }

            // Filter groups by location rules AND exclude global scope groups
            $matchingGroups = [];

            foreach ($groups as $group) {
                $locationRules = json_decode($group['location_rules'] ?? '[]', true) ?: [];

                // Check if group matches location rules
                if (!$this->locationProviderRegistry->matchLocation($locationRules, $context)) {
                    continue;
                }

                $foOptions = json_decode($group['fo_options'] ?? '{}', true);

                if (($foOptions['valueScope'] ?? 'entity') === 'global') {
                    continue;
                }

                $matchingGroups[] = $group;
            }

            if (empty($matchingGroups)) {
                return;
            }

            // Render ACF fields using Twig for full field support
            // Use improved token retrieval logic mirroring EntityFieldService
            $csrfToken = $this->getCsrfToken();

            $html = $this->renderAcfFields($matchingGroups, $entityType, $entityId, $csrfToken);

            $formBuilder->add('acf_fields', AcfContainerType::class, [
                'acf_html' => $html,
            ]);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'ACF FormModifierService error: ' . $e->getMessage(),
                3
            );
        }
    }

    /**
     * Get CSRF token for API.
     * Mirrors logic from EntityFieldService to ensure compatibility with AdminWeprestaAcfBuilder.
     */
    private function getCsrfToken(): string
    {
        // 1. Try to get the current request's _token (verified by PS8 Admin Firewall)
        try {
            $sfContainer = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
            if ($sfContainer && $sfContainer->has('request_stack')) {
                $request = $sfContainer->get('request_stack')->getCurrentRequest();
                if ($request && $request->query->has('_token')) {
                    return (string) $request->query->get('_token');
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // 2. Fallback: Generate token using employee email (matching AcfBuilderController intention)
        try {
            $context = Context::getContext();
            if (isset($context->employee) && $context->employee->id && $context->employee->email) {
                $sfContainer = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
                if ($sfContainer && $sfContainer->has('security.csrf.token_manager')) {
                    return $sfContainer->get('security.csrf.token_manager')->getToken($context->employee->email)->getValue();
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return '';
    }
    /**
     * Extracts ACF field data from submitted form data.
     *
     * @param array $formData The submitted form data
     *
     * @return array<string, mixed> ACF field values keyed by slug
     */
    public function extractAcfData(array $formData): array
    {
        $acfData = [];

        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'acf_')) {
                $slug = substr($key, 4);
                $acfData[$slug] = $value;
            }
        }

        return $acfData;
    }

    /**
     * Saves ACF data after form submission.
     *
     * Called by FormHandler hooks (e.g., actionAfterUpdateCustomerFormHandler).
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $formData Complete form data
     */
    public function saveAcfData(string $entityType, int $entityId, array $formData): void
    {
        if ($entityId <= 0) {
            return;
        }

        try {
            $acfData = $this->extractAcfData($formData);

            if (empty($acfData)) {
                return;
            }

            $shopId = (int) Context::getContext()->shop->id;

            // Handle translatable fields
            $languages = Language::getLanguages(true);
            $translatableValues = [];

            foreach ($acfData as $slug => $value) {
                // Check if this is a translatable field with language suffix
                if (preg_match('/^(.+)_(\d+)$/', $slug, $matches)) {
                    $baseSlug = $matches[1];
                    $langId = (int) $matches[2];

                    $field = $this->fieldRepository->findBySlug($baseSlug);

                    if ($field && (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false)) {
                        $translatableValues[$baseSlug][$langId] = $value;
                        unset($acfData[$slug]);

                        continue;
                    }
                }
            }

            // Save non-translatable values
            $this->valueHandler->saveEntityFieldValues($entityType, $entityId, $acfData, $shopId);

            // Save translatable values
            foreach ($translatableValues as $slug => $langValues) {
                foreach ($langValues as $langId => $value) {
                    $this->valueHandler->saveEntityFieldValue(
                        $entityType,
                        $entityId,
                        $slug,
                        $value,
                        $shopId,
                        $langId
                    );
                }
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'ACF FormModifierService save error: ' . $e->getMessage(),
                3
            );
        }
    }

    /**
     * Gets the entity ID from hook parameters.
     *
     * @param string $entityType Entity type
     * @param array $params Hook parameters
     *
     * @return int|null Entity ID or null if not found
     */
    public function getEntityIdFromParams(string $entityType, array $params): ?int
    {
        // Check common parameter names
        $possibleKeys = ['id', 'id_' . $entityType, $entityType . '_id'];

        foreach ($possibleKeys as $key) {
            if (isset($params[$key]) && (int) $params[$key] > 0) {
                return (int) $params[$key];
            }
        }

        // Check in form_data if present
        if (isset($params['form_data']) && \is_array($params['form_data'])) {
            foreach ($possibleKeys as $key) {
                if (isset($params['form_data'][$key]) && (int) $params['form_data'][$key] > 0) {
                    return (int) $params['form_data'][$key];
                }
            }
        }

        return null;
    }

    /**
     * Renders ACF fields using Vue.js for unified field rendering.
     *
     * @param array $matchingGroups Groups that match location rules
     * @param string $entityType Entity type
     * @param int|null $entityId Entity ID
     *
     * @return string Rendered HTML
     */
    /**
     * Renders ACF fields HTML using Twig.
     */
    /**
     * Renders ACF fields HTML using Twig.
     */
    private function renderAcfFields(
        array $matchingGroups,
        string $entityType,
        ?int $entityId,
        string $csrfToken = ''
    ): string {
        $languages = Language::getLanguages(true);
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
        $currentLangId = (int) Context::getContext()->language->id;
        $shopId = (int) Context::getContext()->shop->id;

        // Build groups data for Vue.js
        $groupsData = [];

        foreach ($matchingGroups as $group) {
            $groupId = (int) $group['id_wepresta_acf_group'];
            $fields = $this->fieldRepository->findByGroup($groupId);

            $fieldsData = [];

            foreach ($fields as $field) {
                $fieldId = (int) $field['id_wepresta_acf_field'];
                $slug = $field['slug'];
                $type = $field['type'];
                $isTranslatable = (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);

                // Get field translations for metadata
                $fieldTranslations = $this->fieldRepository->getFieldTranslations($fieldId);
                $currentLangIso = null;

                foreach ($languages as $lang) {
                    if ((int) $lang['id_lang'] === $currentLangId) {
                        $currentLangIso = $lang['iso_code'];

                        break;
                    }
                }

                // Use translated metadata if available
                $fieldTitle = $field['title'];
                $fieldInstructions = $field['instructions'];

                if ($currentLangIso && isset($fieldTranslations[$currentLangIso])) {
                    $translation = $fieldTranslations[$currentLangIso];

                    if (!empty($translation['title'])) {
                        $fieldTitle = $translation['title'];
                    }

                    if (!empty($translation['instructions'])) {
                        $fieldInstructions = $translation['instructions'];
                    }
                }

                // Parse config
                $config = json_decode($field['config'] ?? '{}', true) ?: [];

                // Parse choices from config
                $choices = [];

                if (isset($config['choices']) && \is_array($config['choices'])) {
                    $choices = $config['choices'];
                }

                // Parse wrapper
                $wrapper = [];

                if (isset($config['wrapper']) && \is_array($config['wrapper'])) {
                    $wrapper = $config['wrapper'];
                }

                $fieldsData[] = [
                    'id' => $fieldId,
                    'slug' => $slug,
                    'type' => $type,
                    'title' => $fieldTitle,
                    'label' => $fieldTitle,
                    'instructions' => $fieldInstructions ?? '',
                    'required' => (bool) (json_decode($field['validation'] ?? '{}', true)['required'] ?? false),
                    'value_translatable' => $isTranslatable,
                    'valueTranslatable' => $isTranslatable,
                    'config' => $config,
                    'choices' => $choices,
                    'wrapper' => $wrapper,
                ];
            }

            $groupsData[] = [
                'id' => $groupId,
                'title' => $group['title'],
                'slug' => $group['slug'] ?? '',
                'description' => $group['description'] ?? '',
                'fields' => $fieldsData,
            ];
        }

        // Get field values for Vue.js (keyed by field ID)
        $values = [];

        if ($entityId !== null && $entityId > 0) {
            // Get values per field ID
            foreach ($groupsData as $group) {
                foreach ($group['fields'] as $field) {
                    $fieldId = $field['id'];
                    $slug = $field['slug'];
                    $isTranslatable = $field['value_translatable'];

                    if ($isTranslatable) {
                        // Get values for all languages
                        $langValues = [];

                        foreach ($languages as $lang) {
                            $langId = (int) $lang['id_lang'];
                            $langValue = $this->valueProvider->getEntityFieldValues($entityType, $entityId, null, $langId);
                            $langValues[$langId] = $langValue[$slug] ?? null;
                        }
                        $values[$fieldId] = $langValues;
                    } else {
                        $fieldValues = $this->valueProvider->getEntityFieldValues($entityType, $entityId, null, $currentLangId);
                        $values[$fieldId] = $fieldValues[$slug] ?? null;
                    }
                }
            }
        }

        // Build API URL
        $apiUrl = $this->getAdminApiBaseUrl();

        // Format languages for Vue.js
        $languagesData = [];

        foreach ($languages as $lang) {
            $languagesData[] = [
                'id_lang' => (int) $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => (int) $lang['id_lang'] === $defaultLangId,
            ];
        }

        // Render using Vue.js Twig template
        return $this->twig->render('@Modules/wepresta_acf/views/templates/admin/form-theme/acf_entity_fields_vue.html.twig', [
            'groups' => $groupsData,
            'values' => $values,
            'entity_type' => $entityType,
            'entity_id' => $entityId ?? 0,
            'api_url' => $apiUrl,
            'languages' => $languagesData,
            'default_lang_id' => $defaultLangId,
            'shop_id' => $shopId,
            'entityFieldsScriptUrl' => _MODULE_DIR_ . 'wepresta_acf/views/js/admin/dist/entity-fields.js',
            'token' => $csrfToken,
        ]);
    }

    /**
     * Get CSRF token for API.
     */


    /**
     * Gets the admin API base URL.
     */
    private function getAdminApiBaseUrl(): string
    {
        $context = Context::getContext();
        $link = $context->link;

        if ($link === null) {
            return '';
        }

        try {
            // Try to get the API URL from the router
            $router = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance()->get('router');

            if ($router) {
                // Use values route and strip /values to get base URL
                $url = $router->generate('wepresta_acf_api_values_save');
                // Remove query string (if any) to prevent double token or malformed URL
                $url = strtok($url, '?');

                return preg_replace('/\/values$/', '', $url);
            }
        } catch (Exception $e) {
            // Fallback
        }

        // Fallback to manual URL construction
        $adminDir = basename(_PS_ADMIN_DIR_);
        $baseUrl = $context->shop->getBaseURL(true) . $adminDir;

        return $baseUrl . '/modules/wepresta_acf/api';
    }
}
