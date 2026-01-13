<script setup lang="ts">
  /**
   * ACF Documentation Panel
   * 
   * Displays documentation on how to use ACF in front-office templates.
   * Can be shown as a slide-out panel or modal.
   */
  
  import { ref, computed } from 'vue'
  import { useCodeGenerator } from '@/composables/useCodeGenerator'
  
  const props = defineProps<{
    show: boolean
  }>()
  
  const emit = defineEmits<{
    (e: 'close'): void
  }>()
  
  const { copyToClipboard } = useCodeGenerator()
  const activeTab = ref<'smarty' | 'twig' | 'shortcodes'>('smarty')
  const copiedIndex = ref<number | null>(null)
  
  async function handleCopy(code: string, index: number) {
    const success = await copyToClipboard(code)
    if (success) {
      copiedIndex.value = index
      setTimeout(() => {
        copiedIndex.value = null
      }, 2000)
    }
  }
<<<<<<< HEAD
}

const smartyExamples = [
  {
    title: 'Get field value (escaped)',
    code: `{$acf->field('brand')}`,
    description: 'Returns the field value with XSS protection'
  },
  {
    title: 'Get field value with default',
    code: `{$acf->field('brand', 'Not specified')}`,
    description: 'Returns field value or default if empty'
  },
  {
    title: 'Get raw value (not escaped)',
    code: `{$acf->raw('description')}`,
    description: 'Returns raw value - use only for trusted content'
  },
  {
    title: 'Render field as HTML',
    code: `{$acf->render('product_image')}`,
    description: 'Renders field with appropriate HTML (images, videos, etc.)'
  },
  {
    title: 'Get translated label (select/radio/checkbox)',
    code: `{$acf->label('size')}`,
    description: 'Returns translated label instead of raw value'
  },
  {
    title: 'Check if field has value',
    code: `{if $acf->has('promo_badge')}\n  <span class="badge">{$acf->field('promo_badge')}</span>\n{/if}`,
    description: 'Conditional display based on field value'
  },
  {
    title: 'Loop through repeater',
    code: `{foreach $acf->repeater('specifications') as $row}\n  <tr>\n    <td>{$row.label}</td>\n    <td>{$row.value}</td>\n  </tr>\n{/foreach}`,
    description: 'Iterate over repeater field rows with auto-label resolution'
  },
  {
    title: 'Loop through repeater with index',
    code: `{foreach $acf->repeater('testimonials') as $index => $row}\n  <div class="testimonial testimonial-{$index}">\n    <blockquote>{$row.text}</blockquote>\n    <cite>{$row.author}</cite>\n  </div>\n{/foreach}`,
    description: 'Access row index in repeater loop'
  },
  {
    title: 'Check repeater count',
    code: `{if $acf->countRepeater('specifications') > 0}\n  <h3>Specifications</h3>\n  <table>\n    {foreach $acf->repeater('specifications') as $row}\n      <tr><td>{$row.label}</td><td>{$row.value}</td></tr>\n    {/foreach}\n  </table>\n{/if}`,
    description: 'Check if repeater has rows before displaying'
  },
  {
    title: 'Display all group fields',
    code: `{foreach $acf->group('product_info') as $field}\n  {if $field.has_value}\n    <div class="field">\n      <label>{$field.title}</label>\n      {$field.rendered nofilter}\n    </div>\n  {/if}\n{/foreach}`,
    description: 'Render all fields from a group'
  },
  {
    title: 'Display group by ID',
    code: `{foreach $acf->group(1) as $field}\n  {if $field.type != 'repeater' && $field.has_value}\n    <div class="acf-field">\n      <label>{$field.title}</label>\n      {$field.rendered nofilter}\n    </div>\n  {/if}\n{/foreach}`,
    description: 'Render all fields from a group by ID'
  },
  {
    title: 'Override context (other product)',
    code: `{$acf->forProduct(123)->field('brand')}`,
    description: 'Get field from a specific product'
  },
  {
    title: 'Override context (any entity)',
    code: `{$acf->forEntity('category', 5)->render('banner')}\n{$acf->forEntity('cms_page', 10)->field('custom_title')}`,
    description: 'Get field from any entity type (product, category, cms_page, customer, etc.)'
  },
  {
    title: 'Using Smarty function',
    code: `{acf_field slug="brand" default="N/A"}`,
    description: 'Alternative syntax using Smarty function'
  },
  {
    title: 'Rich text field (HTML)',
    code: `{$acf->render('rich_content')}`,
    description: 'Render rich text/WYSIWYG content with HTML (use render, not field)'
  },
  {
    title: 'Email field with mailto link',
    code: `<a href="mailto:{$acf->field('contact_email')}">\n  {$acf->field('contact_email')}\n</a>`,
    description: 'Create mailto link using field value'
  },
  {
    title: 'URL field with link',
    code: `<a href="{$acf->field('website')}" target="_blank" rel="noopener">\n  Visit website\n</a>`,
    description: 'Create external link using URL field'
  },
  {
    title: 'Number field with formatting',
    code: `{$acf->field('price')|number_format:2:',':' '} €`,
    description: 'Format number fields with currency'
  },
  {
    title: 'Date field formatting',
    code: `{$acf->field('release_date')|date_format:'%d/%m/%Y'}\n{$acf->field('event_date')|date_format:'%A %d %B %Y'}`,
    description: 'Format date fields with custom patterns'
  },
  {
    title: 'Boolean field (conditional)',
    code: `{if $acf->field('in_stock')}\n  <span class="stock-ok">✓ In stock</span>\n{else}\n  <span class="stock-ko">✗ Out of stock</span>\n{/if}`,
    description: 'Conditional display based on boolean field'
  },
  {
    title: 'Boolean field (rendered)',
    code: `{$acf->render('featured')}`,
    description: 'Render boolean field with icons/symbols'
  },
  {
    title: 'Select/Radio field (raw value)',
    code: `{if $acf->field('size') == 'xl'}\n  <span class="shipping">Free shipping!</span>\n{/if}`,
    description: 'Use raw value for conditional logic'
  },
  {
    title: 'Select/Radio field (translated label)',
    code: `<p>Size: {$acf->label('size')}</p>`,
    description: 'Display translated label for user-friendly output'
  },
  {
    title: 'Checkbox field (multiple values)',
    code: `{assign var="options" value=$acf->raw('features')}\n{if is_array($options)}\n  <ul>\n  {foreach $options as $option}\n    <li>{$option}</li>\n  {/foreach}\n  </ul>\n{/if}`,
    description: 'Handle multiple checkbox selections as array'
  },
  {
    title: 'Image field (custom display)',
    code: `{assign var="img" value=$acf->raw('product_image')}\n{if $img}\n  <img src="{$img.url}" alt="{$img.alt|default:''}" \n       class="custom-image" loading="lazy">\n{/if}`,
    description: 'Access image properties (url, alt, title, etc.)'
  },
  {
    title: 'Gallery field (custom grid)',
    code: `{assign var="images" value=$acf->raw('photo_gallery')}\n{if $images && is_array($images)}\n  <div class="gallery-grid">\n  {foreach $images as $img}\n    <div class="gallery-item">\n      <img src="{$img.url}" alt="{$img.alt|default:''}">\n    </div>\n  {/foreach}\n  </div>\n{/if}`,
    description: 'Create custom gallery layout with image arrays'
  },
  {
    title: 'Video field (custom player)',
    code: `{assign var="video" value=$acf->raw('promo_video')}\n{if $video}\n  <div class="video-wrapper">\n    <video controls poster="{$video.poster|default:''}">\n      <source src="{$video.url}" type="{$video.mime|default:'video/mp4'}">\n    </video>\n  </div>\n{/if}`,
    description: 'Access video properties (url, poster, mime type, etc.)'
  },
  {
    title: 'File field (download link)',
    code: `{assign var="file" value=$acf->raw('brochure')}\n{if $file}\n  <a href="{$file.url}" download class="download-btn">\n    📄 Download {$file.title|default:'file'}\n  </a>\n{/if}`,
    description: 'Create download link with file properties'
  },
  {
    title: 'Color field (inline style)',
    code: `<div style="background-color: {$acf->field('theme_color')}">\n  Colored content\n</div>`,
    description: 'Use color field value in CSS styling'
  },
  {
    title: 'Star rating display',
    code: `Rating: {$acf->render('quality_rating')}\n<span class="rating-text">\n  {$acf->field('quality_rating')}/5 stars\n</span>`,
    description: 'Display star rating with visual and numeric values'
  },
  {
    title: 'List field (custom bullets)',
    code: `{assign var="features" value=$acf->raw('product_features')}\n{if $features && is_array($features)}\n  <ul class="feature-list">\n  {foreach $features as $feature}\n    <li class="feature-item">{$feature}</li>\n  {/foreach}\n  </ul>\n{/if}`,
    description: 'Create custom styled list from list field'
  },
  {
    title: 'Relation field (IDs only)',
    code: `{assign var="productIds" value=$acf->raw('related_products')}\n{if $productIds && is_array($productIds)}\n  {foreach $productIds as $id}\n    <p>Product ID: {$id}</p>\n  {/foreach}\n{/if}`,
    description: 'Access raw IDs from relation field for custom logic'
  },
  {
    title: 'Relation field (enriched data)',
    code: `{foreach $acf->group('product_group') as $field}\n  {if $field.slug == 'related_products' && $field.has_value}\n    {foreach $field.value as $product}\n      <div class="product-card">\n        <a href="{$product.link}">{$product.name}</a>\n        {if $product.image}<img src="{$product.image}" alt="{$product.name}">{/if}\n        {if $product.price}<span class="price">{$product.price}</span>{/if}\n      </div>\n    {/foreach}\n  {/if}\n{/foreach}`,
    description: 'Access enriched relation data with links, images, prices'
  }
]

