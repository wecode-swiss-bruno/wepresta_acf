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

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}
</script>

<template>
  <div class="video-field-config">
    <h6 class="text-muted mb-3">{{ t('videoSources') }}</h6>
    
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
            <strong>{{ t('videoSourceYouTube') }}</strong> - {{ t('videoSourceYouTubeHelp') }}
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
            <strong>{{ t('videoSourceVimeo') }}</strong> - {{ t('videoSourceVimeoHelp') }}
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
            <strong>{{ t('inputMethodUpload') }}</strong> - {{ t('videoSourceUploadHelp') }}
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
            <strong>{{ t('videoSourceUrl') }}</strong> - {{ t('videoSourceUrlHelp') }}
          </label>
        </div>
      </div>
      <small class="form-text text-muted">
        {{ t('videoSourcesHelp') }}
      </small>
    </div>

    <div class="form-group" v-if="config.allowUpload !== false">
      <label class="form-control-label">{{ t('maxFileSize') }}</label>
      <input 
        type="number"
        class="form-control"
        min="1"
        max="500"
        :value="config.maxSizeMB || 100"
        @input="updateConfig('maxSizeMB', parseInt(($event.target as HTMLInputElement).value) || 100)"
      >
      <small class="form-text text-muted">
        {{ t('videoMaxFileSizeHelp') }}
      </small>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">{{ t('videoMetadata') }}</h6>

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

    <hr class="my-3">
    <h6 class="text-muted mb-3">{{ t('displayOptions') }}</h6>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.coverPoster === true"
          @change="updateConfig('coverPoster', ($event.target as HTMLInputElement).checked)"
        >
        {{ t('coverPoster') }}
      </label>
      <small class="form-text text-muted">
        {{ t('coverPosterHelp') }}
      </small>
    </div>
  </div>
</template>

