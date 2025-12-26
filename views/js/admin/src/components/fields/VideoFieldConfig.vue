<script setup lang="ts">
import { computed } from 'vue'
import type { FieldConfig } from '@/types'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}

// Count enabled sources
const enabledSourcesCount = computed(() => {
  let count = 0
  if (props.config.allowYouTube !== false) count++
  if (props.config.allowVimeo !== false) count++
  if (props.config.allowUpload !== false) count++
  if (props.config.allowUrl !== false) count++
  return count
})
</script>

<template>
  <div class="video-field-config">
    <h6 class="text-muted mb-3">Video Sources</h6>
    
    <div class="form-group">
      <div class="acf-source-checkboxes">
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="video-youtube"
            :checked="config.allowYouTube !== false"
            @change="updateConfig('allowYouTube', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="video-youtube">
            <strong>YouTube</strong> - YouTube video URLs
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="video-vimeo"
            :checked="config.allowVimeo !== false"
            @change="updateConfig('allowVimeo', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="video-vimeo">
            <strong>Vimeo</strong> - Vimeo video URLs
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="video-upload"
            :checked="config.allowUpload !== false"
            @change="updateConfig('allowUpload', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="video-upload">
            <strong>Upload</strong> - Direct video file upload (MP4, WebM, OGG)
          </label>
        </div>
        <div class="form-check">
          <input 
            type="checkbox"
            class="form-check-input"
            id="video-url"
            :checked="config.allowUrl !== false"
            @change="updateConfig('allowUrl', ($event.target as HTMLInputElement).checked)"
          >
          <label class="form-check-label" for="video-url">
            <strong>Video URL</strong> - Direct link to video file
          </label>
        </div>
      </div>
      <small class="form-text text-muted">
        Select which video sources are allowed. At least one must be enabled.
      </small>
    </div>

    <div class="form-group" v-if="config.allowUpload !== false">
      <label class="form-control-label">Max File Size (MB)</label>
      <input 
        type="number"
        class="form-control"
        min="1"
        max="500"
        :value="config.maxSizeMB || 100"
        @input="updateConfig('maxSizeMB', parseInt(($event.target as HTMLInputElement).value) || 100)"
      >
      <small class="form-text text-muted">
        Maximum file size for uploaded videos.
      </small>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">Video Metadata</h6>

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
        Allow users to add a custom title for the video.
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
        Allow users to add a description for the video.
      </small>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">Display Options</h6>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.coverPoster === true"
          @change="updateConfig('coverPoster', ($event.target as HTMLInputElement).checked)"
        >
        Cover Poster
      </label>
      <small class="form-text text-muted">
        Use object-fit: cover for poster image (fills the video area, may crop edges).
      </small>
    </div>
  </div>
</template>

