# 📋 **POINT COMPLET - Module WePresta ACF pour Agent IA**

## 🎯 **IDENTITÉ DU MODULE**

**Nom** : `wepresta_acf`  
**Version** : `1.6.0` (Advanced Translation System)  
**Type** : Module PrestaShop 8.x/9.x  
**Description** : Système Advanced Custom Fields (ACF) complet avec traduction multilingue avancée et builder visuel Vue.js  
**Auteur** : Bruno Studer (WeCode)  
**License** : MIT

**🌍 VERSION MULTILINGUE AVANCÉE** : Cette version offre un système de traduction complet à 3 niveaux pour une gestion professionnelle des contenus multilingues en back-office.  

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

### **Options Disponibles pour TOUS les Champs**

#### **Onglet "Presentation" (v1.5.0+)**
- **customClass** : Classe CSS pour styling front-office (stored in `foOptions`)
- **customId** : ID HTML pour ciblage JavaScript (stored in `foOptions`)
- **showTitle** : Afficher/masquer le titre du champ en front-office (stored in `foOptions`)
- **valueTranslatable** : Boolean - Active traduction des VALEURS du champ (pas métadonnées)

#### **Comment ça marche?**
```json
{
  "field": {
    "slug": "text_field",
    "title": "Mon champ",
    "valueTranslatable": true,        // ← Activer traductions des valeurs
    "foOptions": {
      "customClass": "my-custom-class",  // ← Classe CSS pour front
      "customId": "my-field-id"          // ← ID HTML pour JS
    }
  }
}
```

En Back-Office, l'utilisateur voit des **onglets de langue** pour remplir les valeurs:
- EN tab: "English value"
- FR tab: "Valeur française"
- ES tab: "Valor español"

Chaque langue est sauvegardée séparément dans `ps_wepresta_acf_field_value_lang`.

#### **Traduction des Labels d'Options (v1.6.0+)**
Pour les champs **SelectField** et **CheckboxField**, les labels des options peuvent être traduits :
- Dans le builder : Interface multilingue pour éditer les traductions des choices
- En formulaire produit : Affichage automatique dans la langue du back-office
- Structure : `choices[].translations[id_lang] = "Label traduit"`

### **Types Natifs Core**
- **Basiques** : `text`, `textarea`, `number`, `email`, `url`
- **Choix** : `select`, `radio`, `checkbox`, `boolean`, `list`
- **Médias** : `image`, `gallery`, `video`, `file`, `files`
- **Contenu** : `richtext`, `date`, `time`, `datetime`, `color`
- **Avancés** : `relation`, `repeater` (avec support imbriqué v1.6.0+), `star_rating`

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
    supportsTranslation(): bool; // Support de la traduction des valeurs
}
```

#### **Support de Traduction par Type**
- ✅ **Text, Textarea, RichText, Select, Checkbox** : Supportent la traduction des valeurs
- ❌ **Number, Email, URL, Date, Time, Datetime, Color, StarRating** : Non traduisibles (valeurs techniques)

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
- **3 onglets** : General, Validation, Presentation
- **Éditeur multilingue** : Traduction des métadonnées et des choices

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

## 🔗 **REPEATERS IMBRIQUÉS (v1.6.0+)**

### **Architecture Support Multi-Niveaux**

#### **Limitation Levée**
Avant v1.6.0, les repeaters imbriqués n'étaient pas supportés dans l'interface.

À partir de v1.6.0 :
- ✅ **Repeaters imbriqués illimités** - Profondeur: L0 → L1 → L2 → L3 → ∞
- ✅ **Composant récursif SubfieldItem.vue** - Gestion automatique de la profondeur
- ✅ **Structure arborescente en DB** - Via clé étrangère `id_parent` auto-référencée
- ✅ **Drag-drop multi-niveaux** - Réordonnancement à chaque niveau
- ✅ **Expand/collapse récursif** - Navigation intuitive

#### **Exemple - Repeater L0 avec Repeater Imbriqué L1**

```json
{
  "type": "repeater",
  "title": "Product Variants",
  "slug": "product_variants",
  "children": [
    {
      "type": "text",
      "title": "Variant Name",
      "slug": "variant_name"
    },
    {
      "type": "repeater",
      "title": "Variant Options",
      "slug": "variant_options",
      "id_parent": 123,
      "children": [
        {
          "type": "text",
          "title": "Option Name",
          "slug": "option_name"
        },
        {
          "type": "text",
          "title": "Option Value",
          "slug": "option_value"
        }
      ]
    }
  ]
}
```

#### **DB Schema - Structure Arborescente**

```sql
-- Repeater L0
INSERT INTO wepresta_acf_field 
  (uuid, type, title, slug, id_parent, position) 
