<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Config;

/**
 * Configuration centralisée des hooks ACF - Version V1 (Product + Category).
 *
 * Architecture simplifiée pour faciliter la maintenance et l'ajout d'entités.
 * Les hooks sont séparés clairement : ADMIN (back-office) vs FRONT (front-office).
 *
 * Pour ajouter une nouvelle entité:
 * 1. Ajouter dans ENABLED_ENTITIES
 * 2. Ajouter config dans ADMIN_HOOKS et/ou FRONT_HOOKS
 * 3. Ajouter méthodes hook dans EntityFieldHooksTrait
 */
final class EntityHooksConfig
{
    /**
     * Entités activées dans la version actuelle.
     * V1: Product, Category et Customer.
     *
     * @var string[]
     */
    public const ENABLED_ENTITIES = [
        'product',
        'category',
        'customer',
    ];

    /**
     * Configuration des hooks ADMIN (back-office).
     * Structure: entity => ['display' => hook, 'save' => [hooks], 'symfony' => [hooks]]
     *
     * Note: En PrestaShop 8/9, les entités utilisent Symfony Forms.
     * - 'symfony' contient les hooks FormBuilderModifier et FormHandler
     * - 'display' et 'save' sont pour backward compatibility (legacy)
     *
     * @var array<string, array{display: string, save: string[], symfony?: array{form_builder: string, form_handlers: string[]}}>
     */
    public const ADMIN_HOOKS = [
        'product' => [
            'display' => 'displayAdminProductsExtra',
            'save' => [
                'actionProductUpdate',
                'actionProductAdd',
            ],
            'symfony' => [
                'form_builder' => 'actionProductFormBuilderModifier',
                'form_handlers' => [
                    'actionAfterCreateProductFormHandler',
                    'actionAfterUpdateProductFormHandler',
                ],
            ],
        ],
        'category' => [
            'display' => 'displayAdminCategoriesExtra',
            'save' => [
                'actionCategoryUpdate',
                'actionCategoryAdd',
            ],
            'symfony' => [
                'form_builder' => 'actionCategoryFormBuilderModifier',
                'form_handlers' => [
                    'actionAfterCreateCategoryFormHandler',
                    'actionAfterUpdateCategoryFormHandler',
                ],
            ],
        ],
        'customer' => [
            'display' => 'displayAdminCustomers', // Legacy hook
            'save' => [
                'actionObjectCustomerUpdateAfter',
                'actionObjectCustomerAddAfter',
            ],
            'symfony' => [
                'form_builder' => 'actionCustomerFormBuilderModifier',
                'form_handlers' => [
                    'actionAfterCreateCustomerFormHandler',
                    'actionAfterUpdateCustomerFormHandler',
                ],
            ],
        ],
    ];

    /**
     * Configuration des hooks FRONT (front-office).
     * Structure: entity => [hook1, hook2, ...]
     *
     * Note: Cette liste contient les hooks ACTUELLEMENT UTILISÉS par défaut.
     * Pour la liste COMPLÈTE des hooks disponibles, voir FrontHooksRegistry.
     *
     * @var array<string, string[]>
     */
    public const FRONT_HOOKS = [
        'product' => [
            'displayProductAdditionalInfo',
            'displayProductExtraContent',
            'displayProductButtons',
            'displayProductActions',
            'displayProductPriceBlock',
            'displayAfterProductThumbs',
            'displayReassurance',
            'displayProductListReviews',
            'displayProductListFunctionalButtons',
            'displayFooterProduct',
        ],
        'category' => [
            'displayHeaderCategory',
            'displayFooterCategory',
        ],
        'customer' => [
            'displayCustomerAccount',
            'displayCustomerAccountForm',
            'displayCustomerAccountFormTop',
            'displayCustomerAccountTop',
            'displayCustomerLoginFormAfter',
        ],
    ];

    /**
     * Hooks système (toujours actifs, indépendants des entités).
     *
     * @var string[]
     */
    public const SYSTEM_HOOKS = [
        'actionAdminControllerSetMedia',
        'actionFrontControllerSetMedia',
        'displayHeader',
    ];

    /**
     * Vérifie si une entité est activée.
     */
    public static function isEnabled(string $entity): bool
    {
        return in_array($entity, self::ENABLED_ENTITIES, true);
    }

