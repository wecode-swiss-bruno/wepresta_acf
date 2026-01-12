# WePresta ACF - Guide d'intégration Front-Office

## Introduction

WePresta ACF permet d'afficher des champs personnalisés dans vos templates PrestaShop.
La variable `$acf` est automatiquement disponible dans tous les templates Smarty (.tpl).

---

## Méthodes principales

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `$acf->field('slug')` | Valeur du champ (échappée XSS) | `{$acf->field('marque')}` |
| `$acf->raw('slug')` | Valeur brute (non échappée) | `{$acf->raw('code_html')}` |
| `$acf->render('slug')` | Rendu HTML formaté | `{$acf->render('image')}` |
| `$acf->label('slug')` | Label traduit (select/radio/checkbox) | `{$acf->label('taille')}` |
| `$acf->has('slug')` | Vérifie si le champ a une valeur | `{if $acf->has('promo')}` |
| `$acf->group(id)` | Tous les champs d'un groupe | `{foreach $acf->group(1) as $f}` |
| `$acf->repeater('slug')` | Lignes d'un répéteur (labels résolus) | `{foreach $acf->repeater('specs') as $row}` |
| `$acf->repeater('slug', false)` | Lignes d'un répéteur (valeurs brutes) | `{foreach $acf->repeater('specs', false) as $row}` |
| `$acf->countRepeater('slug')` | Nombre de lignes d'un répéteur | `{if $acf->countRepeater('specs') > 0}` |

---

## Champs de texte

### Text (text)
```smarty
{* Affichage simple *}
{$acf->field('titre_custom')}

{* Avec valeur par défaut *}
{$acf->field('titre_custom', 'Titre par défaut')}

{* Conditionnel *}
{if $acf->has('titre_custom')}
    <h2>{$acf->field('titre_custom')}</h2>
{/if}
```

### Textarea (textarea)
```smarty
{* Le texte est automatiquement formaté avec les sauts de ligne *}
{$acf->render('description_courte')}

{* Ou valeur brute *}
{$acf->field('description_courte')|nl2br}
```

### Rich Text / WYSIWYG (richtext)
```smarty
{* IMPORTANT: Toujours utiliser render() pour le HTML *}
{$acf->render('contenu_riche')}

{* Ou avec raw() si vous utilisez field() *}
{$acf->raw('contenu_riche')}
```

### Email (email)
```smarty
{* Lien cliquable automatique avec render() *}
{$acf->render('email_contact')}

{* Ou personnalisé *}
<a href="mailto:{$acf->field('email_contact')}">
    Nous contacter
</a>
```

### URL (url)
```smarty
{* Lien automatique *}
{$acf->render('site_web')}

{* Personnalisé *}
<a href="{$acf->field('site_web')}" target="_blank" rel="noopener">
    Visiter le site
</a>
```

---

## Champs numériques

### Number (number)
```smarty
{* Valeur simple *}
{$acf->field('quantite')}

{* Avec formatage *}
{$acf->field('prix_special')|number_format:2:',':' '} €

{* Dans un calcul *}
{assign var="prix" value=$acf->field('prix_special')}
{if $prix > 100}
    <span class="promo">Prix réduit!</span>
{/if}
```

---

## Champs de choix

### Différence entre les méthodes (Select, Radio, Checkbox)

| Méthode | Retourne | Exemple avec `taille = "xl"` (label FR: "Extra Large") |
|---------|----------|--------------------------------------------------|
| `$acf->field('taille')` | Valeur brute | `xl` |
| `$acf->label('taille')` | Label traduit (texte simple) | `Extra Large` |
| `$acf->render('taille')` | Label traduit (HTML) | `<span class="acf-select">Extra Large</span>` |

### Select (select)
```smarty
{* Label traduit sans HTML (recommandé pour du texte simple) *}
{$acf->label('taille')}

{* Label traduit avec balise HTML *}
{$acf->render('taille')}

{* Valeur brute (la clé technique) *}
{$acf->field('taille')}

{* Conditionnel selon la valeur technique *}
{if $acf->field('taille') == 'xl'}
    <span class="badge">Grande taille</span>
{/if}

{* Affichage personnalisé *}
<p>Taille : {$acf->label('taille')}</p>
```