const twigExamples = [
  {
    title: 'Get field value (escaped)',
    code: `{{ acf_field('brand') }}`,
    description: 'Returns the field value with XSS protection'
  },
  {
    title: 'Get field value with default',
    code: `{{ acf_field('brand', 'Not specified') }}`,
    description: 'Returns field value or default if empty'
  },
  {
    title: 'Get raw value (not escaped)',
    code: `{{ acf_raw('description') }}`,
    description: 'Returns raw value - use only for trusted content'
  },
  {
    title: 'Render field as HTML',
    code: `{{ acf_render('product_image') }}`,
    description: 'Renders field with appropriate HTML'
  },
  {
    title: 'Get translated label (select/radio/checkbox)',
    code: `{{ acf_label('size') }}`,
    description: 'Returns translated label instead of raw value'
  },
  {
    title: 'Check if field has value',
    code: `{% if acf_has('promo_badge') %}\n  <span class="badge">{{ acf_field('promo_badge') }}</span>\n{% endif %}`,
    description: 'Conditional display'
  },
  {
    title: 'Loop through repeater',
    code: `{% for row in acf_repeater('specifications') %}\n  <tr>\n    <td>{{ row.label }}</td>\n    <td>{{ row.value }}</td>\n  </tr>\n{% endfor %}`,
    description: 'Iterate over repeater rows with auto-label resolution'
  },
  {
    title: 'Loop through repeater with index',
    code: `{% for row in acf_repeater('testimonials') %}\n  <div class="testimonial">\n    <blockquote>{{ row.text }}</blockquote>\n    <cite>{{ row.author }}</cite>\n  </div>\n{% endfor %}`,
    description: 'Access repeater rows with automatic index'
  },
  {
    title: 'Check repeater count',
    code: `{% if acf_count_repeater('specifications') > 0 %}\n  <h3>Specifications</h3>\n  <table>\n    {% for row in acf_repeater('specifications') %}\n      <tr><td>{{ row.label }}</td><td>{{ row.value }}</td></tr>\n    {% endfor %}\n  </table>\n{% endif %}`,
    description: 'Check if repeater has rows before displaying'
  },
  {
    title: 'Display all group fields',
    code: `{% for field in acf_group('product_info') %}\n  {% if field.has_value %}\n    <div class="field">\n      <label>{{ field.title }}</label>\n      {{ field.rendered|raw }}\n    </div>\n  {% endif %}\n{% endfor %}`,
    description: 'Render all fields from a group'
  },
  {
    title: 'Display group by ID',
    code: `{% for field in acf_group_by_id(1) %}\n  {% if field.type != 'repeater' and field.has_value %}\n    <div class="acf-field">\n      <label>{{ field.title }}</label>\n      {{ field.rendered|raw }}\n    </div>\n  {% endif %}\n{% endfor %}`,
    description: 'Render all fields from a group by ID'
  },
  {
    title: 'Override context (specific entity)',
    code: `{{ acf_field('brand', '', 'product', 123) }}`,
    description: 'Get field from specific entity (product, category, etc.)'
  },
  {
    title: 'Rich text field (HTML)',
    code: `{{ acf_render('rich_content') }}`,
    description: 'Render rich text/WYSIWYG content (use render, not field)'
  },
  {
    title: 'Email field with mailto link',
    code: `<a href="mailto:{{ acf_field('contact_email') }}">\n  {{ acf_field('contact_email') }}\n</a>`,
    description: 'Create mailto link using field value'
  },
  {
    title: 'URL field with link',
    code: `<a href="{{ acf_field('website') }}" target="_blank" rel="noopener">\n  Visit website\n</a>`,
    description: 'Create external link using URL field'
  },
  {
    title: 'Number field with formatting',
    code: `{{ acf_field('price') }} €`,
    description: 'Display number field (formatting can be done with filters)'
  },
  {
    title: 'Boolean field (conditional)',
    code: `{% if acf_field('in_stock') %}\n  <span class="stock-ok">✓ In stock</span>\n{% else %}\n  <span class="stock-ko">✗ Out of stock</span>\n{% endif %}`,
    description: 'Conditional display based on boolean field'
  },
  {
    title: 'Select/Radio field (translated label)',
    code: `<p>Size: {{ acf_label('size') }}</p>`,
    description: 'Display translated label for user-friendly output'
  },
  {
    title: 'Image field (custom display)',
    code: `{% set img = acf_raw('product_image') %}\n{% if img %}\n  <img src="{{ img.url }}" alt="{{ img.alt|default('') }}" \n       class="custom-image" loading="lazy">\n{% endif %}`,
    description: 'Access image properties in Twig'
  },
  {
    title: 'Gallery field (custom grid)',
    code: `{% set images = acf_raw('photo_gallery') %}\n{% if images is iterable %}\n  <div class="gallery-grid">\n  {% for img in images %}\n    <div class="gallery-item">\n      <img src="{{ img.url }}" alt="{{ img.alt|default('') }}">\n    </div>\n  {% endfor %}\n  </div>\n{% endif %}`,
    description: 'Create custom gallery layout with image arrays'
  }
]

