<script setup lang="ts">
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
  <div class="url-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.placeholder || 'https://example.com'"
        @input="updateConfig('placeholder', ($event.target as HTMLInputElement).value)"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">Link Target</label>
      <select
        class="form-control"
        :value="config.target || '_blank'"
        @change="updateConfig('target', ($event.target as HTMLSelectElement).value)"
      >
        <option value="_blank">New Tab (_blank)</option>
        <option value="_self">Same Tab (_self)</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">Link Text</label>
      <input 
        type="text"
        class="form-control"
        :value="config.linkText"
        @input="updateConfig('linkText', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Custom text to display instead of the URL (optional).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        type="url"
        class="form-control"
        :value="config.defaultValue"
        @input="updateConfig('defaultValue', ($event.target as HTMLInputElement).value)"
      >
    </div>
  </div>
</template>