VALUES 
  ('uuid-1', 'repeater', 'Product Variants', 'product_variants', NULL, 0);
-- id_parent = NULL (top-level)

-- Repeater L1 (imbriqué)
INSERT INTO wepresta_acf_field 
  (uuid, type, title, slug, id_parent, position) 
VALUES 
  ('uuid-2', 'repeater', 'Variant Options', 'variant_options', 1, 0);
-- id_parent = 1 (référence au repeater L0)

-- Repeater L2 (imbriqué dans L1)
INSERT INTO wepresta_acf_field 
  (uuid, type, title, slug, id_parent, position) 
VALUES 
  ('uuid-3', 'repeater', 'Option Variations', 'option_variations', 2, 0);
-- id_parent = 2 (référence au repeater L1)
```

**FK Cascading** :
```sql
CONSTRAINT `fk_wepresta_acf_field_parent` 
    FOREIGN KEY (`id_parent`) 
    REFERENCES `PREFIX_wepresta_acf_field`(`id_wepresta_acf_field`) 
    ON DELETE CASCADE
```

#### **Architecture Frontend - Composant Récursif**

**Composant `SubfieldItem.vue`** :
```typescript
// Props
interface Props {
  field: AcfField              // Champ actuel
  parentField?: AcfField       // Parent (optionnel)
  depth?: number               // Profondeur (0 = niveau top)
}

// Structure récursive
export default {
  name: 'SubfieldItem',
  props: [...],
  components: {
    SubfieldItem: () => import('./SubfieldItem.vue') // ← Self-reference
  }
}
```

**Indentation Visuelle** :
- Niveau 0 : padding-left = 0.5rem
- Niveau 1 : padding-left = 2.25rem (0.5 + 1*1.75)
- Niveau 2 : padding-left = 4rem (0.5 + 2*1.75)
- Niveau 3+ : padding-left = 5.75rem + ...

#### **Utilisation dans Builder**

**Workflow**:
1. Créer Repeater L0 "Product Variants"
2. Ajouter subfields: Text "Variant Name"
3. **Nouveau** - Ajouter Repeater L1 "Variant Options" (bouton "Add Subfield")
4. Expand Repeater L1
5. **Nouveau** - Ajouter subfields dans L1
6. **Nouveau** - Ajouter Repeater L2 dans L1
7. Repeat infiniment !

#### **Comportement UI**

- **Expand toggle** : Icône chevron pour chaque repeater
- **Visual hierarchy** : Indentation progressive + couleur background progressive
- **Drag-drop** : Fonctionne à chaque niveau
- **Add button** : Disponible dans chaque repeater
- **Delete** : Suppression en cascade (FK ON DELETE CASCADE)

#### **Persistance en DB**

**Insertion** :
```php
// Parent L0
$field0 = new AcfField(['type' => 'repeater', 'slug' => 'variants']);
$repository->save($field0); // id = 1, id_parent = NULL

// Child L1 (nested)
$field1 = new AcfField(['type' => 'repeater', 'slug' => 'options', 'id_parent' => 1]);
$repository->save($field1); // id = 2, id_parent = 1

