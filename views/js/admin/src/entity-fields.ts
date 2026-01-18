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
import { createPinia } from 'pinia'
import AcfEntityFields from '@/components/renderer/AcfEntityFields.vue'

interface MountConfig {
  groups: any[]
  values: Record<string, any>
  entityType: string
  entityId: number
  apiUrl?: string
  languages?: any[]
  defaultLangId?: number
  currentLangId?: number
  shopId?: number
  formNamePrefix?: string
  token?: string
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
    groups: parseJson(container.dataset.groups || null, []),
    values: parseJson(container.dataset.values || null, {}),
    entityType: container.dataset.entityType || 'unknown',
    entityId: parseInt(container.dataset.entityId || '0', 10),
    apiUrl: container.dataset.apiUrl,
    languages: parseJson(container.dataset.languages || null, []),
    defaultLangId: parseInt(container.dataset.defaultLangId || '1', 10),
    currentLangId: parseInt(container.dataset.currentLangId || container.dataset.defaultLangId || '1', 10),
    shopId: parseInt(container.dataset.shopId || '1', 10),
    formNamePrefix: container.dataset.formNamePrefix || 'acf',
    token: container.dataset.token
  }

  // Initialize global config for services (like useApi)
  if (!window.acfConfig) {
    (window as any).acfConfig = {
      apiUrl: config.apiUrl || '',
      token: config.token || '',
      entityId: config.entityId,
      languages: config.languages || [],
      defaultLangId: String(config.defaultLangId),
      currentLangId: String(config.currentLangId || config.defaultLangId),
      shopId: config.shopId || 1,
      translations: {}, // Will be populated by components if needed
      fieldTypes: [],
      locations: {},
      productTabs: [],
      layoutOptions: { widths: [], positions: [] }
    }
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

  // Install Pinia
  const pinia = createPinia()
  app.use(pinia)

  // Mount the app
  app.mount(container)

  // Mark container as initialized
  container.classList.add('acf-vue-initialized')

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
  let hiddenContainer = container.querySelector('.acf-hidden-inputs') as HTMLElement

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
function _mountAllContainers(): void {
  const containers = document.querySelectorAll<HTMLElement>(
    '.acf-entity-fields-vue-container:not(.acf-vue-initialized)'
  )

  if (containers.length === 0) {
    return
  }


  containers.forEach(container => {
    try {
      mountAcfApp(container)
    } catch (error) {
      console.error('ACF: Failed to mount app:', error)
    }
  })
}

/**
 * Main init function: unpacks hidden containers then mounts apps
 */
function initAcfEntityFields(): void {
  unpackAcfContainers()
  _mountAllContainers()
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAcfEntityFields)
} else {
  initAcfEntityFields()
}

/**
 * Unpack hidden ACF containers (Symfony Forms)
 * 
 * Looks for .acf-container-data elements, decodes their base64 content,
 * and injects the resulting HTML into the DOM.
 */
function unpackAcfContainers(root: HTMLElement | Document = document): void {
  const hiddenContainers = root.querySelectorAll<HTMLElement>('.acf-container-data')

  if (hiddenContainers.length === 0) {
    return
  }


  hiddenContainers.forEach(container => {
    try {
      // 1. Get base64 content
      const base64Content = container.dataset.acfHtml
      if (!base64Content) {
        return
      }

      // 2. Decode content
      const decodedHtml = atob(base64Content)

      // 3. Create wrapper
      const wrapper = document.createElement('div')
      wrapper.innerHTML = decodedHtml

      // 4. Find the actual Vue container within the decoded HTML
      const vueContainer = wrapper.querySelector('.acf-entity-fields-vue-container')

      if (vueContainer) {
        // 5. Inject after the hidden input (or replace it if you prefer, but keeping it is safer for form submission context)
        container.insertAdjacentElement('afterend', vueContainer)

        // 6. Mark original as processed to avoid double unpacking
        container.classList.remove('acf-container-data')
        container.classList.add('acf-container-data-processed')

      }
    } catch (e) {
      console.error('ACF: Failed to unpack container:', e)
    }
  })
}

// Update observer to also unpack
const observer = new MutationObserver((mutations) => {
  let shouldUnpack = false

  for (const mutation of mutations) {
    if (mutation.type === 'childList') {
      mutation.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) {
          if (node.classList.contains('acf-container-data') || node.querySelector('.acf-container-data')) {
            shouldUnpack = true
          }

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

  if (shouldUnpack) {
    unpackAcfContainers()
    // After unpacking, we need to init the new containers
    _mountAllContainers()
  }
})

observer.observe(document.body, {
  childList: true,
  subtree: true
})

  // Expose for manual initialization if needed
  ; (window as any).initAcfEntityFields = initAcfEntityFields
  ; (window as any).mountAcfApp = mountAcfApp