### Radio (radio)
```smarty
{* Même usage que select *}
{$acf->label('couleur_principale')}

{* Ou avec HTML *}
{$acf->render('couleur_principale')}
```

### Checkbox (checkbox)
```smarty
{* Labels traduits séparés par virgule *}
{$acf->label('options')}

{* Avec HTML *}
{$acf->render('options')}

{* Itérer sur les valeurs brutes *}
{assign var="options" value=$acf->raw('options')}
{if is_array($options)}
    <ul>
    {foreach $options as $opt}
        <li>{$opt}</li>
    {/foreach}
    </ul>
{/if}
```

### Boolean / Switch (boolean)
```smarty
{* Affichage avec icône ✓ ou ✗ *}
{$acf->render('en_stock')}

{* Usage conditionnel *}
{if $acf->field('en_stock')}
    <span class="stock-ok">En stock</span>
{else}
    <span class="stock-ko">Rupture</span>
{/if}
```

---

## Champs média

### Image (image)
```smarty
{* Rendu automatique avec balise <img> *}
{$acf->render('photo_produit')}

{* Personnalisé *}
{assign var="img" value=$acf->raw('photo_produit')}
{if $img}
    <img src="{$img.url}" alt="{$img.alt|default:''}" class="ma-classe">
{/if}
```

### Gallery (gallery)
```smarty
{* Grille automatique *}
{$acf->render('galerie_photos')}

{* Personnalisé *}
{assign var="images" value=$acf->raw('galerie_photos')}
{if $images && is_array($images)}
    <div class="ma-galerie">
    {foreach $images as $img}
        <div class="galerie-item">
            <img src="{$img.url}" alt="{$img.alt|default:''}">
        </div>
    {/foreach}
    </div>
{/if}
```

### Video (video)
```smarty
{* Player automatique (YouTube/Vimeo/Upload) *}
{$acf->render('video_presentation')}

{* Accès aux données *}
{assign var="video" value=$acf->raw('video_presentation')}
{if $video}
    Source: {$video.source} {* youtube, vimeo, upload *}
    URL: {$video.url}
    {if $video.video_id}ID: {$video.video_id}{/if}
{/if}
```

### File (file)
```smarty
{* Lien de téléchargement automatique *}
{$acf->render('fiche_technique')}

{* Personnalisé *}
{assign var="fichier" value=$acf->raw('fiche_technique')}
{if $fichier}
    <a href="{$fichier.url}" download class="btn-download">
        📄 Télécharger {$fichier.title|default:'le fichier'}
    </a>
{/if}
```

---

## Champs date/heure

### Date (date)
```smarty
{* Format automatique (selon config) *}
{$acf->render('date_sortie')}

{* Format personnalisé *}
{$acf->field('date_sortie')|date_format:'%d/%m/%Y'}
{$acf->field('date_sortie')|date_format:'%A %d %B %Y'}
```

### DateTime (datetime)
```smarty
{$acf->render('date_evenement')}

{* Avec heure *}
{$acf->field('date_evenement')|date_format:'%d/%m/%Y à %H:%M'}
```

### Time (time)
```smarty
{$acf->render('heure_ouverture')}
```

---

## Champs spéciaux

### Color (color)
```smarty
{* Aperçu couleur *}
{$acf->render('couleur_theme')}

{* Utilisation en CSS *}
<div style="background-color: {$acf->field('couleur_theme')}">
    Contenu coloré
</div>
```

### Star Rating (star_rating)
```smarty
{* Affichage étoiles ★★★☆☆ *}
{$acf->render('note_qualite')}

{* Valeur numérique *}
Note : {$acf->field('note_qualite')}/5
```

### List (list)
```smarty
{* Liste à puces automatique *}
{$acf->render('caracteristiques')}

{* Personnalisé *}
{assign var="items" value=$acf->raw('caracteristiques')}
{if $items && is_array($items)}
    <ul class="features">
    {foreach $items as $item}
        <li>{$item}</li>
    {/foreach}
    </ul>
{/if}
```

### Relation (relation)

Le champ Relation permet de lier des produits, catégories, pages CMS, fabricants ou fournisseurs.

#### Méthodes disponibles

