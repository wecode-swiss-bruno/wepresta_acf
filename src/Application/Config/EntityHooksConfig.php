<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Config;

/**
 * Centralized configuration for all entity types supported by ACF.
 *
 * This class defines all hooks for Symfony forms (FormBuilderModifier pattern)
 * and legacy ObjectModel entities.
 *
 * Based on PrestaShop 9 hooks documentation:
 * @see https://devdocs.prestashop-project.org/9/modules/concepts/hooks/list-of-hooks/
 */
final class EntityHooksConfig
{
    /**
     * Entities using Symfony Form (FormBuilderModifier pattern).
     * 41 entities total.
     *
     * @var array<string, array{
     *     label: string,
     *     category: string,
     *     form_builder_hook: string,
     *     form_handler_hooks: array<string>,
     *     id_param: string,
     *     display_hooks?: array<string>
     * }>
     */
    public const SYMFONY_ENTITIES = [
        // ============================================
        // CATALOGUE (7)
        // ============================================
        'product' => [
            'label' => 'Product',
            'category' => 'Catalog',
            'form_builder_hook' => 'actionProductFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateProductFormHandler',
                'actionAfterUpdateProductFormHandler',
            ],
            'id_param' => 'id',
            'display_hooks' => ['displayAdminProductsExtra'],
        ],
        'category' => [
            'label' => 'Category',
            'category' => 'Catalog',
            'form_builder_hook' => 'actionCategoryFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCategoryFormHandler',
                'actionAfterUpdateCategoryFormHandler',
            ],
            'id_param' => 'id',
        ],
        'root_category' => [
            'label' => 'Root Category',
            'category' => 'Catalog',
            'form_builder_hook' => 'actionRootCategoryFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateRootCategoryFormHandler',
                'actionAfterUpdateRootCategoryFormHandler',
            ],
            'id_param' => 'id',
        ],
        'manufacturer' => [
            'label' => 'Manufacturer (Brand)',
            'category' => 'Catalog',
            'form_builder_hook' => 'actionManufacturerFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateManufacturerFormHandler',
                'actionAfterUpdateManufacturerFormHandler',
            ],
            'id_param' => 'id',
        ],
        'manufacturer_address' => [
            'label' => 'Manufacturer Address',
            'category' => 'Catalog',
            'form_builder_hook' => 'actionManufacturerAddressFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateManufacturerAddressFormHandler',
                'actionAfterUpdateManufacturerAddressFormHandler',
            ],
            'id_param' => 'id',
        ],
        'feature' => [
            'label' => 'Feature',
            'category' => 'Catalog',
            'form_builder_hook' => 'actionFeatureFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateFeatureFormHandler',
                'actionAfterUpdateFeatureFormHandler',
            ],
            'id_param' => 'id',
        ],
        'feature_value' => [
            'label' => 'Feature Value',
            'category' => 'Catalog',
            'form_builder_hook' => 'actionFeatureValueFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateFeatureValueFormHandler',
                'actionAfterUpdateFeatureValueFormHandler',
            ],
            'id_param' => 'id',
        ],

        // ============================================
        // CLIENTS (1)
        // ============================================
        'customer' => [
            'label' => 'Customer',
            'category' => 'Customers',
            'form_builder_hook' => 'actionCustomerFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCustomerFormHandler',
                'actionAfterUpdateCustomerFormHandler',
            ],
            'id_param' => 'id',
            'display_hooks' => ['displayAdminCustomers'],
        ],

        // ============================================
        // COMMANDES (4)
        // ============================================
        'order_state' => [
            'label' => 'Order State',
            'category' => 'Orders',
            'form_builder_hook' => 'actionOrderStateFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateOrderStateFormHandler',
                'actionAfterUpdateOrderStateFormHandler',
            ],
            'id_param' => 'id',
        ],
        'order_return_state' => [
            'label' => 'Order Return State',
            'category' => 'Orders',
            'form_builder_hook' => 'actionOrderReturnStateFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateOrderReturnStateFormHandler',
                'actionAfterUpdateOrderReturnStateFormHandler',
            ],
            'id_param' => 'id',
        ],
        'order_return' => [
            'label' => 'Order Return',
            'category' => 'Orders',
            'form_builder_hook' => 'actionOrderReturnFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateOrderReturnFormHandler',
                'actionAfterUpdateOrderReturnFormHandler',
            ],
            'id_param' => 'id',
        ],
        'order_message' => [
            'label' => 'Order Message',
            'category' => 'Orders',
            'form_builder_hook' => 'actionOrderMessageFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateOrderMessageFormHandler',
                'actionAfterUpdateOrderMessageFormHandler',
            ],
            'id_param' => 'id',
        ],

        // ============================================
        // CMS (2)
        // ============================================
        'cms_page' => [
            'label' => 'CMS Page',
            'category' => 'CMS',
            'form_builder_hook' => 'actionCmsPageFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCmsPageFormHandler',
                'actionAfterUpdateCmsPageFormHandler',
            ],
            'id_param' => 'id',
        ],
        'cms_category' => [
            'label' => 'CMS Category',
            'category' => 'CMS',
            'form_builder_hook' => 'actionCmsPageCategoryFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCmsPageCategoryFormHandler',
                'actionAfterUpdateCmsPageCategoryFormHandler',
            ],
            'id_param' => 'id',
        ],

        // ============================================
        // LOCALISATION (6)
        // ============================================
        'language' => [
            'label' => 'Language',
            'category' => 'Localization',
            'form_builder_hook' => 'actionLanguageFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateLanguageFormHandler',
                'actionAfterUpdateLanguageFormHandler',
            ],
            'id_param' => 'id',
        ],
        'zone' => [
            'label' => 'Zone',
            'category' => 'Localization',
            'form_builder_hook' => 'actionZoneFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateZoneFormHandler',
                'actionAfterUpdateZoneFormHandler',
            ],
            'id_param' => 'id',
        ],
        'country' => [
            'label' => 'Country',
            'category' => 'Localization',
            'form_builder_hook' => 'actionCountryFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCountryFormHandler',
                'actionAfterUpdateCountryFormHandler',
            ],
            'id_param' => 'id',
        ],
        'state' => [
            'label' => 'State',
            'category' => 'Localization',
            'form_builder_hook' => 'actionStateFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateStateFormHandler',
                'actionAfterUpdateStateFormHandler',
            ],
            'id_param' => 'id',
        ],
        'currency' => [
            'label' => 'Currency',
            'category' => 'Localization',
            'form_builder_hook' => 'actionCurrencyFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCurrencyFormHandler',
                'actionAfterUpdateCurrencyFormHandler',
            ],
            'id_param' => 'id',
        ],
        'tax' => [
            'label' => 'Tax',
            'category' => 'Localization',
            'form_builder_hook' => 'actionTaxFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateTaxFormHandler',
                'actionAfterUpdateTaxFormHandler',
            ],
            'id_param' => 'id',
        ],

        // ============================================
        // CONFIGURATION (8)
        // ============================================
        'tax_rules_group' => [
            'label' => 'Tax Rules Group',
            'category' => 'Configuration',
            'form_builder_hook' => 'actionTaxRulesGroupFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateTaxRulesGroupFormHandler',
                'actionAfterUpdateTaxRulesGroupFormHandler',
            ],
            'id_param' => 'id',
        ],
        'title' => [
            'label' => 'Title (Civility)',
            'category' => 'Configuration',
            'form_builder_hook' => 'actionTitleFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateTitleFormHandler',
                'actionAfterUpdateTitleFormHandler',
            ],
            'id_param' => 'id',
        ],
        'employee' => [
            'label' => 'Employee',
            'category' => 'Configuration',
            'form_builder_hook' => 'actionEmployeeFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateEmployeeFormHandler',
                'actionAfterUpdateEmployeeFormHandler',
            ],
            'id_param' => 'id',
        ],
        'profile' => [
            'label' => 'Profile',
            'category' => 'Configuration',
            'form_builder_hook' => 'actionProfileFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateProfileFormHandler',
                'actionAfterUpdateProfileFormHandler',
            ],
            'id_param' => 'id',
        ],
        'meta' => [
            'label' => 'Meta',
            'category' => 'Configuration',
            'form_builder_hook' => 'actionMetaFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateMetaFormHandler',
                'actionAfterUpdateMetaFormHandler',
            ],
            'id_param' => 'id',
        ],
        'carrier' => [
            'label' => 'Carrier',
            'category' => 'Configuration',
            'form_builder_hook' => 'actionCarrierFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCarrierFormHandler',
                'actionAfterUpdateCarrierFormHandler',
            ],
            'id_param' => 'id',
        ],
        'contact' => [
            'label' => 'Contact',
            'category' => 'Configuration',
            'form_builder_hook' => 'actionContactFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateContactFormHandler',
                'actionAfterUpdateContactFormHandler',
            ],
            'id_param' => 'id',
        ],
        'image_type' => [
            'label' => 'Image Type',
            'category' => 'Configuration',
            'form_builder_hook' => 'actionImageTypeFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateImageTypeFormHandler',
                'actionAfterUpdateImageTypeFormHandler',
            ],
            'id_param' => 'id',
        ],

        // ============================================
        // MARKETING (2)
        // ============================================
        'catalog_price_rule' => [
            'label' => 'Catalog Price Rule',
            'category' => 'Marketing',
            'form_builder_hook' => 'actionCatalogPriceRuleFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCatalogPriceRuleFormHandler',
                'actionAfterUpdateCatalogPriceRuleFormHandler',
            ],
            'id_param' => 'id',
        ],
        'cart_rule' => [
            'label' => 'Cart Rule',
            'category' => 'Marketing',
            'form_builder_hook' => 'actionCartRuleFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCartRuleFormHandler',
                'actionAfterUpdateCartRuleFormHandler',
            ],
            'id_param' => 'id',
        ],

        // ============================================
        // AVANCÉ (5)
        // ============================================
        'search_engine' => [
            'label' => 'Search Engine',
            'category' => 'Advanced',
            'form_builder_hook' => 'actionSearchEngineFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateSearchEngineFormHandler',
                'actionAfterUpdateSearchEngineFormHandler',
            ],
            'id_param' => 'id',
        ],
        'search_term' => [
            'label' => 'Search Term',
            'category' => 'Advanced',
            'form_builder_hook' => 'actionSearchTermFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateSearchTermFormHandler',
                'actionAfterUpdateSearchTermFormHandler',
            ],
            'id_param' => 'id',
        ],
        'sql_request' => [
            'label' => 'SQL Request',
            'category' => 'Advanced',
            'form_builder_hook' => 'actionSqlRequestFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateSqlRequestFormHandler',
                'actionAfterUpdateSqlRequestFormHandler',
            ],
            'id_param' => 'id',
        ],
        'webservice_key' => [
            'label' => 'Webservice Key',
            'category' => 'Advanced',
            'form_builder_hook' => 'actionWebserviceKeyFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateWebserviceKeyFormHandler',
                'actionAfterUpdateWebserviceKeyFormHandler',
            ],
            'id_param' => 'id',
        ],
        'api_client' => [
            'label' => 'API Client',
            'category' => 'Advanced',
            'form_builder_hook' => 'actionApiClientFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateApiClientFormHandler',
                'actionAfterUpdateApiClientFormHandler',
            ],
            'id_param' => 'id',
        ],

        // ============================================
        // AUTRES (6)
        // ============================================
        'attachment' => [
            'label' => 'Attachment',
            'category' => 'Other',
            'form_builder_hook' => 'actionAttachmentFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateAttachmentFormHandler',
                'actionAfterUpdateAttachmentFormHandler',
            ],
            'id_param' => 'id',
        ],
        'product_image' => [
            'label' => 'Product Image',
            'category' => 'Other',
            'form_builder_hook' => 'actionProductImageFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateProductImageFormHandler',
                'actionAfterUpdateProductImageFormHandler',
            ],
            'id_param' => 'id',
        ],
        'product_shops' => [
            'label' => 'Product Shops',
            'category' => 'Other',
            'form_builder_hook' => 'actionProductShopsFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateProductShopsFormHandler',
                'actionAfterUpdateProductShopsFormHandler',
            ],
            'id_param' => 'id',
        ],
        'combination_list' => [
            'label' => 'Combination List',
            'category' => 'Other',
            'form_builder_hook' => 'actionCombinationListFormBuilderModifier',
            'form_handler_hooks' => [
                'actionAfterCreateCombinationListFormHandler',
                'actionAfterUpdateCombinationListFormHandler',
            ],
            'id_param' => 'id',
        ],
    ];

    /**
     * Entities using legacy ObjectModel (no FormBuilderModifier).
     * 8 entities total.
     *
     * @var array<string, array{
     *     label: string,
     *     category: string,
     *     object_class: string,
     *     display_hooks?: array<string>,
     *     action_hooks: array<string>
     * }>
     */
    public const LEGACY_ENTITIES = [
        'customer_address' => [
            'label' => 'Customer Address',
            'category' => 'Customers',
            'object_class' => 'Address',
            'display_hooks' => ['displayAdminCustomersAddressesItemAction'],
            'action_hooks' => [
                'actionObjectAddressAddAfter',
                'actionObjectAddressUpdateAfter',
            ],
        ],
        'customer_group' => [
            'label' => 'Customer Group',
            'category' => 'Customers',
            'object_class' => 'Group',
            'action_hooks' => [
                'actionObjectGroupAddAfter',
                'actionObjectGroupUpdateAfter',
            ],
        ],
        'supplier' => [
            'label' => 'Supplier',
            'category' => 'Catalog',
            'object_class' => 'Supplier',
            'action_hooks' => [
                'actionObjectSupplierAddAfter',
                'actionObjectSupplierUpdateAfter',
            ],
        ],
        'store' => [
            'label' => 'Store',
            'category' => 'Configuration',
            'object_class' => 'Store',
            'display_hooks' => ['displayAdminStoreInformation'],
            'action_hooks' => [
                'actionObjectStoreAddAfter',
                'actionObjectStoreUpdateAfter',
            ],
        ],
        'alias' => [
            'label' => 'Alias',
            'category' => 'Advanced',
            'object_class' => 'Alias',
            'action_hooks' => [
                'actionObjectAliasAddAfter',
                'actionObjectAliasUpdateAfter',
            ],
        ],
        'tag' => [
            'label' => 'Tag',
            'category' => 'Catalog',
            'object_class' => 'Tag',
            'action_hooks' => [
                'actionObjectTagAddAfter',
                'actionObjectTagUpdateAfter',
            ],
        ],
        'order' => [
            'label' => 'Order',
            'category' => 'Orders',
            'object_class' => 'Order',
            'display_hooks' => ['displayAdminOrderMain', 'displayAdminOrderSide'],
            'action_hooks' => [
                'actionObjectOrderAddAfter',
                'actionObjectOrderUpdateAfter',
            ],
        ],
        'cart' => [
            'label' => 'Cart',
            'category' => 'Orders',
            'object_class' => 'Cart',
            'action_hooks' => [
                'actionObjectCartAddAfter',
                'actionObjectCartUpdateAfter',
            ],
        ],
    ];

    /**
     * Map hook name to entity type for quick lookup.
     *
     * @var array<string, string>|null
     */
    private static ?array $hookToEntityMap = null;

    /**
     * Get all hooks that need to be registered.
     *
     * @return array<string> List of hook names
     */
    public static function getAllHooks(): array
    {
        $hooks = [];

        // Symfony entities
        foreach (self::SYMFONY_ENTITIES as $entity) {
            $hooks[] = $entity['form_builder_hook'];
            foreach ($entity['form_handler_hooks'] as $hook) {
                $hooks[] = $hook;
            }
            if (isset($entity['display_hooks'])) {
                foreach ($entity['display_hooks'] as $hook) {
                    $hooks[] = $hook;
                }
            }
        }

        // Legacy entities
        foreach (self::LEGACY_ENTITIES as $entity) {
            foreach ($entity['action_hooks'] as $hook) {
                $hooks[] = $hook;
            }
            if (isset($entity['display_hooks'])) {
                foreach ($entity['display_hooks'] as $hook) {
                    $hooks[] = $hook;
                }
            }
        }

        return array_unique($hooks);
    }

    /**
     * Get entity type from a hook name.
     *
     * @param string $hookName The hook name
     * @return string|null Entity type or null if not found
     */
    public static function getEntityByHook(string $hookName): ?string
    {
        if (self::$hookToEntityMap === null) {
            self::buildHookToEntityMap();
        }

        // Case-insensitive lookup
        $hookNameLower = strtolower($hookName);
        return self::$hookToEntityMap[$hookNameLower] ?? null;
    }

    /**
     * Get all entities grouped by category.
     *
     * @return array<string, array<string, array{label: string, type: string}>>
     */
    public static function getEntitiesGroupedByCategory(): array
    {
        $grouped = [];

        foreach (self::SYMFONY_ENTITIES as $entityType => $config) {
            $category = $config['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$entityType] = [
                'label' => $config['label'],
                'type' => 'symfony',
            ];
        }

        foreach (self::LEGACY_ENTITIES as $entityType => $config) {
            $category = $config['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$entityType] = [
                'label' => $config['label'],
                'type' => 'legacy',
            ];
        }

        return $grouped;
    }

    /**
     * Get entity configuration by type.
     *
     * @param string $entityType Entity type identifier
     * @return array|null Configuration array or null if not found
     */
    public static function getEntityConfig(string $entityType): ?array
    {
        if (isset(self::SYMFONY_ENTITIES[$entityType])) {
            return array_merge(
                self::SYMFONY_ENTITIES[$entityType],
                ['integration_type' => 'symfony']
            );
        }

        if (isset(self::LEGACY_ENTITIES[$entityType])) {
            return array_merge(
                self::LEGACY_ENTITIES[$entityType],
                ['integration_type' => 'legacy']
            );
        }

        return null;
    }

    /**
     * Check if an entity uses Symfony forms.
     *
     * @param string $entityType Entity type identifier
     * @return bool
     */
    public static function isSymfonyEntity(string $entityType): bool
    {
        return isset(self::SYMFONY_ENTITIES[$entityType]);
    }

    /**
     * Check if an entity is legacy (ObjectModel).
     *
     * @param string $entityType Entity type identifier
     * @return bool
     */
    public static function isLegacyEntity(string $entityType): bool
    {
        return isset(self::LEGACY_ENTITIES[$entityType]);
    }

    /**
     * Build the hook to entity map.
     */
    private static function buildHookToEntityMap(): void
    {
        self::$hookToEntityMap = [];

        // Store all keys in lowercase for case-insensitive lookup
        foreach (self::SYMFONY_ENTITIES as $entityType => $config) {
            self::$hookToEntityMap[strtolower($config['form_builder_hook'])] = $entityType;
            foreach ($config['form_handler_hooks'] as $hook) {
                self::$hookToEntityMap[strtolower($hook)] = $entityType;
            }
            if (isset($config['display_hooks'])) {
                foreach ($config['display_hooks'] as $hook) {
                    self::$hookToEntityMap[strtolower($hook)] = $entityType;
                }
            }
        }

        foreach (self::LEGACY_ENTITIES as $entityType => $config) {
            foreach ($config['action_hooks'] as $hook) {
                self::$hookToEntityMap[strtolower($hook)] = $entityType;
            }
            if (isset($config['display_hooks'])) {
                foreach ($config['display_hooks'] as $hook) {
                    self::$hookToEntityMap[strtolower($hook)] = $entityType;
                }
            }
        }
    }
}

