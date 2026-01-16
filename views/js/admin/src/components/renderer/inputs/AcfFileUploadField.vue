<script setup lang="ts">
import { ref, computed } from 'vue'
import { useTranslations } from '@/composables/useTranslations'

const props = defineProps<{
  modelValue: any
  fieldSlug: string
  fieldType: 'file' | 'image' | 'video' | 'gallery'
  multiple?: boolean
  accept?: string
  field?: any // Using any to avoid importing AcfField here if circular, but best to import
}>()

const { t } = useTranslations()

const helperText = computed(() => {
  if (!props.field || !props.field.config) return ''
  const conf = props.field.config
  const parts: string[] = []
  
  // Get max size (support multiple naming conventions)
  const maxSizeValue = conf.maxSize || conf.max_size || conf.maxFileSize
  if (maxSizeValue) {
    const sizeMB = parseInt(maxSizeValue)
    if (!isNaN(sizeMB) && sizeMB > 0) {
       parts.push(t('maxSize', undefined, { size: sizeMB }))
    }
  }
  
  // Get allowed types/formats (for files)
  const allowedTypes = conf.allowedTypes || conf.allowed_types
  if (allowedTypes) {
    let types: string[] = []
    if (Array.isArray(allowedTypes)) {
      types = allowedTypes
    } else if (typeof allowedTypes === 'string') {
      types = allowedTypes.split(',').map(type => type.trim())
    }
    if (types.length > 0) {
      parts.push(t('types', undefined, { types: types.join(', ').toUpperCase() }))
    }
  }
  
  // Get allowed formats (for images/videos)
  const allowedFormats = conf.allowedFormats || conf.allowed_formats
  if (allowedFormats) {
    let formats: string[] = []
    if (Array.isArray(allowedFormats)) {
      formats = allowedFormats
    } else if (typeof allowedFormats === 'object') {
      // Object format like {jpeg: true, png: true}
      formats = Object.keys(allowedFormats).filter(k => allowedFormats[k])
    } else if (typeof allowedFormats === 'string') {
      formats = allowedFormats.split(',').map(f => f.trim())
    }
    if (formats.length > 0) {
      parts.push(t('formats', undefined, { formats: formats.join(', ').toUpperCase() }))
    }
  }
  
  // Get max dimensions (for images)
  const maxWidth = conf.maxWidth || conf.max_width
  const maxHeight = conf.maxHeight || conf.max_height
  if (maxWidth || maxHeight) {
    parts.push(t('maxWidthHeight', undefined, { 
      width: (maxWidth as string | number) || '?', 
      height: (maxHeight as string | number) || '?' 
    }))
  }
  
  // Min dimensions (for images)
  const minWidth = conf.minWidth || conf.min_width
  const minHeight = conf.minHeight || conf.min_height
  if (minWidth || minHeight) {
    parts.push(t('minWidthHeight', undefined, { 
      width: (minWidth as string | number) || '?', 
      height: (minHeight as string | number) || '?' 
    }))
  }
  
  return parts.join(' | ')
})

// Get max file size in bytes for validation
const maxFileSizeBytes = computed(() => {
  if (!props.field?.config) return 0
  const conf = props.field.config
  const maxMB = parseInt(conf.maxSize || conf.max_size || '0')
  return maxMB * 1024 * 1024 // Convert MB to bytes
})

// Get allowed extensions
const allowedExtensions = computed(() => {
  if (!props.field?.config) return []
  const conf = props.field.config
  const allowedTypes = conf.allowedTypes || conf.allowed_types
  
  if (!allowedTypes) return []
  
  if (Array.isArray(allowedTypes)) {
    return allowedTypes.map(t => t.toLowerCase())
  }
  if (typeof allowedTypes === 'string') {
    return allowedTypes.split(',').map(ext => ext.trim().toLowerCase())
  }
  return []
})

// Validate file before upload
const validateFile = (file: File): string | null => {
  // Check file size
  if (maxFileSizeBytes.value > 0 && file.size > maxFileSizeBytes.value) {
    const maxMB = maxFileSizeBytes.value / 1024 / 1024
    const fileMB = (file.size / 1024 / 1024).toFixed(2)
    return t('fileTooLarge', undefined, { size: fileMB, max: maxMB })
  }
  
  // Check file extension
  if (allowedExtensions.value.length > 0) {
    const extension = file.name.split('.').pop()?.toLowerCase() || ''
    if (!allowedExtensions.value.includes(extension) && !allowedExtensions.value.includes('.' + extension)) {
      return t('fileTypeNotAllowed', undefined, { 
        ext: extension, 
        types: allowedExtensions.value.join(', ') 
      })
    }
  }
  
  return null // Valid
}

