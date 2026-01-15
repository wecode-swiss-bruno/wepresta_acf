# URL Amicales CPT - Guide Rapide

## 🚀 Installation & Test

### 1. Réinstaller le Module

```bash
cd /path/to/prestashop
php bin/console prestashop:module:uninstall wepresta_acf
php bin/console prestashop:module:install wepresta_acf

# Ou depuis l'admin: Modules > Module Manager > WePresta ACF > Réinstaller
```

### 2. Vérifier l'URL Rewriting

**Admin Panel:**
1. Aller à: **Paramètres > SEO & URLs**
2. Cocher: ✓ **"Activer les URL amicales"**
3. Cliquer: **Enregistrer**

**Result:**
- Avant: `https://site.com/index.php?module=wepresta_acf&controller=cptarchive&type=blog`
- Après: `https://site.com/blog/`

### 3. Créer des Données de Test

```bash
php modules/wepresta_acf/demo_cpt_blog.php
```

Cela crée:
- ✅ CPT Type: "Blog"
- ✅ Taxonomie: "Categories"
- ✅ Termes: "Tech", "Marketing", "News"
- ✅ Posts: 3 articles (2 publiés, 1 brouillon)

### 4. Tester les Routes

```bash
php modules/wepresta_acf/test_cpt_routes.php
```

**Output attendu:**
```
✅ Module installed
✅ Found 1 active CPT Type(s):
   - Blog (slug: blog)
✅ 8 routes registered:
   - module-wepresta_acf-cpt-blog-archive → blog
   - module-wepresta_acf-cpt-blog-archive-page → blog/page/{page}
   - module-wepresta_acf-cpt-blog-single → blog/{slug}
   - ...
✅ URL rewriting is ENABLED
✅ All systems operational!
```

---

## 📍 URLs Disponibles

### Archive (Page d'accueil du blog)

```
https://site.com/blog/
https://site.com/blog/page/2/
https://site.com/blog/page/3/
```

### Post Unique

```
https://site.com/blog/mon-premier-article
https://site.com/blog/comment-utiliser-vue3
https://site.com/blog/react-best-practices
```

### Catégories

```
https://site.com/blog/category/tech
https://site.com/blog/category/tech/page/2/

https://site.com/blog/category/marketing
https://site.com/blog/category/news
```

---

## 🔧 Code PHP pour Générer les URLs

### Dans les Contrôleurs

```php
$cptUrlService = AcfServiceContainer::get('WeprestaAcf\Application\Service\CptUrlService');

// Archive URL
$archiveUrl = $cptUrlService->getArchiveUrl($type);

// Single post URL
$postUrl = $cptUrlService->getPostUrl($post, $type);

// Term/Category URL
$termUrl = $cptUrlService->getTermUrl($term, $type);
```

### Dans les Templates Smarty

```smarty
{* Archive link *}
<a href="{$cpt_type.url}">View all posts</a>

{* Post link *}
<a href="{$post.url}">Read more</a>

{* Category link *}
<a href="{$link->getModuleLink('wepresta_acf', 'cpttaxonomy', 
    ['type' => $cpt_type.slug, 'taxonomy' => $cpt_taxonomy.id, 'term' => $cpt_term.slug])}">
    {$cpt_term.name}
</a>

{* Pagination *}
<a href="{$link->getModuleLink('wepresta_acf', 'cptarchive', ['type' => $cpt_type.slug, 'p' => 2])}">
    Page suivante
</a>
```

---

## 🎯 Fichiers Modifiés

| Fichier | Changement |
|---------|-----------|
| `wepresta_acf.php` | Complété `hookModuleRoutes()` avec taxonomies |
| `src/Application/Service/CptUrlService.php` | Service pour générer les URLs |
| `controllers/front/*.php` | Contrôleurs front (archive, single, taxonomy) |
| `views/templates/front/cpt/` | Templates Smarty |

---

## 🔍 Routes Enregistrées Complètes

Après avoir créé un CPT Type "Blog":

```
module-wepresta_acf-cpt-blog-archive
  Rule:       blog
  Controller: cptarchive
  URL:        /blog/

module-wepresta_acf-cpt-blog-archive-page
  Rule:       blog/page/{page}
  Controller: cptarchive
  URL:        /blog/page/2/

module-wepresta_acf-cpt-blog-single
  Rule:       blog/{slug}
  Controller: cptsingle
  URL:        /blog/my-article

module-wepresta_acf-cpt-blog-taxonomy-category
  Rule:       blog/category/{term}
  Controller: cpttaxonomy
  URL:        /blog/category/tech

module-wepresta_acf-cpt-blog-taxonomy-category-page
  Rule:       blog/category/{term}/page/{page}
  Controller: cpttaxonomy
  URL:        /blog/category/tech/page/2/
```

---

## ⚠️ Troubleshooting

### ❌ Les URLs montrent toujours `index.php?...`

**Cause:** URL rewriting désactivée

**Solution:**
```bash
# 1. Admin > Paramètres > SEO & URLs > Activer les URL amicales
# 2. Clear cache
php bin/console cache:clear

# 3. Vérifier .htaccess existe
ls -la .htaccess

# 4. Réinstaller le module
php bin/console prestashop:module:install wepresta_acf
```

### ❌ 404 sur `/blog/`

**Cause:** CPT Type "Blog" non créé ou inactif

**Solution:**
```bash
# 1. Créer un CPT Type
php modules/wepresta_acf/demo_cpt_blog.php

# 2. Ou créer manuellement via l'admin
# Admin > Modules > WePresta ACF > Builder > Custom Post Types > New CPT Type
```

### ❌ Les posts ne s'affichent pas

**Cause:** Posts non publiés ou ACF groups non assignés

**Solution:**
1. Créer un CPT Type "Blog"
2. Créer un Groupe ACF "Blog Fields"
3. Assigner le groupe au type CPT
4. Créer des posts
5. Publier les posts (status = "published")

---

## 📚 Documentation Complète

Voir: `CPT_URL_ROUTES.md`

---

## ✅ Checklist de Mise en Place

- [ ] Module réinstallé
- [ ] URL rewriting activée
- [ ] `.htaccess` présent
- [ ] CPT Type créé (ex: "Blog")
- [ ] Taxonomies créées et liées au type
- [ ] Termes créés
- [ ] Posts créés et publiés
- [ ] ACF groups assignés au type CPT
- [ ] Test avec `test_cpt_routes.php`
- [ ] URLs testées en navigateur

---

Generated: 2026-01-15