const shortcodeExamples = [
  {
    title: 'Basic field value',
    code: `[acf field="brand"]`,
    description: 'Display field value in CMS content or product descriptions'
  },
  {
    title: 'Field with default value',
    code: `[acf field="brand" default="Not specified"]`,
    description: 'Show default text if field is empty'
  },
  {
    title: 'Render field as HTML',
    code: `[acf_render field="product_image"]`,
    description: 'Render field with appropriate HTML formatting'
  },
  {
    title: 'Render field as HTML with default',
    code: `[acf_render field="gallery" default="No images available"]`,
    description: 'Render field with HTML or show default if empty'
  },
  {
    title: 'Render complete group',
    code: `[acf_group id="1"]`,
    description: 'Display all fields from group ID 1'
  },
  {
    title: 'Render group by slug',
    code: `[acf_group slug="product_info"]`,
    description: 'Display all fields from group by slug'
  },
  {
    title: 'Simple repeater loop',
    code: `[acf_repeater slug="features"]\n  <li>{row.title}: {row.description}</li>\n[/acf_repeater]`,
    description: 'Loop through repeater rows in CMS content'
  },
  {
    title: 'Repeater with conditional',
    code: `[acf_repeater slug="testimonials"]\n  {if row.rating >= 4}\n    <div class="good-review">\n      <strong>{row.name}</strong>: {row.comment}\n      <span class="stars">★★★★★</span>\n    </div>\n  {/if}\n[/acf_repeater]`,
    description: 'Repeater with conditional logic inside the loop'
  },
  {
    title: 'Field from specific product',
    code: `[acf field="brand" entity_type="product" entity_id="123"]`,
    description: 'Get field value from a specific product'
  },
  {
    title: 'Field from specific category',
    code: `[acf field="banner" entity_type="category" entity_id="5"]`,
    description: 'Get field value from a specific category'
  },
  {
    title: 'Field from specific CMS page',
    code: `[acf_render field="extra_content" entity_type="cms_page" entity_id="10"]`,
    description: 'Render field from a specific CMS page'
  },
  {
    title: 'Render repeater from another product',
    code: `[acf_repeater slug="specifications" entity_type="product" entity_id="456"]\n  <tr>\n    <td>{row.label}</td>\n    <td>{row.value}</td>\n  </tr>\n[/acf_repeater]`,
    description: 'Display repeater from a different entity'
  },
  {
    title: 'Multiple shortcodes in content',
    code: `<h2>Product Information</h2>\n[acf field="brand"] - [acf field="model"]\n\n<h3>Description</h3>\n[acf_render field="description"]\n\n<h3>Specifications</h3>\n<table>\n[acf_repeater slug="specs"]\n  <tr><td>{row.name}</td><td>{row.value}</td></tr>\n[/acf_repeater]\n</table>`,
    description: 'Combine multiple ACF shortcodes in rich content'
  },
  {
    title: 'FAQ section with repeater',
    code: `<div class="faq-section">\n  <h3>Frequently Asked Questions</h3>\n  [acf_repeater slug="faq"]\n    <details>\n      <summary>{row.question}</summary>\n      <div class="answer">{row.answer}</div>\n    </details>\n  [/acf_repeater]\n</div>`,
    description: 'Create FAQ section using repeater shortcode'
  }
]
</script>

<template>
  <transition name="slide">
    <div v-if="show" class="docs-panel">
      <div class="docs-header">
        <h3>
          <span class="material-icons">menu_book</span>
          Front-Office Documentation
        </h3>
        <button class="btn btn-link close-btn" @click="$emit('close')">
          <span class="material-icons">close</span>
        </button>
      </div>

      <div class="docs-tabs">
        <button 
          class="tab-btn" 
          :class="{ active: activeTab === 'smarty' }"
          @click="activeTab = 'smarty'"
        >
          <span class="material-icons">code</span>
          Smarty
        </button>
        <button 
          class="tab-btn" 
          :class="{ active: activeTab === 'twig' }"
          @click="activeTab = 'twig'"
        >
          <span class="material-icons">code</span>
          Twig
        </button>
        <button 
          class="tab-btn" 
          :class="{ active: activeTab === 'shortcodes' }"
          @click="activeTab = 'shortcodes'"
        >
          <span class="material-icons">short_text</span>
          Shortcodes
        </button>
      </div>

      <div class="docs-content">
        <!-- Smarty Tab -->
        <div v-if="activeTab === 'smarty'" class="docs-section">
          <div class="intro-box">
            <p>
              The <code>$acf</code> variable is automatically available in all front-office Smarty templates.
              Use it to display ACF field values with automatic context detection.
            </p>
          </div>

          <div 
            v-for="(example, index) in smartyExamples" 
            :key="index"
            class="example-card"
          >
            <div class="example-header">
              <h5>{{ example.title }}</h5>
              <button 
                class="btn btn-sm copy-btn"
                :class="{ copied: copiedIndex === index }"
                @click="handleCopy(example.code, index)"
              >
                <span class="material-icons">
                  {{ copiedIndex === index ? 'check' : 'content_copy' }}
                </span>
              </button>
            </div>
            <pre class="example-code"><code>{{ example.code }}</code></pre>
            <p class="example-desc">{{ example.description }}</p>
          </div>