const emit = defineEmits<{
  'update:modelValue': [value: any]
}>()

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
  
  // Validate file before upload
  const validationError = validateFile(file)
  if (validationError) {
    alert(validationError)
    if (input) input.value = ''
    return
  }
  
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
    alert(t('uploadFailed'))
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

// Check allowed input methods
const allowedInputMethods = computed(() => {
  const conf = props.field?.config || {}
  const methods = conf.inputMethods || conf.input_methods || {}
  
  // Default to upload if nothing specified
  if (Object.keys(methods).length === 0) {
    return { upload: true, url: false, externalLink: false, attachment: false }
  }
  
  return {
    upload: methods.upload ?? methods.directUpload ?? false,
    url: methods.url ?? methods.importUrl ?? methods.importFromUrl ?? false,
    externalLink: methods.externalLink ?? methods.external_link ?? false,
    attachment: methods.attachment ?? methods.psAttachment ?? false
  }
})

// Check if at least one input method
const hasUploadMethod = computed(() => allowedInputMethods.value.upload)
const hasUrlMethod = computed(() => allowedInputMethods.value.url || allowedInputMethods.value.externalLink)

// URL input handling
const urlInput = ref('')
const showUrlInput = ref(false)

function toggleUrlInput(): void {
  showUrlInput.value = !showUrlInput.value
}

function submitUrl(): void {
  if (!urlInput.value.trim()) return
  
  emit('update:modelValue', {
    url: urlInput.value.trim(),
    type: 'external',
    filename: urlInput.value.split('/').pop() || 'external-file'
  })
  
  urlInput.value = ''
  showUrlInput.value = false
}
</script>

<template>
  <div class="file-upload-field">
    <!-- No file yet - show input methods -->
    <div v-if="!hasFile && !uploading" class="upload-button-container">
      <!-- Upload button (if allowed) -->
      <button
        v-if="hasUploadMethod"
        type="button"
        class="btn btn-outline-primary me-2"
        @click="triggerFileInput"
      >
        <i class="material-icons mr-2">{{ fileIcon }}</i>
        {{ t('uploadFieldType', undefined, { type: fieldType }) }}
      </button>
      <input
        ref="fileInputRef"
        type="file"
        :accept="acceptAttr"
        :multiple="multiple"
        style="display: none"
        @change="handleFileSelect"
      >
      
      <!-- URL button (if allowed) -->
      <button
        v-if="hasUrlMethod && !showUrlInput"
        type="button"
        class="btn btn-outline-secondary"
        @click="toggleUrlInput"
      >
        <i class="material-icons mr-2">link</i>
        {{ t('url') }}
      </button>
      
      <!-- URL input field -->
      <div v-if="showUrlInput" class="url-input-group mt-2">
        <div class="input-group">
          <input
            v-model="urlInput"
            type="url"
            class="form-control"
            placeholder="https://example.com/image.jpg"
            @keyup.enter="submitUrl"
          >
          <button type="button" class="btn btn-primary" @click="submitUrl">
            {{ t('ok') }}
          </button>
          <button type="button" class="btn btn-outline-secondary" @click="toggleUrlInput">
            ✕
          </button>
        </div>
      </div>
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
      <small class="text-muted">{{ t('uploading') }}</small>
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
          :title="t('viewFile')"
        >
          <i class="material-icons">visibility</i>
        </a>
        <button
          type="button"
          class="btn btn-sm btn-outline-danger"
          @click="removeFile"
          :title="t('removeFile')"
        >
          <i class="material-icons">delete</i>
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline-primary"
          @click="triggerFileInput"
          :title="t('replaceFile')"
        >
          <i class="material-icons">swap_horiz</i>
        </button>
      </div>
    </div>
    
    <!-- Helper text -->
    <small v-if="helperText" class="form-text text-muted mt-1">
      {{ helperText }}
    </small>
    <small v-else-if="fieldType" class="form-text text-muted mt-1">
      {{ fieldType === 'image' ? t('acceptedFormatsImage') : 
         fieldType === 'video' ? t('acceptedFormatsVideo') : 
         t('clickToSelectFile') }}
    </small>
  </div>
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

