# 📋 **POINT COMPLET - Module WePresta ACF pour Agent IA**

## 🎯 **IDENTITÉ DU MODULE**

**Nom** : `wepresta_acf`  
**Version** : `1.4.0`  
**Type** : Module PrestaShop 8.x/9.x  
**Description** : Système Advanced Custom Fields (ACF) complet avec builder visuel Vue.js  
**Auteur** : Bruno Studer (WeCode)  
**License** : MIT  

---

## 🏗️ **ARCHITECTURE TECHNIQUE**

### **Framework WEDEV (Clean Architecture)**

```
📁 src/Wedev/Core/           # ⚠️ NON MODIFIABLE - Framework partagé
    ├── Adapter/             # ContextAdapter, ConfigurationAdapter, ShopAdapter
    ├── Trait/               # LoggerTrait, TranslatorTrait, MultiShopTrait
    └── Repository/          # AbstractRepository avec relations many-to-many

📁 src/Wedev/Extension/      # Extensions modulaires (UI, Http, Jobs, etc.)
📁 src/Application/          # ✅ VOTRE CODE - Logique métier
📁 src/Domain/               # ✅ VOTRE CODE - Entités métier pures
📁 src/Infrastructure/       # ✅ VOTRE CODE - Implémentations
📁 src/Presentation/         # ✅ VOTRE CODE - Contrôleurs & vues
```

### **Service Container Intelligent**
```php
AcfServiceContainer::getValueHandler();
// Fallback automatique vers DI Symfony si indisponible dans hooks
```

---

## 📊 **MODÈLE DE DONNÉES (3 ENTITÉS PRINCIPALES)**

### **1. AcfGroup - Groupes de champs**
```php
- id, uuid, title, slug, description
- location_rules (JSONLogic), placement_tab, placement_position
- priority, bo_options, fo_options
- active, date_add/upd
- Relations: fields (OneToMany), translations, shops
```

### **2. AcfField - Définition des champs**
```php
- id, uuid, group_id, parent_id (pour repeater)
- type, title, slug, instructions
- config (JSON), validation (JSON), conditions (JSON)
- wrapper (JSON), fo_options (JSON)
- position, translatable, active
- Relations: group, parent, children, values
```

### **3. AcfFieldValue - Valeurs stockées**
```php
- id, field_id, entity_type, entity_id
- shop_id, lang_id (nullable)
- value (JSON/string), value_index (pour recherche)
- Relations: field
```

### **Tables SQL (6 tables total)**
```sql
wepresta_acf_group           # Groupes
wepresta_acf_group_lang      # Traductions groupes
wepresta_acf_group_shop      # Multi-shop groupes
wepresta_acf_field           # Champs
wepresta_acf_field_lang      # Traductions champs
wepresta_acf_field_value     # Valeurs (table générique)
```

---

## 🎨 **TYPES DE CHAMPS (25+ TYPES)**

### **Types Natifs Core**
- **Basiques** : `text`, `textarea`, `number`, `email`, `url`
- **Choix** : `select`, `radio`, `checkbox`, `boolean`, `list`
- **Médias** : `image`, `gallery`, `video`, `file`, `files`
- **Contenu** : `richtext`, `date`, `time`, `datetime`, `color`
- **Avancés** : `relation`, `repeater`, `star_rating`

### **Architecture Types de Champs**
```php
abstract class AbstractFieldType implements FieldTypeInterface {
    // Méthodes obligatoires
    abstract getType(): string;
    abstract getLabel(): string;
    abstract getFormType(): string;

    // Méthodes optionnelles
    getDefaultConfig(): array;
    getConfigSchema(): array;
    normalizeValue(mixed $value): mixed;
    renderValue(mixed $value): string;
    validate(mixed $value): array;
    renderAdminInput(): string;
}
```

### **Types Custom Chargeables**
- **Depuis theme** : `/themes/mytheme/acf-fields/`
- **Depuis uploads** : `/modules/wepresta_acf/uploads/field-types/`
- **Via API** : Upload ZIP avec structure standard

---

## 🌐 **API REST COMPLÈTE (45+ ENDPOINTS)**

### **Endpoints CRUD Groups**
```
GET    /api/groups              # Liste groupes
POST   /api/groups              # Créer groupe
GET    /api/groups/{id}         # Détails groupe
PUT    /api/groups/{id}         # Modifier groupe
DELETE /api/groups/{id}         # Supprimer groupe
POST   /api/groups/{id}/duplicate # Dupliquer
```