| Méthode | Retourne | Usage |
|---------|----------|-------|
| `$acf->raw('slug')` | `[3, 4]` (IDs bruts) | Rendu 100% personnalisé |
| `$acf->render('slug')` | HTML complet enrichi | Rendu automatique |

#### Rendu automatique avec `render()`
```smarty
{* Affiche selon le displayFormat configuré dans le builder *}
{$acf->render('produits_associes')}
```

#### Options de displayFormat (configuré dans le builder)

| displayFormat | Affichage |
|--------------|-----------|
| `name_only` | Nom (avec lien) |
| `name_reference` | Nom + (Référence) |
| `thumbnail_name` | Thumbnail + Nom |

#### Données enrichies disponibles

Quand vous utilisez `render()`, chaque entité contient :

| Propriété | Description | Types supportés |
|-----------|-------------|-----------------|
| `id` | ID de l'entité | Tous |
| `name` | Nom/Titre | Tous |
| `link` | URL | Tous |
| `reference` | Référence produit | Product |
| `image` | URL thumbnail | Product, Category, Manufacturer, Supplier |
| `price` | Prix TTC | Product |
| `description` | Description | Category |
| `type` | Type d'entité | Tous |

#### Récupérer les IDs bruts (rendu 100% perso)
```smarty
{* Récupère juste les IDs : [3, 4] *}
{assign var="productIds" value=$acf->raw('produits_associes')}

{if $productIds && is_array($productIds)}
    {foreach $productIds as $id_product}
        <p>Product ID: {$id_product}</p>
        {* Ton rendu perso ici *}
    {/foreach}
{/if}
```

#### Accéder aux données enrichies manuellement
```smarty
{* render() enrichit les données automatiquement *}
{* Pour accéder aux données sans le HTML par défaut, utilisez un foreach sur group() *}

{foreach $acf->group('mon_groupe') as $field}
    {if $field.slug == 'produits_associes' && $field.has_value}
        {* $field.value contient les données enrichies *}
        {foreach $field.value as $item}
            <div class="product-card">
                {if $item.image}
                    <img src="{$item.image}" alt="{$item.name}">
                {/if}
                <a href="{$item.link}">{$item.name}</a>
                {if $item.reference}
                    <small>Réf: {$item.reference}</small>
                {/if}
                {if $item.price}
                    <span class="price">{$item.price|number_format:2:',':' '} €</span>
                {/if}
            </div>
        {/foreach}
    {/if}
{/foreach}
```

#### Exemple complet de rendu personnalisé avec IDs
```smarty
{assign var="productIds" value=$acf->raw('produits_associes')}
{if $productIds && is_array($productIds)}
    <div class="related-products-custom">
        {foreach $productIds as $id_product}
            {* Option 1: Utiliser un widget PrestaShop *}
            {widget name="ps_productlist" productIds=[$id_product]}
            
            {* Option 2: Attribut data pour JavaScript *}
            <div class="product-placeholder" data-product-id="{$id_product}"></div>
        {/foreach}
    </div>
{/if}
```

#### Relation simple (non multiple)
```smarty
{* Rendu automatique *}
{$acf->render('produit_principal')}

{* ID brut *}
{assign var="productId" value=$acf->raw('produit_principal')}
{if $productId}
    <p>Product ID: {$productId}</p>
{/if}
```

---

## Repeater (Répéteur)

Le répéteur permet de créer des groupes de champs répétables (ex: spécifications techniques, témoignages, FAQ...).

### Usage basique
```smarty
{* Boucle sur les lignes du répéteur *}
{foreach $acf->repeater('specifications') as $row}
    <tr>
        <td>{$row.label}</td>
        <td>{$row.valeur}</td>
    </tr>
{/foreach}
```

### Avec vérification
```smarty
{if $acf->countRepeater('specifications') > 0}
    <table class="specs-table">
        <tbody>
        {foreach $acf->repeater('specifications') as $row}
            <tr>
                <th>{$row.label}</th>
                <td>{$row.valeur}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
{/if}
```

### Avec index
```smarty
{foreach $acf->repeater('temoignages') as $index => $row}
    <div class="temoignage temoignage-{$index}">
        <blockquote>{$row.texte}</blockquote>
        <cite>{$row.auteur}</cite>
    </div>
{/foreach}
```

