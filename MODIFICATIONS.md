# 📝 Modifications - ACF Module

> Document qui regroupe toutes les modifications et améliorations du module WePresta ACF.
> **Dernière mise à jour:** 05 Jan 2026

---

## 📋 Table des matières

1. [Correction des erreurs de debug](#correction-des-erreurs-de-debug)
2. [Compilation des assets](#compilation-des-assets)
3. [Améliorations UX du Builder](#améliorations-ux-du-builder)

---

## 🐛 Correction des erreurs de debug

**Date:** 05 Jan 2026  
**Priorité:** 🔴 HAUTE  
**Status:** ✅ COMPLÉTÉ

### Problème
Code de debug laissé dans les fichiers source envoyant des requêtes vers `http://127.0.0.1:7255/ingest/...` (serveur inexistant), causant des erreurs `ERR_CONNECTION_REFUSED` dans la console du navigateur.

### Fichiers modifiés
- `views/templates/admin/builder.html.twig` - Suppression de script de debug jQuery (lignes 59-86)
- `views/js/admin/src/main.ts` - Suppression de logs de debug Vue.js

### Solution appliquée
✅ Suppression complète du code de debug inline  
✅ Recompilation des assets Vue.js avec `npm run build`

### Impact
Les erreurs `ERR_CONNECTION_REFUSED` dans le Network tab ont disparu.

---

## 📦 Compilation des assets

**Date:** 05 Jan 2026  
**Priorité:** 🔴 HAUTE  
**Status:** ✅ COMPLÉTÉ

### Problème
Le template référençait `/views/dist/admin.css` (404 Not Found) mais ce fichier n'était jamais compilé par Webpack Encore.

### Fichiers modifiés
Aucune modification de code - compilation uniquement

### Solution appliquée
✅ Exécution de `npm install` pour installer les dépendances  
✅ Exécution de `npm run build` pour compiler les assets Webpack Encore  
✅ Génération du fichier `views/dist/admin.css` (11.9 KB)

### Impact
Les styles du builder se chargent correctement, plus d'erreur 404.

---

## 🎨 Améliorations UX du Builder

**Date:** 05 Jan 2026  
**Priorité:** 🔴 HAUTE  
**Status:** ✅ COMPLÉTÉ

### Problème identifié
L'UX du Field Builder était défaillante :
- Les utilisateurs créaient des champs sans titre
- À la sauvegarde, le groupe était sauvé mais les champs ignorés silencieusement
- Aucun feedback visuel sur l'état des champs incomplets
- Flux d'ajout de champ peu intuitif

### Fichiers modifiés

#### 1. `views/js/admin/src/stores/builderStore.ts`
**Modifications:**
- ✅ Ajout validation avant `saveGroup()` (ligne ~101-120)
  - Vérification du titre du groupe
  - Vérification que tous les champs ont un titre
  - Messages d'erreur explicites
  - Auto-sélection du champ invalide
  
- ✅ Mise à jour du computed `hasUnsavedChanges` (ligne ~46-55)
  - Détecte les nouveaux groupes non sauvegardés
  - Détecte les champs nouveaux non sauvegardés
  
- ✅ Confirmation avant quitter dans `goToList()` (ligne ~235-245)
  - Pop-up si changements non sauvegardés
  - Empêche la perte accidentelle de données
  
- ✅ Ajout de titres par défaut dans `addField()` (ligne ~246-270)
  - Map de 21 types de champs avec titres descriptifs
  - Ex: "Text Field", "Image Upload", "Repeater Field"

#### 2. `views/js/admin/src/components/FieldList.vue`
**Modifications:**
- ✅ Indicateur visuel sur champs incomplets (ligne ~127)
  - Classe `field-incomplete` appliquée si pas de titre
  
- ✅ Icône warning animée (ligne ~133-135)
  - `<span class="material-icons text-warning incomplete-icon">warning</span>`
  - Animation pulse 2s
  
- ✅ Texte titre en gris si vide (ligne ~144)
  - Différenciation visuelle "untitled"
  
- ✅ Styles CSS ajoutés (ligne ~308+)
  - `.field-incomplete` - bordure jaune + background
  - `.incomplete-icon` - animation pulse
  - `@keyframes pulse` - animation 2s

#### 3. `views/js/admin/src/components/FieldConfigurator.vue`
**Modifications:**
- ✅ Import `nextTick` de Vue (ligne 1)
  
- ✅ Focus automatique sur titre pour nouveaux champs (ligne ~41-55)
  - Si champ sans ID → focus + select du texte par défaut
  - Utilise `nextTick` pour attendre le DOM
  
- ✅ Class `field-title-input` sur l'input (ligne ~216)
  - Permet la sélection par JS

#### 4. `views/js/admin/src/components/GroupBuilder.vue`
**Modifications:**
- ✅ Badge "Not saved" dans le titre (ligne ~21-27)
  - Affichage conditionnel si `hasUnsavedChanges && !saving`
  - Icône warning + texte "Not saved"
  - Animation pulse
  
- ✅ Styles CSS ajoutés (ligne ~185+)
  - `.badge` - style et animation
  - `@keyframes pulse-badge` - animation 2s
  - Marges pour Material Icons

### Flux utilisateur amélioré

**Avant:**
1. Créer groupe → titre vide ❌
2. Ajouter champ → titre vide ❌
3. Cliquer Save
4. Le champ est silencieusement ignoré
5. Recharger page → rien n'est sauvé 😞

**Après:**
1. Créer groupe → titre auto-rempli ✅
2. Ajouter champ → titre par défaut + **focus automatique** ✅
3. Éditer le titre (déjà sélectionné) - rapide!
4. Voir le badge "⚠️ Not saved" en rouge
5. Cliquer Save
6. Validation complète → message d'erreur clair si incomplet ✅
7. Changements sauvegardés ✅

### Recompilation

✅ `npm run build` exécuté dans `views/js/admin/`  
✅ Assets générés: `dist/acf-admin.js` (215 KB) + `dist/acf-main.css` (9.35 KB)

### Impact UX

| Aspect | Avant | Après |
|--------|-------|-------|
| **Création champ** | Vide, confus | Titre par défaut, focus auto |
| **Feedback erreurs** | Silencieux (perte de données) | Messages clairs + indicateurs |
| **Visibilité champs incomplets** | Aucune | Bordure jaune + icône warning |
| **Unsaved changes** | Aucun indicateur | Badge "⚠️ Not saved" visible |
| **Confirmation avant quitter** | Perte possible | Pop-up de confirmation |

---

## 🚀 Prochaines améliorations potentielles

- [ ] Dirty checking granulaire (détection changements en temps réel)
- [ ] Sauvegarde auto toutes les 30s
- [ ] Historique des modifications (undo/redo)
- [ ] Validation côté client avancée (pattern, etc.)
- [ ] Export/Import de groupes de champs
- [ ] Prévisualisation du formulaire front-office

---

## 📌 Notes

- Tous les changements sont **rétro-compatibles**
- Aucune migration de données nécessaire
- Tests recommandés sur différents navigateurs (Chrome, Firefox, Safari)
- Performance: Pas d'impact significatif


