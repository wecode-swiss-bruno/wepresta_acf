# 📋 **POINT COMPLET - Module WePresta ACF pour Agent IA**

## 🎯 **IDENTITÉ DU MODULE**

**Nom** : `wepresta_acf`  
**Version** : `1.5.0` (Back-Office Only)  
**Type** : Module PrestaShop 8.x/9.x  
**Description** : Système Advanced Custom Fields (ACF) simplifié pour back-office uniquement avec builder visuel Vue.js  
**Auteur** : Bruno Studer (WeCode)  
**License** : MIT

**⚠️ VERSION SIMPLIFIÉE** : Cette version du module se concentre exclusivement sur la gestion de champs personnalisés en back-office. Toutes les fonctionnalités d'affichage front-office automatique ont été supprimées.  

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
// Container de services pour l'accès aux fonctionnalités du module
```

---

## 📊 **MODÈLE DE DONNÉES (3 ENTITÉS PRINCIPALES)**

### **1. AcfGroup - Groupes de champs**
```php
- id, uuid, title, slug, description
- location_rules (JSONLogic), placement_tab, placement_position
- priority, bo_options
- active, date_add/upd
- Relations: fields (OneToMany), translations, shops
```

### **2. AcfField - Définition des champs**
```php
- id, uuid, group_id, parent_id (pour repeater)
- type, title, slug, instructions
- config (JSON), validation (JSON), conditions (JSON)
- wrapper (JSON)
- position, active
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

## 🌐 **API REST SIMPLIFIÉE (Back-Office Only)**

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
GET    /api/values/{entityId}?entity_type=product  # Récupérer valeurs par entité
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
```

---

## 🎭 **INTERFACE UTILISATEUR (Back-Office Only)**

### **Builder Vue.js SPA**
- **Route** : `/modules/wepresta_acf/builder`
- **Techno** : Vue.js 3 + Composition API
- **Features** : Drag & drop, aperçu temps réel, validation
- **3 onglets** : General, Validation, Fields

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
- **Injection** : Champs ACF dans formulaires admin
- **Focus** : Configuration et gestion des champs uniquement

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

### **Hooks Enregistrés (Back-Office Only)**
```php
// System hooks (toujours actifs)
'actionAdminControllerSetMedia'

// Dynamic hooks (via EntityHooksConfig)
// Admin display hooks
'displayAdminProductsExtra'      // Produits (BO)
'displayAdminCategoriesExtra'    // Catégories (BO)
'displayAdminCustomers'          // Clients (BO)

// Admin save hooks
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
```

### **EntityHooksConfig - Configuration Centralisée**
```php
EntityHooksConfig::getAllHooks(); // Retourne tous hooks admin + système
EntityHooksConfig::isEnabled('product'); // Vérifie si une entité est activée
EntityHooksConfig::getAdminDisplayHook('product'); // Hook d'affichage admin
```

---

## 🎨 **ARCHITECTURE SIMPLIFIÉE (Back-Office Only)**

**Cette version du module se concentre exclusivement sur la gestion de champs personnalisés en back-office. Aucune fonctionnalité d'affichage front-office automatique n'est incluse.**

---

## 🔧 **SERVICES PRINCIPAUX (Back-Office Only)**

### **ValueHandler - Gestion valeurs**
```php
$valueHandler->saveEntityFieldValues($entityType, $entityId, $values, $shopId);
$valueHandler->saveFieldValue($entityType, $entityId, $slug, $value, $shopId, $langId);
```

### **ValueProvider - Lecture valeurs**
```php
$valueProvider->getEntityFieldValues($entityType, $entityId, $shopId);
$valueProvider->getFieldValue($entityType, $entityId, $slug, $shopId, $langId);
```

### **FormModifierService - Modification formulaires**
- Injection champs ACF dans formulaires admin (legacy + Symfony)
- Gestion validation et soumission
- Support complet Customer entity (Symfony forms PS8/9)
- Focus sur l'administration uniquement

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



---

## 🔮 **VERSION ACTUELLE & HISTORIQUE**

### **✅ v1.5.0 - Back-Office Only Refactoring (2025)**
- **Refactoring complet** : Suppression de toutes les fonctionnalités front-office
- **Focus back-office** : Module dédié uniquement à l'administration
- **Nettoyage architecture** : Suppression de 40% du code (hooks, templates, APIs front)
- **Interface simplifiée** : 3 onglets uniquement (General, Validation, Fields)
- **Maintenance facilitée** : Code plus propre et maintenable

### **❌ Fonctionnalités supprimées (Front-Office)**
- **Display hooks** : Tous les hooks `displayProduct*`, `displayCategory*`, `displayCustomer*`
- **Templates front** : `product-info.tpl`, `entity-info.tpl`, rendu automatique
- **Valeurs globales** : Système de valeurs partagées entre entités
- **APIs front** : Endpoints `/api/front-hooks/*`, `/api/global-values`
- **Options front** : `fo_options`, `valueScope`, `displayHooks` dans les entités

### **Roadmap Future**
- **Field types additionnels** : Types de champs spécialisés (couleur, icône, etc.)
- **Export/Import amélioré** : Migration entre environnements
- **Analytics basique** : Statistiques d'utilisation des champs
- **Performance optimisée** : Cache et requêtes optimisées
- **Documentation développeur** : Guides d'intégration pour thèmes

### **Avantages de la Version Simplifiée**
- **Maintenance réduite** : Moins de code = moins de bugs
- **Performance améliorée** : Pas de logique front-office inutile
- **Focus métier** : Concentration sur la création/gestion de champs
- **Évolutivité** : Architecture prête pour futures extensions
- **Simplicité** : Interface claire et intuitive

---

**Ce module représente un exemple de **refactoring réussi** en développement PrestaShop moderne. En se concentrant sur sa **vocation première** (gestion de champs personnalisés en back-office), il offre une **solution robuste, maintenable et performante** pour les besoins d'administration personnalisée.** 🎯
