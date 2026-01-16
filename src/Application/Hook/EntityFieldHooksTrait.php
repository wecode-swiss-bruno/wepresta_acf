<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Hook;

use Context;
use Exception;
use Symfony\Component\Form\FormBuilderInterface;
use Tools;
use WeprestaAcf\Application\Config\EntityHooksConfig;
use WeprestaAcf\Application\Service\EntityFieldService;
use WeprestaAcf\Application\Service\FormModifierService;

/**
 * Trait pour la gestion des hooks ACF - Version V1 (Product + Category).
 *
 * Architecture:
 * - Méthodes explicites pour chaque hook (meilleur debugging + support IDE)
 * - Délégation vers EntityFieldService pour la logique métier
 * - Séparation claire ADMIN vs FRONT hooks
 *
 * Chaque entité dispose de:
 * - hookDisplay{Entity}AdminXxx() : Affichage des champs en BO
 * - hookAction{Entity}Xxx() : Sauvegarde des valeurs en BO
 * - hookDisplay{Entity}Xxx() : Affichage des champs en FO
 */
trait EntityFieldHooksTrait
{
    // =========================================================================
    // PRODUCT HOOKS - ADMIN (Back-Office)
    // =========================================================================

    /**
     * Affiche les champs ACF dans l'édition produit (BO).
     */
    public function hookDisplayAdminProductsExtra(array $params): string
    {
        return $this->renderAdminFields('product', $params);
    }

    /**
     * Sauvegarde les champs ACF lors de la mise à jour produit (legacy).
     */
    public function hookActionProductUpdate(array $params): void
    {
        $this->saveEntityFields('product', $params);
    }

    /**
     * Sauvegarde les champs ACF lors de l'ajout produit (legacy).
     */
    public function hookActionProductAdd(array $params): void
    {
        $this->saveEntityFields('product', $params);
    }

    // =========================================================================
    // CATEGORY HOOKS - ADMIN (Back-Office)
    // =========================================================================

    /**
     * Affiche les champs ACF dans l'édition catégorie (BO).
     */
    public function hookDisplayAdminCategoriesExtra(array $params): string
    {
        return $this->renderAdminFields('category', $params);
    }

    /**
     * Sauvegarde les champs ACF lors de la mise à jour catégorie.
     */
    public function hookActionCategoryUpdate(array $params): void
    {
        $this->saveEntityFields('category', $params);
    }

    /**
     * Sauvegarde les champs ACF lors de la création catégorie.
     */
    public function hookActionCategoryAdd(array $params): void
    {
        $this->saveEntityFields('category', $params);
    }

    // =========================================================================
    // SYMFONY FORM HOOKS - Product
    // =========================================================================

    /**
     * Injecte les champs ACF dans le formulaire Symfony Product (BO).
     * Hook: actionProductFormBuilderModifier.
     */
    public function hookActionProductFormBuilderModifier(array $params): void
    {
        $this->handleSymfonyFormBuilder('product', $params);
    }

    /**
     * Sauvegarde les champs ACF après création Product (Symfony).
     * Hook: actionAfterCreateProductFormHandler.
     */
    public function hookActionAfterCreateProductFormHandler(array $params): void
    {
        $this->handleSymfonyFormHandler('product', $params);
    }

    /**
     * Sauvegarde les champs ACF après mise à jour Product (Symfony).
     * Hook: actionAfterUpdateProductFormHandler.
     */
    public function hookActionAfterUpdateProductFormHandler(array $params): void
    {
        $this->handleSymfonyFormHandler('product', $params);
    }

    // =========================================================================
    // SYMFONY FORM HOOKS - Category
    // =========================================================================

    /**
     * Injecte les champs ACF dans le formulaire Symfony Category (BO).
     * Hook: actionCategoryFormBuilderModifier.
     */
    public function hookActionCategoryFormBuilderModifier(array $params): void
    {
        $this->handleSymfonyFormBuilder('category', $params);
    }

    /**
     * Sauvegarde les champs ACF après création Category (Symfony).
     * Hook: actionAfterCreateCategoryFormHandler.
     */
    public function hookActionAfterCreateCategoryFormHandler(array $params): void
    {
        $this->handleSymfonyFormHandler('category', $params);
    }

    /**
     * Sauvegarde les champs ACF après mise à jour Category (Symfony).
     * Hook: actionAfterUpdateCategoryFormHandler.
     */
    public function hookActionAfterUpdateCategoryFormHandler(array $params): void
    {
        $this->handleSymfonyFormHandler('category', $params);
    }

    // =========================================================================
    // CUSTOMER HOOKS - ADMIN (Back-Office)
    // =========================================================================

