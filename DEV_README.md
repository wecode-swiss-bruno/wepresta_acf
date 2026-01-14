# 🛠️ Guide de Développement - WePresta ACF

## Architecture du Build

Ce module utilise **2 systèmes de build** :

| Système | Technologie | Source | Output | Usage |
|---------|-------------|--------|--------|-------|
| **Webpack Encore** | Webpack 5 | `_dev/js/` + `_dev/scss/` | `views/dist/` | jQuery admin, SCSS basique |
| **Vite** | Vite 5 | `views/js/admin/src/` | `views/js/admin/dist/` | **App Vue.js** (ACF Builder, CPT, Entity Fields) |

## 🚀 Commandes de Développement

### Développement (watch mode)

```bash
# Depuis la RACINE du module
cd /Users/work/Documents/DEV/PRESTASHOP\ MODULES\ DEV/dev-ps9/modules/wepresta_acf

# ✅ RECOMMANDÉ: Lance les deux systèmes en parallèle
npm run dev

# Alternative: Vue uniquement (si vous ne modifiez que les composants Vue)
npm run dev:vue

# Alternative: Encore uniquement (si vous ne modifiez que _dev/)
npm run dev:encore
```

### Build Production

```bash
# Build complet (Encore + Vue)
npm run build

# Ou séparément
npm run build:encore
npm run build:vue
```

### Installation des dépendances

```bash
# Installe tout (racine + Vue)
npm run install:all

# Ou manuellement
npm install
cd views/js/admin && npm install
```

## 📁 Structure des Assets

```
wepresta_acf/
├── _dev/                          # Sources Webpack Encore
│   ├── js/admin.js                # jQuery admin
│   └── scss/admin.scss            # Styles admin basiques
│
├── views/
│   ├── dist/                      # Output Webpack Encore
│   │   ├── admin.js
│   │   └── admin.css
│   │
│   └── js/admin/                  # App Vue.js
│       ├── src/                   # Sources Vue/TypeScript
│       │   ├── main.ts            # ACF Builder entry
│       │   ├── cpt-main.ts        # CPT Builder entry
│       │   ├── entity-fields.ts   # Entity Fields entry
│       │   └── components/        # Composants Vue
│       │
│       └── dist/                  # Output Vite
│           ├── .vite/manifest.json    # Manifest principal
│           ├── manifest-entity.json   # Manifest entity-fields
│           ├── acf-main.{hash}.js     # ACF Builder
│           ├── acf-main.{hash}.css
│           ├── acf-cpt.{hash}.js      # CPT Builder
│           ├── acf-cpt.{hash}.css
│           └── entity-fields.{hash}.js # Entity Fields
```

## 🔄 Cache-Busting

Le système utilise des **manifests Vite** pour le cache-busting automatique :

- En **développement** : fichiers sans hash (`acf-main.js`)
- En **production** : fichiers avec hash (`acf-main.D9TJKMtJ.js`)

Les templates Twig utilisent l'extension `ViteAssetExtension` :

```twig
{# Charge automatiquement le bon fichier via le manifest #}
{{ vite_stylesheet('main') }}
{{ vite_script('main') }}
```

## ⚠️ Dépannage

### Les modifications Vue ne s'appliquent pas

1. **Vérifiez que vous êtes dans le bon dossier** :
   ```bash
   pwd
   # Doit afficher: .../modules/wepresta_acf
   ```

2. **Lancez le bon script** :
   ```bash
   npm run dev  # PAS juste "npm run watch"
   ```

3. **Hard refresh le navigateur** :
   - Mac: `Cmd + Shift + R`
   - Windows: `Ctrl + Shift + R`

4. **Videz le cache PrestaShop** :
   ```bash
   rm -rf ../../var/cache/*
   ```

5. **Vérifiez que Vite compile** :
   ```bash
   # Le terminal doit afficher:
   # [vue] watching for file changes...
   ```

### Erreur "Module not found"

```bash
# Réinstallez les dépendances Vue
cd views/js/admin
rm -rf node_modules
npm install
```

### Les styles ne s'appliquent pas

1. Vérifiez que le CSS est importé dans le template Twig
2. Inspectez les DevTools > Network pour voir si le CSS est chargé
3. Vérifiez qu'il n'y a pas d'erreur 404

## 📋 Checklist avant Commit

- [ ] `npm run build` passe sans erreur
- [ ] Tester en mode production (pas juste dev)
- [ ] Vider le cache PrestaShop
- [ ] Tester sur un navigateur en navigation privée

## 🔧 Configuration

### Vite (views/js/admin/vite.config.ts)

- `manifest: true` - Génère le manifest pour le cache-busting
- Hash en production uniquement pour faciliter le debug en dev

### Webpack Encore (webpack.config.js)

- Output dans `views/dist/`
- Pas de versioning (géré manuellement si besoin)