### **Endpoints Fields**
```
POST   /api/groups/{id}/fields  # Créer champ
PUT    /api/fields/{id}         # Modifier champ
DELETE /api/fields/{id}         # Supprimer champ
POST   /api/groups/{id}/fields/reorder # Réordonner
```

### **Endpoints Values**
```
POST   /api/values              # Sauvegarder valeurs
GET    /api/values/{productId}  # Récupérer valeurs produit
GET    /api/groups/{id}/global-values    # Récupérer valeurs globales
POST   /api/groups/{id}/global-values    # Sauvegarder valeurs globales
POST   /api/upload-file                  # Upload fichiers (global scope)
```

### **Endpoints Sync (Template ↔ Boutique)**
```
GET    /api/sync/status          # Statut sync
POST   /api/sync/push/{groupId}  # Push vers template
POST   /api/sync/pull/{slug}     # Pull depuis template
POST   /api/sync/push-all        # Push tous groupes
POST   /api/sync/pull-all        # Pull tous templates
GET    /api/sync/export/{id}     # Export JSON
```

### **Endpoints Utilitaires**
```
GET    /api/field-types          # Types disponibles
POST   /api/slugify              # Générer slug
GET    /api/front-hooks          # Hooks front-office (toutes entités)
GET    /api/front-hooks/{entity} # Hooks front-office par entité (product, category, customer)
```

---

## 🎭 **INTERFACE UTILISATEUR**

### **Builder Vue.js SPA**
- **Route** : `/modules/wepresta_acf/builder`
- **Techno** : Vue.js 3 + Composition API
- **Features** : Drag & drop, aperçu temps réel, validation
- **Nouveaux composants** :
  - `GlobalValuesEditor.vue` - Éditeur valeurs globales avec validation
  - `FileUploadField.vue` - Upload fichiers réutilisable
  - Support translatable fields avec onglets langues

### **Configuration Module**
- **Route** : `/modules/wepresta_acf/configuration`
- **Features** : Sync templates, debug, paramètres généraux

### **URLs d'administration par entité**

| Entité | URL d'édition | Hook ACF principal |
|--------|---------------|-------------------|
| **📦 Produit** | `/sell/catalog/products/{id}/edit#tab-product_extra_modules-tab` | `displayAdminProductsExtra` |
| **📁 Catégorie** | `/sell/catalog/categories/{id}/edit` | `displayAdminCategoriesExtra` |
| **👤 Client** | `/sell/customers/{id}/edit` | `displayAdminCustomers` |

### **URLs de navigation**
- **Liste clients** : `/sell/customers/`
- **Créer client** : `/sell/customers/new`
- **Éditer client** : `/sell/customers/{id}/edit`

### **Injection Back-Office**
- **Hook** : `actionAdminControllerSetMedia`
- **Injection** : Champs ACF dans formulaires produit/catégorie
- **JS** : `acf-fields.js` détecte automatiquement `#acf-entity-fields`

---

## 🔄 **SYSTÈME DE SYNCHRONISATION**

### **Principe**
- **Templates JSON** stockés dans theme/uploads
- **Push** : Boutique → Template (export)
- **Pull** : Template → Boutique (import)
- **Multi-environnements** : dev → staging → prod

### **Structure Template JSON**
```json
{
  "title": "Product Specs",
  "slug": "product_specs",
  "location_rules": {...},
  "fields": [
    {
      "type": "text",
      "title": "Brand",
      "slug": "brand",
      "config": {...}
    }
  ]
}
```

### **Chemins Sync**
- **Theme** : `/themes/mytheme/acf-templates/`
- **Parent** : `/themes/classic/acf-templates/`
- **Custom** : Configurable via module settings

---

## 🌍 **ENTITÉS SUPPORTÉES (18+ TYPES)**

### **Core Entities (v1)**
- `product` - Produits (`/sell/catalog/products/{id}/edit#tab-product_extra_modules-tab`)
- `category` - Catégories (`/sell/catalog/categories/{id}/edit`)
- `customer` - Clients (`/sell/customers/{id}/edit`)
- `customer_address` - Adresses clients

