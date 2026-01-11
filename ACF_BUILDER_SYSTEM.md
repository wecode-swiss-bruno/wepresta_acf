# 🏗️ ACF Builder System - Fonctionnement Complet

## Architecture Générale

Le Builder est une **SPA (Single Page Application) Vue.js 3** qui permet de créer et gérer des groupes de champs ACF. Il suit une architecture **Clean Architecture** avec séparation claire des responsabilités :

- **Frontend Vue.js** (dans `views/js/admin/`) : Interface utilisateur
- **API REST Symfony** (dans `src/Infrastructure/Api/`) : Endpoints back-end
- **Services métier** (dans `src/Application/`) : Logique applicative
- **Repositories** (dans `src/Infrastructure/Repository/`) : Accès base de données

## 📋 Flux de Fonctionnement de A à Z

### 1. Chargement Initial (`/modules/wepresta_acf/builder`)

**Backend (BuilderController.php)** :
```php
// Charge les types de champs disponibles
$this->fieldTypeRegistry->getAll()
// Charge les emplacements possibles (product, category, etc.)
$this->locationProviderRegistry->getLocationsGrouped()
// Charge les langues pour les champs traduisibles
Language::getLanguages(true)
```

**Frontend (App.vue)** :
```typescript
// Store Vue.js initialise l'état
const store = useBuilderStore()
store.loadGroups() // Charge la liste des groupes existants
```

### 2. Création d'un Nouveau Groupe

**Frontend (GroupBuilder.vue)** :
```typescript
// L'utilisateur clique "Add Group" → toolbar bouton
store.createNewGroup() // Crée un groupe vide localement
```

**Structure d'un groupe ACF** :
```typescript
{
  uuid: crypto.randomUUID(),
  title: '',
  slug: '',
  locationRules: [], // Où afficher les champs
  placementTab: 'extra', // Onglet dans le BO
  boOptions: {}, // Options back-office
  foOptions: {}, // Options front-office
  active: true,
  fields: [] // Les champs du groupe
}
```

### 3. Configuration du Groupe (Wizard en 3 étapes)

#### Étape 1 : Paramètres Généraux (Settings Tab)
L'utilisateur remplit :
- **Title** : Nom du groupe
- **Slug** : Identifiant unique (auto-généré)
- **Description** : Description optionnelle
- **Active** : Activation/désactivation

**Validation** :
```typescript
if (!currentGroup.value.title?.trim()) {
  error.value = '❌ Group title is required'
  return
}
```

#### Étape 2 : Règles de Localisation (Location Tab)
Définit **où** les champs apparaissent dans l'admin :
- **Entity Type** : `product`, `category`, `customer`, etc.
- **Condition** : `==` (égal) ou `!=` (différent de)
- **Value** : ID spécifique ou `*` (tous)

**Exemple** :
```json
{
  "==": ["product", "*"]
}
```
→ Les champs s'affichent sur **toutes les pages produit**

#### Étape 3 : Champs (Fields Tab)
Ajout et configuration des champs individuels.

### 4. Ajout d'un Nouveau Champ

**Frontend (FieldList.vue)** :
```typescript
// L'utilisateur clique "Add Field"
store.addField(type, parentField)
// type = 'text', 'select', 'repeater', etc.
```

**Structure d'un champ** :
```typescript
{
  uuid: crypto.randomUUID(),
  type: 'text',
  title: 'Mon Champ Texte',
  slug: 'mon_champ_texte', // Auto-généré
  parentId: null,
  config: {}, // Configuration spécifique au type
  validation: {}, // Règles de validation
  conditions: {}, // Conditions d'affichage
  wrapper: { width: '100' }, // Mise en page
  position: 0,
  translatable: false,
  active: true,
  translations: {} // Traductions du titre/instructions
}
```

### 5. Configuration d'un Champ (FieldConfigurator.vue)

Chaque champ a plusieurs onglets :

#### General :
- **Title** : Nom affiché
- **Name** : Slug unique (auto-généré)
- **Instructions** : Texte d'aide
- **Required** : Champ obligatoire

#### Validation :
- **Min/Max Length** : Pour les textes
- **Pattern** : Expression régulière
- **Custom Error** : Message d'erreur personnalisé

#### Presentation :
- **Width** : Largeur (25%, 50%, 75%, 100%)
- **CSS Class/ID** : Classes personnalisées

#### Configuration spécifique au type :
- **Text** : Placeholder, default value
- **Select** : Liste de choix, multiple
- **Repeater** : Sous-champs récursifs

### 6. Sauvegarde du Groupe

**Frontend (builderStore.ts)** :
```typescript
async function saveGroup() {
  // Validation côté client
  if (!currentGroup.value.title?.trim()) {
    error.value = 'Group title required'
    return
  }

  // Sauvegarde via API
  const updated = await api.updateGroup(groupId, currentGroup.value)

  // Sauvegarde de chaque champ
  for (const field of fieldsToSave) {
    if (field.id) {
      await api.updateField(field.id, field)
    } else {
      await api.createField(groupId, field)
    }
  }
}
```

**Backend (GroupMutationService.php)** :
```php
// Résout les slugs uniques
$slug = $this->slugValidator->resolveGroupSlug($slug, $title);

// Crée le groupe en base
$groupId = $this->groupRepository->create([...]);

// Sauvegarde les traductions si multilingue
$this->groupRepository->saveGroupTranslations($groupId, $translations);

// Marque pour auto-sync (export vers thème)
$this->autoSyncService->markDirty();
```

### 7. Système de Types de Champs Extensibles

