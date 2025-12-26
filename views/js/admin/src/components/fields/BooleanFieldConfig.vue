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
  <div class="boolean-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <div class="ps-switch">
        <input 
          id="boolean-default"
          type="checkbox"
          :checked="config.defaultValue"
          @change="updateConfig('defaultValue', ($event.target as HTMLInputElement).checked)"
        >
        <label for="boolean-default">Default to checked</label>
      </div>
    </div>

    <div class="form-group">
      <label class="form-control-label">True Label</label>
      <input 
        type="text"
        class="form-control"
        :value="config.trueLabel || 'Yes'"
        @input="updateConfig('trueLabel', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Text to display when value is true/checked.
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">False Label</label>
      <input 
        type="text"
        class="form-control"
        :value="config.falseLabel || 'No'"
        @input="updateConfig('falseLabel', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Text to display when value is false/unchecked.
      </small>
    </div>
  </div>
</template>

<style scoped>
/* Global .ps-switch styles from admin.scss */
</style>