=======
  
  const smartyExamples = [
    {
      title: 'Get field value (escaped)',
      code: `{$acf->field('brand')}`,
      description: 'Returns the field value with XSS protection'
    },
    {
      title: 'Get field value with default',
      code: `{$acf->field('brand', 'Not specified')}`,
      description: 'Returns field value or default if empty'
    },
    {
      title: 'Get raw value (not escaped)',
      code: `{$acf->raw('description')}`,
      description: 'Returns raw value - use only for trusted content'
    },
    {
      title: 'Render field as HTML',
      code: `{$acf->render('product_image')}`,
      description: 'Renders field with appropriate HTML (images, videos, etc.)'
    },
    {
      title: 'Get translated label (select/radio/checkbox)',
      code: `{$acf->label('size')}`,
      description: 'Returns translated label instead of raw value'
    },
    {
      title: 'Check if field has value',
      code: `{if $acf->has('promo_badge')}\n  <span class="badge">{$acf->field('promo_badge')}</span>\n{/if}`,
      description: 'Conditional display based on field value'
    },
    {
      title: 'Loop through repeater',
      code: `{foreach $acf->repeater('specifications') as $row}\n  <tr>\n    <td>{$row.label}</td>\n    <td>{$row.value}</td>\n  </tr>\n{/foreach}`,
      description: 'Iterate over repeater field rows with auto-label resolution'
    },
    {
      title: 'Loop through repeater with index',
      code: `{foreach $acf->repeater('testimonials') as $index => $row}\n  <div class="testimonial testimonial-{$index}">\n    <blockquote>{$row.text}</blockquote>\n    <cite>{$row.author}</cite>\n  </div>\n{/foreach}`,
      description: 'Access row index in repeater loop'
    },
    {
      title: 'Check repeater count',
      code: `{if $acf->countRepeater('specifications') > 0}\n  <h3>Specifications</h3>\n  <table>\n    {foreach $acf->repeater('specifications') as $row}\n      <tr><td>{$row.label}</td><td>{$row.value}</td></tr>\n    {/foreach}\n  </table>\n{/if}`,
      description: 'Check if repeater has rows before displaying'
    },
    {
      title: 'Display all group fields',
      code: `{foreach $acf->group('product_info') as $field}\n  {if $field.has_value}\n    <div class="field">\n      <label>{$field.title}</label>\n      {$field.rendered nofilter}\n    </div>\n  {/if}\n{/foreach}`,
      description: 'Render all fields from a group'
    },
    {
      title: 'Display group by ID',
      code: `{foreach $acf->group(1) as $field}\n  {if $field.type != 'repeater' && $field.has_value}\n    <div class="acf-field">\n      <label>{$field.title}</label>\n      {$field.rendered nofilter}\n    </div>\n  {/if}\n{/foreach}`,
      description: 'Render all fields from a group by ID'
    },
    {
      title: 'Override context (other product)',
      code: `{$acf->forProduct(123)->field('brand')}`,
      description: 'Get field from a specific product'
    },
    {
      title: 'Override context (any entity)',
      code: `{$acf->forEntity('category', 5)->render('banner')}\n{$acf->forEntity('cms_page', 10)->field('custom_title')}`,
      description: 'Get field from any entity type (product, category, cms_page, customer, etc.)'
    },
    {
      title: 'Using Smarty function',
      code: `{acf_field slug="brand" default="N/A"}`,
      description: 'Alternative syntax using Smarty function'
    },
    {
      title: 'Rich text field (HTML)',
      code: `{$acf->render('rich_content')}`,
      description: 'Render rich text/WYSIWYG content with HTML (use render, not field)'
    },
    {
      title: 'Email field with mailto link',
      code: `<a href="mailto:{$acf->field('contact_email')}">\n  {$acf->field('contact_email')}\n</a>`,
      description: 'Create mailto link using field value'
    },
    {
      title: 'URL field with link',
      code: `<a href="{$acf->field('website')}" target="_blank" rel="noopener">\n  Visit website\n</a>`,
      description: 'Create external link using URL field'
    },
    {
      title: 'Number field with formatting',
      code: `{$acf->field('price')|number_format:2:',':' '} €`,
      description: 'Format number fields with currency'
    },
    {
      title: 'Date field formatting',
      code: `{$acf->field('release_date')|date_format:'%d/%m/%Y'}\n{$acf->field('event_date')|date_format:'%A %d %B %Y'}`,
      description: 'Format date fields with custom patterns'
    },
    {
      title: 'Boolean field (conditional)',
      code: `{if $acf->field('in_stock')}\n  <span class="stock-ok">✓ In stock</span>\n{else}\n  <span class="stock-ko">✗ Out of stock</span>\n{/if}`,
      description: 'Conditional display based on boolean field'
    },
    {
      title: 'Boolean field (rendered)',
      code: `{$acf->render('featured')}`,
      description: 'Render boolean field with icons/symbols'
    },
    {
      title: 'Select/Radio field (raw value)',
      code: `{if $acf->field('size') == 'xl'}\n  <span class="shipping">Free shipping!</span>\n{/if}`,
      description: 'Use raw value for conditional logic'
    },
    {
      title: 'Select/Radio field (translated label)',
      code: `<p>Size: {$acf->label('size')}</p>`,
      description: 'Display translated label for user-friendly output'
    },
    {
      title: 'Checkbox field (multiple values)',
      code: `{assign var="options" value=$acf->raw('features')}\n{if is_array($options)}\n  <ul>\n  {foreach $options as $option}\n    <li>{$option}</li>\n  {/foreach}\n  </ul>\n{/if}`,
      description: 'Handle multiple checkbox selections as array'
    },
    {
      title: 'Image field (custom display)',
      code: `{assign var="img" value=$acf->raw('product_image')}\n{if $img}\n  <img src="{$img.url}" alt="{$img.alt|default:''}" \n       class="custom-image" loading="lazy">\n{/if}`,
      description: 'Access image properties (url, alt, title, etc.)'
    },
    {
      title: 'Gallery field (custom grid)',
      code: `{assign var="images" value=$acf->raw('photo_gallery')}\n{if $images && is_array($images)}\n  <div class="gallery-grid">\n  {foreach $images as $img}\n    <div class="gallery-item">\n      <img src="{$img.url}" alt="{$img.alt|default:''}">\n    </div>\n  {/foreach}\n  </div>\n{/if}`,
      description: 'Create custom gallery layout with image arrays'
    },
    {
      title: 'Video field (custom player)',
      code: `{assign var="video" value=$acf->raw('promo_video')}\n{if $video}\n  <div class="video-wrapper">\n    <video controls poster="{$video.poster|default:''}">\n      <source src="{$video.url}" type="{$video.mime|default:'video/mp4'}">\n    </video>\n  </div>\n{/if}`,
      description: 'Access video properties (url, poster, mime type, etc.)'
    },
    {
      title: 'File field (download link)',
      code: `{assign var="file" value=$acf->raw('brochure')}\n{if $file}\n  <a href="{$file.url}" download class="download-btn">\n    📄 Download {$file.title|default:'file'}\n  </a>\n{/if}`,
      description: 'Create download link with file properties'
    },
    {
      title: 'Color field (inline style)',
      code: `<div style="background-color: {$acf->field('theme_color')}">\n  Colored content\n</div>`,
      description: 'Use color field value in CSS styling'
    },
    {
      title: 'Star rating display',
      code: `Rating: {$acf->render('quality_rating')}\n<span class="rating-text">\n  {$acf->field('quality_rating')}/5 stars\n</span>`,
      description: 'Display star rating with visual and numeric values'
    },
    {
      title: 'List field (custom bullets)',
      code: `{assign var="features" value=$acf->raw('product_features')}\n{if $features && is_array($features)}\n  <ul class="feature-list">\n  {foreach $features as $feature}\n    <li class="feature-item">{$feature}</li>\n  {/foreach}\n  </ul>\n{/if}`,
      description: 'Create custom styled list from list field'
    },
    {
      title: 'Relation field (IDs only)',
      code: `{assign var="productIds" value=$acf->raw('related_products')}\n{if $productIds && is_array($productIds)}\n  {foreach $productIds as $id}\n    <p>Product ID: {$id}</p>\n  {/foreach}\n{/if}`,
      description: 'Access raw IDs from relation field for custom logic'
    },
    {
      title: 'Relation field (enriched data)',
      code: `{foreach $acf->group('product_group') as $field}\n  {if $field.slug == 'related_products' && $field.has_value}\n    {foreach $field.value as $product}\n      <div class="product-card">\n        <a href="{$product.link}">{$product.name}</a>\n        {if $product.image}<img src="{$product.image}" alt="{$product.name}">{/if}\n        {if $product.price}<span class="price">{$product.price}</span>{/if}\n      </div>\n    {/foreach}\n  {/if}\n{/foreach}`,
      description: 'Access enriched relation data with links, images, prices'
    }
  ]
  
  const twigExamples = [
    {
      title: 'Get field value (escaped)',
      code: `{{ acf_field('brand') }}`,
      description: 'Returns the field value with XSS protection'
    },
    {
      title: 'Get field value with default',
      code: `{{ acf_field('brand', 'Not specified') }}`,
      description: 'Returns field value or default if empty'
    },
    {
      title: 'Get raw value (not escaped)',
      code: `{{ acf_raw('description') }}`,
      description: 'Returns raw value - use only for trusted content'
    },
    {
      title: 'Render field as HTML',
      code: `{{ acf_render('product_image') }}`,
      description: 'Renders field with appropriate HTML'
    },
    {
      title: 'Get translated label (select/radio/checkbox)',
      code: `{{ acf_label('size') }}`,
      description: 'Returns translated label instead of raw value'
    },
    {
      title: 'Check if field has value',
      code: `{% if acf_has('promo_badge') %}\n  <span class="badge">{{ acf_field('promo_badge') }}</span>\n{% endif %}`,
      description: 'Conditional display'
    },
    {
      title: 'Loop through repeater',
      code: `{% for row in acf_repeater('specifications') %}\n  <tr>\n    <td>{{ row.label }}</td>\n    <td>{{ row.value }}</td>\n  </tr>\n{% endfor %}`,
      description: 'Iterate over repeater rows with auto-label resolution'
    },
    {
      title: 'Loop through repeater with index',
      code: `{% for row in acf_repeater('testimonials') %}\n  <div class="testimonial">\n    <blockquote>{{ row.text }}</blockquote>\n    <cite>{{ row.author }}</cite>\n  </div>\n{% endfor %}`,
      description: 'Access repeater rows with automatic index'
    },
    {
      title: 'Check repeater count',
      code: `{% if acf_count_repeater('specifications') > 0 %}\n  <h3>Specifications</h3>\n  <table>\n    {% for row in acf_repeater('specifications') %}\n      <tr><td>{{ row.label }}</td><td>{{ row.value }}</td></tr>\n    {% endfor %}\n  </table>\n{% endif %}`,
      description: 'Check if repeater has rows before displaying'
    },
    {
      title: 'Display all group fields',
      code: `{% for field in acf_group('product_info') %}\n  {% if field.has_value %}\n    <div class="field">\n      <label>{{ field.title }}</label>\n      {{ field.rendered|raw }}\n    </div>\n  {% endif %}\n{% endfor %}`,
      description: 'Render all fields from a group'
    },
    {
      title: 'Display group by ID',
      code: `{% for field in acf_group_by_id(1) %}\n  {% if field.type != 'repeater' and field.has_value %}\n    <div class="acf-field">\n      <label>{{ field.title }}</label>\n      {{ field.rendered|raw }}\n    </div>\n  {% endif %}\n{% endfor %}`,
      description: 'Render all fields from a group by ID'
    },
    {
      title: 'Override context (specific entity)',
      code: `{{ acf_field('brand', '', 'product', 123) }}`,
      description: 'Get field from specific entity (product, category, etc.)'
    },
    {
      title: 'Rich text field (HTML)',
      code: `{{ acf_render('rich_content') }}`,
      description: 'Render rich text/WYSIWYG content (use render, not field)'
    },
    {
      title: 'Email field with mailto link',
      code: `<a href="mailto:{{ acf_field('contact_email') }}">\n  {{ acf_field('contact_email') }}\n</a>`,
      description: 'Create mailto link using field value'
    },
    {
      title: 'URL field with link',
      code: `<a href="{{ acf_field('website') }}" target="_blank" rel="noopener">\n  Visit website\n</a>`,
      description: 'Create external link using URL field'
    },
    {
      title: 'Number field with formatting',
      code: `{{ acf_field('price') }} €`,
      description: 'Display number field (formatting can be done with filters)'
    },
    {
      title: 'Boolean field (conditional)',
      code: `{% if acf_field('in_stock') %}\n  <span class="stock-ok">✓ In stock</span>\n{% else %}\n  <span class="stock-ko">✗ Out of stock</span>\n{% endif %}`,
      description: 'Conditional display based on boolean field'
    },
    {
      title: 'Select/Radio field (translated label)',
      code: `<p>Size: {{ acf_label('size') }}</p>`,
      description: 'Display translated label for user-friendly output'
    },
    {
      title: 'Image field (custom display)',
      code: `{% set img = acf_raw('product_image') %}\n{% if img %}\n  <img src="{{ img.url }}" alt="{{ img.alt|default('') }}" \n       class="custom-image" loading="lazy">\n{% endif %}`,
      description: 'Access image properties in Twig'
    },
    {
      title: 'Gallery field (custom grid)',
      code: `{% set images = acf_raw('photo_gallery') %}\n{% if images is iterable %}\n  <div class="gallery-grid">\n  {% for img in images %}\n    <div class="gallery-item">\n      <img src="{{ img.url }}" alt="{{ img.alt|default('') }}">\n    </div>\n  {% endfor %}\n  </div>\n{% endif %}`,
      description: 'Create custom gallery layout with image arrays'
    }
  ]
  
  const shortcodeExamples = [
    {
      title: 'Basic field value',
      code: `[acf field="brand"]`,
      description: 'Display field value in CMS content or product descriptions'
    },
    {
      title: 'Field with default value',
      code: `[acf field="brand" default="Not specified"]`,
      description: 'Show default text if field is empty'
    },
    {
      title: 'Render field as HTML',
      code: `[acf_render field="product_image"]`,
      description: 'Render field with appropriate HTML formatting'
    },
    {
      title: 'Render field as HTML with default',
      code: `[acf_render field="gallery" default="No images available"]`,
      description: 'Render field with HTML or show default if empty'
    },
    {
      title: 'Render complete group',
      code: `[acf_group id="1"]`,
      description: 'Display all fields from group ID 1'
    },
    {
      title: 'Render group by slug',
      code: `[acf_group slug="product_info"]`,
      description: 'Display all fields from group by slug'
    },
    {
      title: 'Simple repeater loop',
      code: `[acf_repeater slug="features"]\n  <li>{row.title}: {row.description}</li>\n[/acf_repeater]`,
      description: 'Loop through repeater rows in CMS content'
    },
    {
      title: 'Repeater with conditional',
      code: `[acf_repeater slug="testimonials"]\n  {if row.rating >= 4}\n    <div class="good-review">\n      <strong>{row.name}</strong>: {row.comment}\n      <span class="stars">★★★★★</span>\n    </div>\n  {/if}\n[/acf_repeater]`,
      description: 'Repeater with conditional logic inside the loop'
    },
    {
      title: 'Field from specific product',
      code: `[acf field="brand" entity_type="product" entity_id="123"]`,
      description: 'Get field value from a specific product'
    },
    {
      title: 'Field from specific category',
      code: `[acf field="banner" entity_type="category" entity_id="5"]`,
      description: 'Get field value from a specific category'
    },
    {
      title: 'Field from specific CMS page',
      code: `[acf_render field="extra_content" entity_type="cms_page" entity_id="10"]`,
      description: 'Render field from a specific CMS page'
    },
    {
      title: 'Render repeater from another product',
      code: `[acf_repeater slug="specifications" entity_type="product" entity_id="456"]\n  <tr>\n    <td>{row.label}</td>\n    <td>{row.value}</td>\n  </tr>\n[/acf_repeater]`,
      description: 'Display repeater from a different entity'
    },
    {
      title: 'Multiple shortcodes in content',
      code: `<h2>Product Information</h2>\n[acf field="brand"] - [acf field="model"]\n\n<h3>Description</h3>\n[acf_render field="description"]\n\n<h3>Specifications</h3>\n<table>\n[acf_repeater slug="specs"]\n  <tr><td>{row.name}</td><td>{row.value}</td></tr>\n[/acf_repeater]\n</table>`,
      description: 'Combine multiple ACF shortcodes in rich content'
    },
    {
      title: 'FAQ section with repeater',
      code: `<div class="faq-section">\n  <h3>Frequently Asked Questions</h3>\n  [acf_repeater slug="faq"]\n    <details>\n      <summary>{row.question}</summary>\n      <div class="answer">{row.answer}</div>\n    </details>\n  [/acf_repeater]\n</div>`,
      description: 'Create FAQ section using repeater shortcode'
    }
  ]
  </script>
  
  <template>
    <transition name="slide">
      <div v-if="show" class="docs-panel">
        <div class="docs-header">
          <h3>
            <span class="material-icons">menu_book</span>
            Front-Office Documentation
          </h3>
          <button class="btn btn-link close-btn" @click="$emit('close')">
            <span class="material-icons">close</span>
          </button>