// Child L2 (nested in nested)
$field2 = new AcfField(['type' => 'repeater', 'slug' => 'variations', 'id_parent' => 2]);
$repository->save($field2); // id = 3, id_parent = 2
```

**Récupération** :
```php
// Tous les children d'un repeater (récursif)
public function getChildrenRecursive(int $parentId): array {
    $children = $repository->findBy(['id_parent' => $parentId]);
    foreach ($children as $child) {
        if ($child['type'] === 'repeater') {
            $child['children'] = $this->getChildrenRecursive($child['id']);
        }
    }
    return $children;
}
```

#### **Limitations & Recommandations**

| Aspect | Limite | Recommandation |
|--------|--------|----------------|
| **Profondeur** | Aucune limite technique | Rester ≤ 5 niveaux (UX) |
| **Largeur** | Aucune limite technique | ≤ 50 subfields par level |
| **Performance** | O(n) par niveau | Lazy-load si > 100 fields |
| **Stockage** | Aucune limite | JSON dans `value` supporté |

---

## 🌍 **ENTITÉS SUPPORTÉES (18+ TYPES)**

### **Architecture Traductions (v1.5.0+)**

#### **3 Niveaux de Traductions**

**1️⃣ Traduction des MÉTADONNÉES du Champ** (Back-Office Builder)
- Stockée dans : `ps_wepresta_acf_field_lang`
- Traduction de : `title`, `instructions`, `placeholder`
- Editée dans : ACF Builder (onglets de langue)
- Table principale : `ps_wepresta_acf_field.title/instructions` = valeur langue PAR DÉFAUT (fallback)

**2️⃣ Traduction des VALEURS du Champ** (Back-Office Product)
- Stockée dans : `ps_wepresta_acf_field_value` (main) + `ps_wepresta_acf_field_value_lang`
- Traduction de : Contenu utilisateur (valeurs saisies)
- Editée dans : Product/Entity edit page (onglets de langue)
- Activation : Option `valueTranslatable: boolean` sur le champ
- Table principale : `ps_wepresta_acf_field_value.value` = valeur langue PAR DÉFAUT (fallback)

**3️⃣ Traduction des LABELS d'Options** (Back-Office Builder, v1.6.0+)
- Stockée dans : `ps_wepresta_acf_field.config` (JSON)
- Traduction de : Labels des choices/options (SelectField, CheckboxField)
- Editée dans : ACF Builder (onglets de langue dans l'éditeur de choices)
- Structure : `choices[].translations[id_lang] = "Label traduit"`
- Affichage : Automatique selon la langue du back-office

#### **Structure Base de Données (Traductions Valeurs)**

```sql
-- Table principale (1 record par field/entity)
ps_wepresta_acf_field_value:
  - id_wepresta_acf_field_value (PK)
  - id_wepresta_acf_field (FK)
  - entity_type, entity_id
  - id_shop
  - value (= langue par défaut, fallback)
  - value_index (pour recherche)
  - date_add, date_upd

-- Table traductions (N records, 1 par langue)
ps_wepresta_acf_field_value_lang (NEW):
  - id_wepresta_acf_field_value (PK, FK)
  - id_lang (PK)
  - value (traduction)
  - value_index
  -- ⚠️ PAS de date_add/date_upd (standard PrestaShop legacy)
```

#### **Flux de Traduction des Valeurs**
1. **Frontend** : Collecte TOUTES les langues via `collectAllValues()` → `{slug: {langId: "value"}}`
2. **API** : POST `/api/values` avec structure par langue
3. **Backend** : `ValueHandler` itère chaque langue
4. **Repository** : Crée 1 main record + N lang records (pas de duplication)
5. **Résultat** : Main value = langue par défaut, toutes traductions dans `_lang`

#### **Exemple - Sauvegarde Champ Translatable**

```javascript
// Frontend collecte
const values = {
  text_field: {
    1: "EN Text Value",    // Anglais
    2: "FR Valeur Texte",  // Français
    3: "ES Valor Texto"    // Espagnol
  }
};
// POST /api/values {productId: 123, values}
```

```sql
-- Résultat en BD
ps_wepresta_acf_field_value:
  id=1, field_id=5, entity_id=123, value='EN Text Value'  -- 1 SEUL record ✅

ps_wepresta_acf_field_value_lang:
  id=1, lang=1, value='EN Text Value'
  id=1, lang=2, value='FR Valeur Texte'
  id=1, lang=3, value='ES Valor Texto'  -- 3 records (1 par langue) ✅
```

#### **Récupération des Traductions**
```php
// En Back-Office (Product edit, affichage lang FR)
$value = $repository->findByEntity('product', 123, shopId, langId=2);
// → Cherche dans _lang table, fallback sur main si manquante