    /**
     * Retourne tous les hooks à enregistrer dans le module.
     * 
     * Inclut:
     * - Hooks système (media, header)
     * - Hooks admin (display, save, Symfony)
     * - TOUS les hooks front disponibles (FrontHooksRegistry)
     *
     * @return string[]
     */
    public static function getAllHooks(): array
    {
        $hooks = self::SYSTEM_HOOKS;

        // Admin hooks (legacy + Symfony)
        foreach (self::ADMIN_HOOKS as $config) {
            $hooks[] = $config['display'];
            $hooks = array_merge($hooks, $config['save']);
            
            // Ajouter les hooks Symfony si présents
            if (isset($config['symfony'])) {
                $hooks[] = $config['symfony']['form_builder'];
                $hooks = array_merge($hooks, $config['symfony']['form_handlers']);
            }
        }

        // Front hooks - TOUS les hooks disponibles (pas seulement ceux par défaut)
        $hooks = array_merge($hooks, FrontHooksRegistry::getAllHookNames());

        return array_unique($hooks);
    }

    /**
     * Retourne le hook d'affichage admin pour une entité.
     */
    public static function getAdminDisplayHook(string $entity): ?string
    {
        return self::ADMIN_HOOKS[$entity]['display'] ?? null;
    }

    /**
     * Retourne les hooks de sauvegarde admin pour une entité.
     *
     * @return string[]
     */
    public static function getAdminSaveHooks(string $entity): array
    {
        return self::ADMIN_HOOKS[$entity]['save'] ?? [];
    }

    /**
     * Retourne le hook FormBuilderModifier Symfony pour une entité.
     */
    public static function getSymfonyFormBuilderHook(string $entity): ?string
    {
        return self::ADMIN_HOOKS[$entity]['symfony']['form_builder'] ?? null;
    }

    /**
     * Retourne les hooks FormHandler Symfony pour une entité.
     *
     * @return string[]
     */
    public static function getSymfonyFormHandlerHooks(string $entity): array
    {
        return self::ADMIN_HOOKS[$entity]['symfony']['form_handlers'] ?? [];
    }

    /**
     * Retourne les hooks front-office pour une entité.
     *
     * @return string[]
     */
    public static function getFrontHooks(string $entity): array
    {
        return self::FRONT_HOOKS[$entity] ?? [];
    }

    /**
     * Retourne le nom du paramètre ID pour une entité.
     * Ex: 'product' => 'id_product', 'category' => 'id_category'
     */
    public static function getIdParam(string $entity): string
    {
        return 'id_' . $entity;
    }

    /**
     * Retourne la liste des entités groupées par catégorie.
     * Backward compatibility avec l'ancienne structure.
     *
     * @return array<string, array<string, array{label: string, type: string}>>
     */
    public static function getEntitiesGroupedByCategory(): array
    {
        return [
            'Catalog' => [
                'product' => ['label' => 'Product', 'type' => 'active'],
                'category' => ['label' => 'Category', 'type' => 'active'],
                'customer' => ['label' => 'Customer', 'type' => 'active'],
            ],
        ];
    }

    /**
     * Retourne la configuration d'une entité.
     * Backward compatibility avec l'ancienne structure.
     *
     * @return array|null
     */
    public static function getEntityConfig(string $entityType): ?array
    {
        if (!self::isEnabled($entityType)) {
            return null;
        }

        $adminConfig = self::ADMIN_HOOKS[$entityType] ?? null;
        $frontHooks = self::FRONT_HOOKS[$entityType] ?? [];

        if ($adminConfig === null) {
            return null;
        }

        return [
            'label' => ucfirst($entityType),
            'category' => $entityType === 'customer' ? 'Customers' : 'Catalog',
            'form_builder_hook' => null,
            'form_handler_hooks' => [],
            'id_param' => self::getIdParam($entityType),
            'display_hooks' => array_merge([$adminConfig['display']], $frontHooks),
            'integration_type' => 'active',
        ];
    }

    /**
     * Retourne le type d'entité à partir d'un nom de hook.
     * Backward compatibility.
     */
    public static function getEntityByHook(string $hookName): ?string
    {
        $hookNameLower = strtolower($hookName);

        // Check admin hooks
        foreach (self::ADMIN_HOOKS as $entity => $config) {
            if (strtolower($config['display']) === $hookNameLower) {
                return $entity;
            }
            foreach ($config['save'] as $saveHook) {
                if (strtolower($saveHook) === $hookNameLower) {
                    return $entity;
                }
            }
        }

        // Check front hooks
        foreach (self::FRONT_HOOKS as $entity => $hooks) {
            foreach ($hooks as $hook) {
                if (strtolower($hook) === $hookNameLower) {
                    return $entity;
                }
            }
        }

        return null;
    }

    /**
     * Backward compatibility - Always return true for enabled entities.
     */
    public static function isEntityEnabled(string $entityType): bool
    {
        return self::isEnabled($entityType);
    }
}