>>>>>>> 4f4b6e5 (fix: Corrected all duplicate IDs in French XLIFF translation file)
        </div>
  
        <div class="docs-tabs">
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'smarty' }"
            @click="activeTab = 'smarty'"
          >
            <span class="material-icons">code</span>
            Smarty
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'twig' }"
            @click="activeTab = 'twig'"
          >
            <span class="material-icons">code</span>
            Twig
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'shortcodes' }"
            @click="activeTab = 'shortcodes'"
          >
            <span class="material-icons">short_text</span>
            Shortcodes
          </button>
        </div>
<<<<<<< HEAD

        <!-- API Reference -->
        <div class="api-reference">
          <h4>
            <span class="material-icons">api</span>
            API Reference
          </h4>
          
          <div class="api-sections">
            <div class="api-section">
              <h5>📝 Field Methods</h5>
              <table class="api-table">
                <thead>
                  <tr>
                    <th>Method</th>
                    <th>Description</th>
                    <th>Use Case</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>field(slug, default)</code></td>
                    <td>Get escaped field value</td>
                    <td>Text, numbers, emails, URLs</td>
                  </tr>
                  <tr>
                    <td><code>raw(slug, default)</code></td>
                    <td>Get raw field value (not escaped)</td>
                    <td>Trusted HTML, arrays, objects</td>
                  </tr>
                  <tr>
                    <td><code>render(slug)</code></td>
                    <td>Render field as HTML</td>
                    <td>Rich text, images, videos, galleries</td>
                  </tr>
                  <tr>
                    <td><code>label(slug)</code></td>
                    <td>Get translated label (select/radio/checkbox)</td>
                    <td>User-friendly display of choice fields</td>
                  </tr>
                  <tr>
                    <td><code>has(slug)</code></td>
                    <td>Check if field has value</td>
                    <td>Conditional display</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="api-section">
              <h5>🔄 Repeater Methods</h5>
              <table class="api-table">
                <thead>
                  <tr>
                    <th>Method</th>
                    <th>Description</th>
                    <th>Returns</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>repeater(slug)</code></td>
                    <td>Get repeater rows (with label resolution)</td>
                    <td>Array of row objects</td>
                  </tr>
                  <tr>
                    <td><code>repeater(slug, false)</code></td>
                    <td>Get repeater rows (raw values)</td>
                    <td>Array of row objects</td>
                  </tr>
                  <tr>
                    <td><code>countRepeater(slug)</code></td>
                    <td>Count repeater rows</td>
                    <td>Integer</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="api-section">
              <h5>📁 Group Methods</h5>
              <table class="api-table">
                <thead>
                  <tr>
                    <th>Method</th>
                    <th>Description</th>
                    <th>Parameters</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>group(id)</code></td>
                    <td>Get all fields from group by ID</td>
                    <td>Integer group ID</td>
                  </tr>
                  <tr>
                    <td><code>group(slug)</code></td>
                    <td>Get all fields from group by slug</td>
                    <td>String group slug</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="api-section">
              <h5>🎯 Context Override Methods</h5>
              <table class="api-table">
                <thead>
                  <tr>
                    <th>Method</th>
                    <th>Description</th>
                    <th>Entity Types</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>forProduct(id)</code></td>
                    <td>Override context for product</td>
                    <td>Products</td>
                  </tr>
                  <tr>
                    <td><code>forCategory(id)</code></td>
                    <td>Override context for category</td>
                    <td>Categories</td>
                  </tr>
                  <tr>
                    <td><code>forCms(id)</code></td>
                    <td>Override context for CMS page</td>
                    <td>CMS pages</td>
                  </tr>
                  <tr>
                    <td><code>forEntity(type, id)</code></td>
                    <td>Override context for any entity</td>
                    <td>All entity types</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="api-section">
              <h5>🏷️ Shortcode Functions</h5>
              <table class="api-table">
                <thead>
                  <tr>
                    <th>Shortcode</th>
                    <th>Description</th>
                    <th>Attributes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>[acf field="slug"]</code></td>
                    <td>Display field value</td>
                    <td>field, default, entity_type, entity_id</td>
                  </tr>
                  <tr>
                    <td><code>[acf_render field="slug"]</code></td>
                    <td>Render field as HTML</td>
                    <td>field, default, entity_type, entity_id</td>
                  </tr>
                  <tr>
                    <td><code>[acf_group id="1"]</code></td>
                    <td>Render complete group</td>
                    <td>id or slug, entity_type, entity_id</td>
                  </tr>
                  <tr>
                    <td><code>[acf_repeater slug="name"]</code></td>
                    <td>Loop through repeater</td>
                    <td>slug, entity_type, entity_id</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="api-section">
              <h5>🔧 Twig Functions</h5>
              <table class="api-table">
                <thead>
                  <tr>
                    <th>Function</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>{{ acf_field(slug, default, entity_type, entity_id) }}</code></td>
                    <td>Get escaped field value</td>
                  </tr>
                  <tr>
                    <td><code>{{ acf_raw(slug, default) }}</code></td>
                    <td>Get raw field value</td>
                  </tr>
                  <tr>
                    <td><code>{{ acf_render(slug) }}</code></td>
                    <td>Render field as HTML</td>
                  </tr>
                  <tr>
                    <td><code>{{ acf_label(slug) }}</code></td>
                    <td>Get translated label</td>
                  </tr>
                  <tr>
                    <td>{{ acf_has(slug) }}</td>
                    <td>Check if field has value</td>
                  </tr>
                  <tr>
                    <td>{{ acf_repeater(slug) }}</td>
                    <td>Get repeater rows</td>
                  </tr>
                  <tr>
                    <td>{{ acf_count_repeater(slug) }}</td>
                    <td>Count repeater rows</td>
                  </tr>
                  <tr>
                    <td>{{ acf_group(slug) }}</td>
                    <td>Get group fields</td>
                  </tr>
                </tbody>
              </table>
