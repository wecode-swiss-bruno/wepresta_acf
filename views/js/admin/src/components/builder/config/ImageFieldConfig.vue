<script setup lang="ts">
import { computed } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()

// Check if multiple input methods are enabled
const hasMultipleMethods = computed(() => {
  let count = 0
  if (props.config.allowUpload !== false) count++
  if (props.config.allowUrlImport) count++
  if (props.config.allowUrlLink) count++
  return count > 1
})

const formatOptions = [
  { value: 'jpg', label: 'JPEG' },
  { value: 'png', label: 'PNG' },
  { value: 'gif', label: 'GIF' },
  { value: 'webp', label: 'WebP' },
]

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}

function toggleFormat(format: string): void {
  const current = (props.config.allowedFormats as string[]) || ['jpg', 'png', 'gif', 'webp']
  const index = current.indexOf(format)
  
  let newFormats: string[]
  if (index === -1) {
    newFormats = [...current, format]
  } else {
    newFormats = current.filter(f => f !== format)
  }
  
  // Ensure at least one format is selected
  if (newFormats.length === 0) {
    newFormats = ['jpg']
  }
  
  updateConfig('allowedFormats', newFormats)
}

function isFormatSelected(format: string): boolean {
  const formats = (props.config.allowedFormats as string[]) || ['jpg', 'png', 'gif', 'webp']
  return formats.includes(format)
}
</script>

<template>
  <div class="image-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('allowedFormats') }}</label>
      <div class="acf-format-checkboxes">
        <div 
          v-for="option in formatOptions" 
          :key="option.value"
          class="form-check form-check-inline"
        >
          <input 
            type="checkbox"
            class="form-check-input"
            :id="'format-' + option.value"
            :checked="isFormatSelected(option.value)"
            @change="toggleFormat(option.value)"
          >
          <label class="form-check-label" :for="'format-' + option.value">
            {{ option.label }}
          </label>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">{{ t('maxWidthPx') }}</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.maxWidth || ''"
            :placeholder="t('noLimit')"
            @input="updateConfig('maxWidth', parseInt(($event.target as HTMLInputElement).value) || null)"
          >
          <small class="form-text text-muted">
            {{ t('maxWidthHelp') }}
          </small>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">{{ t('maxHeightPx') }}</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.maxHeight || ''"
            :placeholder="t('noLimit')"
            @input="updateConfig('maxHeight', parseInt(($event.target as HTMLInputElement).value) || null)"
          >
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('maxFileSize') }}</label>
      <input 
        type="number"
        class="form-control"
        min="1"
        max="20"
        :value="config.maxSizeMB || 5"
        @input="updateConfig('maxSizeMB', parseInt(($event.target as HTMLInputElement).value) || 5)"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.useFixedPath !== false"
          @change="updateConfig('useFixedPath', ($event.target as HTMLInputElement).checked)"
        >
        {{ t('useFixedPath') }}
      </label>
      <small class="form-text text-muted">
        {{ t('useFixedPathHelp') }}
      </small>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">{{ t('inputMethods') }}</h6>

    <div class="form-group">
      <label class="form-control-label">{{ t('allowedInputMethods') }}</label>
      <div class="acf-method-checkboxes">
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="img-method-upload"
            :checked="config.allowUpload !== false"
            @change="updateConfig('allowUpload', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="img-method-upload">
            <strong>{{ t('inputMethodUpload') }}</strong> - {{ t('inputMethodUploadHelp') }}
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="img-method-import"
            :checked="config.allowUrlImport === true"
            @change="updateConfig('allowUrlImport', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="img-method-import">
            <strong>{{ t('inputMethodImport') }}</strong> - {{ t('inputMethodImportHelp') }}
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="img-method-link"
            :checked="config.allowUrlLink === true"
            @change="updateConfig('allowUrlLink', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="img-method-link">
            <strong>{{ t('inputMethodLink') }}</strong> - {{ t('inputMethodLinkHelp') }}
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="img-method-attachment"
            :checked="config.allowAttachment === true"
            @change="updateConfig('allowAttachment', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="img-method-attachment">
            <strong>{{ t('inputMethodAttachment') }}</strong> - {{ t('inputMethodAttachmentHelp') }}
          </label>
        </div>
      </div>
      <small class="form-text text-muted">
        {{ t('inputMethodsAtLeastOne') }}
      </small>
    </div>

    <div class="form-group" v-if="hasMultipleMethods">
      <label class="form-control-label">{{ t('defaultInputMethod') }}</label>
      <select 
        class="form-control"
        :value="config.defaultInputMethod || 'upload'"
        @change="updateConfig('defaultInputMethod', ($event.target as HTMLSelectElement).value)"
      >
        <option value="upload" v-if="config.allowUpload !== false">{{ t('inputMethodUpload') }}</option>
        <option value="import" v-if="config.allowUrlImport">{{ t('inputMethodImport') }}</option>
        <option value="link" v-if="config.allowUrlLink">{{ t('inputMethodLink') }}</option>
      </select>
      <small class="form-text text-muted">
        {{ t('defaultInputMethodHelp') }}
      </small>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">{{ t('imageMetadata') }}</h6>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.enableTitle === true"
          @change="updateConfig('enableTitle', ($event.target as HTMLInputElement).checked)"
        >
        {{ t('enableTitleField') }}
      </label>
      <small class="form-text text-muted">
        {{ t('enableTitleImageHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.enableDescription === true"
          @change="updateConfig('enableDescription', ($event.target as HTMLInputElement).checked)"
        >
        {{ t('enableDescriptionField') }}
      </label>
      <small class="form-text text-muted">
        {{ t('enableDescriptionImageHelp') }}
      </small>
    </div>
  </div>
</template>

