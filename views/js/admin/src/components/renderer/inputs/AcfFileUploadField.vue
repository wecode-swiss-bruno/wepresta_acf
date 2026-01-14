<script setup lang="ts">
import { ref, computed } from 'vue'
import { useApi } from '@/composables/useApi'

const props = defineProps<{
  modelValue: any
  fieldSlug: string
  fieldType: 'file' | 'image' | 'video' | 'gallery'
  multiple?: boolean
  accept?: string
  field?: any // Using any to avoid importing AcfField here if circular, but best to import
}>()

const helperText = computed(() => {
  if (!props.field || !props.field.config) return ''
  const conf = props.field.config
  const parts = []
  
  if (conf.max_size) {
    // Format size (assuming bytes or MB?) usually stored as bytes or numeric
    const size = parseInt(conf.max_size)
    if (!isNaN(size)) {
       const sizeMb = (size / 1024 / 1024).toFixed(2)
       parts.push(`Max size: ${sizeMb} MB`)
    }
  }
  
  if (conf.allowed_types) {
     // If it's an array or string
     const types = Array.isArray(conf.allowed_types) ? conf.allowed_types.join(', ') : conf.allowed_types
     if (types) parts.push(`Types: ${types}`)
  }
  
  return parts.join(' | ')
})

const emit = defineEmits<{
  'update:modelValue': [value: any]
}>()

const api = useApi()
const uploading = ref(false)
const uploadProgress = ref(0)
const fileInputRef = ref<HTMLInputElement | null>(null)

// Get file data from value
const fileData = computed(() => {
  if (!props.modelValue) return null
  
  // Value can be JSON string or object
  if (typeof props.modelValue === 'string') {
    try {
      return JSON.parse(props.modelValue)
    } catch {
      return null
    }
  }
  
  return props.modelValue
})

// Check if file exists
const hasFile = computed(() => {
  return fileData.value && (fileData.value.filename || fileData.value.url)
})

// Get display filename
const displayFilename = computed(() => {
  if (!fileData.value) return ''
  return fileData.value.original_name || fileData.value.filename || fileData.value.url || ''
})

// Get file URL for preview
const fileUrl = computed(() => {
  if (!fileData.value) return ''
  
  // If it's an external URL
  if (fileData.value.url) {
    return fileData.value.url
  }
  
  // If it's an uploaded file
  if (fileData.value.filename) {
    const baseUrl = window.location.origin
    return `${baseUrl}/modules/wepresta_acf/uploads/files/${fileData.value.filename}`
  }
  
  return ''
})

// Trigger file input
function triggerFileInput(): void {
  fileInputRef.value?.click()
}

// Handle file selection
async function handleFileSelect(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  
  if (!file) return
  
  uploading.value = true
  uploadProgress.value = 0
  
  try {
    // Create FormData
    const formData = new FormData()
    formData.append('file', file)
    formData.append('field_slug', props.fieldSlug)
    formData.append('entity_type', 'global') // Mark as global upload
    formData.append('entity_id', '0')
    
    // Upload via API (you'll need to create this endpoint)
    const response = await uploadFile(formData, (progress) => {
      uploadProgress.value = progress
    })
    
    // Emit the file data
    emit('update:modelValue', response.data)
    
  } catch (error) {
    console.error('Upload failed:', error)
    alert('Failed to upload file. Please try again.')
  } finally {
    uploading.value = false
    uploadProgress.value = 0
    // Reset input
    if (input) input.value = ''
  }
}

// Upload file with progress
async function uploadFile(formData: FormData, onProgress: (progress: number) => void): Promise<any> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest()
    
    // Progress tracking
    xhr.upload.addEventListener('progress', (e) => {
      if (e.lengthComputable) {
        const percentComplete = (e.loaded / e.total) * 100
        onProgress(percentComplete)
      }
    })
    
    // Success
    xhr.addEventListener('load', () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const response = JSON.parse(xhr.responseText)
          resolve(response)
        } catch {
          reject(new Error('Invalid response'))
        }
      } else {
        reject(new Error(`Upload failed: ${xhr.statusText}`))
      }
    })
    
    // Error
    xhr.addEventListener('error', () => {
      reject(new Error('Network error'))
    })
    
    // Send request
    const apiUrl = window.acfConfig?.apiUrl || ''
    const token = window.acfConfig?.token || ''
    xhr.open('POST', `${apiUrl}/upload-file?_token=${token}`)
    xhr.send(formData)
  })
}