=======
  
        <div class="docs-content">
          <!-- Smarty Tab -->
          <div v-if="activeTab === 'smarty'" class="docs-section">
            <div class="intro-box">
              <p>
                The <code>$acf</code> variable is automatically available in all front-office Smarty templates.
                Use it to display ACF field values with automatic context detection.
              </p>
            </div>
  
            <div 
              v-for="(example, index) in smartyExamples" 
              :key="index"
              class="example-card"
            >
              <div class="example-header">
                <h5>{{ example.title }}</h5>
                <button 
                  class="btn btn-sm copy-btn"
                  :class="{ copied: copiedIndex === index }"
                  @click="handleCopy(example.code, index)"
                >
                  <span class="material-icons">
                    {{ copiedIndex === index ? 'check' : 'content_copy' }}
                  </span>
                </button>
              </div>
              <pre class="example-code"><code>{{ example.code }}</code></pre>
              <p class="example-desc">{{ example.description }}</p>
            </div>
          </div>
  
          <!-- Twig Tab -->
          <div v-if="activeTab === 'twig'" class="docs-section">
            <div class="intro-box">
              <p>
                ACF provides Twig functions for use in back-office templates or custom Twig templates.
                All functions follow the pattern <code>acf_*</code>.
              </p>
            </div>
  
            <div 
              v-for="(example, index) in twigExamples" 
              :key="index"
              class="example-card"
            >
              <div class="example-header">
                <h5>{{ example.title }}</h5>
                <button 
                  class="btn btn-sm copy-btn"
                  :class="{ copied: copiedIndex === 100 + index }"
                  @click="handleCopy(example.code, 100 + index)"
                >
                  <span class="material-icons">
                    {{ copiedIndex === 100 + index ? 'check' : 'content_copy' }}
                  </span>
                </button>
              </div>
              <pre class="example-code"><code>{{ example.code }}</code></pre>
              <p class="example-desc">{{ example.description }}</p>
            </div>
          </div>
  
          <!-- Shortcodes Tab -->
          <div v-if="activeTab === 'shortcodes'" class="docs-section">
            <div class="intro-box">
              <p>
                Shortcodes can be used in CMS pages, product descriptions, and other WYSIWYG content.
                They are automatically parsed and replaced with field values.
              </p>
            </div>
  
            <div 
              v-for="(example, index) in shortcodeExamples" 
              :key="index"
              class="example-card"
            >
              <div class="example-header">
                <h5>{{ example.title }}</h5>
                <button 
                  class="btn btn-sm copy-btn"
                  :class="{ copied: copiedIndex === 200 + index }"
                  @click="handleCopy(example.code, 200 + index)"
                >
                  <span class="material-icons">
                    {{ copiedIndex === 200 + index ? 'check' : 'content_copy' }}
                  </span>
                </button>
              </div>
              <pre class="example-code"><code>{{ example.code }}</code></pre>
              <p class="example-desc">{{ example.description }}</p>
            </div>
          </div>
  
          <!-- API Reference -->
          <div class="api-reference">
            <h4>
              <span class="material-icons">api</span>
              API Reference
            </h4>
            
            <div class="api-sections">
              <div class="api-section">
                <h5>📝 Field Methods</h5>
                <table class="api-table">
                  <thead>
                    <tr>
                      <th>Method</th>
                      <th>Description</th>
                      <th>Use Case</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><code>field(slug, default)</code></td>
                      <td>Get escaped field value</td>
                      <td>Text, numbers, emails, URLs</td>
                    </tr>
                    <tr>
                      <td><code>raw(slug, default)</code></td>
                      <td>Get raw field value (not escaped)</td>
                      <td>Trusted HTML, arrays, objects</td>
                    </tr>
                    <tr>
                      <td><code>render(slug)</code></td>
                      <td>Render field as HTML</td>
                      <td>Rich text, images, videos, galleries</td>
                    </tr>
                    <tr>
                      <td><code>label(slug)</code></td>
                      <td>Get translated label (select/radio/checkbox)</td>
                      <td>User-friendly display of choice fields</td>
                    </tr>
                    <tr>
                      <td><code>has(slug)</code></td>
                      <td>Check if field has value</td>
                      <td>Conditional display</td>
                    </tr>
                  </tbody>
                </table>
              </div>
  
              <div class="api-section">
                <h5>🔄 Repeater Methods</h5>
                <table class="api-table">
                  <thead>
                    <tr>
                      <th>Method</th>
                      <th>Description</th>
                      <th>Returns</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><code>repeater(slug)</code></td>
                      <td>Get repeater rows (with label resolution)</td>
                      <td>Array of row objects</td>
                    </tr>
                    <tr>
                      <td><code>repeater(slug, false)</code></td>
                      <td>Get repeater rows (raw values)</td>
                      <td>Array of row objects</td>
                    </tr>
                    <tr>
                      <td><code>countRepeater(slug)</code></td>
                      <td>Count repeater rows</td>
                      <td>Integer</td>
                    </tr>
                  </tbody>
                </table>
              </div>
  
              <div class="api-section">
                <h5>📁 Group Methods</h5>
                <table class="api-table">
                  <thead>
                    <tr>
                      <th>Method</th>
                      <th>Description</th>
                      <th>Parameters</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><code>group(id)</code></td>
                      <td>Get all fields from group by ID</td>
                      <td>Integer group ID</td>
                    </tr>
                    <tr>
                      <td><code>group(slug)</code></td>
                      <td>Get all fields from group by slug</td>
                      <td>String group slug</td>
                    </tr>
                  </tbody>
                </table>
              </div>
  
              <div class="api-section">
                <h5>🎯 Context Override Methods</h5>
                <table class="api-table">
                  <thead>
                    <tr>
                      <th>Method</th>
                      <th>Description</th>
                      <th>Entity Types</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><code>forProduct(id)</code></td>
                      <td>Override context for product</td>
                      <td>Products</td>
                    </tr>
                    <tr>
                      <td><code>forCategory(id)</code></td>
                      <td>Override context for category</td>
                      <td>Categories</td>
                    </tr>
                    <tr>
                      <td><code>forCms(id)</code></td>
                      <td>Override context for CMS page</td>
                      <td>CMS pages</td>
                    </tr>
                    <tr>
                      <td><code>forEntity(type, id)</code></td>
                      <td>Override context for any entity</td>
                      <td>All entity types</td>
                    </tr>
                  </tbody>
                </table>
              </div>
  
              <div class="api-section">
                <h5>🏷️ Shortcode Functions</h5>
                <table class="api-table">
                  <thead>
                    <tr>
                      <th>Shortcode</th>
                      <th>Description</th>
                      <th>Attributes</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><code>[acf field="slug"]</code></td>
                      <td>Display field value</td>
                      <td>field, default, entity_type, entity_id</td>
                    </tr>
                    <tr>
                      <td><code>[acf_render field="slug"]</code></td>
                      <td>Render field as HTML</td>
                      <td>field, default, entity_type, entity_id</td>
                    </tr>
                    <tr>
                      <td><code>[acf_group id="1"]</code></td>
                      <td>Render complete group</td>
                      <td>id or slug, entity_type, entity_id</td>
                    </tr>
                    <tr>
                      <td><code>[acf_repeater slug="name"]</code></td>
                      <td>Loop through repeater</td>
                      <td>slug, entity_type, entity_id</td>
                    </tr>
                  </tbody>
                </table>
              </div>
  
              <div class="api-section">
                <h5>🔧 Twig Functions</h5>
                <table class="api-table">
                  <thead>
                    <tr>
                      <th>Function</th>
                      <th>Description</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><code>acf_field(slug, default, entity_type, entity_id)</code></td>
                      <td>Get escaped field value</td>
                    </tr>
                    <tr>
                      <td><code>acf_raw(slug, default)</code></td>
                      <td>Get raw field value</td>
                    </tr>
                    <tr>
                      <td><code>acf_render(slug)</code></td>
                      <td>Render field as HTML</td>
                    </tr>
                    <tr>
                      <td><code>acf_label(slug)</code></td>
                      <td>Get translated label</td>
                    </tr>
                    <tr>
                      <td>acf_has(slug)</td>
                      <td>Check if field has value</td>
                    </tr>
                    <tr>
                      <td>acf_repeater(slug)</td>
                      <td>Get repeater rows</td>
                    </tr>
                    <tr>
                      <td>acf_count_repeater(slug)</td>
                      <td>Count repeater rows</td>
                    </tr>
                    <tr>
                      <td>acf_group(slug)</td>
                      <td>Get group fields</td>
                    </tr>
                  </tbody>
                </table>
              </div>
