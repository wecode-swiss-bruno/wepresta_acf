<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { useApi } from '@/composables/useApi'
import type { AcfField } from '@/types'
import TextFieldConfig from '@/components/fields/TextFieldConfig.vue'
import TextareaFieldConfig from '@/components/fields/TextareaFieldConfig.vue'
import NumberFieldConfig from '@/components/fields/NumberFieldConfig.vue'
import EmailFieldConfig from '@/components/fields/EmailFieldConfig.vue'
import UrlFieldConfig from '@/components/fields/UrlFieldConfig.vue'
import SelectFieldConfig from '@/components/fields/SelectFieldConfig.vue'
import CheckboxFieldConfig from '@/components/fields/CheckboxFieldConfig.vue'
import RadioFieldConfig from '@/components/fields/RadioFieldConfig.vue'
import BooleanFieldConfig from '@/components/fields/BooleanFieldConfig.vue'
import DateFieldConfig from '@/components/fields/DateFieldConfig.vue'
import TimeFieldConfig from '@/components/fields/TimeFieldConfig.vue'
import DatetimeFieldConfig from '@/components/fields/DatetimeFieldConfig.vue'
import ColorFieldConfig from '@/components/fields/ColorFieldConfig.vue'
import RichTextFieldConfig from '@/components/fields/RichTextFieldConfig.vue'
import FileFieldConfig from '@/components/fields/FileFieldConfig.vue'
import ImageFieldConfig from '@/components/fields/ImageFieldConfig.vue'
import VideoFieldConfig from '@/components/fields/VideoFieldConfig.vue'
import GalleryFieldConfig from '@/components/fields/GalleryFieldConfig.vue'
import FilesFieldConfig from '@/components/fields/FilesFieldConfig.vue'
import RelationFieldConfig from '@/components/fields/RelationFieldConfig.vue'
import ListFieldConfig from '@/components/fields/ListFieldConfig.vue'
import RepeaterFieldConfig from '@/components/fields/RepeaterFieldConfig.vue'

const store = useBuilderStore()
const { t } = useTranslations()
const api = useApi()

const activeTab = ref<'general' | 'validation' | 'presentation' | 'conditions'>('general')

// Local copy for editing
const localField = ref<AcfField | null>(null)

// Sync with store selection
watch(() => store.selectedField, (newField) => {
  if (newField) {
    localField.value = JSON.parse(JSON.stringify(newField))
  } else {
    localField.value = null
  }
}, { immediate: true })

// Auto-generate slug from title
const slugTimeout = ref<number | null>(null)
function onTitleChange(): void {
  if (!localField.value) return
  
  // Only auto-generate if slug is empty or matches previous auto-generated
  if (!localField.value.slug || !localField.value.id) {
    if (slugTimeout.value) {
      clearTimeout(slugTimeout.value)
    }
    slugTimeout.value = window.setTimeout(async () => {
      if (localField.value?.title) {
        localField.value.slug = await api.slugify(localField.value.title)
      }
    }, 500)
  }
}

// Update local store (no API call yet)
function onFieldChange(): void {
  if (!localField.value) return
  store.updateFieldLocal(localField.value)
}

// Save to API
async function saveFieldToApi(): Promise<void> {
  if (!localField.value) return
  await store.saveField(localField.value)
}

// Check if field needs saving (new or modified)
const needsSaving = computed(() => {
  if (!localField.value) return false
  // New field without ID needs saving
  if (!localField.value.id && localField.value.title.trim()) return true
  return false
})

// Get field type config component
const fieldConfigComponent = computed(() => {
  if (!localField.value) return null
  
  switch (localField.value.type) {
    case 'text':
      return TextFieldConfig
    case 'textarea':
      return TextareaFieldConfig
    case 'number':
      return NumberFieldConfig
    case 'email':
      return EmailFieldConfig
    case 'url':
      return UrlFieldConfig
    case 'select':
      return SelectFieldConfig
    case 'checkbox':
      return CheckboxFieldConfig
    case 'radio':
      return RadioFieldConfig
    case 'boolean':
      return BooleanFieldConfig
    case 'date':
      return DateFieldConfig
    case 'time':
      return TimeFieldConfig
    case 'datetime':
      return DatetimeFieldConfig
    case 'color':
      return ColorFieldConfig
    case 'richtext':
      return RichTextFieldConfig
    case 'file':
      return FileFieldConfig
    case 'image':
      return ImageFieldConfig
    case 'video':
      return VideoFieldConfig
    case 'gallery':
      return GalleryFieldConfig
    case 'files':
      return FilesFieldConfig
    case 'relation':
      return RelationFieldConfig
    case 'list':
      return ListFieldConfig
    case 'repeater':
      return RepeaterFieldConfig
    default:
      return null
  }
})