### **Extended Entities (Providers)**
- `order`, `cart` - Commandes & paniers
- `manufacturer`, `supplier` - Marques & fournisseurs
- `cms_page`, `cms_category` - Pages & catégories CMS
- `language`, `currency` - Langues & devises
- `zone`, `country`, `state` - Géographie
- `carrier` - Transporteurs

### **Architecture EntityFieldProvider**
```php
interface EntityFieldProviderInterface {
    getEntityType(): string;
    getDisplayName(): string;
    getFormHook(): string;
    getDisplayHooks(): array;
    getLocationOptions(): array;
}
```

---

## 🎯 **HOOKS PRESTASHOP**

### **Hooks Enregistrés**
```php
// System hooks (toujours actifs)
'actionAdminControllerSetMedia'
'actionFrontControllerSetMedia'
'displayHeader'
'hookActionProductAdd'
'hookActionValidateOrder'

// Dynamic hooks (via EntityHooksConfig)
// Admin hooks
'displayAdminProductsExtra'      // Produits (BO)
'displayAdminCategoriesExtra'    // Catégories (BO)
'displayAdminCustomers'          // Clients (BO)
'actionProductUpdate'            // Produits
'actionProductAdd'               // Produits
'actionCategoryUpdate'           // Catégories
'actionCategoryAdd'              // Catégories
'actionObjectCustomerUpdateAfter' // Clients
'actionObjectCustomerAddAfter'   // Clients
// Symfony Form hooks (PS8/9)
'actionProductFormBuilderModifier'
'actionAfterCreateProductFormHandler'
'actionAfterUpdateProductFormHandler'
'actionCategoryFormBuilderModifier'
'actionAfterCreateCategoryFormHandler'
'actionAfterUpdateCategoryFormHandler'
'actionCustomerFormBuilderModifier'
'actionAfterCreateCustomerFormHandler'
'actionAfterUpdateCustomerFormHandler'
// Front hooks
'displayProductAdditionalInfo'   // Produits
'displayProductExtraContent'     // Produits
'displayProductButtons'          // Produits
'displayProductActions'          // Produits
'displayProductPriceBlock'       // Produits
'displayAfterProductThumbs'      // Produits
'displayReassurance'             // Produits
'displayProductListReviews'      // Produits
'displayProductListFunctionalButtons' // Produits
'displayFooterProduct'           // Produits
'displayHeaderCategory'          // Catégories
'displayFooterCategory'          // Catégories
'displayCustomerAccount'         // Clients
'displayMyAccountBlock'          // Clients
'displayMyAccountBlockfooter'    // Clients
'displayCustomerAccountForm'     // Clients
'displayCustomerAccountFormTop'  // Clients
```

### **EntityHooksConfig - Configuration Centralisée**
```php
EntityHooksConfig::getAllHooks(); // Retourne tous hooks selon entités
EntityHooksConfig::getAdminHooks(); // Hooks back-office (display + save + symfony)
EntityHooksConfig::getFrontHooks(); // Hooks front-office par entité
EntityHooksConfig::getSystemHooks(); // Hooks système (media, header)
```

---

## 🎨 **AFFICHAGE FRONT-OFFICE**

### **Méthode Générique**
```php
private function renderEntityFieldsForDisplayInHook(
    string $entityType,
    int $entityId,
    string $hookName
): string
```

### **Templates Smarty**
- `product-info.tpl` - Produits (legacy)
- `entity-info.tpl` - Toutes entités (générique)
- Styles CSS intégrés

### **Hooks Front-Office par entité**

#### **🏷️ Produits (10 hooks)**
- `displayProductAdditionalInfo` - Informations supplémentaires produit
- `displayProductExtraContent` - Contenu supplémentaire (onglets)
- `displayProductButtons` - Boutons d'action produit
- `displayProductActions` - Zone actions produit
- `displayProductPriceBlock` - Bloc prix produit
- `displayAfterProductThumbs` - Après miniatures produit
- `displayReassurance` - Bloc confiance/produits similaires
- `displayProductListReviews` - Avis dans liste produits
- `displayProductListFunctionalButtons` - Boutons fonctionnels liste
- `displayFooterProduct` - Pied de page produit

#### **📁 Catégories (2 hooks)**
- `displayHeaderCategory` - En-tête catégorie
- `displayFooterCategory` - Pied de page catégorie