### Résolution automatique des labels (Select, Radio, Checkbox)

Les champs de type **select**, **radio** et **checkbox** dans un repeater affichent automatiquement le **label traduit** et non la valeur brute.

```smarty
{* Exemple : repeater avec un champ select "ingredient" *}
{* Valeur stockée : "choice_1" → Affiche : "Tomate" (FR) ou "Tomato" (EN) *}

{foreach $acf->repeater('ingredients') as $row}
    <li>{$row.ingredient}</li>  {* Affiche le label traduit *}
{/foreach}
```

| Valeur stockée | Affichage (FR) | Affichage (EN) |
|----------------|----------------|----------------|
| `choice_1` | Tomate | Tomato |
| `choice_2` | Laitue | Lettuce |

### Accès aux données brutes (Code PHP)

#### Structure des données en base

Les données repeater sont stockées en JSON dans la colonne `value` de la table `wepresta_acf_field_value` :

```json
[
  {
    "row_id": "row_1768219816105_sl56glrme",
    "collapsed": false,
    "values": {
      "select_field": "choice_1"
    }
  },
  {
    "row_id": "row_1768219818925_l3yzhuh6k",
    "collapsed": false,
    "values": {
      "select_field": "choice_2"
    }
  },
  {
    "row_id": "row_1768219820708_odkjyhh7j",
    "collapsed": false,
    "values": {
      "select_field": "choice_3"
    }
  }
]
```

#### Via ValueProvider (bas niveau)

```php
use WeprestaAcf\Application\Service\AcfServiceContainer;

$valueProvider = AcfServiceContainer::getValueProvider();

// Récupérer pour une page CMS 6
$values = $valueProvider->getEntityFieldValues('cms_page', 6, 1); // shop_id = 1

// Accéder au repeater (remplacez 'mon_repeater' par votre slug)
$repeaterData = $values['mon_repeater'] ?? [];

// Itérer sur les rangées
foreach ($repeaterData as $row) {
    $selectValue = $row['values']['select_field']; // "choice_1", "choice_2", "choice_3"
    echo "Valeur : $selectValue<br>";
}
```

#### Via AcfFrontService (recommandé)

```php
// Dans un contrôleur ou hook
$acfService = AcfServiceContainer::getAcfFrontService();

// Définir le contexte (page CMS 6)
$acfService->forEntity('cms_page', 6);

// Méthode 1 : Générateur (mémoire optimisée)
foreach ($acfService->repeater('mon_repeater') as $index => $row) {
    $selectValue = $row['select_field']; // Labels résolus automatiquement
    echo "Rangée $index : $selectValue<br>";
}

// Méthode 2 : Array complet
$rows = $acfService->getRepeaterRows('mon_repeater');
foreach ($rows as $index => $row) {
    $selectValue = $row['select_field'];
    echo "Rangée $index : $selectValue<br>";
}

// Méthode 3 : Comptage
$count = $acfService->countRepeater('mon_repeater');
echo "Nombre de rangées : $count";
```

#### Données disponibles dans chaque rangée

Chaque rangée contient :
- **`_index`** : Index numérique (0, 1, 2...)
- **`_row_id`** : ID unique de la rangée
- **`$row['nom_du_champ']`** : Valeur du sous-champ avec label résolu (si applicable)


{* Définir le contexte *}
{$acf->forEntity('cms_page', 6)}

{* Itérer sur le repeater *}
{foreach $acf->repeater('mon_repeater') as $row}
    <div class="repeater-row">
        <p>Valeur sélectionnée : {$row.select_field}</p>
        <p>Index de la rangée : {$row._index}</p>
    </div>
{/foreach}

{* Ou avec array *}
{$repeaterRows = $acf->getRepeaterRows('mon_repeater')}
{foreach $repeaterRows as $row}
    <p>Rangée {$row._index} : {$row.select_field}</p>
{/foreach}

### Mode valeur brute (sans résolution)

Si vous avez besoin de la valeur technique (clé) plutôt que le label :

```smarty
{* Deuxième paramètre = false → pas de résolution des labels *}
{foreach $acf->repeater('ingredients', false) as $row}
    <li data-value="{$row.ingredient}">{$row.ingredient}</li>
    {* Affiche : "choice_1", "choice_2"... *}
{/foreach}
```