// En Front-Office (futur, toutes les langues)
$allValues = $repository->findByEntityAllLanguages('product', 123);
// → Retourne {langId: value} pour traduisibles
```

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
- Traduction automatique des labels selon langue BO
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

### **Configuration des Traductions**
1. **Métadonnées** : Dans l'onglet General, utiliser les onglets de langue pour traduire title/instructions
2. **Valeurs** : Dans l'onglet Presentation, activer "Value translatable" pour permettre la traduction des contenus
3. **Options** : Pour Select/Checkbox, utiliser l'éditeur de choices avec onglets de langue pour traduire les labels
4. **Affichage** : Contrôler la visibilité du titre avec "Show field title"

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

### **Traductions des Valeurs - Architecture Robuste (v1.5.0+)**

**❌ Problème Initial** : 
- Tentative d'insérer `date_add`/`date_upd` dans table `_lang`
- Table `ps_wepresta_acf_field_value_lang` n'a pas ces colonnes (standard PrestaShop legacy)
- Erreur SQL : "Unknown column 'date_add' in field list"

**✅ Solution Implémentée** :
```php
// AVANT (ERREUR):
$langSql = 'INSERT INTO _lang 
  (`id_value`, `id_lang`, `value`, `value_index`, `date_add`, `date_upd`)
  VALUES (...)';

// APRÈS (CORRECT):
$langSql = 'INSERT INTO _lang 
  (`id_wepresta_acf_field_value`, `id_lang`, `value`, `value_index`)
  VALUES (...)';
  // ✅ Sans date_add/date_upd (conforme standard PrestaShop)
