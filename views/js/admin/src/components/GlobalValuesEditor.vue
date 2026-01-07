<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { useApi } from '@/composables/useApi'
import type { AcfField } from '@/types'
import FileUploadField from '@/components/ui/FileUploadField.vue'

const emit = defineEmits<{
  'next-step': []
  'prev-step': []
}>()

const store = useBuilderStore()
const { t } = useTranslations()
const api = useApi()

const group = computed(() => store.currentGroup)
const fields = computed(() => store.currentGroup?.fields || [])

// Global values state
const globalValues = ref<Record<string, any>>({})
const loading = ref(false)
const saving = ref(false)
const entityType = ref<string>('')
const validationErrors = ref<Record<string, string[]>>({})

// Language management
const languages = computed(() => {
  const langs = (window as any).acfConfig?.languages || []
  return langs.map((l: any) => ({
    id_lang: l.id_lang || l.id,
    iso_code: l.iso_code || l.code,
    name: l.name || l.iso_code,
    is_default: l.is_default || false
  }))
})

const defaultLanguage = computed(() => 
  languages.value.find((l: any) => l.is_default) || languages.value[0]
)

// Track current language for each translatable field
const currentLangByField = ref<Record<string, number>>({})

// Load global values when component mounts or group changes
watch(() => group.value?.id, async (newId) => {
  if (newId) {
    await loadGlobalValues()
  }
}, { immediate: true })

async function loadGlobalValues(): Promise<void> {
  if (!group.value?.id) return
  
  loading.value = true
  try {
    const response = await api.getGlobalValues(group.value.id)
    entityType.value = response.entityType
    // Ensure values is always an object, not an array
    // PHP returns [] for empty arrays which JS interprets as array, not object
    const values = response.values
    if (!values || Array.isArray(values)) {
      globalValues.value = {}
    } else {
      globalValues.value = values
    }
  } catch (error) {
    console.error('Failed to load global values:', error)
    globalValues.value = {}
  } finally {
    loading.value = false
  }
}

async function saveGlobalValues(): Promise<void> {
  if (!group.value?.id) return
  
  // Validate before saving
  if (!validateAllFields()) {
    alert('Please fix validation errors before saving.')
    return
  }
  
  saving.value = true
  try {
    await api.saveGlobalValues(group.value.id, globalValues.value)
    validationErrors.value = {}
    
    // Show success message
    const successDiv = document.createElement('div')
    successDiv.className = 'alert alert-success alert-dismissible fade show position-fixed'
    successDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;'
    successDiv.innerHTML = `
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <i class="material-icons mr-2" style="vertical-align: middle;">check_circle</i>
      <strong>Success!</strong> Global values saved.
    `
    document.body.appendChild(successDiv)
    setTimeout(() => successDiv.remove(), 3000)
    
  } catch (error) {
    console.error('Failed to save global values:', error)
    alert('Failed to save global values. Please try again.')
  } finally {
    saving.value = false
  }
}

// Validate all fields
function validateAllFields(): boolean {
  validationErrors.value = {}
  let isValid = true
  
  for (const field of fields.value) {
    if (isTranslatable(field)) {
      // For translatable fields, validate each language
      const fieldValues = globalValues.value[field.slug]
      if (typeof fieldValues === 'object' && fieldValues !== null) {
        for (const langId of Object.keys(fieldValues)) {
          const errors = validateField(field, fieldValues[langId])
          if (errors.length > 0) {
            const lang = languages.value.find(l => l.id_lang === parseInt(langId))
            const langLabel = lang ? ` (${lang.iso_code.toUpperCase()})` : ''
            validationErrors.value[field.slug] = [
              ...(validationErrors.value[field.slug] || []),
              ...errors.map(e => `${langLabel}: ${e}`)
            ]
            isValid = false
          }
        }
      }
    } else {
      // For non-translatable fields, validate simple value
      const errors = validateField(field, getFieldValue(field))
      if (errors.length > 0) {
        validationErrors.value[field.slug] = errors
        isValid = false
      }
    }
  }
  
  return isValid
}