#### **👤 Clients (5 hooks)**
- `displayCustomerAccount` - Page Mon Compte (principale)
- `displayMyAccountBlock` - Bloc latéral Mon Compte (liens)
- `displayMyAccountBlockfooter` - Pied du bloc Mon Compte
- `displayCustomerAccountForm` - Formulaire édition compte (après)
- `displayCustomerAccountFormTop` - Formulaire édition compte (avant)

### **Filtrage Intelligent**
- **Par hook** : Un groupe peut s'afficher dans `displayHome` mais pas `displayFooter`
- **Par options FO** : `fo_options.visible`, `fo_options.show_label`
- **Conditions** : Respecte les règles `conditions` des champs

---

## 🔧 **SERVICES PRINCIPAUX**

### **ValueHandler - Gestion valeurs**
```php
$valueHandler->saveProductFieldValues($productId, $values, $shopId);
$valueHandler->saveFieldValue($productId, $slug, $value, $shopId, $langId);
```

### **FieldRenderService - Rendu champs**
```php
$renderService->getEntityFieldsForDisplayInHook($entityType, $entityId, $hookName);
```

### **ValueProvider - Lecture valeurs**
```php
$valueProvider->getProductFieldValues($productId, $shopId);
$valueProvider->getFieldValue($productId, $slug, $shopId, $langId);
$valueProvider->getEntityFieldValuesAllLanguages($entityType, $entityId, $shopId); // NOUVEAU
```

### **FormModifierService - Modification formulaires**
- Injection champs ACF dans formulaires admin (legacy + Symfony)
- Gestion validation et soumission
- Support complet Customer entity (Symfony forms PS8/9)
- **Filtrage groupes globaux** : Exclusion automatique des groupes `valueScope: 'global'`

### **EntityFieldHooksTrait - Gestion hooks**
- **12 méthodes Customer** ajoutées (admin + front + symfony)
- **extractCustomerIdFromParams()** - Extraction ID client sécurisée
- Support context PrestaShop + paramètres URL + objets Customer

---

## 🔐 **SÉCURITÉ & VALIDATION**

### **Security Measures**
- **Prepared statements** partout (pas de concaténation SQL)
- **Tools::getValue()** pour inputs GET/POST
- **pSQL()** pour sécurisation SQL
- **htmlspecialchars()** pour output HTML
- **Validation stricte** via `FieldType::validate()`

### **Upload Security**
- **Types MIME** vérifiés
- **Extensions** whitelistées
- **Taille fichiers** limitée (10MB par défaut)
- **Stockage sécurisé** hors webroot

---

## 🧪 **TESTS & QUALITÉ CODE**

### **Stack QA**
```json
{
  "phpunit": "^10.0",
  "phpstan": "^1.10",
  "php-cs-fixer": "^3.40",
  "rector": "^1.0",
  "infection": "^0.29"
}
```

### **Scripts Composer**
```bash
composer test       # cs-check + phpstan + phpunit
composer qa         # test + psalm
composer fix        # cs-fix + rector
composer phpstan    # Analyse statique
composer phpunit    # Tests unitaires
```

### **Tests Types**
- **Unit** : Classes isolées (FieldType, Services)
- **Integration** : Repository, API controllers, valeurs globales
- **Functional** : Workflows complets (création → sauvegarde → affichage)
- **Global Values Testing** : Tests prioritaires (spécifique → global → vide)

---

## 📦 **DÉPENDANCES & COMPATIBILITÉ**

### **Requirements**
```json
{
  "php": ">=8.1",
  "ext-json": "*",
  "ext-pdo": "*",
  "ext-mbstring": "*"
}
```

### **Compatibilité PrestaShop**
- **Versions** : 8.0.0 → 9.99.99
- **Bootstrap** : `true` (formulaire config)
- **Multishop** : Support complet
- **Multilang** : Support complet

---

## 🚀 **WORKFLOW DÉVELOPPEMENT**

### **Création Nouveau Type Champ**
1. `src/Application/FieldType/MyField.php` extends `AbstractFieldType`
2. Implémenter méthodes abstraites
3. Template : `views/templates/admin/fields/myfield.tpl`
4. Enregistrement automatique via PSR-4

### **Ajout Nouvelle Entité**
1. `src/Application/Provider/EntityField/MyEntityProvider.php`
2. Implémenter `EntityFieldProviderInterface`
3. Enregistrer dans `config/services.yml`
4. Hooks dans `EntityHooksConfig::V1_ENTITIES`

