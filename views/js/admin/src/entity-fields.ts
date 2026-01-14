/**
 * entity-fields.ts
 * 
 * Entry point for mounting ACF Entity Fields Vue.js application
 * on PrestaShop entity edit pages (products, categories, etc.).
 * 
 * This script looks for containers with the class 'acf-entity-fields-vue-container'
 * and mounts an AcfEntityFields Vue.js app on each one.
 * 
 * The container must have data attributes:
 * - data-groups: JSON array of ACF groups with their fields
 * - data-values: JSON object of field values keyed by field ID
 * - data-entity-type: Entity type (product, category, etc.)
 * - data-entity-id: Entity ID
 * - data-api-url: API URL for saving values
 * - data-languages: JSON array of available languages
 * - data-default-lang-id: Default language ID
 * - data-shop-id: Shop ID
 * - data-form-name-prefix: Form field name prefix (optional)
 */

import { createApp, h } from 'vue'
import AcfEntityFields from '@/components/AcfEntityFields.vue'

interface MountConfig {
  groups: any[]
  values: Record<string, any>
  entityType: string
  entityId: number
  apiUrl?: string
  languages?: any[]
  defaultLangId?: number
  shopId?: number
  formNamePrefix?: string
}

/**
 * Parse JSON from data attribute safely
 */
function parseJson(jsonString: string | null, defaultValue: any = null): any {
  if (!jsonString) return defaultValue
  try {
    return JSON.parse(jsonString)
  } catch (e) {
    console.error('ACF: Failed to parse JSON:', e)
    return defaultValue
  }
}

/**
 * Mount ACF Entity Fields app on a container
 */
function mountAcfApp(container: HTMLElement): void {
  const config: MountConfig = {
    groups: parseJson(container.dataset.groups, []),
    values: parseJson(container.dataset.values, {}),
    entityType: container.dataset.entityType || 'unknown',
    entityId: parseInt(container.dataset.entityId || '0', 10),
    apiUrl: container.dataset.apiUrl,
    languages: parseJson(container.dataset.languages, []),
    defaultLangId: parseInt(container.dataset.defaultLangId || '1', 10),
    shopId: parseInt(container.dataset.shopId || '1', 10),
    formNamePrefix: container.dataset.formNamePrefix || 'acf'
  }

  // Find default language
  const defaultLanguage = config.languages?.find(
    (lang: any) => lang.id_lang === config.defaultLangId || lang.is_default
  ) || config.languages?.[0]

  // Create Vue app
  const app = createApp({
    name: 'AcfEntityFieldsApp',
    setup() {
      return () => h(AcfEntityFields, {
        groups: config.groups,
        initialValues: config.values,
        entityType: config.entityType,
        entityId: config.entityId,
        apiUrl: config.apiUrl,
        languages: config.languages,
        defaultLanguage: defaultLanguage,
        shopId: config.shopId,
        formNamePrefix: config.formNamePrefix,
        autoSave: false, // Manual save via form submission
        'onUpdate:values': (values: any) => {
          // Update hidden inputs when values change
          updateHiddenInputs(container, values, config.formNamePrefix || 'acf')
        }
      })
    }
  })

  // Mount the app
  app.mount(container)
  
  // Mark container as initialized
  container.classList.add('acf-vue-initialized')
  
  console.log(`ACF: Mounted entity fields app for ${config.entityType} #${config.entityId}`)
}

/**
 * Update hidden inputs for form submission
 */
function updateHiddenInputs(
  container: HTMLElement, 
  values: Record<string, any>, 
  prefix: string
): void {
  // Get or create hidden inputs container
  let hiddenContainer = container.querySelector('.acf-hidden-inputs')
  
  if (!hiddenContainer) {
    hiddenContainer = document.createElement('div')
    hiddenContainer.className = 'acf-hidden-inputs'
    hiddenContainer.style.display = 'none'
    container.appendChild(hiddenContainer)
  }
  
  // Clear existing hidden inputs
  hiddenContainer.innerHTML = ''
  
  // Create hidden inputs for each field value
  for (const [fieldId, value] of Object.entries(values)) {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = `${prefix}[${fieldId}]`
    input.value = typeof value === 'object' ? JSON.stringify(value) : String(value ?? '')
    hiddenContainer.appendChild(input)
  }
}

/**
 * Initialize ACF Entity Fields on page load
 */
function initAcfEntityFields(): void {
  const containers = document.querySelectorAll<HTMLElement>(
    '.acf-entity-fields-vue-container:not(.acf-vue-initialized)'
  )
  
  if (containers.length === 0) {
    return
  }
  
  console.log(`ACF: Found ${containers.length} entity fields container(s)`)
  
  containers.forEach(container => {
    try {
      mountAcfApp(container)
    } catch (error) {
      console.error('ACF: Failed to mount app:', error)
    }
  })
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAcfEntityFields)
} else {
  initAcfEntityFields()
}

// Also observe for dynamically added containers (AJAX loaded forms)
const observer = new MutationObserver((mutations) => {
  for (const mutation of mutations) {
    if (mutation.type === 'childList') {
      mutation.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) {
          // Check if the added node is a container
          if (node.classList.contains('acf-entity-fields-vue-container')) {
            if (!node.classList.contains('acf-vue-initialized')) {
              mountAcfApp(node)
            }
          }
          // Check for containers within added node
          const containers = node.querySelectorAll<HTMLElement>(
            '.acf-entity-fields-vue-container:not(.acf-vue-initialized)'
          )
          containers.forEach(container => {
            try {
              mountAcfApp(container)
            } catch (error) {
              console.error('ACF: Failed to mount app on dynamic container:', error)
            }
          })
        }
      })
    }
  }
})

observer.observe(document.body, {
  childList: true,
  subtree: true
})

// Expose for manual initialization if needed
;(window as any).initAcfEntityFields = initAcfEntityFields
;(window as any).mountAcfApp = mountAcfApp
