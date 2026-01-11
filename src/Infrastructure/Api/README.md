# API Architecture - Clean & SOLID

Cette architecture API suit les principes **SOLID** pour une **maintenabilité maximale**.

## 📁 Structure

```
src/Infrastructure/Api/
├── *ApiController.php    # 🎯 Contrôleurs LÉGERS (routing uniquement)
│   ├── FieldApiController.php
│   ├── GroupApiController.php
│   ├── ValueApiController.php
│   ├── FieldTypeApiController.php
│   ├── RelationApiController.php
│   ├── SyncApiController.php
│   └── UtilityApiController.php
│
├── Request/              # 📥 DTOs d'entrée avec validation
│   ├── CreateFieldRequest.php
│   ├── UpdateFieldRequest.php
│   ├── CreateGroupRequest.php
│   ├── UpdateGroupRequest.php
│   └── SaveValuesRequest.php
│
├── Response/             # 📤 DTOs de sortie standardisés
│   ├── FieldResponse.php
│   └── GroupResponse.php
│
├── Transformer/          # 🔄 Conversion Entity → Response DTO
│   ├── FieldTransformer.php
│   └── GroupTransformer.php
│
├── Validator/            # ✅ Validateurs réutilisables
│   └── SlugValidator.php
│
├── Service/              # 🏗️ Logique métier API
│   ├── FieldMutationService.php
│   └── GroupMutationService.php
│
└── AbstractApiController.php  # 🧱 Base commune pour tous les contrôleurs
```

## ✨ Principes SOLID Appliqués

### 1️⃣ **Single Responsibility Principle (SRP)**

Chaque classe a **UNE seule responsabilité** :

- ✅ **Controllers** : Routing et orchestration
- ✅ **Request DTOs** : Parsing et validation des inputs
- ✅ **Response DTOs** : Structuration des outputs
- ✅ **Transformers** : Conversion Entity → Response
- ✅ **Validators** : Validation métier réutilisable
- ✅ **Services** : Logique métier pure

### 2️⃣ **Open/Closed Principle (OCP)**

- Les contrôleurs sont **fermés à la modification** (toute nouvelle logique va dans un Service)
- Les transformers sont **ouverts à l'extension** (on peut ajouter de nouveaux DTOs sans modifier l'existant)

### 3️⃣ **Liskov Substitution Principle (LSP)**

- Tous les contrôleurs héritent de `AbstractApiController`
- On peut remplacer n'importe quel contrôleur par un autre sans casser le code

### 4️⃣ **Interface Segregation Principle (ISP)**

- Les Request DTOs ne forcent pas les contrôleurs à dépendre de champs inutiles
- Séparation claire entre `CreateFieldRequest` et `UpdateFieldRequest`

### 5️⃣ **Dependency Inversion Principle (DIP)**

- Les contrôleurs dépendent d'**abstractions** (interfaces de repository)
- Injection de dépendances via le constructeur

## 🚀 Exemple d'Utilisation

### Avant (❌ Mauvais)

```php
// TOUT dans le contrôleur
public function create(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), true);
    
    if (empty($data['title'])) { // Validation inline
        return $this->json(['error' => 'Title required'], 400);
    }
    
    // Logique métier dans le contrôleur
    $slug = $this->slugGenerator->generate($data['title']);
    if ($this->groupRepository->slugExists($slug)) {
        return $this->json(['error' => 'Slug exists'], 400);
    }
    
    // Création
    $groupId = $this->groupRepository->create([...]);
    
    // Serialization inline
    $group = $this->groupRepository->findById($groupId);
    return $this->json([
        'id' => $group['id_wepresta_acf_group'],
        'title' => $group['title'],
        // ... 20 lignes de mapping
    ]);
}
```

### Après (✅ Bon)

```php
// Contrôleur LÉGER - délègue tout
public function create(Request $request): JsonResponse {
    try {
        // Parse + Validate via DTO
        $data = $this->getJsonPayload($request);
        $createRequest = CreateGroupRequest::fromArray($data);
        
        $errors = $createRequest->validate();
        if (!empty($errors)) {
            return $this->jsonValidationError($errors);
        }
        
        // Logique métier déléguée au Service
        $result = $this->groupMutationService->create(
            $createRequest, 
            $this->generateUuid()
        );
        
        if (!$result['success']) {
            return $this->jsonError($result['error'], 400);
        }
        
        // Transformation déléguée au Transformer
        $group = $this->groupRepository->findById($result['groupId']);
        $response = $this->groupTransformer->transform($group);
        
        return $this->jsonSuccess($response->toArray(), null, 201);
    } catch (Exception $e) {
        return $this->jsonError($e->getMessage());
    }
}
```

## 📋 Avantages de l'Architecture

| Avant | Après |
|------|------|
| ❌ 367 lignes dans FieldApiController | ✅ ~120 lignes (divisé par 3) |
| ❌ Validation éparpillée | ✅ Validation centralisée dans Request DTOs |
| ❌ Code dupliqué (getJsonPayload, jsonError, generateUuid) | ✅ Réutilisé via AbstractApiController |
| ❌ Logique métier dans contrôleur | ✅ Extraite dans Services |
| ❌ Serialization manuelle | ✅ Transformers dédiés |
| ❌ Impossible à tester unitairement | ✅ Chaque composant testable isolément |
| ❌ Modification = risque de régression | ✅ Modification isolée, zéro impact |

## 🧪 Testabilité

Chaque composant est **testable unitairement** :

```php
// Tester la validation
$request = CreateGroupRequest::fromArray(['title' => '']);
$errors = $request->validate();
$this->assertArrayHasKey('title', $errors);

// Tester le transformer
$transformer = new GroupTransformer($repo, $fieldRepo, $fieldTransformer);
$response = $transformer->transform($groupArray);
$this->assertEquals($groupArray['title'], $response->title);

// Tester le service
$service = new GroupMutationService($repo, $fieldRepo, $validator, $slugGen, $sync);
$result = $service->create($request, 'uuid');
$this->assertTrue($result['success']);
```

## 🔧 Ajout d'une Nouvelle Fonctionnalité

Pour ajouter un nouveau endpoint (ex: `PATCH /api/groups/{id}/activate`):

1. **Créer Request DTO** : `ActivateGroupRequest.php`
2. **Ajouter méthode au Service** : `GroupMutationService::activate()`
3. **Ajouter méthode au Controller** : `GroupApiController::activate()`

✅ **Zéro impact sur le code existant !**

## 📚 Règles à Suivre

### ✅ FAIRE

- Créer un Request DTO pour chaque input
- Utiliser les Transformers pour les outputs
- Déléguer la logique métier aux Services
- Hériter de `AbstractApiController`
- Typer TOUTES les méthodes et propriétés

### ❌ NE PAS FAIRE

- Validation inline dans les contrôleurs
- Logique métier dans les contrôleurs
- Duplication de code
- Accès direct à `Db::getInstance()`
- Retourner des arrays au lieu de DTOs

## 🎯 Performance

- **Pas d'overhead** : Les DTOs sont **readonly** (pas de copie mémoire)
- **Autowiring** : Injection de dépendances automatique par Symfony
- **Cache Symfony** : Les services sont instanciés une seule fois

---

**Cette architecture est scalable, maintenable et suit les best practices modernes.**
