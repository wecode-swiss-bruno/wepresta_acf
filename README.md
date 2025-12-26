# WeprestaAcf

Module WeprestaAcf pour PrestaShop

## Installation

```bash
# Via console PrestaShop
bin/console prestashop:module install wepresta_acf

# Ou via DDEV
ddev exec bin/console prestashop:module install wepresta_acf
```

## Développement

### Structure

```
wepresta_acf/
├── wepresta_acf.php      # Fichier principal du module
├── config/
│   ├── services.yml       # Services Symfony
│   └── routes.yml         # Routes admin
├── controllers/
│   └── front/             # Contrôleurs front-office
├── src/
│   ├── Controller/        # Contrôleurs Symfony
│   ├── Entity/            # Entités Doctrine
│   ├── Repository/        # Repositories
│   └── Service/           # Services
├── sql/
│   ├── install.sql        # Tables à créer
│   └── uninstall.sql      # Tables à supprimer
├── views/
│   ├── css/               # Styles
│   ├── js/                # Scripts
│   └── templates/         # Templates Smarty
└── tests/                 # Tests PHPUnit
```

### Commandes

```bash
# Vider le cache
rm -rf var/cache/* && php bin/console cache:clear --no-warmup 2>/dev/null || true
# Tests
composer phpunit

# Analyse statique
composer phpstan
```

## Hooks enregistrés

- `displayHeader`
- `displayHome`
- `displayFooter`
- `displayProductAdditionalInfo`
- `displayShoppingCart`
- `actionFrontControllerSetMedia`
- `actionAdminControllerSetMedia`
- `actionProductAdd`
- `actionValidateOrder`

## Licence

MIT
