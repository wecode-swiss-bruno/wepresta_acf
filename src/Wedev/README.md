# WEDEV Framework

⚠️ **NE PAS MODIFIER** - Ce dossier est géré par WEDEV CLI.

## Contenu

### Core/
Classes de base et utilitaires :
- **Adapter/** : ConfigurationAdapter, ContextAdapter, ShopAdapter
- **Contract/** : Interfaces (Configurable, Installable, Repository...)
- **Exception/** : Exceptions métier (EntityNotFound, Validation...)
- **Repository/** : AbstractRepository avec méthodes CRUD et ManyToMany
- **Service/** : CacheService
- **Trait/** : LoggerTrait, TranslatorTrait, MultiShopTrait...

### Extension/
Extensions optionnelles :
- **Audit/** : Journalisation des actions
- **EntityPicker/** : Sélection d'entités via AJAX
- **Http/** : Client HTTP avec retry et rate limiting
- **Import/** : Import/Export CSV, JSON, XML
- **Jobs/** : Tâches asynchrones en arrière-plan
- **Notifications/** : Email, SMS, Push
- **Rules/** : Moteur de règles métier
- **UI/** : Composants UI Twig/Smarty/JS

## Mise à jour

Pour mettre à jour le framework :
```bash
wedev ps module --update-core
```

## Personnalisation

Pour personnaliser une classe du Core, **étendez-la** dans votre namespace :

```php
namespace MonModule\Infrastructure\Repository;

use MonModule\Wedev\Core\Repository\AbstractRepository;

class MyRepository extends AbstractRepository
{
    // Votre code
}
```