### **Création Groupe avec Valeurs Globales**
1. Créer groupe dans builder
2. Sélectionner `EntityType` (Customer, Product, etc.)
3. Choisir `Value Scope = Global` dans Location Rules
4. Ajouter champs dans onglet "Fields"
5. Définir valeurs globales dans onglet "Values"
6. Sauvegarder - valeurs disponibles pour toutes entités du type

### **Sync Template**
1. Créer groupe dans admin
2. `POST /api/sync/push/{groupId}`
3. Template JSON créé dans `/themes/mytheme/acf-templates/`
4. `POST /api/sync/pull/{slug}` pour importer ailleurs

---

## 🎯 **POINTS D'ATTENTION CRITIQUES**

### **Grid Framework PrestaShop 9**
- **NE PAS auto-enregistrer** les grids (cause erreur autowiring)
- **Toujours configurer explicitement** avec `$dbPrefix: '%database_prefix%'`

### **SearchCriteria PS9**
```php
// ❌ ERREUR - Méthode inexistante
$searchCriteria = $this->buildSearchCriteriaFromRequest($request, 'grid_id');

// ✅ SOLUTION - Construction manuelle
$filters = $request->query->all('grid_id');
$searchCriteria = new SearchCriteria(
    $filters['filters'] ?? [],
    $filters['orderBy'] ?? 'id',
    $filters['sortOrder'] ?? 'desc',
    (int)($filters['offset'] ?? 0),
    (int)($filters['limit'] ?? 10)
);
```

### **Service Container Fallback**
```php
// Dans hooks, utiliser AcfServiceContainer
$valueHandler = AcfServiceContainer::getValueHandler();
// Pas directement $this->get() car peut être indisponible
```

### **Display Hooks - Bug corrigé (v1.3.1)**

**❌ Problème** : Les Display Hooks n'étaient pas sauvegardés dans le builder Vue.js
- Cause : `foOptions.displayHooks` était un array au lieu d'un objet
- Symptôme : Sélecteur vide après sauvegarde/rechargement

**✅ Solution** :
```typescript
// Dans builderStore.ts - normalizeGroup()
foOptions.displayHooks = Array.isArray(foOptions.displayHooks)
  ? {} // Convertir array en objet
  : (foOptions.displayHooks || {});
```

**Impact** : Les Display Hooks sont maintenant correctement sauvegardés et persistent après rechargement de la page.

---

## 🌍 **VALEURS GLOBALES (v1.4.0 - NOUVELLES FONCTIONNALITÉS)**

### **Principe des Valeurs Globales**

Les **valeurs globales** permettent de définir des valeurs par défaut communes à toutes les entités d'un même type, plutôt que des valeurs spécifiques à chaque entité.

**Logique de priorité :**
1. **Valeur spécifique** (entity_id = X) si définie
2. **Valeur globale** (entity_id = 0) comme fallback
3. **Vide** sinon

### **Architecture Technique**

#### **Value Scope dans GroupFrontendOptions**
```typescript
export interface GroupFrontendOptions {
  visible?: boolean
  template?: string
  wrapperClass?: string
  displayHooks?: Record<string, string>
  valueScope?: 'global' | 'entity' // ← NOUVEL ATTRIBUT
}
```

#### **Stockage en Base**
```sql
-- Valeurs spécifiques (par entité)
INSERT INTO wepresta_acf_field_value
  (field_id, entity_type, entity_id, value, shop_id, lang_id)
VALUES
  (1, 'customer', 123, 'John Doe', 1, 1);

-- Valeurs globales (entity_id = 0)
INSERT INTO wepresta_acf_field_value
  (field_id, entity_type, entity_id, value, shop_id, lang_id)
VALUES
  (1, 'customer', 0, 'Default Name', 1, 1);
```

### **Interface Utilisateur**

#### **Configuration du Scope**
- **Emplacement** : Étape "Location Rules" du builder
- **Choix** : Radio buttons "Global" / "Per Entity"
- **Visibilité** : Après sélection du type d'entité

#### **Édition des Valeurs Globales**
- **Nouvel onglet** : "Values" dans le wizard builder
- **Conditionnel** : Visible seulement si `valueScope = 'global'`
- **Support complet** :
  - Champs translatables (onglets par langue)
  - Validation client-side (required, minLength, pattern, etc.)
  - Upload de fichiers (image, video, file, gallery, files)
  - Aperçu temps réel
  - Sauvegarde automatique

### **API REST - Nouveaux Endpoints**