**Architecture modulaire** :
- Chaque type de champ = classe PHP dans `src/Application/FieldType/`
- Interface commune : `FieldTypeInterface`
- Auto-discovery : `FieldTypeLoader` scanne les dossiers

**Exemple TextField** :
```php
class TextField implements FieldTypeInterface {
    public function getLabel(): string { return 'Text'; }
    public function getIcon(): string { return 'text_fields'; }
    public function validate($value, array $config): bool { /*...*/ }
    public function normalizeValue($value, array $config) { /*...*/ }
}
```

### 8. Gestion des Valeurs (Front-Office)

**Sauvegarde des valeurs** :
```php
// API: /api/values (POST)
$valueHandler->saveEntityFieldValues(
    'product',     // entityType
    $productId,    // entityId
    $values,       // ['field_slug' => 'value']
    $shopId,
    $langId
);
```

**Récupération des valeurs** :
```php
$valueProvider->getProductFieldValues($productId, $shopId, $langId);
// Retourne ['field_slug' => 'valeur']
```

### 9. Système de Traductions Multi-Niveaux

**2 niveaux de traductions** :

1. **Métadonnées du champ** (title, instructions) :
   - Stockées dans `ps_wepresta_acf_field_lang`
   - Éditées dans ACF Builder
   - Table principale = langue par défaut

2. **Valeurs des champs** (si `translatable: true`) :
   - Stockées dans `ps_wepresta_acf_field_value` + `_lang`
   - Éditées dans les pages produit/catégorie
   - Fallback sur langue par défaut

### 10. Auto-Sync et Export/Import

**Auto-sync vers thème** :
- Détecte les changements et exporte automatiquement vers `sync/acf-config.json`
- Permet la synchronisation entre environnements
- Utile pour le développement → production

**Sync manuelle** :
- Export JSON des groupes
- Import depuis thème ou fichier
- Validation des conflits

## 🔄 Flux de Données Complet

```
1. BuilderController::index()
   ↓ Charge types de champs, emplacements, langues
2. Vue.js App → builderStore.loadGroups()
   ↓ API GET /api/groups → GroupApiController::list()
3. Utilisateur crée groupe → store.createNewGroup()
   ↓ État local uniquement
4. Configuration wizard (3 étapes)
   ↓ Validation côté client
5. Sauvegarde → API POST /api/groups → GroupApiController::create()
   ↓ GroupMutationService::create() → Repository → Base de données
6. Ajout champs → API POST /api/groups/{id}/fields
   ↓ FieldMutationService::create() → Validation slugs, Repository
7. Sauvegarde valeurs (BO produit) → API POST /api/values
   ↓ ValueHandler::saveEntityFieldValues() → Repository
8. Affichage front-office → ValueProvider::getProductFieldValues()
   ↓ Injection dans templates Smarty/Twig
```

## 🎯 Points Clés du Système

- **Validation en cascade** : Groupe → Champs → Valeurs
- **Slugs uniques** : Auto-génération avec résolution conflits
- **Architecture extensible** : Types de champs plugables
- **Multi-shop/multi-langue** : Support complet
- **Clean Architecture** : Séparation claire des couches
- **Auto-sync** : Synchronisation thème/environnements

## 📁 Structure des Fichiers Principaux

### Backend
- `src/Presentation/Controller/Admin/BuilderController.php` - Point d'entrée
- `src/Infrastructure/Api/GroupApiController.php` - API Groupes
- `src/Infrastructure/Api/FieldApiController.php` - API Champs
- `src/Infrastructure/Api/ValueApiController.php` - API Valeurs
- `src/Application/Service/GroupMutationService.php` - Logique groupes
- `src/Application/Service/FieldMutationService.php` - Logique champs
- `src/Application/Service/ValueHandler.php` - Gestion valeurs

### Frontend
- `views/js/admin/src/App.vue` - Application principale
- `views/js/admin/src/stores/builderStore.ts` - État Vue.js
- `views/js/admin/src/components/GroupBuilder.vue` - Éditeur de groupe
- `views/js/admin/src/components/FieldConfigurator.vue` - Éditeur de champ
- `views/js/admin/src/components/FieldList.vue` - Liste des champs

### Configuration
- `config/routes.yml` - Routes Symfony
- `views/templates/admin/builder.html.twig` - Template principal

## 🔧 API Endpoints Principaux

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/groups` | GET | Liste des groupes |
| `/api/groups` | POST | Créer un groupe |
| `/api/groups/{id}` | PUT | Modifier un groupe |
| `/api/groups/{id}` | DELETE | Supprimer un groupe |
| `/api/groups/{id}/fields` | POST | Ajouter un champ |
| `/api/fields/{id}` | PUT | Modifier un champ |
| `/api/fields/{id}` | DELETE | Supprimer un champ |
| `/api/values` | POST | Sauvegarder les valeurs |
| `/api/values/{productId}` | GET | Récupérer les valeurs |

## 💾 Structure Base de Données

### Tables principales
- `ps_wepresta_acf_group` - Groupes de champs
- `ps_wepresta_acf_group_lang` - Traductions groupes
- `ps_wepresta_acf_field` - Définition des champs
- `ps_wepresta_acf_field_lang` - Traductions champs
- `ps_wepresta_acf_field_value` - Valeurs des champs
- `ps_wepresta_acf_field_value_lang` - Traductions valeurs

### Clés étrangères
- `id_wepresta_acf_group` lie les champs aux groupes
- `id_wepresta_acf_field` lie les valeurs aux champs
- `id_entity` + `entity_type` identifient l'entité (produit, catégorie, etc.)

---

*Ce document décrit le système ACF Builder tel qu'implémenté dans le module Wepresta ACF pour PrestaShop 8/9.*