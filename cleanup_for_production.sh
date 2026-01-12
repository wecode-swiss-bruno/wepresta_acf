#!/bin/bash

# Script de nettoyage pour déploiement en production du module WePresta ACF
# Supprime tous les fichiers de développement inutiles en production

echo "🧹 Nettoyage du module WePresta ACF pour déploiement en production..."
echo "📁 Dossier actuel: $(pwd)"

# Liste des fichiers/dossiers à supprimer
TO_REMOVE=(
    "_dev"                    # Sources JavaScript/SCSS (déjà compilées)
    "node_modules"           # Dépendances Node.js
    "webpack.config.js"      # Configuration Webpack
    "package.json"           # Config npm
    "package-lock.json"      # Verrouillage npm
    "phpunit.xml"           # Tests PHPUnit
    "phpstan.neon"          # PHPStan
    "rector.php"            # Rector
    "tests"                 # Dossier tests
    "var"                   # Cache développement
    "stubs"                 # Stubs développement
    "composer.lock"         # Optionnel - peut être gardé
    "*.bak"                 # Fichiers backup
    "README.md"            # Documentation développeur
    "ACF_FRONT_OFFICE_GUIDE.md"
    "wepresta_acf_guide_complet.md"
)

echo "🗑️  Fichiers/dossiers à supprimer:"
for item in "${TO_REMOVE[@]}"; do
    if [ -e "$item" ]; then
        echo "  - $item"
    fi
done

echo ""
echo "⚠️  Cette action est IRRÉVERSIBLE!"
read -p "Continuer ? (y/N): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Opération annulée."
    exit 1
fi

# Supprimer les fichiers
REMOVED_COUNT=0
for item in "${TO_REMOVE[@]}"; do
    if [ -e "$item" ]; then
        if [ -d "$item" ]; then
            rm -rf "$item"
            echo "🗂️  Dossier supprimé: $item"
        else
            rm -f "$item"
            echo "📄 Fichier supprimé: $item"
        fi
        ((REMOVED_COUNT++))
    fi
done

echo ""
echo "✅ Nettoyage terminé!"
echo "📊 $REMOVED_COUNT éléments supprimés"
echo ""
echo "📦 Fichiers conservés pour la production:"
echo "  - src/ (code PHP)"
echo "  - views/ (templates + assets compilés)"
echo "  - config/ (configuration)"
echo "  - translations/ (traductions)"
echo "  - sql/ (scripts installation)"
echo "  - upgrade/ (mises à jour)"
echo "  - vendor/ (dépendances PHP)"
echo "  - uploads/ (fichiers utilisateur)"
echo "  - *.php, *.xml (fichiers principaux)"
echo ""
echo "🚀 Le module est prêt pour le déploiement!"