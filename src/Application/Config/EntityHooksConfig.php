<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Configuration centralisée des hooks ACF - Version V1 (Product + Category).
 *
 * Architecture simplifiée pour faciliter la maintenance et l'ajout d'entités.
 * Les hooks sont séparés clairement : ADMIN (back-office).
 *
 * Pour ajouter une nouvelle entité:
 * 1. Ajouter dans ENABLED_ENTITIES
 * 2. Ajouter config dans ADMIN_HOOKS
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
        'cms_page',
    ];

    /**
     * Configuration des hooks ADMIN (back-office).
     * Structure: entity => ['display' => hook, 'save' => [hooks], 'symfony' => [hooks]].
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
        'cms_page' => [
            'display' => 'displayAdminCmsContent',
            'save' => [
                'actionObjectCmsUpdateAfter',
                'actionObjectCmsAddAfter',
            ],
            // PrestaShop 8/9 (Symfony Forms) - CMS Page
            'symfony' => [
                'form_builder' => 'actionCmsPageFormBuilderModifier',
                'form_handlers' => [
                    'actionAfterCreateCmsPageFormHandler',
                    'actionAfterUpdateCmsPageFormHandler',
                ],
            ],
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
    ];

    /**
     * Hooks front-office pour l'affichage ACF.
     *
     * @var string[]
     */
    public const FRONT_HOOKS = [
        'displayHeader',           // Initialize $acf in Smarty + register plugins
        'filterCmsContent',        // Parse shortcodes in CMS pages
        'filterCategoryContent',   // Parse shortcodes in category descriptions
        'filterProductContent',    // Parse shortcodes in product descriptions
    ];

    /**
     * Vérifie si une entité est activée.
     */
    public static function isEnabled(string $entity): bool
    {
        return \in_array($entity, self::ENABLED_ENTITIES, true);
    }

    /**
     * Retourne tous les hooks à enregistrer dans le module.
     *
     * Inclut:
     * - Hooks système (media, header)
     * - Hooks admin (display, save, Symfony)
     * - Hooks front-office (display, shortcodes)
     *
     * @return string[]
     */
    public static function getAllHooks(): array
    {
        $hooks = self::SYSTEM_HOOKS;

        // Front-office hooks
        $hooks = array_merge($hooks, self::FRONT_HOOKS);

        // Admin hooks (legacy + Symfony)
        foreach (self::ADMIN_HOOKS as $config) {
            $hooks[] = $config['display'];
            $hooks = array_merge($hooks, $config['save']);

            // Ajouter les hooks Symfony
            if (\is_array($config['symfony'])) {
                $hooks[] = $config['symfony']['form_builder'];
                $hooks = array_merge($hooks, $config['symfony']['form_handlers']);
            }
        }

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
     * Retourne le nom du paramètre ID pour une entité.
     * Ex: 'product' => 'id_product', 'category' => 'id_category'.
     */
    public static function getIdParam(string $entity): string
    {
        return match ($entity) {
            'cms_page' => 'id_cms',
            default => 'id_' . $entity,
        };
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
            ],
            'Customers' => [
                'customer' => ['label' => 'Customer', 'type' => 'active'],
            ],
            'CMS' => [
                'cms_page' => ['label' => 'CMS Page', 'type' => 'active'],
            ],
        ];
    }

    /**
     * Retourne la configuration d'une entité.
     * Backward compatibility avec l'ancienne structure.
     */
    public static function getEntityConfig(string $entityType): ?array
    {
        if (! self::isEnabled($entityType)) {
            return null;
        }

        $adminConfig = self::ADMIN_HOOKS[$entityType] ?? null;

        if ($adminConfig === null) {
            return null;
        }

        return [
            'label' => $entityType === 'cms_page' ? 'CMS Page' : ucfirst($entityType),
            'category' => match ($entityType) {
                'customer' => 'Customers',
                'cms_page' => 'CMS',
                default => 'Catalog',
            },
            'form_builder_hook' => null,
            'form_handler_hooks' => [],
            'id_param' => self::getIdParam($entityType),
            'display_hooks' => [$adminConfig['display']],
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
