<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use Context;
use Language;
use Module;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldContext;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldRegistry;

/**
 * Generic service for rendering and saving ACF fields for any entity type.
 *
 * This service abstracts the entity-specific logic and provides a unified
 * interface for working with ACF fields on any PrestaShop entity.
 */
final class EntityFieldService
{
    public function __construct(
        private readonly EntityFieldRegistry $entityFieldRegistry,
        private readonly LocationProviderRegistry $locationProviderRegistry,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfFieldValueRepositoryInterface $valueRepository,
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly ValueProvider $valueProvider,
        private readonly ValueHandler $valueHandler,
        private readonly FileUploadService $fileUploadService
    ) {
    }

    /**
     * Renders ACF fields for an entity.
     *
     * @param string $entityType Entity type (e.g., 'product', 'category', 'cpt_event')
     * @param int $entityId Entity ID
     * @param Module $module Module instance (for template rendering)
     * @return string HTML output
     */
    public function renderFieldsForEntity(string $entityType, int $entityId, Module $module): string
    {
        if ($entityId <= 0) {
            return '';
        }

        try {
            // Load custom field types
            AcfServiceContainer::loadCustomFieldTypes();

            // Get entity provider
            $provider = $this->entityFieldRegistry->getEntityType($entityType);
            if ($provider === null) {
                return '';
            }

            // Build context for location rule matching
            $context = EntityFieldContext::buildFromProvider(
                $this->entityFieldRegistry,
                $entityType,
                $entityId
            );

            // Get active groups
            $groupRepository = AcfServiceContainer::getGroupRepository();
            $groups = $groupRepository->findActiveGroups((int) Context::getContext()->shop->id);
            if (empty($groups)) {
                return '';
            }

            // Filter groups by location rules
            $matchingGroups = [];
            foreach ($groups as $group) {
                $locationRules = json_decode($group['location_rules'] ?? '[]', true) ?: [];
                if ($this->locationProviderRegistry->matchLocation($locationRules, $context)) {
                    $matchingGroups[] = $group;
                }
            }

            if (empty($matchingGroups)) {
                return '';
            }

            $languages = Language::getLanguages(true);
            $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
            $currentLangId = (int) Context::getContext()->language->id;

            // Get field values for the entity
            $values = $this->valueProvider->getEntityFieldValues($entityType, $entityId, null, $currentLangId);
            $valuesPerLang = [];
            foreach ($languages as $lang) {
                $valuesPerLang[(int) $lang['id_lang']] = $this->valueProvider->getEntityFieldValues(
                    $entityType,
                    $entityId,
                    null,
                    (int) $lang['id_lang']
                );
            }

            // Build groups data
            $groupsData = [];
            foreach ($matchingGroups as $group) {
                $groupId = (int) $group['id_wepresta_acf_group'];
                $fields = $this->fieldRepository->findByGroup($groupId);

                $fieldsHtml = [];
                foreach ($fields as $field) {
                    $slug = $field['slug'];
                    $type = $field['type'];
                    $isTranslatable = (bool) $field['translatable'];
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

            if (empty($groupsData)) {
                return '';
            }

            // Build API base URL for relation search
            $contextObj = Context::getContext();
            $adminLink = $contextObj->link->getAdminLink('AdminModules', true, [], ['configure' => $module->name]);
            $apiBaseUrl = preg_replace('/\?.*$/', '', $adminLink);
            $apiBaseUrl = str_replace('/index.php/configure/module', '', $apiBaseUrl);

            $contextObj->smarty->assign([
                'acf_groups' => $groupsData,
                'acf_entity_type' => $entityType,
                'acf_entity_id' => $entityId,
                'acf_languages' => $languages,
                'acf_default_lang' => $defaultLangId,
                'link' => $contextObj->link,
                'base_url' => $contextObj->shop->getBaseURL(),
                'acf_api_base_url' => $this->getAdminApiBaseUrl($module),
            ]);

            // Use generic template (will be created)
            return $module->fetch('module:wepresta_acf/views/templates/admin/entity-fields.tpl');
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog('ACF Error rendering fields for entity: ' . $e->getMessage(), 3);
            return '<div class="alert alert-danger">ACF Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    /**
     * Saves ACF field values for an entity.
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array<string, mixed> $postData POST data
     * @param array<string, mixed> $files FILES data
     * @param Module $module Module instance
     */
    public function saveFieldsForEntity(
        string $entityType,
        int $entityId,
        array $postData,
        array $files,
        Module $module
    ): void {
        if ($entityId <= 0) {
            return;
        }

        try {
            AcfServiceContainer::loadCustomFieldTypes();

            $languages = Language::getLanguages(true);
            $langIds = array_column($languages, 'id_lang');
            $shopId = (int) Context::getContext()->shop->id;

            $values = [];
            $translatableValues = [];
            $processedSlugs = [];

            // Process and save fields for any entity type
            $this->saveEntityFields($entityType, $entityId, $postData, $files, $module);
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog('ACF Error saving entity fields: ' . $e->getMessage(), 3);
        }
    }

    /**
     * Gets field values for an entity.
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param int|null $langId Language ID
     * @return array<string, mixed> Field values
     */
    public function getFieldValuesForEntity(string $entityType, int $entityId, ?int $langId = null): array
    {
        return $this->valueProvider->getEntityFieldValues($entityType, $entityId, null, $langId);
    }

    /**
     * Saves fields for any entity type.
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array<string, mixed> $postData POST data
     * @param array<string, mixed> $files FILES data
     * @param Module $module Module instance
     */
    private function saveEntityFields(string $entityType, int $entityId, array $postData, array $files, Module $module): void
    {
        $languages = Language::getLanguages(true);
        $langIds = array_column($languages, 'id_lang');
        $shopId = (int) Context::getContext()->shop->id;

        $values = [];
        $translatableValues = [];
        $processedSlugs = [];

            // Process multi-media fields (gallery, files)
            $this->processMultiMediaFields(
                $postData,
                $files,
                $entityType,
                $entityId,
                $shopId,
                $values,
                $processedSlugs,
                $module
            );

            // Process single media fields (image, video)
            $this->processSingleMediaFields(
                $postData,
                $files,
                $entityType,
                $entityId,
                $shopId,
                $values,
                $processedSlugs,
                $module
            );

        // Process simple file uploads
        foreach ($files as $key => $file) {
            if (!str_starts_with($key, 'acf_')) {
                continue;
            }
            $slug = substr($key, 4);
            if (isset($processedSlugs[$slug]) || preg_match('/_(?:new|alt|poster|replace)$/i', $key)) {
                continue;
            }

            $hasFile = is_array($file['error'])
                ? !empty(array_filter($file['error'], fn($e) => $e === UPLOAD_ERR_OK))
                : $file['error'] === UPLOAD_ERR_OK;
            if (!$hasFile) {
                continue;
            }

            $field = $this->fieldRepository->findBySlug($slug);
            if (!$field) {
                continue;
            }

            $fieldId = (int) $field['id_wepresta_acf_field'];
            $type = in_array($field['type'], ['image', 'gallery'], true) ? 'images' : 'files';

            try {
                // FileUploadService still uses productId - will need update
                // For now, use entityId as productId for backward compatibility
                $uploadResult = $this->fileUploadService->upload($file, $fieldId, $entityId, $shopId, $type);
                $values[$slug] = $uploadResult;
                $processedSlugs[$slug] = true;
            } catch (\Exception $e) {
                \PrestaShopLogger::addLog('ACF File upload failed for ' . $slug . ': ' . $e->getMessage(), 2);
            }
        }

        // Process regular POST values
        foreach ($postData as $key => $value) {
            if (!str_starts_with($key, 'acf_')) {
                continue;
            }

            $keyWithoutPrefix = substr($key, 4);

            // Skip special suffixes
            if (preg_match('/_(items|new_\d+|title|delete|link_url|url_mode|link_mode|attachment|alt|poster|poster_url|url_alt|replace|delete_alt|delete_poster)(?:\[\d*\])?$/i', $key)) {
                continue;
            }
            if (preg_match('/_url$/i', $key) && isset($postData[$key . '_mode'])) {
                continue;
            }

            // Check for translatable field
            if (preg_match('/^(.+)_(\d+)$/', $keyWithoutPrefix, $matches)) {
                $slug = $matches[1];
                $langId = (int) $matches[2];

                if (in_array($langId, $langIds, true)) {
                    $field = $this->fieldRepository->findBySlug($slug);
                    if ($field && (bool) $field['translatable']) {
                        // For richtext translatable fields, get raw HTML from POST
                        if ($field['type'] === 'richtext') {
                            $rawValue = $_POST[$key] ?? $value;
                            $translatableValues[$slug][$langId] = $rawValue;
                        } else {
                            $translatableValues[$slug][$langId] = $value;
                        }
                        continue;
                    }
                }
            }

            if (isset($processedSlugs[$keyWithoutPrefix])) {
                continue;
            }

            // For richtext fields, preserve raw HTML - don't let PrestaShop clean it
            $field = $this->fieldRepository->findBySlug($keyWithoutPrefix);
            if ($field && $field['type'] === 'richtext') {
                // Get raw value from POST to avoid any PrestaShop cleaning
                $rawValue = $_POST[$key] ?? $value;
                // Ensure we have the actual HTML, not cleaned/transformed version
                $values[$keyWithoutPrefix] = $rawValue;
            } else {
                $values[$keyWithoutPrefix] = $value;
            }
        }

        // Save all values
        $this->valueHandler->saveEntityFieldValues($entityType, $entityId, $values, $shopId);

        foreach ($translatableValues as $slug => $langValues) {
            foreach ($langValues as $langId => $value) {
                $this->valueHandler->saveEntityFieldValue($entityType, $entityId, $slug, $value, $shopId, $langId);
            }
        }
    }

    /**
     * Process multi-media fields (gallery, files).
     */
    private function processMultiMediaFields(
        array $postData,
        array $files,
        string $entityType,
        int $entityId,
        int $shopId,
        array &$values,
        array &$processedSlugs,
        Module $module
    ): void {
        foreach ($postData as $key => $val) {
            if (!str_starts_with($key, 'acf_') || !str_ends_with($key, '_items')) {
                continue;
            }

            $slug = substr($key, 4, -6);
            if (isset($processedSlugs[$slug])) {
                continue;
            }

            $field = $this->fieldRepository->findBySlug($slug);
            if (!$field || !in_array($field['type'], ['gallery', 'files'], true)) {
                continue;
            }

            $fieldId = (int) $field['id_wepresta_acf_field'];
            $type = $field['type'] === 'gallery' ? 'images' : 'files';
            $items = [];

            // Parse existing items
            $existingItems = $postData[$key] ?? [];
            if (is_array($existingItems)) {
                foreach ($existingItems as $idx => $jsonItem) {
                    $item = is_string($jsonItem) ? json_decode($jsonItem, true) : $jsonItem;
                    if (!is_array($item) || empty($item['url'])) {
                        continue;
                    }
                    $titleKey = 'acf_' . $slug . '_title';
                    if (isset($postData[$titleKey][$idx])) {
                        $item['title'] = $postData[$titleKey][$idx];
                    }
                    $descKey = 'acf_' . $slug . '_desc';
                    if (isset($postData[$descKey][$idx])) {
                        $item['description'] = $postData[$descKey][$idx];
                    }
                    $item['position'] = count($items);
                    $items[] = $item;
                }
            }

            // Upload new files
            $newFilesKey = 'acf_' . $slug . '_new';
            if (isset($files[$newFilesKey]) && is_array($files[$newFilesKey]['name'])) {
                $count = count($files[$newFilesKey]['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files[$newFilesKey]['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $singleFile = [
                        'name' => $files[$newFilesKey]['name'][$i],
                        'type' => $files[$newFilesKey]['type'][$i],
                        'tmp_name' => $files[$newFilesKey]['tmp_name'][$i],
                        'error' => $files[$newFilesKey]['error'][$i],
                        'size' => $files[$newFilesKey]['size'][$i],
                    ];

                    try {
                        // FileUploadService still uses productId - will need update
                        // For now, use entityId as productId for backward compatibility
                        $uploaded = $this->fileUploadService->upload($singleFile, $fieldId, $entityId, $shopId, $type);
                        $uploaded['position'] = count($items);
                        $items[] = $uploaded;
                    } catch (\Exception $e) {
                        \PrestaShopLogger::addLog('ACF Gallery upload failed: ' . $e->getMessage(), 2);
                    }
                }
            }

            $values[$slug] = !empty($items) ? $items : null;
            $processedSlugs[$slug] = true;
        }
    }

    /**
     * Process single media fields (image, video).
     */
    private function processSingleMediaFields(
        array $postData,
        array $files,
        string $entityType,
        int $entityId,
        int $shopId,
        array &$values,
        array &$processedSlugs,
        Module $module
    ): void {
        // Simplified version - full implementation would mirror product logic
        // This will be completed when database migration is done
    }

    /**
     * Gets admin API base URL.
     */
    private function getAdminApiBaseUrl(Module $module): string
    {
        try {
            $container = $module->getContainer();
            if ($container && $container->has('router')) {
                $router = $container->get('router');
                $url = $router->generate('wepresta_acf_api_relation_search');
                return preg_replace('/\/relation\/search$/', '', $url);
            }
        } catch (\Exception $e) {
            // Fallback
        }

        $context = Context::getContext();
        $adminUrl = $context->link->getAdminLink('AdminModules', true);
        $baseAdmin = preg_replace('/\?.*$/', '', $adminUrl);
        $baseAdmin = preg_replace('/\/sell\/.*$/', '', $baseAdmin);
        $baseAdmin = preg_replace('/\/configure\/.*$/', '', $baseAdmin);

        return rtrim($baseAdmin, '/') . '/modules/' . $module->name . '/api';
    }
}