```

**Architecture Finale**:
- **Table main** (`wepresta_acf_field_value`) : 1 record avec dates
- **Table _lang** (`wepresta_acf_field_value_lang`) : N records SANS dates
- **Upsert** : `ON DUPLICATE KEY UPDATE` pour éviter duplications
- **Fallback** : Langue manquante → utilise main value (défaut)

**Impact** : Les traductions de valeurs sont maintenant sauvegardées correctement sans erreur SQL.

### **🐛 Corrections de Bugs (v1.6.0)**

#### **Support des Repeaters Imbriqués**
**❌ Problème** : Les repeaters imbriqués n'étaient pas gérés par l'UI du builder

**✅ Solution** :
- Création composant récursif `SubfieldItem.vue`
- Modification de `FieldList.vue` pour utiliser le composant
- Gestion automatique de la profondeur via prop `depth`
- Indentation progressive basée sur le niveau d'imbrication
- Support illimité de niveaux (testé jusqu'à 10+)

**Impact** : Repeaters imbriqués maintenant entièrement fonctionnels avec UX intuitive

#### **Traduction des Choices en Repeaters**
**❌ Problème** : Les repeaters affichaient des labels vides pour les choices traduites

**✅ Solution** :
- Modification de `getJsTemplate()` dans `SelectField`, `CheckboxField`, `RadioField`
- Utilisation de `getChoiceLabelForValidation()` pour résoudre les labels
- Fallback automatique: translation[defaultLang] → label → value
- Affichage cohérent avec le BO produit

**Impact** : Les choices traduites s'affichent correctement dans les repeaters

#### **Validation des Choices Traduits**
**❌ Problème** : Erreur "Invalid choice selected" lors de la sauvegarde des SelectField/CheckboxField avec traductions

**✅ Solution** : Séparation des données d'affichage et de validation
- **Validation Symfony** : Utilise toujours les labels originaux des choices
- **Affichage** : Utilise les traductions via templates Smarty
- **Cohérence** : Même source de données, traduction côté présentation

#### **Messages Debug Console**
**❌ Problème** : Messages console.log polluant la console du navigateur

**✅ Solution** : Nettoyage complet
- Suppression de tous les `console.log` non conditionnés
- Conservation des logs de debug (conditionnés par `config.debug`)
- Code de production propre et professionnel

#### **Persistence des Choices**
**❌ Problème** : Choices avec translations ne persistaient pas après rechargement

**✅ Solution** :
- Correction de `parseChoices()` dans `SelectFieldConfig.vue`, `CheckboxFieldConfig.vue`, `RadioFieldConfig.vue`
- Préservation explicite de la propriété `translations` : `translations: (item as FieldChoice).translations || {}`
- Correction import : `import type { FieldChoice } from '@/types'`
- Ajout flags `isUpdatingChoices` pour éviter les boucles infinies

**Impact** : Choices avec traductions sont maintenant persistées correctement en DB

---





---

## 🔮 **VERSION ACTUELLE & HISTORIQUE**

### **✅ v1.6.0 - Advanced Translation System + Nested Repeaters (2025)**
- **🆕 Repeaters imbriqués illimités** : Support complet multi-niveaux (L0 → L1 → L2 → ∞)
- **🆕 Composant récursif SubfieldItem.vue** : Gestion automatique de la profondeur
- **🆕 Visual hierarchy** : Indentation progressive pour clarté visuelle
- **Traduction étendue** : Support complet multilingue pour tous les niveaux
- **Architecture à 3 niveaux** :
  - **Métadonnées du champ** (title, instructions) → `ps_wepresta_acf_field_lang`
  - **Valeurs du champ** (contenu utilisateur) → `ps_wepresta_acf_field_value_lang`
  - **Labels d'options** (choices) → `ps_wepresta_acf_field.config` JSON
- **Interface multilingue avancée** : Éditeur de choices avec onglets de langue
- **Affichage intelligent** : Traductions automatiques selon langue back-office
- **Validation robuste** : Cohérence parfaite entre affichage et validation
- **Option "Show field title"** : Contrôle d'affichage du titre en front-office
- **Code optimisé** : Suppression de tous les messages debug console.log
- **Performance améliorée** : Traductions côté template pour rapidité
- **DB Scalability** : Arborescence via `id_parent` auto-référencée, FK cascading

### **❌ Fonctionnalités supprimées (Front-Office)**
- **Display hooks** : Tous les hooks `displayProduct*`, `displayCategory*`, `displayCustomer*`
- **Templates front** : `product-info.tpl`, `entity-info.tpl`, rendu automatique
- **Valeurs globales** : Système de valeurs partagées entre entités
- **APIs front** : Endpoints `/api/front-hooks/*`, `/api/global-values`
- **Options front** : `fo_options`, `valueScope`, `displayHooks` dans les entités

### **Fonctionnalités Implémentées (v1.6.0)**
- ✅ **Repeaters imbriqués illimités** : Architecture récursive complète
- ✅ **Composant SubfieldItem.vue** : Auto-référencé, profondeur illimitée
- ✅ **Visual hierarchy** : Indentation + couleurs par niveau
- ✅ **Traduction complète** : Métadonnées, valeurs et labels d'options
- ✅ **Interface multilingue** : Éditeur de choices avec onglets de langue
- ✅ **Validation robuste** : Cohérence affichage/validation
- ✅ **Options de présentation** : Contrôle d'affichage du titre
- ✅ **Code optimisé** : Suppression des messages debug

### **Roadmap Future**
- **Field types additionnels** : Types de champs spécialisés (couleur, icône, etc.)
- **Export/Import amélioré** : Migration entre environnements avec traductions
- **Analytics avancé** : Statistiques d'utilisation multilingue
- **Performance optimisée** : Cache intelligent pour les traductions
- **API front-office** : Exposition des champs traduits pour thèmes
- **Documentation développeur** : Guides complets d'intégration multilingue

### **Avantages de la Version Avancée**
- **Multilinguisme complet** : Traduction à tous les niveaux (métadonnées, valeurs, options)
- **Repeaters imbriqués** : Support illimité des niveaux d'imbrication avec UI intuitive
- **Interface professionnelle** : Éditeur multilingue intuitif avec onglets et hiérarchie visuelle
- **Performance optimisée** : Traductions côté template, code de production propre
- **Robustesse** : Validation cohérente, pas de conflits d'affichage, FK cascading
- **Extensibilité** : Architecture modulaire prête pour nouveaux types de champs
- **UX moderne** : Interface Vue.js réactive avec feedback temps réel
- **Scalabilité DB** : Arborescence supportée nativement via auto-références

---

**Ce module représente un exemple de **développement moderne** en écosystème PrestaShop. Avec son système de traduction avancé à 3 niveaux, il offre une **solution complète et professionnelle** pour la gestion multilingue de champs personnalisés en back-office.** 🌍🎯