    /**
     * Affiche les champs ACF dans l'édition client (BO).
     */
    public function hookDisplayAdminCustomers(array $params): string
    {
        return $this->renderAdminFields('customer', $params);
    }

    /**
     * Sauvegarde les champs ACF lors de la mise à jour client.
     */
    public function hookActionObjectCustomerUpdateAfter(array $params): void
    {
        $this->saveEntityFields('customer', $params);
    }

    /**
     * Sauvegarde les champs ACF lors de la création client.
     */
    public function hookActionObjectCustomerAddAfter(array $params): void
    {
        $this->saveEntityFields('customer', $params);
    }

    // =========================================================================
    // SYMFONY FORM HOOKS - Customer
    // =========================================================================

    /**
     * Injecte les champs ACF dans le formulaire Symfony Customer (BO).
     * Hook: actionCustomerFormBuilderModifier.
     */
    public function hookActionCustomerFormBuilderModifier(array $params): void
    {
        $this->handleSymfonyFormBuilder('customer', $params);
    }

    /**
     * Sauvegarde les champs ACF après création Customer (Symfony).
     * Hook: actionAfterCreateCustomerFormHandler.
     */
    public function hookActionAfterCreateCustomerFormHandler(array $params): void
    {
        $this->handleSymfonyFormHandler('customer', $params);
    }

    /**
     * Sauvegarde les champs ACF après mise à jour Customer (Symfony).
     * Hook: actionAfterUpdateCustomerFormHandler.
     */
    public function hookActionAfterUpdateCustomerFormHandler(array $params): void
    {
        $this->handleSymfonyFormHandler('customer', $params);
    }

    // =========================================================================
    // CMS PAGE HOOKS - ADMIN (Back-Office)
    // =========================================================================

    /**
     * Affiche les champs ACF dans l'édition d'une page CMS (BO).
     *
     * Hook: displayAdminCmsContent
     */
    public function hookDisplayAdminCmsContent(array $params): string
    {
        return $this->renderAdminFields('cms_page', $params);
    }

    /**
     * Sauvegarde les champs ACF lors de la mise à jour d'une page CMS (legacy ObjectModel).
     *
     * Hook: actionObjectCmsUpdateAfter
     */
    public function hookActionObjectCmsUpdateAfter(array $params): void
    {
        $this->saveEntityFields('cms_page', $params);
    }

    /**
     * Sauvegarde les champs ACF lors de la création d'une page CMS (legacy ObjectModel).
     *
     * Hook: actionObjectCmsAddAfter
     */
    public function hookActionObjectCmsAddAfter(array $params): void
    {
        $this->saveEntityFields('cms_page', $params);
    }

    // =========================================================================
    // SYMFONY FORM HOOKS - CMS Page (PrestaShop 8/9)
    // =========================================================================

    /**
     * Injecte les champs ACF dans le formulaire Symfony CMS Page (BO).
     * Hook: actionCmsPageFormBuilderModifier.
     */
    public function hookActionCmsPageFormBuilderModifier(array $params): void
    {
        $this->handleSymfonyFormBuilder('cms_page', $params);
    }

    /**
     * Sauvegarde les champs ACF après création CMS Page (Symfony).
     * Hook: actionAfterCreateCmsPageFormHandler.
     */
    public function hookActionAfterCreateCmsPageFormHandler(array $params): void
    {
        $this->handleSymfonyFormHandler('cms_page', $params);
    }

    /**
     * Sauvegarde les champs ACF après mise à jour CMS Page (Symfony).
     * Hook: actionAfterUpdateCmsPageFormHandler.
     */
    public function hookActionAfterUpdateCmsPageFormHandler(array $params): void
    {
        $this->handleSymfonyFormHandler('cms_page', $params);
    }

    // =========================================================================
    // MÉTHODES PRIVÉES - Logique commune
    // =========================================================================

