# CPT - Template Override Guide

## 📝 Comment Overrider les Templates CPT

Les templates CPT supportent les overrides au niveau du thème pour **PrestaShop 8.x et 9.x**.

---

## 🎯 Structure de Template Hierarchy

### Archive (List de posts)

1. **Theme-specific archive for type:**
   ```
   themes/{theme}/modules/wepresta_acf/cpt/archive-{type}.tpl
   Exemple: themes/hummingbird/modules/wepresta_acf/cpt/archive-blog.tpl
   ```

2. **Generic theme archive:**
   ```
   themes/{theme}/modules/wepresta_acf/cpt/archive.tpl
   Exemple: themes/hummingbird/modules/wepresta_acf/cpt/archive.tpl
   ```

3. **Module default (fallback):**
   ```
   modules/wepresta_acf/views/templates/front/cpt/archive.tpl
   ```

### Single Post

1. **Theme-specific single for type:**
   ```
   themes/{theme}/modules/wepresta_acf/cpt/single-{type}.tpl
   Exemple: themes/hummingbird/modules/wepresta_acf/cpt/single-blog.tpl
   ```

2. **Generic theme single:**
   ```
   themes/{theme}/modules/wepresta_acf/cpt/single.tpl
   Exemple: themes/hummingbird/modules/wepresta_acf/cpt/single.tpl
   ```

3. **Module default (fallback):**
   ```
   modules/wepresta_acf/views/templates/front/cpt/single.tpl
   ```

### Taxonomy (Term archive)

1. **Theme-specific taxonomy for type + taxonomy:**
   ```
   themes/{theme}/modules/wepresta_acf/cpt/taxonomy-{type}-{taxonomy}.tpl
   Exemple: themes/hummingbird/modules/wepresta_acf/cpt/taxonomy-blog-category.tpl
   ```

2. **Theme-specific taxonomy for type only:**
   ```
   themes/{theme}/modules/wepresta_acf/cpt/taxonomy-{type}.tpl
   Exemple: themes/hummingbird/modules/wepresta_acf/cpt/taxonomy-blog.tpl
   ```

3. **Generic theme taxonomy:**
   ```
   themes/{theme}/modules/wepresta_acf/cpt/taxonomy.tpl
   Exemple: themes/hummingbird/modules/wepresta_acf/cpt/taxonomy.tpl
   ```

4. **Module default (fallback):**
   ```
   modules/wepresta_acf/views/templates/front/cpt/taxonomy.tpl
   ```

---

## 🔧 How to Override

### Step 1: Create Directory Structure

```bash
# Navigate to your theme directory
cd themes/{your-theme}/

# Create the override directory
mkdir -p modules/wepresta_acf/cpt/
```

### Step 2: Copy Template

```bash
# Copy the template you want to override
cp modules/wepresta_acf/views/templates/front/cpt/single.tpl \
   modules/wepresta_acf/cpt/single.tpl

# OR for a specific type:
cp modules/wepresta_acf/views/templates/front/cpt/single.tpl \
   modules/wepresta_acf/cpt/single-blog.tpl
```

### Step 3: Edit the Template

Edit the file in your theme and customize it:

```smarty
{* themes/hummingbird/modules/wepresta_acf/cpt/single-blog.tpl *}

{extends file='page.tpl'}

{block name='page_title'}
    <h1 class="my-custom-class">{$cpt_post.title}</h1>
{/block}

{block name='page_content'}
    <article class="cpt-single-blog my-blog-layout">
        {* Your custom HTML here *}
    </article>
{/block}
```

---

## ✅ Template Variables Available

### In All CPT Templates

```smarty
{* Type Information *}
{$cpt_type.id}          {* Type ID *}
{$cpt_type.slug}        {* Type slug (e.g., "blog") *}
{$cpt_type.name}        {* Type name *}
{$cpt_type.description} {* Type description *}
{$cpt_type.url}         {* Archive URL *}
```

### In Archive Template

```smarty
{* Posts array *}
{foreach $cpt_posts as $post}
    {$post.id}           {* Post ID *}
    {$post.title}        {* Post title *}
    {$post.slug}         {* Post slug *}
    {$post.url}          {* Single post URL *}
    {$post.date_add}     {* Creation date *}
    {$post.date_upd}     {* Update date *}
{/foreach}

{* Pagination *}
{$cpt_pagination.current_page}   {* Current page number *}
{$cpt_pagination.total_pages}    {* Total pages *}
{$cpt_pagination.total_items}    {* Total posts count *}
{$cpt_pagination.items_per_page} {* Items per page *}
```

### In Single Template

```smarty
{* Post Information *}
{$cpt_post.id}       {* Post ID *}
{$cpt_post.slug}     {* Post slug *}
{$cpt_post.title}    {* Post title *}
{$cpt_post.date_add} {* Creation date *}
{$cpt_post.date_upd} {* Update date *}

{* ACF Fields *}
{$acf}               {* ACF Front Service *}

{* Usage: *}
{if $acf->has('featured_image')}
    {$acf->render('featured_image')}
{/if}
```

