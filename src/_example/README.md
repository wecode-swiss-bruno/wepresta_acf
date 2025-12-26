# Exemples WEDEV

📝 Ce dossier contient des **exemples de code** pour vous aider à démarrer.

## Utilisation

1. **Copiez** les fichiers dont vous avez besoin vers les dossiers correspondants
2. **Renommez** les classes (WeprestaAcf → VotreModule)
3. **Adaptez** le code à vos besoins
4. **Supprimez** ce dossier `_example/` quand vous avez terminé

## Contenu

### Application/
- `Form/ConfigurationType.php` - Formulaire de configuration du module
- `Installer/ModuleInstaller.php` - Logique d'installation (tables, hooks, config)
- `Installer/ModuleUninstaller.php` - Logique de désinstallation
- `Service/WeprestaAcfService.php` - Service métier exemple

### Domain/
- `Entity/WeprestaAcfEntity.php` - Entité métier avec getters/setters
- `Repository/WeprestaAcfRepositoryInterface.php` - Interface du repository

### Infrastructure/
- `Adapter/ConfigurationAdapter.php` - Exemple d'adapter custom
- `Api/ApiController.php` - Contrôleur API REST
- `EventSubscriber/` - Listeners d'événements Symfony
- `Repository/WeprestaAcfRepository.php` - Implémentation repository

### Presentation/
- `Controller/Admin/ConfigurationController.php` - Contrôleur back-office

## Workflow typique

```bash
# 1. Copier l'entité
cp _example/Domain/Entity/WeprestaAcfEntity.php Domain/Entity/MonEntite.php

# 2. Copier le repository interface
cp _example/Domain/Repository/WeprestaAcfRepositoryInterface.php Domain/Repository/MonEntiteRepositoryInterface.php

# 3. Copier l'implémentation
cp _example/Infrastructure/Repository/WeprestaAcfRepository.php Infrastructure/Repository/MonEntiteRepository.php

# 4. Copier le service
cp _example/Application/Service/WeprestaAcfService.php Application/Service/MonEntiteService.php

# 5. Adapter les noms de classes et namespaces
# 6. Supprimer _example/ quand terminé
```

## Namespace

Les exemples utilisent le namespace `WeprestaAcf\Example\...`.
Lors de la copie, remplacez par votre namespace : `VotreModule\Application\...`

