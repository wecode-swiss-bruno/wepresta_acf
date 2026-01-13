# 📚 Rulebook Master PrestaShop (WEDEV Framework)

Ce document compile l'intégralité des standards techniques, architecturaux et de sécurité pour le développement de modules PrestaShop 8/9.

---

## 🏗️ 01. Architecture: Clean & Layered

Respecter strictement la séparation des couches. Dépendances : **Presentation → Application → Domain ← Infrastructure**.

### 📦 Domain Layer (`src/Domain/`)
**Zéro dépendance externe.**
- **Entities**: Objets riches avec logique métier. Pas de setters publics; utiliser des méthodes d'action (ex: `$item->activate()`).
- **ValueObjects**: Immuables, validés à la construction (ex: `ItemId`, `ItemName`).
- **Interfaces**: Définit les contrats de stockage (ex: `ItemRepositoryInterface`).
- **Exceptions**: Exceptions métier spécifiques (ex: `ItemNotFoundException`).

### 🔧 Application Layer (`src/Application/`)
**Orchestration et cas d'utilisation.**
- **Services**: Coordonnent le Domain et l'Infrastructure. Utiliser la promotion de propriétés (PHP 8.1+).
- **Forms**: Form Types Symfony utilisant `TranslatorAwareType`. Utiliser les types natifs PS (`SwitchType`, `TranslatableType`).
- **Validation**: Contraintes Symfony (`Assert\NotBlank`, etc.).

### 🔌 Infrastructure Layer (`src/Infrastructure/`)
**Implémentations techniques.**
- **Repositories**: Étendent `AbstractRepository` ou implémentent l'interface via Doctrine/Db.
- **Adapters**: Wrappers pour les classes statiques PS (`ConfigurationAdapter`, `ContextAdapter`).
- **Api**: Contrôleurs REST et EventSubscribers.

### 🎨 Presentation Layer (`src/Presentation/`)
**Points d'entrée UI.**
- **Controllers Symfony**: Admin uniquement (PS 8+). Utiliser les Attributes PHP 8 `#[AdminSecurity]`.
- **Grid Framework**: Grilles complexes. (Voir section Grids pour PS9).
- **Legacy**: `controllers/front/` et `controllers/admin/` classiques.

---

## ⚠️ 02. PrestaShop 9 & Grids (Critique)

### Manual SearchCriteria
`buildSearchCriteriaFromRequest` est **supprimé**. Construction manuelle obligatoire :
```php
$filters = $request->query->all(MyGridDefinitionFactory::GRID_ID) ?: [];
$searchCriteria = new SearchCriteria(
    $filters['filters'] ?? [],
    $filters['orderBy'] ?? 'id_myentity',
    $filters['sortOrder'] ?? 'desc',
    (int) ($filters['offset'] ?? 0),
    (int) ($filters['limit'] ?? 10)
);
```

### Services Configuration
Les Grids **ne doivent pas** être en auto-registration. Configuration explicite requise :
```yaml
ModuleStarter\Presentation\Grid\MyEntityGridQueryBuilder:
    arguments:
        $connection: '@doctrine.dbal.default_connection'
        $dbPrefix: '%database_prefix%'
```

---

## 🔒 03. Sécurité et Standards PHP

### Headers et Types
Chaque fichier PHP doit commencer par :
```php
<?php
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}
```

### Protection SQL
- **Casting**: Toujours caster les IDs : `(int) $id`.
- **pSQL**: Toujours utiliser `pSQL($string)` pour les chaines.
- **CSRF**: Toujours utiliser les tokens (Symfony ou `Tools::getToken(false)`).

---

## 🚀 04. Extensions WEDEV (Usage Technique)

### Http (`Extension/Http`)
```php
$client = (new HttpClient())->withAuth(new BearerAuth($token))->withRetry(3);
$response = $client->postJson($url, $data);
if ($response->isSuccess()) { $data = $response->json(); }
```

### Jobs (`Extension/Jobs`)
- Hériter de `AbstractJob`. Implémenter `handle()`, `serialize()` et `deserialize()`.
- Dispatch : `JobDispatcher::dispatch(new MyJob($data))`.

### Rules (`Extension/Rules`)
```php
$rule = RuleBuilder::create('promo')->when(new CartCondition(...))->then(new SetContextAction(...))->build();
$engine->executeFirst([$rule], $context);
```

### EntityPicker (`Extension/EntityPicker`)
- Charger `services_entitypicker.yml`.
- Utiliser `EntitySearchTrait` dans les contrôleurs.
- Rendu Twig : `{{ picker.render({ name: 'ids', ... }) }}`.

### Audit (`Extension/Audit`)
- Utiliser `AuditableTrait` dans les services.
- Log : `$this->auditUpdate('Entity', $id, $old, $new)`. Respecter le RGPD (masquer données sensibles).

---

## 🎨 05. Frontend et Assets

- **Structure**: Sources dans `_dev/`, compilation via Webpack dans `views/dist/`.
- **JS ES6+**: Utiliser des classes, async/await, et l'utilitaire `AjaxHelper`.
- **SCSS**: Nomenclature **BEM**, variables et mixins centralisés. Préfixer toutes les classes par le nom du module.
- **Templates**: Toujours échapper les variables Smarty : `|escape:'html':'UTF-8'`.

---

## ✅ 06. Qualité et Tests

- **PHPStan**: Niveau 8 recommandé (Niveau 6 minimum).
- **PHP-CS-Fixer**: Standard **PSR-12** avec `declare_strict_types`.
- **Tests**:
    - **Unit**: Dans `tests/Unit/`. Mock des dépendances. Pas de DB.
    - **Integration**: Dans `tests/Integration/`. Connexion DB réelle via `legacy.context`.

---
*Ce Master Rulebook garantit la robustesse et l'évolution de nos modules. Ne jamais dévier sans validation.*
