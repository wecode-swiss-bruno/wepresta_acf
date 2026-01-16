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
  <div class="richtext-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('editorHeight') }}</label>
      <input 
        type="number"
        class="form-control"
        min="5"
        max="30"
        :value="config.rows || 10"
        @input="updateConfig('rows', parseInt(($event.target as HTMLInputElement).value) || 10)"
      >
      <small class="form-text text-muted">
        {{ t('editorHeightHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('toolbarStyle') }}</label>
      <select
        class="form-control"
        :value="config.toolbar || 'standard'"
        @change="updateConfig('toolbar', ($event.target as HTMLSelectElement).value)"
      >
        <option value="basic">{{ t('toolbarBasic') }}</option>
        <option value="standard">{{ t('toolbarStandard') }}</option>
        <option value="full">{{ t('toolbarFull') }}</option>
      </select>
      <small class="form-text text-muted">
        {{ t('toolbarHelp') }}
      </small>
    </div>

    <div class="alert alert-info mt-3">
      <span class="material-icons" style="vertical-align: middle;">info</span>
      <strong>{{ t('securityNote') }}</strong> {{ t('securityNoteContent') }}
    </div>
  </div>
</template>

