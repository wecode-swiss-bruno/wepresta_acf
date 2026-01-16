#!/bin/bash

# ============================================================================
# Script de build production pour WePresta ACF
# ============================================================================
# Ce script :
# 1. Compile les assets (npm build)
# 2. Crée une copie temporaire du module
# 3. Nettoie la copie (supprime fichiers dev)
# 4. Optimise composer (--no-dev)
# 5. Crée le ZIP
# 6. Nettoie le dossier temporaire
# 
# ⚠️ IMPORTANT : Les fichiers sources ne sont PAS modifiés !
# ============================================================================

set -e  # Exit on error

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

MODULE_NAME="wepresta_acf"
VERSION="1.0.0"  # Version du module (pour info uniquement)
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BUILD_DIR=".build-${TIMESTAMP}"
ZIP_NAME="${MODULE_NAME}.zip"

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  WePresta ACF - Production Build Script                   ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ============================================================================
# 1. COMPILATION DES ASSETS
# ============================================================================
echo -e "${YELLOW}[1/7]${NC} Compilation des assets..."

if [ ! -d "node_modules" ]; then
    echo "  → Installation des dépendances npm..."
    npm run install:all
fi

echo "  → Build production (Webpack + Vue.js)..."
npm run build:all

echo -e "${GREEN}✓${NC} Assets compilés\n"

# ============================================================================
# 2. VÉRIFICATION QUALITÉ (SAUTÉ)
# ============================================================================
# Étape supprimée pour plus de rapidité
# (Décommenter pour activer PHPStan, PHP-CS-Fixer, etc.)


# ============================================================================
# 3. CRÉATION COPIE TEMPORAIRE
# ============================================================================
echo -e "${YELLOW}[3/7]${NC} Création de la copie temporaire..."

# Créer le dossier de build
mkdir -p "$BUILD_DIR"

# Copier tout le module (exclure .git, .cursor, node_modules)
echo "  → Copie du module..."
rsync -a --exclude='.git' \
         --exclude='.cursor' \
         --exclude='node_modules' \
         --exclude="$BUILD_DIR" \
         --exclude='*.zip' \
         --exclude='_dev' \
         --exclude='.editorconfig' \
         --exclude='.gitignore' \
         --exclude='.gitattributes' \
         --include='views/dist/***' \
         --include='views/js/admin/dist/***' \
         --include='views/css/***' \
         ./ "$BUILD_DIR/$MODULE_NAME/"

echo -e "${GREEN}✓${NC} Copie créée dans $BUILD_DIR/$MODULE_NAME/\n"

# ============================================================================
# 4. OPTIMISATION COMPOSER (dans la copie)
# ============================================================================
echo -e "${YELLOW}[4/7]${NC} Optimisation Composer..."

cd "$BUILD_DIR/$MODULE_NAME"

if [ -f "composer.json" ]; then
    echo "  → Installation dépendances production uniquement..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    echo "  → Dump optimized autoloader..."
    composer dump-autoload --optimize --no-dev
fi

cd ../..

echo -e "${GREEN}✓${NC} Composer optimisé\n"

# ============================================================================
# 5. NETTOYAGE FICHIERS DEV (dans la copie)
# ============================================================================
echo -e "${YELLOW}[5/7]${NC} Nettoyage des fichiers de développement..."

cd "$BUILD_DIR/$MODULE_NAME"

# Liste des fichiers/dossiers à supprimer
TO_REMOVE=(
    "_dev"
    "node_modules"
    "webpack.config.js"
    "package.json"
    "package-lock.json"
    "phpunit.xml"
    "phpstan.neon"
    "phpstan-baseline.neon"
    "rector.php"
    "psalm.xml"
    "infection.json"
    ".php-cs-fixer.php"
    ".php-cs-fixer.cache"
    "tests"
    "var/cache"
    "var/coverage"
    "stubs"
    "README.md"
    "ACF_FRONT_OFFICE_GUIDE.md"
    "wepresta_acf_guide_complet.md"
    ".editorconfig"
    ".gitignore"
    ".gitattributes"
    "test_*.php"
    "*.bak"
    "*.log"
)

REMOVED_COUNT=0
for item in "${TO_REMOVE[@]}"; do
    if [ -e "$item" ]; then
        rm -rf "$item"
        echo "  ✗ $item"
        ((REMOVED_COUNT++))
    fi
done

# Nettoyer var/ mais garder la structure
if [ -d "var" ]; then
    find var -type f -name "*.log" -delete
    find var -type f -name "*.cache" -delete
fi

# Nettoyer uploads/ des fichiers de test
if [ -d "uploads" ]; then
    # Optionnel : vider uploads (à activer si besoin)
    # find uploads -type f ! -name "index.php" -delete
    echo "  → uploads/ conservé (contient peut-être des données)"
fi

# Optionnel : vider sync/ pour livrer un module vierge
read -p "$(echo -e ${YELLOW}Vider le fichier sync/acf-config.json? [y/N]:${NC} )" -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if [ -f "sync/acf-config.json" ]; then
        echo "{}" > sync/acf-config.json
        echo "  ✗ sync/acf-config.json vidé"
    fi
fi

cd ../..

echo -e "${GREEN}✓${NC} $REMOVED_COUNT éléments supprimés\n"

# ============================================================================
# 6. CRÉATION DU ZIP
# ============================================================================
echo -e "${YELLOW}[6/7]${NC} Création du fichier ZIP..."

cd "$BUILD_DIR"

# Créer le ZIP (exclure les fichiers cachés restants)
zip -r "../$ZIP_NAME" "$MODULE_NAME" \
    -x "*/.*" \
    -x "*/__pycache__/*" \
    -x "*.DS_Store" \
    -q

cd ..

FILE_SIZE=$(du -h "$ZIP_NAME" | cut -f1)
echo -e "${GREEN}✓${NC} ZIP créé : $ZIP_NAME (${FILE_SIZE})\n"

# ============================================================================
# 7. NETTOYAGE DOSSIER TEMPORAIRE
# ============================================================================
echo -e "${YELLOW}[7/7]${NC} Nettoyage..."

rm -rf "$BUILD_DIR"

echo -e "${GREEN}✓${NC} Dossier temporaire supprimé\n"

# ============================================================================
# RÉSUMÉ
# ============================================================================
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✓ Build terminé avec succès !                            ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "📦 Fichier ZIP : ${BLUE}$ZIP_NAME${NC} (${FILE_SIZE})"
echo -e "📁 Emplacement : ${BLUE}$(pwd)/${ZIP_NAME}${NC}"
echo ""
echo -e "${GREEN}✓${NC} Tes fichiers sources sont intacts !"
echo -e "${GREEN}✓${NC} Tu peux continuer à développer normalement"
echo ""
echo -e "🚀 ${YELLOW}Prochaines étapes :${NC}"
echo "   1. Tester le module sur un PrestaShop de test"
echo "   2. Uploader sur PrestaShop Addons (si applicable)"
echo "   3. Installer sur les sites clients"
echo ""