// Validate single field
function validateField(field: AcfField, value: any): string[] {
  const errors: string[] = []
  const validation = field.validation || {}
  
  // Required
  if (validation.required && (value === null || value === '' || value === undefined)) {
    errors.push('This field is required')
  }
  
  // String validations
  if (typeof value === 'string' && value !== '') {
    // Min length
    if (validation.minLength && value.length < validation.minLength) {
      errors.push(`Minimum ${validation.minLength} characters required`)
    }
    
    // Max length
    if (validation.maxLength && value.length > validation.maxLength) {
      errors.push(`Maximum ${validation.maxLength} characters allowed`)
    }
    
    // Pattern
    if (validation.pattern) {
      try {
        const regex = new RegExp(validation.pattern)
        if (!regex.test(value)) {
          errors.push(validation.message || 'Invalid format')
        }
      } catch {
        // Invalid regex, skip
      }
    }
  }
  
  // Number validations
  if (field.type === 'number' && value !== null && value !== '') {
    const numValue = parseFloat(value)
    
    if (validation.min !== undefined && numValue < validation.min) {
      errors.push(`Minimum value: ${validation.min}`)
    }
    
    if (validation.max !== undefined && numValue > validation.max) {
      errors.push(`Maximum value: ${validation.max}`)
    }
  }
  
  return errors
}

// Check if field is translatable (values vary by language)
function isTranslatable(field: AcfField): boolean {
  return !!field.value_translatable || !!field.valueTranslatable || !!field.translatable
}

// Get or initialize value for a field
function getFieldValue(field: AcfField): any {
  if (isTranslatable(field)) {
    // For translatable fields, get value for current language
    const langId = currentLangByField.value[field.slug] ?? defaultLanguage.value?.id_lang
    const allValues = globalValues.value[field.slug]
    if (typeof allValues === 'object' && allValues !== null) {
      return allValues[langId] ?? ''
    }
    return ''
  }
  // For non-translatable fields, simple value
  return globalValues.value[field.slug] ?? null
}

// Update value for a field
function updateFieldValue(field: AcfField, value: any): void {
  if (isTranslatable(field)) {
    // For translatable fields, store as {langId: value}
    const langId = currentLangByField.value[field.slug] ?? defaultLanguage.value?.id_lang
    if (!globalValues.value[field.slug] || typeof globalValues.value[field.slug] !== 'object') {
      globalValues.value[field.slug] = {}
    }
    globalValues.value[field.slug][langId] = value
  } else {
    // For non-translatable fields, simple value
    globalValues.value[field.slug] = value
  }
  
  // Clear validation errors for this field
  if (validationErrors.value[field.slug]) {
    delete validationErrors.value[field.slug]
  }
}

// Set current language for a translatable field
function setCurrentLanguage(fieldSlug: string, langId: number): void {
  currentLangByField.value[fieldSlug] = langId
}

// Check if field is required
function isRequired(field: AcfField): boolean {
  return field.validation?.required || false
}

// Get validation info text for a field
function getValidationInfo(field: AcfField): string[] {
  const info: string[] = []
  const validation = field.validation || {}
  
  if (validation.minLength) {
    info.push(`Min: ${validation.minLength} characters`)
  }
  if (validation.maxLength) {
    info.push(`Max: ${validation.maxLength} characters`)
  }
  if (validation.min !== undefined) {
    info.push(`Min: ${validation.min}`)
  }
  if (validation.max !== undefined) {
    info.push(`Max: ${validation.max}`)
  }
  if (validation.pattern) {
    info.push(`Pattern: ${validation.pattern}`)
  }
  
  return info
}

// Get entity type label
const entityTypeLabel = computed(() => {
  const entityTypes: Record<string, string> = {
    product: 'Products',
    category: 'Categories',
    customer: 'Customers',
    manufacturer: 'Manufacturers',
    supplier: 'Suppliers',
    cms_page: 'CMS Pages',
    cms_category: 'CMS Categories',
  }
  return entityTypes[entityType.value] || entityType.value
})
</script>

