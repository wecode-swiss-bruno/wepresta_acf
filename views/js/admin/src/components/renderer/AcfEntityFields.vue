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
import SaveButton from '../common/SaveButton.vue'
import type { AcfField } from '@/types'
import { useApi, ApiError } from '@/composables/useApi'

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

  /**
   * Whether to hide the save toolbar and status indicators
   */
  hideToolbar?: boolean
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
const saveError = ref<string | string[] | null>(null)

// ...

const lastSaved = ref<Date | null>(null)

const api = useApi()

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
  if (saving.value) {
    return
  }
  
  saving.value = true
  saveError.value = null
  
  try {
    const data = await api.saveEntityValues({
      entityType: props.entityType,
      entityId: props.entityId,
      shopId: props.shopId,
      langId: props.defaultLanguage?.id_lang,
      values: values
    })
    
    lastSaved.value = new Date()
    emit('save:success', data)
  } catch (error) {
    if (error instanceof ApiError && error.errors) {
       // Create lookups for fields and languages
       const fieldMap = new Map<number | string, string>()
       
       const processFields = (fields: AcfField[]) => {
         fields.forEach(f => {
           if (f.id) fieldMap.set(f.id, f.title || f.config?.label || `Field #${f.id}`)
           if (f.children) processFields(f.children)
         })
       }
       props.groups.forEach(g => processFields(g.fields))
       
       const langMap = new Map<number | string, string>()
       if (props.languages) {
         props.languages.forEach(l => langMap.set(l.id_lang, l.name || l.iso_code))
       }

       // Smart error flattening
       const messages: string[] = []
       
       Object.entries(error.errors).forEach(([fieldId, langErrors]) => {
         const fieldName = fieldMap.get(Number(fieldId)) || fieldMap.get(fieldId) || `Field ${fieldId}`
         
         if (typeof langErrors === 'object' && langErrors !== null && !Array.isArray(langErrors)) {
            // It's keyed by language ID
            Object.entries(langErrors).forEach(([langId, msgs]) => {
                const langName = langMap.get(Number(langId)) || langMap.get(langId) || langId
                const msgList = Array.isArray(msgs) ? msgs : [msgs]
                
                msgList.forEach((msg: any) => {
                    messages.push(`<strong>${fieldName}</strong> (${langName}): ${msg}`)
                })
            })
         } else if (Array.isArray(langErrors)) {
             // Direct array of messages for the field (non-translatable?)
             langErrors.forEach((msg: any) => {
                 messages.push(`<strong>${fieldName}</strong>: ${msg}`)
             })
         } else {
             // Fallback
             messages.push(`<strong>${fieldName}</strong>: ${String(langErrors)}`)
         }
       })
       
       saveError.value = messages.length > 0 ? messages : ['Validation failed']
    } else {
       saveError.value = error instanceof Error ? error.message : 'Unknown error'
    }
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
    
    <template v-else>
      
      <!-- Premium Save Toolbar at Top - FORCED VISIBLE -->
      <SaveButton
        :saving="saving"
        :last-saved="lastSaved"
        :error="saveError"
        :disabled="!apiUrl"
        @save="saveValues"
      />
      
      <!-- Save status indicator (if autoSave is enabled) -->
      <div v-if="autoSave" class="acf-save-status mb-3">
        <span v-if="saving" class="text-info">
          <i class="material-icons spinning">sync</i> Saving...
        </span>
        <span v-else-if="saveError" class="text-danger">
          <i class="material-icons">error</i> {{ saveError }}
        </span>
        <span v-else-if="lastSaved" class="text-success">
          <i class="material-icons">check_circle</i> 
          Last saved {{ lastSaved.toLocaleTimeString() }}
        </span>
      </div>

      <!-- Groups -->
      <div 
        v-for="group in groups" 
      :key="group.id" 
      class="acf-group card mb-3"
      :data-group-id="group.id"
    >
      <div class="card-header">
        <h4>
          <i class="material-icons acf-mr-2">layers</i>
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
              :model-value="getFieldValue(field.id!)"
              :languages="languages"
              :default-language="defaultLanguage"
              @update:model-value="setFieldValue(field.id!, $event)"
            />
            
            <!-- Hidden input for form submission (if formNamePrefix is set) -->
            <input 
              v-if="formNamePrefix"
              type="hidden"
              :name="getFormFieldName(field.id!)"
              :value="JSON.stringify(getFieldValue(field.id!))"
            />
          </div>
        </div>
      </div>
    </div>
    
    </template>
  </div>
</template>

<style scoped>
.acf-entity-fields {
  /* Design system applied via acf-admin.css */
}
</style>