>>>>>>> 4f4b6e5 (fix: Corrected all duplicate IDs in French XLIFF translation file)
            </div>
          </div>
        </div>
      </div>
<<<<<<< HEAD
    </div>
  </transition>
</template>

<style scoped>
.docs-panel {
  position: fixed;
  top: 0;
  right: 0;
  width: 500px;
  max-width: 90vw;
  height: 100vh;
  background: white;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
  z-index: 1050;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.docs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.docs-header h3 {
  margin: 0;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.close-btn {
  color: white;
  padding: 0.25rem;
}

.close-btn:hover {
  color: rgba(255, 255, 255, 0.8);
}

.docs-tabs {
  display: flex;
  border-bottom: 1px solid #e9ecef;
  background: #f8f9fa;
}

.tab-btn {
  flex: 1;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  border-bottom: 2px solid transparent;
}

.tab-btn:hover {
  color: #495057;
  background: #e9ecef;
}

.tab-btn.active {
  color: #6366f1;
  border-bottom-color: #6366f1;
  background: white;
}

.tab-btn .material-icons {
  font-size: 18px;
}

.docs-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.docs-section {
  margin-bottom: 2rem;
}

.intro-box {
  background: #f0f9ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.intro-box p {
  margin: 0;
  color: #1e40af;
  font-size: 0.875rem;
  line-height: 1.5;
}

.intro-box code {
  background: #dbeafe;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
}

.example-card {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  margin-bottom: 1rem;
  overflow: hidden;
}

.example-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: #fff;
  border-bottom: 1px solid #e9ecef;
}

.example-header h5 {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.copy-btn {
  padding: 0.25rem 0.5rem;
  background: #e9ecef;
  border: none;
  border-radius: 4px;
  color: #6c757d;
  transition: all 0.2s;
}

.copy-btn:hover {
  background: #dee2e6;
  color: #495057;
}

.copy-btn.copied {
  background: #d1fae5;
  color: #059669;
}

.copy-btn .material-icons {
  font-size: 16px;
}

.example-code {
  margin: 0;
  padding: 1rem;
  background: #1e293b;
  color: #e2e8f0;
  font-size: 0.8rem;
  line-height: 1.5;
  overflow-x: auto;
}

.example-code code {
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', monospace;
}

.example-desc {
  margin: 0;
  padding: 0.75rem 1rem;
  font-size: 0.8rem;
  color: #6c757d;
  background: white;
}

.api-reference {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.api-reference h4 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 1rem;
  color: #374151;
}

.api-sections {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.api-section h5 {
  margin: 0 0 0.75rem 0;
  font-size: 0.9rem;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.api-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.75rem;
  margin-bottom: 1rem;
}

.api-table th,
.api-table td {
  padding: 0.5rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
  vertical-align: top;
}

.api-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #374151;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.api-table code {
  background: #f3f4f6;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.7rem;
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', monospace;
  word-break: break-all;
}

.api-table td:nth-child(3) {
  font-size: 0.7rem;
  color: #6b7280;
}

/* Slide transition */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}
</style>
=======
    </transition>
  </template>
  
  <style scoped>
  .docs-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 500px;
    max-width: 90vw;
    height: 100vh;
    background: white;
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
    z-index: 1050;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  
  .docs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
  }
  
  .docs-header h3 {
    margin: 0;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .close-btn {
    color: white;
    padding: 0.25rem;
  }
  
  .close-btn:hover {
    color: rgba(255, 255, 255, 0.8);
  }
  
  .docs-tabs {
    display: flex;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
  }
  
  .tab-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border: none;
    background: none;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 2px solid transparent;
  }
  
  .tab-btn:hover {
    color: #495057;
    background: #e9ecef;
  }
  
  .tab-btn.active {
    color: #6366f1;
    border-bottom-color: #6366f1;
    background: white;
  }
  
  .tab-btn .material-icons {
    font-size: 18px;
  }
  
  .docs-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
  }
  
  .docs-section {
    margin-bottom: 2rem;
  }
  
  .intro-box {
    background: #f0f9ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
  }
  
  .intro-box p {
    margin: 0;
    color: #1e40af;
    font-size: 0.875rem;
    line-height: 1.5;
  }
  
  .intro-box code {
    background: #dbeafe;
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
  }
  
  .example-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
  }
  
  .example-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #fff;
    border-bottom: 1px solid #e9ecef;
  }
  
  .example-header h5 {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
  }
  
  .copy-btn {
    padding: 0.25rem 0.5rem;
    background: #e9ecef;
    border: none;
    border-radius: 4px;
    color: #6c757d;
    transition: all 0.2s;
  }
  
  .copy-btn:hover {
    background: #dee2e6;
    color: #495057;
  }
  
  .copy-btn.copied {
    background: #d1fae5;
    color: #059669;
  }
  
  .copy-btn .material-icons {
    font-size: 16px;
  }
  
  .example-code {
    margin: 0;
    padding: 1rem;
    background: #1e293b;
    color: #e2e8f0;
    font-size: 0.8rem;
    line-height: 1.5;
    overflow-x: auto;
  }
  
  .example-code code {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', monospace;
  }
  
  .example-desc {
    margin: 0;
    padding: 0.75rem 1rem;
    font-size: 0.8rem;
    color: #6c757d;
    background: white;
  }
  
  .api-reference {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
  }
  
  .api-reference h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 1rem;
    color: #374151;
  }
  
  .api-sections {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .api-section h5 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .api-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.75rem;
    margin-bottom: 1rem;
  }
  
  .api-table th,
  .api-table td {
    padding: 0.5rem 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
    vertical-align: top;
  }
  
  .api-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #374151;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  
  .api-table code {
    background: #f3f4f6;
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', monospace;
    word-break: break-all;
  }
  
  .api-table td:nth-child(3) {
    font-size: 0.7rem;
    color: #6b7280;
  }
  
  /* Slide transition */
  .slide-enter-active,
  .slide-leave-active {
    transition: transform 0.3s ease;
  }
  
  .slide-enter-from,
  .slide-leave-to {
    transform: translateX(100%);
  }
  </style>
>>>>>>> 4f4b6e5 (fix: Corrected all duplicate IDs in French XLIFF translation file)