<template>
  <div class="global-values-editor">
    <!-- Header -->
    <div class="alert alert-info mb-4">
      <i class="material-icons mr-2" style="vertical-align: middle; font-size: 18px;">info</i>
      <strong>Global Values</strong>
      <p class="mb-0 mt-2 small">
        These values will be shared by <strong>ALL {{ entityTypeLabel }}</strong>. 
        They will be displayed on every entity's page, but users cannot edit them individually.
      </p>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-5">
      <i class="material-icons rotating" style="font-size: 48px; color: #007bff;">sync</i>
      <p class="text-muted mt-3">Loading global values...</p>
    </div>

    <!-- Fields form -->
    <div v-else-if="fields.length > 0" class="global-values-form">
      <div v-for="field in fields" :key="field.uuid" class="form-group mb-4">
        <!-- Field Label with Required indicator -->
        <label class="form-control-label">
          <strong>{{ field.title }}</strong>
          <span v-if="isRequired(field)" class="text-danger ml-1">*</span>
          <span v-if="isTranslatable(field)" class="badge badge-info ml-2" style="font-size: 0.7rem;">
            <i class="material-icons" style="font-size: 12px; vertical-align: middle;">language</i>
            Translatable
          </span>
        </label>
        
        <!-- Instructions -->
        <div v-if="field.instructions" class="form-text text-muted mb-2">
          <i class="material-icons" style="font-size: 14px; vertical-align: middle;">help_outline</i>
          {{ field.instructions }}
        </div>

        <!-- Language Tabs (only for translatable fields) -->
        <div v-if="isTranslatable(field) && languages.length > 1" class="acf-lang-tabs mb-2">
          <button
            v-for="lang in languages"
            :key="lang.id_lang"
            type="button"
            class="acf-lang-tab"
            :class="{ 
              active: (currentLangByField[field.slug] ?? defaultLanguage?.id_lang) === lang.id_lang,
              'is-default': lang.is_default
            }"
            @click="setCurrentLanguage(field.slug, lang.id_lang)"
            :title="lang.is_default ? 'Default language' : ''"
          >
            {{ lang.iso_code.toUpperCase() }}
            <span v-if="lang.is_default" class="material-icons" style="font-size: 12px; margin-left: 2px;">star</span>
          </button>
        </div>

        <!-- Text input -->
        <input
          v-if="field.type === 'text' || field.type === 'email' || field.type === 'url'"
          :type="field.type"
          class="form-control"
          :class="{ 'is-invalid': validationErrors[field.slug] }"
          :value="getFieldValue(field)"
          @input="updateFieldValue(field, ($event.target as HTMLInputElement).value)"
          :placeholder="field.config?.placeholder || ''"
          :required="isRequired(field)"
          :minlength="field.validation?.minLength"
          :maxlength="field.validation?.maxLength"
          :pattern="field.validation?.pattern"
        >

        <!-- Textarea -->
        <textarea
          v-else-if="field.type === 'textarea'"
          class="form-control"
          :class="{ 'is-invalid': validationErrors[field.slug] }"
          :rows="field.config?.rows || 3"
          :value="getFieldValue(field)"
          @input="updateFieldValue(field, ($event.target as HTMLTextAreaElement).value)"
          :placeholder="field.config?.placeholder || ''"
          :required="isRequired(field)"
          :minlength="field.validation?.minLength"
          :maxlength="field.validation?.maxLength"
        />

        <!-- Number -->
        <input
          v-else-if="field.type === 'number'"
          type="number"
          class="form-control"
          :class="{ 'is-invalid': validationErrors[field.slug] }"
          :value="getFieldValue(field)"
          @input="updateFieldValue(field, ($event.target as HTMLInputElement).value)"
          :min="field.validation?.min ?? field.config?.min"
          :max="field.validation?.max ?? field.config?.max"
          :step="field.config?.step || 1"
          :required="isRequired(field)"
        >

        <!-- Date -->
        <input
          v-else-if="field.type === 'date'"
          type="date"
          class="form-control"
          :class="{ 'is-invalid': validationErrors[field.slug] }"
          :value="getFieldValue(field)"
          @input="updateFieldValue(field, ($event.target as HTMLInputElement).value)"
          :required="isRequired(field)"
        >

        <!-- Color -->
        <input
          v-else-if="field.type === 'color'"
          type="color"
          class="form-control"
          :class="{ 'is-invalid': validationErrors[field.slug] }"
          :value="getFieldValue(field) || '#000000'"
          @input="updateFieldValue(field, ($event.target as HTMLInputElement).value)"
          :required="isRequired(field)"
        >

        <!-- Boolean (checkbox) -->
        <div v-else-if="field.type === 'boolean'" class="form-check">
          <input
            type="checkbox"
            class="form-check-input"
            :id="`field-${field.slug}`"
            :checked="!!getFieldValue(field)"
            @change="updateFieldValue(field, ($event.target as HTMLInputElement).checked)"
            :required="isRequired(field)"
          >
          <label class="form-check-label" :for="`field-${field.slug}`">
            {{ field.config?.label || 'Enable' }}
          </label>
        </div>

        <!-- Select -->
        <select
          v-else-if="field.type === 'select'"
          class="form-control"
          :class="{ 'is-invalid': validationErrors[field.slug] }"
          :value="getFieldValue(field)"
          @change="updateFieldValue(field, ($event.target as HTMLSelectElement).value)"
          :required="isRequired(field)"
        >
          <option value="">-- {{ t('none') || 'None' }} --</option>
          <option
            v-for="choice in parseChoices(field.config?.choices)"
            :key="choice.value"
            :value="choice.value"
          >
            {{ choice.label }}
          </option>
        </select>

        <!-- File Upload -->
        <FileUploadField
          v-else-if="field.type === 'file'"
          :model-value="getFieldValue(field)"
          :field-slug="field.slug"
          field-type="file"
          @update:model-value="updateFieldValue(field, $event)"
        />

        <!-- Image Upload -->
        <FileUploadField
          v-else-if="field.type === 'image'"
          :model-value="getFieldValue(field)"
          :field-slug="field.slug"
          field-type="image"
          accept="image/*"
          @update:model-value="updateFieldValue(field, $event)"
        />

        <!-- Video Upload -->
        <FileUploadField
          v-else-if="field.type === 'video'"
          :model-value="getFieldValue(field)"
          :field-slug="field.slug"
          field-type="video"
          accept="video/*"
          @update:model-value="updateFieldValue(field, $event)"
        />

        <!-- Fallback for unsupported types -->
        <div v-else class="alert alert-warning">
          <i class="material-icons mr-2" style="vertical-align: middle;">warning</i>
          Field type <code>{{ field.type }}</code> is not yet supported for global values.
          <br>
          <small class="text-muted mt-1 d-block">
            Supported types: text, textarea, number, email, url, date, color, boolean, select, file, image, video
          </small>
        </div>

        <!-- Validation info -->
        <small v-if="getValidationInfo(field).length > 0" class="form-text text-muted">
          <i class="material-icons" style="font-size: 12px; vertical-align: middle;">info</i>
          {{ getValidationInfo(field).join(' • ') }}
        </small>

        <!-- Validation errors -->
        <div v-if="validationErrors[field.slug]" class="invalid-feedback d-block">
          <i class="material-icons" style="font-size: 14px; vertical-align: middle;">error</i>
          <span v-for="(error, idx) in validationErrors[field.slug]" :key="idx">
            {{ error }}{{ idx < validationErrors[field.slug].length - 1 ? ', ' : '' }}
          </span>
        </div>
      </div>

    </div>

    <!-- No fields -->
    <div v-else class="alert alert-warning">
      <i class="material-icons mr-2" style="vertical-align: middle;">warning</i>
      No fields defined yet. Please add fields in the previous step.
    </div>

    <!-- Step Navigation -->
    <div class="acfps-step-navigation">
      <button class="btn btn-outline-secondary" @click="emit('prev-step')">
        <span class="material-icons">arrow_back</span>
        {{ t('fields') || 'Fields' }}
      </button>
      <button 
        class="btn btn-success"
        :disabled="saving"
        @click="saveGlobalValues"
      >
        <i class="material-icons mr-1" style="font-size: 18px; vertical-align: middle;">
          {{ saving ? 'sync' : 'check_circle' }}
        </i>
        <span v-if="saving">{{ t('saving') || 'Saving...' }}</span>
        <span v-else>{{ t('saveAndFinish') || 'Save & Finish' }}</span>
      </button>
    </div>
  </div>
