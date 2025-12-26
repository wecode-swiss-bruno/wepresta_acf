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
  <div class="textarea-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.placeholder"
        @input="updateConfig('placeholder', ($event.target as HTMLInputElement).value)"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">Rows</label>
      <input 
        type="number"
        class="form-control"
        min="2"
        max="20"
        :value="config.rows || 4"
        @input="updateConfig('rows', parseInt(($event.target as HTMLInputElement).value) || 4)"
      >
      <small class="form-text text-muted">
        Number of visible text lines (2-20).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <textarea
        class="form-control"
        rows="3"
        :value="config.defaultValue"
        @input="updateConfig('defaultValue', ($event.target as HTMLTextAreaElement).value)"
      />
    </div>
  </div>
</template>

