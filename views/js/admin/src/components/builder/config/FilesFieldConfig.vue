<script setup lang="ts">
import type { FieldConfig } from '@/types'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const mimeOptions = [
  { value: 'application/pdf', label: 'PDF' },
  { value: 'application/msword', label: 'Word (.doc)' },
  { value: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', label: 'Word (.docx)' },
  { value: 'application/vnd.ms-excel', label: 'Excel (.xls)' },
  { value: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', label: 'Excel (.xlsx)' },
  { value: 'text/plain', label: 'Text (.txt)' },
  { value: 'text/csv', label: 'CSV' },
  { value: 'application/zip', label: 'ZIP' },
]

const defaultMimes = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'text/plain',
  'text/csv',
  'application/zip',
]

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}

function toggleMime(mime: string): void {
  const current = (props.config.allowedMimes as string[]) || defaultMimes
  const index = current.indexOf(mime)
  
  let newMimes: string[]
  if (index === -1) {
    newMimes = [...current, mime]
  } else {
    newMimes = current.filter(m => m !== mime)
  }
  
  if (newMimes.length === 0) {
    newMimes = ['application/pdf']
  }
  
  updateConfig('allowedMimes', newMimes)
}

function isMimeSelected(mime: string): boolean {
  const mimes = (props.config.allowedMimes as string[]) || defaultMimes
  return mimes.includes(mime)
}
</script>

<template>
  <div class="files-field-config">
    <div class="form-group">
      <label class="form-control-label">Allowed File Types</label>
      <div class="acf-mime-checkboxes">
        <div 
          v-for="option in mimeOptions" 
          :key="option.value"
          class="form-check"
        >
          <input 
            type="checkbox"
            class="form-check-input"
            :id="'files-mime-' + option.value.replace(/[^a-z]/gi, '')"
            :checked="isMimeSelected(option.value)"
            @change="toggleMime(option.value)"
          >
          <label class="form-check-label" :for="'files-mime-' + option.value.replace(/[^a-z]/gi, '')">
            {{ option.label }}
          </label>
        </div>
      </div>
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
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">Minimum Files</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.minItems || ''"
            placeholder="No minimum"
            @input="updateConfig('minItems', parseInt(($event.target as HTMLInputElement).value) || null)"
          >
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">Maximum Files</label>
          <input 
            type="number"
            class="form-control"
            min="1"
            :value="config.maxItems || ''"
            placeholder="No limit"
            @input="updateConfig('maxItems', parseInt(($event.target as HTMLInputElement).value) || null)"
          >
        </div>
      </div>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">File Metadata</h6>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.enableTitle !== false"
          @change="updateConfig('enableTitle', ($event.target as HTMLInputElement).checked)"
        >
        Enable Title Field
      </label>
      <small class="form-text text-muted">
        Allow users to add a custom title for each file.
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.enableDescription !== false"
          @change="updateConfig('enableDescription', ($event.target as HTMLInputElement).checked)"
        >
        Enable Description Field
      </label>
      <small class="form-text text-muted">
        Allow users to add a description for each file.
      </small>
    </div>
  </div>
</template>