</template>

<script lang="ts">
// Helper to parse choices from string or array
function parseChoices(choices: any): Array<{ value: string, label: string }> {
  if (!choices) return []
  
  if (Array.isArray(choices)) {
    return choices.map((item: any) => {
      if (typeof item === 'object' && item !== null) {
        return { value: item.value || '', label: item.label || '' }
      }
      return { value: String(item), label: String(item) }
    })
  }
  
  if (typeof choices === 'string') {
    return choices.split('\n')
      .map((line: string) => line.trim())
      .filter((line: string) => line.length > 0)
      .map((line: string) => {
        const parts = line.split(':').map((p: string) => p.trim())
        return {
          value: parts[0] || '',
          label: parts[1] || parts[0] || ''
        }
      })
  }
  
  return []
}
</script>

<style scoped>
.global-values-editor {
  padding: 1.5rem;
}

.global-values-form {
  background: white;
  padding: 2rem;
  border-radius: 0.375rem;
  border: 1px solid #dee2e6;
}

.form-control-label {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
  display: block;
}

.form-control-label .text-danger {
  font-size: 1.2em;
}

.form-control.is-invalid {
  border-color: #dc3545;
}

.form-control.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.invalid-feedback {
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.invalid-feedback .material-icons {
  color: #dc3545;
  margin-right: 0.25rem;
}

.form-text {
  font-size: 0.875rem;
  color: #6c757d;
}

.form-text .material-icons {
  margin-right: 0.25rem;
}

.ml-1 {
  margin-left: 0.25rem;
}

.ml-2 {
  margin-left: 0.5rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.acfps-step-navigation {
  display: flex;
  justify-content: space-between;
  padding: 1.5rem;
  border-top: 1px solid #dee2e6;
  margin-top: 2rem;
  background: #f8f9fa;
}

.acfps-step-navigation .btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.acfps-step-navigation .btn .material-icons {
  font-size: 18px;
}

.mr-1 {
  margin-right: 0.25rem;
}

/* Language tabs */
.acf-lang-tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.acf-lang-tab {
  padding: 0.5rem 1rem;
  border: 1px solid #dee2e6;
  background: white;
  border-radius: 0.25rem;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.875rem;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.acf-lang-tab:hover {
  border-color: #25b9d7;
  color: #25b9d7;
}

.acf-lang-tab.active {
  background: #25b9d7;
  color: white;
  border-color: #25b9d7;
  font-weight: 600;
}

.acf-lang-tab.is-default {
  border-color: #ffc107;
}

.acf-lang-tab.is-default.active {
  background: #ffc107;
  color: #000;
}

.acf-lang-tab .material-icons {
  font-size: 14px;
}

.badge-info {
  background-color: #17a2b8;
  color: white;
  padding: 0.35rem 0.5rem;
  font-size: 0.7rem;
  border-radius: 0.25rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.badge-info .material-icons {
  font-size: 12px;
  line-height: 1;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.rotating {
  animation: rotate 2s linear infinite;
}
</style>

