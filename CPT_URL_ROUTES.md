# CPT - URL Rewriting & Routes Documentation

## 📍 Overview

This document explains how CPT (Custom Post Types) generates friendly URLs and implements URL rewriting in PrestaShop 9.

---

## 🔗 URL Routes Implementation

### Hook: `hookModuleRoutes()`

Located in: `wepresta_acf.php` (line 372+)

This hook generates dynamic routes for each active CPT Type and its taxonomies.

---

## 📋 Available Routes

### 1. **Archive Route** (List all posts for a type)

**URL Pattern:**
```
/blog/
/blog/page/2/
```

**PHP:**
```php
$cptUrlService->getArchiveUrl($type);
```

**Route Key:**
```
module-wepresta_acf-cpt-{type_slug}-archive
module-wepresta_acf-cpt-{type_slug}-archive-page
```

**Controller:** `cptarchive`

**Parameters:**
- `type`: Type slug (e.g., "blog")
- `p`: Page number (optional)

---

### 2. **Single Post Route** (Display individual post)

**URL Pattern:**
```
/blog/my-first-article
/blog/how-to-use-vue3
```

**PHP:**
```php
$cptUrlService->getPostUrl($post, $type);
```

**Route Key:**
```
module-wepresta_acf-cpt-{type_slug}-single
```

**Controller:** `cptsingle`

**Parameters:**
- `type`: Type slug (e.g., "blog")
- `slug`: Post slug (e.g., "my-first-article")

---

### 3. **Taxonomy Term Route** (Filter posts by category/tag)

**URL Pattern:**
```
/blog/category/tech
/blog/category/tech/page/2
```

**PHP:**
```php
$cptUrlService->getTermUrl($term, $type);
```

**Route Key:**
```
module-wepresta_acf-cpt-{type_slug}-taxonomy-{taxonomy_slug}
module-wepresta_acf-cpt-{type_slug}-taxonomy-{taxonomy_slug}-page
```

**Controller:** `cpttaxonomy`

**Parameters:**
- `type`: Type slug (e.g., "blog")
- `taxonomy`: Taxonomy ID (numeric)
- `term`: Term slug (e.g., "tech")
- `p`: Page number (optional)

---

## 🛠️ How It Works

### Step 1: CPT Type Creation

When you create a CPT Type in the admin:
- **Name:** "Blog"
- **Slug:** "blog"
- **URL Prefix:** "blog"
- **Archive Enabled:** Yes

### Step 2: Hook Execution

When PrestaShop loads, the `hookModuleRoutes()` method:

1. Gets all **active CPT Types**
2. For each type, generates routes:
   - Archive route (`/blog/`)
   - Single post route (`/blog/{slug}`)
   - Pagination route (`/blog/page/{page}`)
3. Gets all **taxonomies linked to the type**
4. For each taxonomy, generates routes:
   - Taxonomy route (`/blog/{taxonomy_slug}/{term}`)
   - Taxonomy pagination route (`/blog/{taxonomy_slug}/{term}/page/{page}`)

### Step 3: URL Matching

When user visits `/blog/my-article`:

1. PrestaShop route dispatcher matches against all registered routes
2. Finds: `module-wepresta_acf-cpt-blog-single`
3. Extracts parameters:
   - `type` = "blog"
   - `slug` = "my-article"
4. Routes to `cptsingle` controller with these parameters

### Step 4: Controller Processing

The `cptsingle` controller:

1. Gets the type from `$_GET['type']` or route parameter
2. Gets the post slug from `$_GET['slug']` or route parameter
3. Queries the database
4. Renders the template

---

## 📊 Complete URL Mapping Example

**Setup:**
- CPT Type: "Blog" (slug: `blog`, prefix: `blog`)
- Taxonomies: "Categories" (slug: `category`), "Tags" (slug: `tag`)
- Terms under Categories: "Tech", "Marketing"
- Posts: "Vue 3 Guide" (slug: `vue-3-guide`), "React Tips" (slug: `react-tips`)

**Generated URLs:**

| URL | Controller | What it shows |
|-----|-----------|---------------|
| `/blog/` | cptarchive | All blog posts (page 1) |
| `/blog/page/2/` | cptarchive | All blog posts (page 2) |
| `/blog/vue-3-guide` | cptsingle | Single post: "Vue 3 Guide" |
| `/blog/react-tips` | cptsingle | Single post: "React Tips" |
| `/blog/category/tech` | cpttaxonomy | Posts in "Tech" category |
| `/blog/category/tech/page/2` | cpttaxonomy | Posts in "Tech" category (page 2) |
| `/blog/category/marketing` | cpttaxonomy | Posts in "Marketing" category |
| `/blog/tag/javascript` | cpttaxonomy | Posts with "JavaScript" tag |

---