const layoutOptions = computed(() => window.acfConfig?.layoutOptions || { widths: [], positions: [] })
</script>

<template>
  <div class="acfps-field-configurator">
    <!-- No field selected -->
    <div v-if="!localField" class="acfps-empty-state">
      <span class="material-icons">touch_app</span>
      <p>{{ t('selectField') }}</p>
    </div>

    <!-- Field editor -->
    <template v-else>
      <!-- Save button for new unsaved fields -->
      <div v-if="needsSaving" class="acfps-save-field-bar">
        <button 
          class="btn btn-primary btn-sm"
          :disabled="store.saving"
          @click="saveFieldToApi"
        >
          <span v-if="store.saving">{{ t('saving') }}</span>
          <span v-else>{{ t('save') }} Field</span>
        </button>
        <small class="text-muted ml-2">This field hasn't been saved yet</small>
      </div>

      <!-- Config tabs -->
      <div class="acfps-config-tabs">
        <button 
          class="acfps-config-tab"
          :class="{ active: activeTab === 'general' }"
          @click="activeTab = 'general'"
        >
          {{ t('general') }}
        </button>
        <button 
          class="acfps-config-tab"
          :class="{ active: activeTab === 'validation' }"
          @click="activeTab = 'validation'"
        >
          {{ t('validation') }}
        </button>
        <button 
          class="acfps-config-tab"
          :class="{ active: activeTab === 'presentation' }"
          @click="activeTab = 'presentation'"
        >
          {{ t('presentation') }}
        </button>
        <button 
          class="acfps-config-tab"
          :class="{ active: activeTab === 'conditions' }"
          @click="activeTab = 'conditions'"
        >
          {{ t('conditions') }}
        </button>
      </div>

      <div class="config-content">
        <!-- General tab -->
        <template v-if="activeTab === 'general'">
          <div class="acfps-form-section">
            <div class="form-group">
              <label class="form-control-label">{{ t('fieldTitle') }} *</label>
              <input 
                v-model="localField.title"
                type="text"
                class="form-control"
                @input="onTitleChange(); onFieldChange()"
              >
            </div>

            <div class="form-group">
              <label class="form-control-label">{{ t('fieldSlug') }} *</label>
              <input 
                v-model="localField.slug"
                type="text"
                class="form-control"
                pattern="[a-z0-9_]+"
                @input="onFieldChange"
              >
              <small class="form-text text-muted">
                Use lowercase letters, numbers, and underscores only.
              </small>
            </div>

            <div class="form-group">
              <label class="form-control-label">{{ t('fieldInstructions') }}</label>
              <textarea 
                v-model="localField.instructions"
                class="form-control"
                rows="3"
                @input="onFieldChange"
              />
              <small class="form-text text-muted">
                Help text shown below the field in the back-office.
              </small>
            </div>
          </div>

          <!-- Type-specific config -->
          <div v-if="fieldConfigComponent" class="acfps-form-section">
            <h4>{{ t('fieldType') }}: {{ localField.type }}</h4>
            <component 
              :is="fieldConfigComponent"
              v-model:config="localField.config"
              @update:config="onFieldChange"
            />
          </div>
        </template>

        <!-- Validation tab -->
        <template v-if="activeTab === 'validation'">
          <div class="acfps-form-section">
            <div class="form-group">
              <div class="ps-switch">
                <input 
                  id="field-required"
                  v-model="localField.validation.required"
                  type="checkbox"
                  @change="onFieldChange"
                >
                <label for="field-required">{{ t('required') }}</label>
              </div>
            </div>

            <template v-if="localField.type === 'text'">
              <div class="form-row">
                <div class="form-group col-6">
                  <label class="form-control-label">{{ t('minLength') }}</label>
                  <input 
                    v-model.number="localField.validation.minLength"
                    type="number"
                    class="form-control"
                    min="0"
                    @input="onFieldChange"
                  >
                </div>
                <div class="form-group col-6">
                  <label class="form-control-label">{{ t('maxLength') }}</label>
                  <input 
                    v-model.number="localField.validation.maxLength"
                    type="number"
                    class="form-control"
                    min="0"
                    @input="onFieldChange"
                  >
                </div>
              </div>

              <div class="form-group">
                <label class="form-control-label">{{ t('pattern') }}</label>
                <input 
                  v-model="localField.validation.pattern"
                  type="text"
                  class="form-control"
                  placeholder="^[A-Z].*$"
                  @input="onFieldChange"
                >
              </div>
            </template>

            <template v-if="localField.type === 'number'">
              <div class="form-row">
                <div class="form-group col-6">
                  <label class="form-control-label">{{ t('minValue') }}</label>
                  <input 
                    v-model.number="localField.validation.min"
                    type="number"
                    class="form-control"
                    @input="onFieldChange"
                  >
                </div>
                <div class="form-group col-6">
                  <label class="form-control-label">{{ t('maxValue') }}</label>
                  <input 
                    v-model.number="localField.validation.max"
                    type="number"
                    class="form-control"
                    @input="onFieldChange"
                  >
                </div>
              </div>
            </template>

            <div class="form-group">
              <label class="form-control-label">{{ t('errorMessage') }}</label>
              <input 
                v-model="localField.validation.message"
                type="text"
                class="form-control"
                :placeholder="t('errorMessage')"
                @input="onFieldChange"
              >
            </div>
          </div>
        </template>

        <!-- Presentation tab -->
        <template v-if="activeTab === 'presentation'">
          <div class="acfps-form-section">
            <div class="form-group">
              <label class="form-control-label">{{ t('wrapperWidth') }}</label>
              <select 
                v-model="localField.wrapper.width"
                class="form-control"
                @change="onFieldChange"
              >
                <option value="">100%</option>
                <option 
                  v-for="opt in layoutOptions.widths" 
                  :key="opt.value" 
                  :value="opt.value"
                >
                  {{ opt.label }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-control-label">{{ t('wrapperClass') }}</label>
              <input 
                v-model="localField.wrapper.class"
                type="text"
                class="form-control"
                placeholder="my-custom-class"
                @input="onFieldChange"
              >
            </div>

            <div class="form-group">
              <label class="form-control-label">{{ t('wrapperId') }}</label>
              <input 
                v-model="localField.wrapper.id"
                type="text"
                class="form-control"
                placeholder="my-field-id"
                @input="onFieldChange"
              >
            </div>
          </div>

          <div class="acfps-form-section">
            <h4>{{ t('frontendOptions') }}</h4>
            
            <div class="form-group">
              <div class="ps-switch">
                <input 
                  id="field-translatable"
                  v-model="localField.translatable"
                  type="checkbox"
                  @change="onFieldChange"
                >
                <label for="field-translatable">{{ t('translatable') }}</label>
              </div>
            </div>

            <div class="form-group">
              <div class="ps-switch">
                <input 
                  id="field-active"
                  v-model="localField.active"
                  type="checkbox"
                  @change="onFieldChange"
                >
                <label for="field-active">{{ t('active') }}</label>
              </div>
            </div>

            <div class="form-group">
              <div class="ps-switch">
                <input 
                  id="field-fo-visible"
                  v-model="localField.foOptions.visible"
                  type="checkbox"
                  @change="onFieldChange"
                >
                <label for="field-fo-visible">{{ t('showOnFrontend') }}</label>
              </div>
            </div>
          </div>
        </template>

        <!-- Conditions tab -->
        <template v-if="activeTab === 'conditions'">
          <div class="acfps-form-section">
            <p class="text-muted">
              Conditional logic allows you to show/hide or require fields based on other field values.
            </p>
            <div class="alert alert-info">
              Conditions editor coming in Phase 4.
            </div>
          </div>
        </template>
      </div>
    </template>
  </div>
</template>

<style scoped>
.acfps-field-configurator {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.config-content {
  flex: 1;
  overflow-y: auto;
}

.form-row {
  display: flex;
  margin: 0 -0.5rem;
}

.form-row .form-group {
  padding: 0 0.5rem;
}

.col-6 {
  flex: 0 0 50%;
  max-width: 50%;
}

.acfps-save-field-bar {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  background: #fff3cd;
  border-bottom: 1px solid #ffc107;
  margin-bottom: 0.5rem;
}

.acfps-save-field-bar .ml-2 {
  margin-left: 0.5rem;
}
</style>

