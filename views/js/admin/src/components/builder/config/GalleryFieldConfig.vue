<script setup lang="ts">
import { computed } from 'vue'
import type { FieldConfig } from '@/types'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

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
  <div class="gallery-field-config">
    <div class="form-group">
      <label class="form-control-label">Allowed Formats</label>
      <div class="acf-format-checkboxes">
        <div 
          v-for="option in formatOptions" 
          :key="option.value"
          class="form-check form-check-inline"
        >
          <input 
            type="checkbox"
            class="form-check-input"
            :id="'gallery-format-' + option.value"
            :checked="isFormatSelected(option.value)"
            @change="toggleFormat(option.value)"
          >
          <label class="form-check-label" :for="'gallery-format-' + option.value">
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
        max="20"
        :value="config.maxSizeMB || 5"
        @input="updateConfig('maxSizeMB', parseInt(($event.target as HTMLInputElement).value) || 5)"
      >
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">Minimum Images</label>
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
          <label class="form-control-label">Maximum Images</label>
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
    <h6 class="text-muted mb-3">Image Metadata</h6>

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
        Allow users to add a custom title (alt text) for each image.
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
        Allow users to add a caption/description for each image.
      </small>
    </div>
  </div>
</template>