### In Taxonomy Template

```smarty
{* Taxonomy Information *}
{$cpt_taxonomy.id}    {* Taxonomy ID *}
{$cpt_taxonomy.slug}  {* Taxonomy slug (e.g., "category") *}
{$cpt_taxonomy.name}  {* Taxonomy name *}

{* Term Information *}
{$cpt_term.id}          {* Term ID *}
{$cpt_term.slug}        {* Term slug (e.g., "tech") *}
{$cpt_term.name}        {* Term name *}
{$cpt_term.description} {* Term description *}

{* Posts and Pagination *}
{* Same as archive template *}
```

---

## 📚 Example: Override Single-Blog Template

### File: `themes/hummingbird/modules/wepresta_acf/cpt/single-blog.tpl`

```smarty
{extends file='page.tpl'}

{block name='page_title'}
    <h1 class="blog-post-title">{$cpt_post.title}</h1>
    <div class="blog-meta">
        <time datetime="{$cpt_post.date_upd}">
            {$cpt_post.date_upd|date_format:'%B %d, %Y'}
        </time>
        <a href="{$cpt_type.url}" class="back-to-blog">
            ← Back to Blog
        </a>
    </div>
{/block}

{block name='page_content'}
    <article class="blog-post">
        
        {* Featured Image *}
        {if $acf->has('featured_image')}
            <figure class="featured-image">
                {$acf->render('featured_image')}
                <figcaption>Featured image</figcaption>
            </figure>
        {/if}

        {* Main Content *}
        {if $acf->has('content')}
            <div class="blog-content">
                {$acf->render('content')}
            </div>
        {/if}

        {* Author Info *}
        {if $acf->has('author_name')}
            <footer class="blog-footer">
                <p>By <strong>{$acf->field('author_name')}</strong></p>
            </footer>
        {/if}

    </article>

    {* Related Posts (Custom Logic) *}
    <section class="related-posts">
        <h3>More Articles</h3>
        <a href="{$cpt_type.url}" class="btn">View All Posts</a>
    </section>

{/block}
```

---

## 🚀 Best Practices

### ✅ DO

- ✅ Use the template hierarchy (specific-type first, then generic)
- ✅ Keep Smarty delimiters `{...}`
- ✅ Use `{extends file='page.tpl'}` to inherit theme layout
- ✅ Use variables from the template context
- ✅ Test on both PS8 and PS9
- ✅ Clear cache after editing templates

### ❌ DON'T

- ❌ Don't modify module files directly (use override instead)
- ❌ Don't remove required Smarty blocks
- ❌ Don't hardcode content
- ❌ Don't break the template structure
- ❌ Don't forget to clear cache after changes

---

## 🔍 Debugging Template Issues

### Template Not Found Error

```
PrestaShopException: No template found for ...
```

**Solution:**
1. Check file path is correct
2. Verify filename doesn't have typos
3. Clear cache: `php bin/console cache:clear`
4. Check file exists: `ls -la themes/{theme}/modules/wepresta_acf/cpt/`

### Variables Not Showing

```smarty
{$cpt_post.title} {* Shows nothing *}
```

**Solution:**
1. Verify variable name (case-sensitive)
2. Check if controller assigns the variable
3. Use `{debug}` to inspect variables
4. Check Smarty syntax: `{$variable}` not `{$variable }` (extra space)

### ACF Fields Not Displaying

```smarty
{$acf->render('featured_image')} {* Shows nothing *}
```

**Solution:**
1. Verify field name exists in ACF group
2. Check post has value for that field
3. Use `{if $acf->has('field_name')}` before rendering
4. Check field type supports rendering

---

## 📋 Template Override Checklist

- [ ] Created `themes/{theme}/modules/wepresta_acf/cpt/` directory
- [ ] Copied template file to theme override directory
- [ ] Edited template with your custom HTML/CSS
- [ ] Verified variable names are correct
- [ ] Tested on PrestaShop 8.x (if applicable)
- [ ] Tested on PrestaShop 9.x (if applicable)
- [ ] Cleared cache after changes
- [ ] Verified page displays correctly
- [ ] Checked console for errors (F12 > Console)
- [ ] Tested responsive design (mobile/desktop)

---

## 🎓 Learning Resources

- **Smarty Template Engine:** https://www.smarty.net/docs/en/
- **PrestaShop Template Overrides:** https://devdocs.prestashop.com/
- **CPT Variables:** See "Template Variables Available" section above

---

## 📞 Support

If template override doesn't work:

1. Verify file path and filename
2. Clear all caches (Symfony + PrestaShop)
3. Check file permissions (644 or 755)
4. Verify Smarty syntax
5. Check browser console for JavaScript errors

---

Generated: 2026-01-15
Compatible: PrestaShop 8.x, 9.x
