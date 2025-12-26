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
      <label class="form-control-label">Allowed File Types</label>
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
        Select which file types users can upload.
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">Max File Size (MB)</label>
      <input 
        type="number"
        class="form-control"
        min="1"
        max="50"
        :value="config.maxSizeMB || 10"
        @input="updateConfig('maxSizeMB', parseInt(($event.target as HTMLInputElement).value) || 10)"
      >
      <small class="form-text text-muted">
        Maximum file size in megabytes (1-50 MB).
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
        Use Fixed File Path
      </label>
      <small class="form-text text-muted">
        When enabled, files use a predictable path (field_product_shop.ext) - better for caching. 
        When disabled, files get unique names - avoids browser cache issues after delete/re-upload.
      </small>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">Input Methods</h6>

    <div class="form-group">
      <label class="form-control-label">Allowed Input Methods</label>
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
            <strong>Upload</strong> - Direct file upload from computer
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
            <strong>Import from URL</strong> - Download file from URL and store locally
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
            <strong>External Link</strong> - Reference URL without downloading
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
            <strong>PrestaShop Attachment</strong> - Choose from product attachments
          </label>
        </div>
      </div>
      <small class="form-text text-muted">
        Select which input methods are available to users. At least one must be enabled.
      </small>
    </div>

    <div class="form-group" v-if="hasMultipleMethods">
      <label class="form-control-label">Default Input Method</label>
      <select 
        class="form-control"
        :value="config.defaultInputMethod || 'upload'"
        @change="updateConfig('defaultInputMethod', ($event.target as HTMLSelectElement).value)"
      >
        <option value="upload" v-if="config.allowUpload !== false">Upload</option>
        <option value="import" v-if="config.allowUrlImport">Import from URL</option>
        <option value="link" v-if="config.allowUrlLink">External Link</option>
      </select>
      <small class="form-text text-muted">
        The default tab/option shown when editing a product.
      </small>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">File Metadata Options</h6>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.enableTitle === true"
          @change="updateConfig('enableTitle', ($event.target as HTMLInputElement).checked)"
        >
        Enable Title Field
      </label>
      <small class="form-text text-muted">
        Allow users to add a custom title for the file.
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
        Enable Description Field
      </label>
      <small class="form-text text-muted">
        Allow users to add a description for the file.
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