### Exemple FAQ
```smarty
{if $acf->countRepeater('faq') > 0}
<div class="faq-section">
    <h3>Questions fréquentes</h3>
    {foreach $acf->repeater('faq') as $item}
        <details class="faq-item">
            <summary>{$item.question}</summary>
            <div class="faq-answer">{$item.reponse}</div>
        </details>
    {/foreach}
</div>
{/if}
```

### Exemple avec champs mixtes

Un repeater peut contenir différents types de champs :

```smarty
{* Repeater "produits_associes" avec : nom (text), categorie (select), prix (number) *}
{if $acf->countRepeater('produits_associes') > 0}
<div class="related-products">
    {foreach $acf->repeater('produits_associes') as $row}
        <div class="product-card">
            <h4>{$row.nom}</h4>
            <span class="category">{$row.categorie}</span> {* Label traduit automatiquement *}
            <span class="price">{$row.prix} €</span>
        </div>
    {/foreach}
</div>
{/if}
```

### Propriétés spéciales dans `$row`

| Propriété | Description |
|-----------|-------------|
| `$row.slug_du_champ` | Valeur du sous-champ (label résolu pour select/radio/checkbox) |
| `$row._index` | Index de la ligne (0, 1, 2...) |
| `$row._row_id` | Identifiant unique de la ligne |

---

## Groupes de champs

Afficher tous les champs d'un groupe en une seule boucle.

### Par ID du groupe
```smarty
{foreach $acf->group(1) as $field}
    {if $field.has_value}
        <div class="champ champ-{$field.type}">
            <label>{$field.title}</label>
            <div class="valeur">{$field.rendered nofilter}</div>
        </div>
    {/if}
{/foreach}
```

### Par slug du groupe
```smarty
{foreach $acf->group('infos_produit') as $field}
    {if $field.has_value}
        {$field.rendered nofilter}
    {/if}
{/foreach}
```

### Filtrer certains champs
```smarty
{foreach $acf->group('infos_produit') as $field}
    {if $field.slug != 'champ_a_exclure' && $field.has_value}
        {$field.rendered nofilter}
    {/if}
{/foreach}
```

### Groupe avec Repeater

Quand un groupe contient un repeater, il faut le traiter séparément :

```smarty
{* Champs simples du groupe *}
{foreach $acf->group('mon_groupe') as $field}
    {if $field.type != 'repeater' && $field.has_value}
        <div class="acf-field">
            <label>{$field.title}</label>
            {$field.rendered nofilter}
        </div>
    {/if}
{/foreach}

{* Repeater séparément *}
{if $acf->countRepeater('mon_repeater') > 0}
    <div class="specifications">
        <h4>Spécifications</h4>
        <table>
            {foreach $acf->repeater('mon_repeater') as $row}
                <tr>
                    <td>{$row.label}</td>
                    <td>{$row.valeur}</td>
                </tr>
            {/foreach}
        </table>
    </div>
{/if}
```

### Propriétés disponibles dans `$field`

| Propriété | Description | Exemple |
|-----------|-------------|---------|
| `$field.slug` | Identifiant unique | `marque` |
| `$field.type` | Type de champ | `text`, `image`, `repeater`... |
| `$field.title` | Titre du champ | `Marque` |
| `$field.instructions` | Instructions/aide | `Entrez la marque...` |
| `$field.value` | Valeur brute | Dépend du type |
| `$field.rendered` | HTML généré | `<span>...</span>` |
| `$field.has_value` | A une valeur ? | `true` / `false` |

---

## Contexte différent

Afficher les champs d'une autre entité que celle de la page actuelle.

### Autre produit
```smarty
{$acf->forProduct(123)->field('marque')}
{$acf->forProduct(123)->render('image')}
```

### Autre catégorie
```smarty
{$acf->forCategory(5)->field('banniere')}
```

### Page CMS
```smarty
{$acf->forCms(10)->render('contenu_extra')}
```

### Entité générique
```smarty
{$acf->forEntity('product', 123)->field('marque')}
{$acf->forEntity('category', 5)->render('banniere')}
{$acf->forEntity('customer', 42)->field('note_interne')}
```

---

