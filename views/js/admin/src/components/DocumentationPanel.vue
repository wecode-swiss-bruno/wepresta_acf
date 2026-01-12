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

const smartyExamples = [
  {
    title: 'Get field value (escaped)',
    code: `{$acf->field('brand')}`,
    description: 'Returns the field value with XSS protection'
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
    title: 'Check if field has value',
    code: `{if $acf->has('promo_badge')}\n  <span class="badge">{$acf->field('promo_badge')}</span>\n{/if}`,
    description: 'Conditional display based on field value'
  },
  {
    title: 'Loop through repeater',
    code: `{foreach $acf->repeater('specifications') as $row}\n  <tr>\n    <td>{$row.label}</td>\n    <td>{$row.value}</td>\n  </tr>\n{/foreach}`,
    description: 'Iterate over repeater field rows'
  },
  {
    title: 'Display all group fields',
    code: `{foreach $acf->group('product_info') as $field}\n  {if $field.has_value}\n    <div class="field">\n      <label>{$field.title}</label>\n      {$field.rendered nofilter}\n    </div>\n  {/if}\n{/foreach}`,
    description: 'Render all fields from a group'
  },
  {
    title: 'Override context (other product)',
    code: `{$acf->forProduct(123)->field('brand')}`,
    description: 'Get field from a specific product'
  },
  {
    title: 'Using Smarty function',
    code: `{acf_field slug="brand" default="N/A"}`,
    description: 'Alternative syntax using Smarty function'
  }
]

const twigExamples = [
  {
    title: 'Get field value (escaped)',
    code: `{{ acf_field('brand') }}`,
    description: 'Returns the field value with XSS protection'
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
    title: 'Check if field has value',
    code: `{% if acf_has('promo_badge') %}\n  <span class="badge">{{ acf_field('promo_badge') }}</span>\n{% endif %}`,
    description: 'Conditional display'
  },
  {
    title: 'Loop through repeater',
    code: `{% for row in acf_repeater('specifications') %}\n  <tr>\n    <td>{{ row.label }}</td>\n    <td>{{ row.value }}</td>\n  </tr>\n{% endfor %}`,
    description: 'Iterate over repeater rows'
  },
  {
    title: 'Display all group fields',
    code: `{% for field in acf_group('product_info') %}\n  {% if field.has_value %}\n    <div class="field">\n      <label>{{ field.title }}</label>\n      {{ field.rendered|raw }}\n    </div>\n  {% endif %}\n{% endfor %}`,
    description: 'Render all fields from a group'
  },
  {
    title: 'Override context',
    code: `{{ acf_field('brand', '', 'product', 123) }}`,
    description: 'Get field from specific entity'
  }
]

const shortcodeExamples = [
  {
    title: 'Basic field value',
    code: `[acf field="brand"]`,
    description: 'Display field value in CMS content'
  },
  {
    title: 'With default value',
    code: `[acf field="brand" default="Not specified"]`,
    description: 'Show default if field is empty'
  },
  {
    title: 'Render as HTML',
    code: `[acf_render field="product_image"]`,
    description: 'Render field with HTML formatting'
  },
  {
    title: 'Render a group',
    code: `[acf_group id="1"]`,
    description: 'Display all fields from group ID 1'
  },
  {
    title: 'Repeater loop',
    code: `[acf_repeater slug="features"]\n  <li>{row.title}: {row.description}</li>\n[/acf_repeater]`,
    description: 'Loop through repeater in CMS content'
  },
  {
    title: 'Specific entity',
    code: `[acf field="brand" entity_type="product" entity_id="123"]`,
    description: 'Get field from specific entity'
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
          
          <table class="api-table">
            <thead>
              <tr>
                <th>Method</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>field(slug, default)</code></td>
                <td>Get escaped field value</td>
              </tr>
              <tr>
                <td><code>raw(slug, default)</code></td>
                <td>Get raw field value (not escaped)</td>
              </tr>
              <tr>
                <td><code>render(slug, options)</code></td>
                <td>Render field as HTML</td>
              </tr>
              <tr>
                <td><code>has(slug)</code></td>
                <td>Check if field has value</td>
              </tr>
              <tr>
                <td><code>repeater(slug)</code></td>
                <td>Get repeater rows as iterable</td>
              </tr>
              <tr>
                <td><code>group(id|slug)</code></td>
                <td>Get all fields from a group</td>
              </tr>
              <tr>
                <td><code>forProduct(id)</code></td>
                <td>Override context for product</td>
              </tr>
              <tr>
                <td><code>forCategory(id)</code></td>
                <td>Override context for category</td>
              </tr>
              <tr>
                <td><code>forEntity(type, id)</code></td>
                <td>Override context for any entity</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
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

.api-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.api-table th,
.api-table td {
  padding: 0.5rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.api-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #374151;
}

.api-table code {
  background: #f3f4f6;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.75rem;
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