    /**
     * Rendu des champs ACF pour le back-office.
     *
     * @param string $entity Type d'entité ('product', 'category')
     * @param array $params Paramètres du hook
     *
     * @return string HTML généré
     */
    private function renderAdminFields(string $entity, array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        if (!EntityHooksConfig::isEnabled($entity)) {
            return '';
        }

        $entityId = $this->extractEntityId($entity, $params);

        if ($entityId <= 0) {
            return '';
        }

        try {
            $service = $this->getService(EntityFieldService::class);

            if ($service === null) {
                return '';
            }

            return $service->renderFieldsForEntity($entity, $entityId, $this);
        } catch (Exception $e) {
            $this->log("Error rendering admin fields for {$entity}: " . $e->getMessage(), 3);

            return '<div class="alert alert-danger">ACF Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    /**
     * Sauvegarde des champs ACF pour le back-office.
     *
     * @param string $entity Type d'entité ('product', 'category')
     * @param array $params Paramètres du hook
     */
    private function saveEntityFields(string $entity, array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        if (!EntityHooksConfig::isEnabled($entity)) {
            return;
        }

        $entityId = $this->extractEntityId($entity, $params);

        if ($entityId <= 0) {
            return;
        }

        try {
            $service = $this->getService(EntityFieldService::class);

            if ($service === null) {
                return;
            }
            $service->saveFieldsForEntity($entity, $entityId, $_POST, $_FILES, $this);
        } catch (Exception $e) {
            $this->log("Error saving fields for {$entity}: " . $e->getMessage(), 3);
        }
    }

    /**
     * Extrait l'ID de l'entité à partir des paramètres du hook.
     *
     * @param string $entity Type d'entité
     * @param array $params Paramètres du hook
     *
     * @return int ID de l'entité ou 0 si non trouvé
     */
    private function extractEntityId(string $entity, array $params): int
    {
        $idKey = EntityHooksConfig::getIdParam($entity);

        // Essayer différentes sources
        $entityId = $params[$idKey] ?? null;

        if ($entityId !== null) {
            return (int) $entityId;
        }

        // Essayer 'id' générique
        $entityId = $params['id'] ?? null;

        if ($entityId !== null) {
            return (int) $entityId;
        }

        // Essayer depuis l'objet
        $object = $params['object'] ?? null;

        if ($object !== null && property_exists($object, 'id')) {
            return (int) $object->id;
        }

        return 0;
    }

    /**
     * Gère les hooks Symfony FormBuilderModifier.
     * Injecte les champs ACF dans le formulaire Symfony.
     *
     * @param string $entity Type d'entité
     * @param array $params Paramètres du hook (doit contenir 'form_builder' et 'data')
     */
    private function handleSymfonyFormBuilder(string $entity, array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        if (!EntityHooksConfig::isEnabled($entity)) {
            return;
        }

        if (!isset($params['form_builder']) || !($params['form_builder'] instanceof FormBuilderInterface)) {
            return;
        }

        try {
            $formBuilder = $params['form_builder'];
            $data = &$params['data'] ?? [];
            $entityId = $this->extractEntityIdFromSymfonyParams($entity, $params);

            $service = $this->getService(FormModifierService::class);

            if ($service === null) {
                return;
            }

            $service->modifyForm($formBuilder, $entity, $entityId, $data);
        } catch (Exception $e) {
            $this->log("Error in Symfony FormBuilder for {$entity}: " . $e->getMessage(), 3);
        }
    }

    /**
     * Gère les hooks Symfony FormHandler (après création/mise à jour).
     * Sauvegarde les valeurs des champs ACF.
     *
     * @param string $entity Type d'entité
     * @param array $params Paramètres du hook (doit contenir 'id' et 'form_data')
     */
    private function handleSymfonyFormHandler(string $entity, array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        if (!EntityHooksConfig::isEnabled($entity)) {
            return;
        }

        $entityId = $this->extractEntityIdFromSymfonyParams($entity, $params);

        if ($entityId <= 0) {
            return;
        }

        try {
            $formData = $params['form_data'] ?? $params['data'] ?? [];

            // Utiliser EntityFieldService pour gérer POST + FILES
            // (FormModifierService ne gère que les données POST simples)
            $entityService = $this->getService(EntityFieldService::class);

            if ($entityService !== null) {
                $files = $_FILES ?? [];
                $entityService->saveFieldsForEntity($entity, $entityId, $formData, $files, $this);

                return;
            }

            // Fallback: utiliser FormModifierService pour les données POST simples
            if (empty($formData)) {
                return;
            }

            $service = $this->getService(FormModifierService::class);

            if ($service === null) {
                return;
            }

            $service->saveAcfData($entity, $entityId, $formData);
        } catch (Exception $e) {
            $this->log("Error in Symfony FormHandler for {$entity}: " . $e->getMessage(), 3);
        }
    }

    /**
     * Extrait l'ID de l'entité depuis les paramètres d'un hook Symfony.
     *
     * @param string $entity Type d'entité
     * @param array $params Paramètres du hook
     *
     * @return int|null ID de l'entité ou null si non trouvé
     */
    private function extractEntityIdFromSymfonyParams(string $entity, array $params): ?int
    {
        // Essayer 'id' directement
        if (isset($params['id']) && (int) $params['id'] > 0) {
            return (int) $params['id'];
        }

        // Essayer depuis form_data
        if (isset($params['form_data']['id']) && (int) $params['form_data']['id'] > 0) {
            return (int) $params['form_data']['id'];
        }

        // Essayer depuis data
        if (isset($params['data']['id']) && (int) $params['data']['id'] > 0) {
            return (int) $params['data']['id'];
        }

        // Essayer avec la clé spécifique à l'entité
        $idKey = EntityHooksConfig::getIdParam($entity);

        if (isset($params[$idKey]) && (int) $params[$idKey] > 0) {
            return (int) $params[$idKey];
        }

        // Essayer depuis form_data avec la clé spécifique
        if (isset($params['form_data'][$idKey]) && (int) $params['form_data'][$idKey] > 0) {
            return (int) $params['form_data'][$idKey];
        }

        // Utiliser FormModifierService si disponible
        $service = $this->getService(FormModifierService::class);

        if ($service !== null) {
            $entityId = $service->getEntityIdFromParams($entity, $params);

            if ($entityId !== null) {
                return $entityId;
            }
        }

        return null;
    }

    /**
     * Extrait l'ID produit de manière sécurisée depuis les paramètres du hook.
     * Gère les cas où $params['product'] peut être un array ou un objet Product.
     *
     * @param array $params Paramètres du hook
     *
     * @return int ID du produit ou 0 si non trouvé
     */
    private function extractProductIdFromParams(array $params): int
    {
        $product = $params['product'] ?? null;

        if ($product === null) {
            return 0;
        }

        // Si c'est un objet Product, accéder à la propriété id ou id_product
        if (\is_object($product)) {
            if (property_exists($product, 'id')) {
                return (int) $product->id;
            }

            if (property_exists($product, 'id_product')) {
                return (int) $product->id_product;
            }

            // Essayer d'autres propriétés communes via isset (pour les propriétés dynamiques)
            if (isset($product->id_product)) {
                return (int) $product->id_product;
            }
        }

        // Si c'est un array, accéder à l'index id_product
        if (\is_array($product) && isset($product['id_product'])) {
            return (int) $product['id_product'];
        }

        return 0;
    }

    /**
     * Extrait l'ID catégorie de manière sécurisée depuis les paramètres du hook.
     * Gère les cas où $params['category'] peut être un array ou un objet Category.
     * En front-office, cherche aussi dans le contexte PrestaShop.
     *
     * @param array $params Paramètres du hook
     *
     * @return int ID de la catégorie ou 0 si non trouvé
     */
    private function extractCategoryIdFromParams(array $params): int
    {
        $category = $params['category'] ?? null;

        // 1. Essayer depuis $params['category']
        if ($category !== null) {
            // Si c'est un objet Category, accéder à la propriété id
            if (\is_object($category)) {
                if (property_exists($category, 'id')) {
                    return (int) $category->id;
                }

                if (property_exists($category, 'id_category')) {
                    return (int) $category->id_category;
                }

                if (isset($category->id)) {
                    return (int) $category->id;
                }
            }

            // Si c'est un array, accéder à l'index id_category
            if (\is_array($category)) {
                if (isset($category['id_category'])) {
                    return (int) $category['id_category'];
                }

                if (isset($category['id'])) {
                    return (int) $category['id'];
                }
            }
        }

        // 2. Essayer depuis $params directement
        if (isset($params['id_category'])) {
            return (int) $params['id_category'];
        }

        if (isset($params['id'])) {
            return (int) $params['id'];
        }

        // 3. Essayer depuis le contexte PrestaShop (front-office)
        $context = Context::getContext();

        // Depuis Tools::getValue (URL parameter)
        $idFromUrl = Tools::getValue('id_category');

        if ($idFromUrl && (int) $idFromUrl > 0) {
            return (int) $idFromUrl;
        }

        // Depuis le controller
        if ($context->controller !== null) {
            // Essayer controller->category (objet Category)
            if (isset($context->controller->category)) {
                $controllerCategory = $context->controller->category;

                if (\is_object($controllerCategory)) {
                    if (property_exists($controllerCategory, 'id') || isset($controllerCategory->id)) {
                        return (int) $controllerCategory->id;
                    }
                }

                if (\is_array($controllerCategory) && isset($controllerCategory['id_category'])) {
                    return (int) $controllerCategory['id_category'];
                }
            }

            // Essayer controller->id_category (propriété directe)
            if (isset($context->controller->id_category) && (int) $context->controller->id_category > 0) {
                return (int) $context->controller->id_category;
            }
        }

        return 0;
    }
}
