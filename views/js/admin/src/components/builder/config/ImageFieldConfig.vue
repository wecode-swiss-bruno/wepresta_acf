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
          <label class="form-control-label">Max Width (px)</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.maxWidth || ''"
            placeholder="No limit"
            @input="updateConfig('maxWidth', parseInt(($event.target as HTMLInputElement).value) || null)"
          >
          <small class="form-text text-muted">
            Images larger will be resized.
          </small>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">Max Height (px)</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.maxHeight || ''"
            placeholder="No limit"
            @input="updateConfig('maxHeight', parseInt(($event.target as HTMLInputElement).value) || null)"
          >
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
        When enabled, images use a predictable path - better for caching. 
        When disabled, images get unique names - avoids browser cache issues after delete/re-upload.
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
            id="img-method-upload"
            :checked="config.allowUpload !== false"
            @change="updateConfig('allowUpload', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="img-method-upload">
            <strong>Upload</strong> - Direct image upload from computer
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
            <strong>Import from URL</strong> - Download image from URL and store locally
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
            <strong>External Link</strong> - Reference URL without downloading
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
    <h6 class="text-muted mb-3">Image Metadata Options</h6>

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
        Allow users to add a custom title (alt text) for the image.
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
        Allow users to add a caption/description for the image.
      </small>
    </div>
  </div>
</template>