// Remove file
function removeFile(): void {
  emit('update:modelValue', null)
}

// Get accept attribute
const acceptAttr = computed(() => {
  if (props.accept) return props.accept
  
  switch (props.fieldType) {
    case 'image':
    case 'gallery':
      return 'image/*'
    case 'video':
      return 'video/*'
    case 'file':
    default:
      return '*/*'
  }
})

// Get icon based on file type
const fileIcon = computed(() => {
  if (props.fieldType === 'image' || props.fieldType === 'gallery') return 'image'
  if (props.fieldType === 'video') return 'video_library'
  return 'insert_drive_file'
})
</script>

<template>
  <div class="file-upload-field">
    <!-- Upload button (no file) -->
    <div v-if="!hasFile && !uploading" class="upload-button-container">
      <button
        type="button"
        class="btn btn-outline-primary"
        @click="triggerFileInput"
      >
        <i class="material-icons mr-2">{{ fileIcon }}</i>
        Upload {{ fieldType }}
      </button>
      <input
        ref="fileInputRef"
        type="file"
        :accept="acceptAttr"
        :multiple="multiple"
        style="display: none"
        @change="handleFileSelect"
      >
    </div>

    <!-- Uploading progress -->
    <div v-if="uploading" class="upload-progress">
      <div class="progress">
        <div
          class="progress-bar progress-bar-striped progress-bar-animated"
          :style="{ width: uploadProgress + '%' }"
        >
          {{ Math.round(uploadProgress) }}%
        </div>
      </div>
      <small class="text-muted">Uploading...</small>
    </div>

    <!-- File preview -->
    <div v-if="hasFile && !uploading" class="file-preview">
      <!-- Image preview -->
      <div v-if="fieldType === 'image'" class="image-preview">
        <img :src="fileUrl" :alt="displayFilename" class="preview-image">
      </div>

      <!-- Video preview -->
      <div v-else-if="fieldType === 'video'" class="video-preview">
        <video :src="fileUrl" controls class="preview-video"></video>
      </div>

      <!-- File info -->
      <div v-else class="file-info">
        <i class="material-icons">{{ fileIcon }}</i>
        <span class="filename">{{ displayFilename }}</span>
      </div>

      <!-- Actions -->
      <div class="file-actions">
        <a
          v-if="fileUrl"
          :href="fileUrl"
          target="_blank"
          class="btn btn-sm btn-outline-secondary"
          title="View file"
        >
          <i class="material-icons">visibility</i>
        </a>
        <button
          type="button"
          class="btn btn-sm btn-outline-danger"
          @click="removeFile"
          title="Remove file"
        >
          <i class="material-icons">delete</i>
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline-primary"
          @click="triggerFileInput"
          title="Replace file"
        >
          <i class="material-icons">swap_horiz</i>
        </button>
      </div>
    </div>
  </div>
  <small v-if="helperText" class="form-text text-muted mt-1">
    {{ helperText }}
  </small>
</template>

<style scoped>
.file-upload-field {
  width: 100%;
}

.upload-button-container {
  padding: 1rem;
  border: 2px dashed #dee2e6;
  border-radius: 0.375rem;
  text-align: center;
  background: #f8f9fa;
  transition: all 0.2s;
}

.upload-button-container:hover {
  border-color: #007bff;
  background: #e7f3ff;
}

.btn .material-icons {
  font-size: 18px;
  vertical-align: middle;
}

.mr-2 {
  margin-right: 0.5rem;
}

.upload-progress {
  padding: 1rem;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
}

.progress {
  height: 25px;
  margin-bottom: 0.5rem;
}

.file-preview {
  padding: 1rem;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  background: white;
}

.image-preview {
  margin-bottom: 1rem;
}

.preview-image {
  max-width: 100%;
  max-height: 300px;
  border-radius: 0.25rem;
  display: block;
  margin: 0 auto;
}

.video-preview {
  margin-bottom: 1rem;
}

.preview-video {
  max-width: 100%;
  max-height: 400px;
  border-radius: 0.25rem;
  display: block;
  margin: 0 auto;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 0.25rem;
  margin-bottom: 1rem;
}

.file-info .material-icons {
  color: #6c757d;
  font-size: 24px;
}

.filename {
  font-weight: 500;
  color: #495057;
  word-break: break-all;
}

.file-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.file-actions .btn {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.file-actions .material-icons {
  font-size: 16px;
}
</style>

