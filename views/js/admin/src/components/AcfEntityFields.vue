<script setup lang="ts">
/**
 * AcfEntityFields.vue
 * 
 * Unified Vue.js component for rendering ACF fields on any entity type.
 * Used for both CPT posts and native PrestaShop entities (products, categories, etc.).
 * 
 * Data is injected via props from the mounting point.
 */
import { ref, reactive, computed, watch, onMounted } from 'vue'
import AcfFieldRenderer from './AcfFieldRenderer.vue'
import type { AcfField } from '@/types'

interface AcfGroup {
  id: number
  title: string
  slug?: string
  description?: string
  fields: AcfField[]
}

interface Language {
  id_lang: number
  iso_code: string
  name?: string
  is_default?: boolean
}

const props = defineProps<{
  /**
   * ACF groups to display with their fields
   */
  groups: AcfGroup[]
  
  /**
   * Initial field values keyed by field ID
   */
  initialValues?: Record<number | string, any>
  
  /**
   * Entity type (product, category, cpt_post, etc.)
   */
  entityType: string
  
  /**
   * Entity ID (product ID, category ID, etc.)
   */
  entityId: number
  
  /**
   * Available languages for multilingual fields
   */
  languages?: Language[]
  
  /**
   * Default language
   */
  defaultLanguage?: Language
  
  /**
   * API URL for saving values
   */
  apiUrl?: string
  
  /**
   * Shop ID for saving values
   */
  shopId?: number
  
  /**
   * CSRF token for API calls
   */
  csrfToken?: string
  
  /**
   * Whether to auto-save on value change (debounced)
   */
  autoSave?: boolean
  
  /**
   * Form name prefix for form field names
   * Used when values need to be submitted with the parent form
   */
  formNamePrefix?: string
}>()

const emit = defineEmits<{
  'update:values': [values: Record<number | string, any>]
  'save:success': [response: any]
  'save:error': [error: Error]
}>()

// Reactive values object
const values = reactive<Record<number | string, any>>({})

// Saving state
const saving = ref(false)
const saveError = ref<string | null>(null)
const lastSaved = ref<Date | null>(null)

// Debounce timer for auto-save
let saveDebounceTimer: ReturnType<typeof setTimeout> | null = null

// Initialize values from props
onMounted(() => {
  if (props.initialValues) {
    Object.assign(values, props.initialValues)
  }
})

// Watch for external value changes
watch(() => props.initialValues, (newValues) => {
  if (newValues) {
    Object.assign(values, newValues)
  }
}, { deep: true })

/**
 * Get value for a field
 */
function getFieldValue(fieldId: number | string): any {
  return values[fieldId] ?? null
}

/**
 * Set value for a field
 */
function setFieldValue(fieldId: number | string, value: any): void {
  values[fieldId] = value
  emit('update:values', { ...values })
  
  // Trigger auto-save if enabled
  if (props.autoSave) {
    debouncedSave()
  }
}

/**
 * Debounced save function
 */
function debouncedSave(): void {
  if (saveDebounceTimer) {
    clearTimeout(saveDebounceTimer)
  }
  
  saveDebounceTimer = setTimeout(() => {
    saveValues()
  }, 1000)
}

/**
 * Save values to the API
 */
async function saveValues(): Promise<void> {
  if (!props.apiUrl || saving.value) {
    return
  }
  
  saving.value = true
  saveError.value = null
  
  try {
    const response = await fetch(props.apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(props.csrfToken ? { 'X-CSRF-Token': props.csrfToken } : {})
      },
      body: JSON.stringify({
        entity_type: props.entityType,
        entity_id: props.entityId,
        shop_id: props.shopId,
        values: values
      })
    })
    
    if (!response.ok) {
      throw new Error(`Save failed: ${response.statusText}`)
    }
    
    const data = await response.json()
    lastSaved.value = new Date()
    emit('save:success', data)
  } catch (error) {
    saveError.value = error instanceof Error ? error.message : 'Unknown error'
    emit('save:error', error instanceof Error ? error : new Error('Unknown error'))
  } finally {
    saving.value = false
  }
}

/**
 * Get form field name for a field (for form submission)
 */
function getFormFieldName(fieldId: number | string): string {
  const prefix = props.formNamePrefix || 'acf'
  return `${prefix}[${fieldId}]`
}

/**
 * Check if groups have any fields
 */
const hasFields = computed(() => {
  return props.groups.some(group => group.fields && group.fields.length > 0)
})

// Expose methods for parent components
defineExpose({
  values,
  saveValues,
  getFieldValue,
  setFieldValue
})
</script>

<template>
  <div class="acf-entity-fields" :data-entity-type="entityType" :data-entity-id="entityId">
    <!-- No fields message -->
    <div v-if="!hasFields" class="alert alert-info">
      <i class="material-icons">info</i>
      No custom fields configured for this entity type.
    </div>
    
    <!-- Groups -->
    <div 
      v-for="group in groups" 
      :key="group.id" 
      class="acf-group card mb-3"
      :data-group-id="group.id"
    >
      <div class="card-header bg-light">
        <h4 class="mb-0">
          <i class="material-icons mr-2" style="vertical-align: middle; font-size: 20px;">layers</i>
          {{ group.title || 'Untitled Group' }}
        </h4>
        <p v-if="group.description" class="text-muted mb-0 mt-1 small">
          {{ group.description }}
        </p>
      </div>
      
      <div class="card-body">
        <div v-if="!group.fields || group.fields.length === 0" class="text-muted">
          No fields in this group.
        </div>
        
        <!-- Fields -->
        <div v-else>
          <div 
            v-for="field in group.fields" 
            :key="field.id" 
            class="acf-field-container mb-3"
          >
            <AcfFieldRenderer
              :field="field"
              :model-value="getFieldValue(field.id)"
              :languages="languages"
              :default-language="defaultLanguage"
              @update:model-value="setFieldValue(field.id, $event)"
            />
            
            <!-- Hidden input for form submission (if formNamePrefix is set) -->
            <input 
              v-if="formNamePrefix"
              type="hidden"
              :name="getFormFieldName(field.id)"
              :value="JSON.stringify(getFieldValue(field.id))"
            />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Save status indicator (if autoSave is enabled) -->
    <div v-if="autoSave" class="acf-save-status mt-2">
      <span v-if="saving" class="text-info">
        <i class="material-icons spinning">sync</i> Saving...
      </span>
      <span v-else-if="saveError" class="text-danger">
        <i class="material-icons">error</i> {{ saveError }}
      </span>
      <span v-else-if="lastSaved" class="text-success">
        <i class="material-icons">check_circle</i> 
        Saved {{ lastSaved.toLocaleTimeString() }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.acf-entity-fields {
  /* Inherit PrestaShop admin styles */
}

.acf-group.card {
  border: 1px solid #dee2e6;
  border-radius: 4px;
}

.acf-group .card-header {
  border-bottom: 1px solid #dee2e6;
  padding: 0.75rem 1rem;
  background-color: #f8f9fa;
}

.acf-group .card-header h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #363a41;
}

.acf-group .card-body {
  padding: 1rem;
}

.acf-field-container {
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #f0f0f0;
}

.acf-field-container:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.acf-save-status {
  font-size: 0.875rem;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 4px;
}

.acf-save-status .material-icons {
  font-size: 16px;
  vertical-align: middle;
  margin-right: 4px;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