## ⚙️ Configuration

### Route Parameters in `hookModuleRoutes()`

```php
$routes["module-wepresta_acf-cpt-blog-archive"] = [
    'controller' => 'cptarchive',                    // Controller name
    'rule' => 'blog',                               // URL pattern
    'keywords' => [],                                // Dynamic parameters
    'params' => [
        'fc' => 'module',                           // Front controller type
        'module' => 'wepresta_acf',                // Module name
        'controller' => 'cptarchive',              // Controller name (again)
        'type' => 'blog',                          // Fixed type slug
    ],
];
```

### Available Route Variables

- `{slug}` - Post slug (matches `[_a-zA-Z0-9-\pL]*`)
- `{term}` - Term slug (matches `[_a-zA-Z0-9-\pL]*`)
- `{page}` - Page number (matches `[0-9]*`)

---

## 🔍 Debugging

### Check registered routes

```php
// In admin or CLI
$module = Module::getInstanceByName('wepresta_acf');
$routes = $module->hookModuleRoutes();
echo json_encode($routes, JSON_PRETTY_PRINT);
```

### Check if URL rewriting is enabled

```php
// In PrestaShop
echo Configuration::get('PS_REWRITING_SETTINGS');  // 1 = enabled, 0 = disabled
```

### Test a specific URL

1. Visit: `https://yoursite.com/blog/`
2. Check in browser DevTools Network tab
3. URL should show friendly route, not `index.php?...`

---

## 🚨 Troubleshooting

### URLs still show `index.php?module=wepresta_acf&...`

**Cause:** URL rewriting not enabled or routes not registered

**Solution:**
1. Admin > Preferences > SEO & URLs > Enable friendly URLs
2. Clear PrestaShop cache
3. Reinstall module

### 404 errors on friendly URLs

**Cause:** Route not matching or controller not found

**Solution:**
1. Check CPT Type is **active** (`active = 1`)
2. Check post/term is **published/active**
3. Clear cache
4. Check `.htaccess` is present in PrestaShop root

### Pagination not working

**Cause:** Pagination route not registered

**Solution:**
- Already included in `hookModuleRoutes()`
- Ensure type has `hasArchive = 1`
- Clear cache

---

## 🔗 Service Layer

### CptUrlService

Generates URLs for templates and PHP code.

```php
// Archive URL
$url = $cptUrlService->getArchiveUrl($type);
// Output: /blog/

// Single post URL
$url = $cptUrlService->getPostUrl($post, $type);
// Output: /blog/my-article

// Term URL
$url = $cptUrlService->getTermUrl($term, $type);
// Output: /blog/category/tech

// Friendly URL (manual construction)
$url = $cptUrlService->getFriendlyUrl($type, $post);
// Output: /blog/my-article
```

---

## 📝 Template Usage

### archive.tpl

```smarty
{* Link to single post *}
<a href="{$post.url}">{$post.title}</a>

{* Pagination links *}
<a href="{$link->getModuleLink('wepresta_acf', 'cptarchive', ['type' => $cpt_type.slug, 'p' => 2])}">
    Next page
</a>
```

### single.tpl

```smarty
{* Link back to archive *}
<a href="{$cpt_type.url}">Back to {$cpt_type.name}</a>
```

### taxonomy.tpl

```smarty
{* Breadcrumb *}
<a href="{$cpt_type.url}">{$cpt_type.name}</a>
<span>/</span>
<span>{$cpt_term.name}</span>

{* Pagination *}
<a href="{$link->getModuleLink('wepresta_acf', 'cpttaxonomy', 
    ['type' => $cpt_type.slug, 'taxonomy' => $cpt_taxonomy.id, 'term' => $cpt_term.slug, 'p' => 2])}">
    Next
</a>
```

---

## 📚 Related Files

- **Route Definition:** `wepresta_acf.php` → `hookModuleRoutes()`
- **URL Generation:** `src/Application/Service/CptUrlService.php`
- **Front Controllers:** `controllers/front/cptarchive.php`, `cptsingle.php`, `cpttaxonomy.php`
- **Templates:** `views/templates/front/cpt/`
- **Services:** `src/Application/Service/CptTypeService.php`, `CptPostService.php`, `CptTaxonomyService.php`

---

## ✅ Checklist

- [ ] CPT Type created with unique slug
- [ ] URL Prefix set for the type
- [ ] Archive enabled if needed
- [ ] Taxonomies linked to type
- [ ] Terms created under taxonomies
- [ ] Posts created and published
- [ ] ACF groups assigned to CPT Type
- [ ] URL rewriting enabled in PrestaShop admin
- [ ] Module cache cleared
- [ ] `.htaccess` present in PrestaShop root
- [ ] Test URLs in browser

---

Generated: 2026-01-15
Module: wepresta_acf v1.0
