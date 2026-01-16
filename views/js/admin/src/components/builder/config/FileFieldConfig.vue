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

const fileTypeOptions = [
  { value: 'application/pdf', label: 'PDF' },
  { value: 'application/msword', label: 'Word (.doc)' },
  { value: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', label: 'Word (.docx)' },
  { value: 'application/vnd.ms-excel', label: 'Excel (.xls)' },
  { value: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', label: 'Excel (.xlsx)' },
  { value: 'text/plain', label: 'Text (.txt)' },
  { value: 'text/csv', label: 'CSV' },
  { value: 'application/zip', label: 'ZIP' },
]

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}

function toggleMimeType(mime: string): void {
  const current = (props.config.allowedMimes as string[]) || []
  const index = current.indexOf(mime)
  
  let newMimes: string[]
  if (index === -1) {
    newMimes = [...current, mime]
  } else {
    newMimes = current.filter(m => m !== mime)
  }
  
  updateConfig('allowedMimes', newMimes)
}

function isMimeSelected(mime: string): boolean {
  const mimes = (props.config.allowedMimes as string[]) || []
  return mimes.includes(mime)
}
</script>

<template>
  <div class="file-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('allowedFileTypes') }}</label>
      <div class="acf-checkbox-list">
        <div 
          v-for="option in fileTypeOptions" 
          :key="option.value"
          class="form-check"
        >
          <input 
            type="checkbox"
            class="form-check-input"
            :id="'mime-' + option.value"
            :checked="isMimeSelected(option.value)"
            @change="toggleMimeType(option.value)"
          >
          <label class="form-check-label" :for="'mime-' + option.value">
            {{ option.label }}
          </label>
        </div>
      </div>
      <small class="form-text text-muted">
        {{ t('allowedFileTypesHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('maxFileSize') }}</label>
      <input 
        type="number"
        class="form-control"
        min="1"
        max="50"
        :value="config.maxSizeMB || 10"
        @input="updateConfig('maxSizeMB', parseInt(($event.target as HTMLInputElement).value) || 10)"
      >
      <small class="form-text text-muted">
        {{ t('maxFileSizeHelp') }}
      </small>
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
            id="method-upload"
            :checked="config.allowUpload !== false"
            @change="updateConfig('allowUpload', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="method-upload">
            <strong>{{ t('inputMethodUpload') }}</strong> - {{ t('inputMethodUploadHelp') }}
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="method-import"
            :checked="config.allowUrlImport === true"
            @change="updateConfig('allowUrlImport', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="method-import">
            <strong>{{ t('inputMethodImport') }}</strong> - {{ t('inputMethodImportHelp') }}
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="method-link"
            :checked="config.allowUrlLink === true"
            @change="updateConfig('allowUrlLink', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="method-link">
            <strong>{{ t('inputMethodLink') }}</strong> - {{ t('inputMethodLinkHelp') }}
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="method-attachment"
            :checked="config.allowAttachment === true"
            @change="updateConfig('allowAttachment', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="method-attachment">
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
    <h6 class="text-muted mb-3">{{ t('fileMetadata') }}</h6>

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
        {{ t('enableTitleFileHelp') }}
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
        {{ t('enableDescriptionFileHelp') }}
      </small>
    </div>
  </div>
</template>

<style scoped>
.acf-checkbox-list {
  max-height: 200px;
  overflow-y: auto;
  padding: 0.5rem;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  background: #fff;
}
</style>

