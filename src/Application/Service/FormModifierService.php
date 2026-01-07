<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use Context;
use Language;
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
                
                // ⚠️ Exclude groups with global value scope (entity_id = 0)
                // Global values are managed in the builder, not in entity forms
                $foOptions = json_decode($group['fo_options'] ?? '{}', true);
                if (($foOptions['valueScope'] ?? 'entity') === 'global') {
                    \PrestaShopLogger::addLog('[ACF modifyForm] Skipping group "' . $group['title'] . '" (global scope)', 1);
                    continue;
                }
                
                $matchingGroups[] = $group;
            }

            if (empty($matchingGroups)) {
                \PrestaShopLogger::addLog('[ACF modifyForm] No matching groups', 1);
                return;
            }

            \PrestaShopLogger::addLog('[ACF modifyForm] Found ' . count($matchingGroups) . ' matching groups', 1);

            // Render ACF fields using Twig for full field support
            $html = $this->renderAcfFields($matchingGroups, $entityType, $entityId);
            
            \PrestaShopLogger::addLog('[ACF modifyForm] Rendered HTML length: ' . strlen($html), 1);
            // Log meaningful preview - skip leading whitespace
            $trimmedHtml = ltrim($html);
            \PrestaShopLogger::addLog('[ACF modifyForm] HTML preview: ' . substr($trimmedHtml, 0, 400), 1);

            // Add container with pre-rendered HTML
            $formBuilder->add('acf_fields', AcfContainerType::class, [
                'acf_html' => $html,
            ]);
            
            \PrestaShopLogger::addLog('[ACF modifyForm] Added acf_fields to form', 1);
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'ACF FormModifierService error: ' . $e->getMessage(),
                3
            );
        }
    }

    /**
     * Renders ACF fields using Twig for full field support.
     *
     * @param array $matchingGroups Groups that match location rules
     * @param string $entityType Entity type
     * @param int|null $entityId Entity ID
     * @return string Rendered HTML
     */
    private function renderAcfFields(array $matchingGroups, string $entityType, ?int $entityId): string
    {
        $languages = Language::getLanguages(true);
        $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
        $currentLangId = (int) Context::getContext()->language->id;
        $shopId = (int) Context::getContext()->shop->id;

        // Get field values for the entity
        $values = [];
        $valuesPerLang = [];
        if ($entityId !== null && $entityId > 0) {
            $values = $this->valueProvider->getEntityFieldValues($entityType, $entityId, null, $currentLangId);
            foreach ($languages as $lang) {
                $valuesPerLang[(int) $lang['id_lang']] = $this->valueProvider->getEntityFieldValues(
                    $entityType,
                    $entityId,
                    null,
                    (int) $lang['id_lang']
                );
            }
        }

        // Build groups data for Twig
        $groupsData = [];
        \PrestaShopLogger::addLog('[ACF renderAcfFields] Processing ' . count($matchingGroups) . ' groups', 1);
        foreach ($matchingGroups as $group) {
            $groupId = (int) $group['id_wepresta_acf_group'];
            \PrestaShopLogger::addLog('[ACF renderAcfFields] Group ID: ' . $groupId . ', Title: ' . ($group['title'] ?? 'N/A'), 1);
            $fields = $this->fieldRepository->findByGroup($groupId);
            \PrestaShopLogger::addLog('[ACF renderAcfFields] Found ' . count($fields) . ' fields', 1);

            $fieldsHtml = [];
            foreach ($fields as $field) {
                $slug = $field['slug'];
                $type = $field['type'];
                $isTranslatable = (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);
                $fieldType = $this->fieldTypeRegistry->getOrNull($type);

                if (!$fieldType) {
                    continue;
                }

                $fieldData = [
                    'slug' => $slug,
                    'title' => $field['title'],
                    'instructions' => $field['instructions'],
                    'required' => (bool) (json_decode($field['validation'] ?? '{}', true)['required'] ?? false),
                    'translatable' => $isTranslatable,
                ];

                // For repeater fields, load children and generate JS templates
                if ($type === 'repeater') {
                    $fieldId = (int) $field['id_wepresta_acf_field'];
                    $children = $this->fieldRepository->findByParent($fieldId);
                    $field['children'] = $children;
                    $field['jsTemplates'] = [];
                    foreach ($children as $child) {
                        $childType = $this->fieldTypeRegistry->getOrNull($child['type']);
                        if ($childType) {
                            $field['jsTemplates'][$child['slug']] = $childType->getJsTemplate($child);
                        }
                    }
                }

                if ($isTranslatable) {
                    // Render field for each language
                    $langInputs = [];
                    foreach ($languages as $lang) {
                        $langId = (int) $lang['id_lang'];
                        $langValue = $valuesPerLang[$langId][$slug] ?? null;
                        $langInputs[] = [
                            'id_lang' => $langId,
                            'iso_code' => $lang['iso_code'],
                            'name' => $lang['name'],
                            'is_default' => $langId === $defaultLangId,
                            'html' => $fieldType->renderAdminInput($field, $langValue, [
                                'prefix' => 'acf_',
                                'suffix' => '_' . $langId,
                                'fieldRenderer' => $this->fieldTypeRegistry,
                            ]),
                        ];
                    }
                    $fieldData['lang_inputs'] = $langInputs;
                    $fieldData['html'] = '';
                } else {
                    $value = $values[$slug] ?? null;
                    $fieldData['html'] = $fieldType->renderAdminInput($field, $value, [
                        'prefix' => 'acf_',
                        'fieldRenderer' => $this->fieldTypeRegistry,
                    ]);
                    $fieldData['lang_inputs'] = [];
                }

                $fieldsHtml[] = $fieldData;
            }

            $groupsData[] = [
                'id' => $groupId,
                'title' => $group['title'],
                'description' => $group['description'],
                'fields' => $fieldsHtml,
            ];
        }

        // Build API URL
        $apiUrl = $this->getAdminApiBaseUrl();

        \PrestaShopLogger::addLog('[ACF renderAcfFields] Total groups to render: ' . count($groupsData), 1);
        foreach ($groupsData as $g) {
            \PrestaShopLogger::addLog('[ACF renderAcfFields] Group "' . $g['title'] . '" has ' . count($g['fields']) . ' fields', 1);
        }

        // Render using Twig
        return $this->twig->render('@Modules/wepresta_acf/views/templates/admin/form-theme/acf_entity_fields.html.twig', [
            'groups' => $groupsData,
            'entity_type' => $entityType,
            'entity_id' => $entityId ?? 0,
            'api_url' => $apiUrl,
        ]);
    }

    /**
     * Gets the admin API base URL.
     *
     * @return string
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
                return preg_replace('/\/values$/', '', $url);
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // Fallback to manual URL construction
        $adminDir = basename(_PS_ADMIN_DIR_);
        $baseUrl = $context->shop->getBaseURL(true) . $adminDir;
        return $baseUrl . '/modules/wepresta_acf/api';
    }

    /**
     * Extracts ACF field data from submitted form data.
     *
     * @param array $formData The submitted form data
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
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
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
        if (isset($params['form_data']) && is_array($params['form_data'])) {
            foreach ($possibleKeys as $key) {
                if (isset($params['form_data'][$key]) && (int) $params['form_data'][$key] > 0) {
                    return (int) $params['form_data'][$key];
                }
            }
        }

        return null;
    }
}