#### **Gestion des Valeurs Globales**
```
GET    /api/groups/{id}/global-values    # Récupérer valeurs globales
POST   /api/groups/{id}/global-values    # Sauvegarder valeurs globales
POST   /api/upload-file                  # Upload fichiers (global scope)
```

#### **Repository Methods**
```php
// Nouvelle méthode dans AcfFieldValueRepository
findByEntityAllLanguages(string $entityType, int $entityId, ?int $shopId): array

// Nouvelle méthode dans ValueProvider
getEntityFieldValuesAllLanguages(string $entityType, int $entityId, ?int $shopId): array
```

### **Services Modifiés**

#### **FormModifierService**
```php
// Exclusion groupes globaux des formulaires admin
if (($foOptions['valueScope'] ?? 'entity') === 'global') {
    continue; // Skip global groups
}
```

#### **EntityFieldService**
```php
// Même logique pour hooks displayAdmin*
if (($foOptions['valueScope'] ?? 'entity') === 'global') {
    continue; // Skip global groups
}
```

### **Composants Vue.js Ajoutés**

#### **GlobalValuesEditor.vue**
- Éditeur complet pour valeurs globales
- Support champs translatables avec onglets langues
- Validation intégrée (HTML5 + custom)
- Gestion erreurs et aperçu

#### **FileUploadField.vue**
- Composant réutilisable pour uploads
- Support single/multi fichiers
- Aperçu, progression, remplacement
- Intégration API upload

### **Types de Champs Supportés**
- ✅ **Tous les types natifs** : text, textarea, number, email, select, etc.
- ✅ **Médias complets** : image, gallery, video, file, files
- ✅ **Contenu riche** : richtext, date, time, datetime
- ✅ **Translatable fields** : Gestion multilangue complète
- ✅ **Validation** : required, minLength, maxLength, pattern, min, max

### **Sécurité & Performance**
- **Filtrage strict** : Groupes globaux exclus des formulaires entités
- **Fallback intelligent** : Valeurs globales = backup, jamais écrasées
- **Cache optimisé** : Requêtes séparées pour valeurs globales
- **Upload sécurisé** : Même sécurité que valeurs spécifiques

### **Cas d'Usage**
- **Template produit** : "Marque par défaut" pour tous produits
- **Client entreprise** : "Secteur d'activité par défaut"
- **Catégorie générique** : "Description commune"
- **Configuration globale** : Valeurs partagées multi-entités

### **Migration & Compatibilité**
- **Backward compatible** : Groupes existants = scope "entity"
- **Migration automatique** : Pas de script requis
- **Multi-shop** : Support complet (shop_id dans valeurs)
- **Multi-lang** : Support complet (lang_id nullable)

---

## 🔮 **ÉVOLUTION & ROADMAP**

### **✅ v1.4.0 - Global Values System**
- **Valeurs globales** : Définition de valeurs par défaut pour tous EntityTypes
- **Logique de priorité** : spécifique → global → vide
- **Builder amélioré** : Onglet "Values" pour groupes globaux
- **Support fichiers** : Upload image/video/file dans valeurs globales
- **Validation complète** : Client-side + server-side pour valeurs globales
- **Filtrage intelligent** : Groupes globaux exclus des formulaires entités

### **✅ v1.3.1 - Customer Entity Support**
- **Support complet Customer entity** (admin + front)
- **5 hooks front-office** pour pages compte client
- **Hooks Symfony PS8/9** pour formulaires clients
- **Correction bug Display Hooks** (sauvegarde Vue.js)
- **URLs admin documentées** pour toutes entités

### **Features Planifiées**
- **Templates marketplace** (partage groupes entre boutiques)
- **Workflows approval** (validation avant publication)
- **Analytics reporting** (utilisation champs)
- **API GraphQL** (alternative REST)
- **Field types premium** (paiement, signature, etc.)

### **Améliorations Architecturales**
- **CQRS complet** (séparation read/write models)
- **Event sourcing** (historique modifications)
- **Microservices** (API en service séparé)
- **Real-time sync** (WebSocket pour builder collaboratif)

---

**Ce module représente un exemple d'excellence en développement PrestaShop moderne, combinant architecture propre, UX moderne, et fonctionnalités avancées. Avec le système de valeurs globales v1.4.0, il offre désormais une flexibilité ultime pour la gestion de contenu personnalisé.** 🎉
