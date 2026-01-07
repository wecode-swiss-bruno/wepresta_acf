# 📋 **POINT COMPLET - Module WePresta ACF pour Agent IA**

## 🎯 **IDENTITÉ DU MODULE**

**Nom** : `wepresta_acf`  
**Version** : `1.2.1`  
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

## 🌐 **API REST COMPLÈTE (40+ ENDPOINTS)**

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
GET    /api/front-hooks          # Hooks front-office
```

---

## 🎭 **INTERFACE UTILISATEUR**

### **Builder Vue.js SPA**
- **Route** : `/modules/wepresta_acf/builder`
- **Techno** : Vue.js 3 + Composition API
- **Features** : Drag & drop, aperçu temps réel, validation

### **Configuration Module**
- **Route** : `/modules/wepresta_acf/configuration`
- **Features** : Sync templates, debug, paramètres généraux

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

## 🌍 **ENTITÉS SUPPORTÉES (17+ TYPES)**

### **Core Entities (v1)**
- `product` - Produits
- `category` - Catégories
- `customer` - Clients
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
'displayProductAdditionalInfo'    // Produits
'actionProductUpdate'            // Produits
'displayCategoryHeader'          // Catégories
// ... selon entités activées
```

### **EntityHooksConfig - Configuration Centralisée**
```php
EntityHooksConfig::getAllHooks(); // Retourne tous hooks selon entités
EntityHooksConfig::getAdminHooks(); // Hooks back-office
EntityHooksConfig::getFrontHooks(); // Hooks front-office
EntityHooksConfig::getSystemHooks(); // Hooks système
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
```

### **FormModifierService - Modification formulaires**
- Injection champs ACF dans formulaires admin
- Gestion validation et soumission

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
- **Integration** : Repository, API controllers
- **Functional** : Workflows complets (création → sauvegarde → affichage)

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

---

## 🔮 **ÉVOLUTION & ROADMAP**

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

**Ce module représente un exemple d'excellence en développement PrestaShop moderne, combinant architecture propre, UX moderne, et fonctionnalités avancées.** 🎉
