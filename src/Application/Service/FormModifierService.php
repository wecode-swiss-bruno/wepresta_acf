<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use Context;
use Language;
use Symfony\Component\Form\FormBuilderInterface;
use WeprestaAcf\Application\Config\EntityHooksConfig;
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
 * Based on PrestaShop 9's FormBuilderModifier pattern.
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
        private readonly ValueHandler $valueHandler
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

            // Filter groups by location rules
            $matchingGroups = [];
            foreach ($groups as $group) {
                $locationRules = json_decode($group['location_rules'] ?? '[]', true) ?: [];
                if ($this->locationProviderRegistry->matchLocation($locationRules, $context)) {
                    $matchingGroups[] = $group;
                }
            }

            if (empty($matchingGroups)) {
                return;
            }

            // Get existing values if editing
            $existingValues = [];
            if ($entityId !== null && $entityId > 0) {
                $existingValues = $this->valueProvider->getEntityFieldValues($entityType, $entityId);
            }

            // Add ACF fields to form
            foreach ($matchingGroups as $group) {
                $groupId = (int) $group['id_wepresta_acf_group'];
                $fields = $this->fieldRepository->findByGroup($groupId);

                foreach ($fields as $field) {
                    $slug = $field['slug'];
                    $fieldType = $this->fieldTypeRegistry->getOrNull($field['type']);

                    if (!$fieldType) {
                        continue;
                    }

                    // Build Symfony form field options
                    $formFieldName = 'acf_' . $slug;
                    $formFieldOptions = $this->buildFormFieldOptions($field, $existingValues[$slug] ?? null);

                    // Add field to form
                    $symfonyType = $this->mapToSymfonyFormType($field['type']);
                    $formBuilder->add($formFieldName, $symfonyType, $formFieldOptions);

                    // Pre-populate data
                    if (isset($existingValues[$slug])) {
                        $data[$formFieldName] = $existingValues[$slug];
                    }
                }
            }
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'ACF FormModifierService error: ' . $e->getMessage(),
                3
            );
        }
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
                    if ($field && (bool) $field['translatable']) {
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

    /**
     * Maps ACF field type to Symfony form type.
     *
     * @param string $acfType ACF field type
     * @return string Symfony form type class
     */
    private function mapToSymfonyFormType(string $acfType): string
    {
        return match ($acfType) {
            'text', 'url', 'email' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
            'textarea' => \Symfony\Component\Form\Extension\Core\Type\TextareaType::class,
            'richtext' => \PrestaShopBundle\Form\Admin\Type\FormattedTextareaType::class,
            'number' => \Symfony\Component\Form\Extension\Core\Type\NumberType::class,
            'boolean' => \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class,
            'select' => \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
            'radio' => \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
            'checkbox' => \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class,
            'date' => \Symfony\Component\Form\Extension\Core\Type\DateType::class,
            'datetime' => \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class,
            'time' => \Symfony\Component\Form\Extension\Core\Type\TimeType::class,
            'color' => \Symfony\Component\Form\Extension\Core\Type\ColorType::class,
            default => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
        };
    }

    /**
     * Builds form field options from ACF field configuration.
     *
     * @param array $field ACF field configuration
     * @param mixed $existingValue Existing value if editing
     * @return array Symfony form field options
     */
    private function buildFormFieldOptions(array $field, mixed $existingValue): array
    {
        $validation = json_decode($field['validation'] ?? '{}', true) ?: [];
        $settings = json_decode($field['settings'] ?? '{}', true) ?: [];

        $options = [
            'label' => $field['title'] ?? $field['slug'],
            'required' => (bool) ($validation['required'] ?? false),
            'attr' => [
                'class' => 'acf-field acf-field-' . $field['type'],
            ],
        ];

        // Add help text
        if (!empty($field['instructions'])) {
            $options['help'] = $field['instructions'];
        }

        // Set default value
        if ($existingValue !== null) {
            $options['data'] = $existingValue;
        } elseif (isset($field['default_value'])) {
            $options['data'] = $field['default_value'];
        }

        // Handle select/radio choices
        if (in_array($field['type'], ['select', 'radio'], true)) {
            $choices = $settings['options'] ?? [];
            $options['choices'] = array_combine($choices, $choices);

            if ($field['type'] === 'radio') {
                $options['expanded'] = true;
                $options['multiple'] = false;
            }
        }

        // Checkbox options
        if ($field['type'] === 'checkbox') {
            $options['value'] = '1';
        }

        return $options;
    }
}