## Shortcodes (CMS & Descriptions)

Utilisables dans les pages CMS ou descriptions produits (éditeur WYSIWYG).

### Champ simple
```
[acf field="marque"]
[acf field="marque" default="Non spécifié"]
```

### Rendu HTML
```
[acf_render field="image"]
[acf_render field="video"]
```

### Groupe entier
```
[acf_group id="1"]
[acf_group slug="infos_produit"]
```

### Repeater
```
[acf_repeater slug="specifications"]
  <tr>
    <td>{row.label}</td>
    <td>{row.valeur}</td>
  </tr>
[/acf_repeater]
```

### Entité spécifique
```
[acf field="marque" entity_type="product" entity_id="123"]
```

---

## Exemples complets

### Fiche produit enrichie
```smarty
{* Section ACF sur page produit *}
{if $acf->has('video_presentation') || $acf->has('description_detaillee')}
<section class="product-acf-section">
    
    {* Vidéo de présentation *}
    {if $acf->has('video_presentation')}
        <div class="product-video">
            <h3>Vidéo</h3>
            {$acf->render('video_presentation')}
        </div>
    {/if}
    
    {* Description enrichie *}
    {if $acf->has('description_detaillee')}
        <div class="product-description-extra">
            {$acf->render('description_detaillee')}
        </div>
    {/if}
    
    {* Caractéristiques techniques (repeater) *}
    {if $acf->countRepeater('caracteristiques') > 0}
        <div class="product-specs">
            <h3>Caractéristiques</h3>
            <table class="table table-striped">
                {foreach $acf->repeater('caracteristiques') as $row}
                    <tr>
                        <th>{$row.nom}</th>
                        <td>{$row.valeur}</td>
                    </tr>
                {/foreach}
            </table>
        </div>
    {/if}
    
    {* Documents téléchargeables *}
    {if $acf->has('fiche_technique')}
        <div class="product-downloads">
            <h3>Documents</h3>
            {$acf->render('fiche_technique')}
        </div>
    {/if}
    
</section>
{/if}
```

### Page catégorie avec bannière
```smarty
{if $acf->has('banniere_categorie')}
    <div class="category-banner">
        {$acf->render('banniere_categorie')}
    </div>
{/if}

{if $acf->has('description_seo')}
    <div class="category-seo-text">
        {$acf->render('description_seo')}
    </div>
{/if}
```

---

## Bonnes pratiques

### ✅ À faire

```smarty
{* Toujours vérifier l'existence avant d'afficher *}
{if $acf->has('mon_champ')}
    {$acf->render('mon_champ')}
{/if}

{* Utiliser render() pour les types complexes *}
{$acf->render('image')}
{$acf->render('video')}
{$acf->render('richtext')}
{$acf->render('gallery')}

{* Utiliser field() pour les valeurs simples *}
{$acf->field('titre')}
{$acf->field('prix')}
{$acf->field('email')}
```

### ❌ À éviter

```smarty
{* Ne pas oublier nofilter pour le HTML des groupes *}
{$field.rendered}         {* ❌ HTML échappé *}
{$field.rendered nofilter} {* ✅ HTML correct *}

{* Ne pas utiliser field() pour le richtext *}
{$acf->field('richtext')} {* ❌ HTML échappé *}
{$acf->render('richtext')} {* ✅ HTML correct *}
```

---

## Dépannage

### Le champ n'affiche rien
1. Vérifiez que le champ a une valeur dans le back-office
2. Utilisez `{if $acf->has('slug')}` pour débugger
3. Vérifiez le slug exact du champ (sensible à la casse)

### Erreur "Call to member function on null"
Le module n'est pas actif ou le hook `displayHeader` n'est pas enregistré.
→ Réinstallez le module depuis le back-office.

### Le HTML s'affiche en texte
Utilisez `{$acf->render('slug')}` au lieu de `{$acf->field('slug')}` pour les champs riches.

### Le repeater ne s'affiche pas
Utilisez `{foreach $acf->repeater('slug') as $row}` et non `{$acf->render('slug')}`.

---

## Support

**Module** : WePresta ACF  
**Version** : 1.2.1  
**Compatibilité** : PrestaShop 8.x / 9.x

Pour toute question technique, contactez votre développeur.
