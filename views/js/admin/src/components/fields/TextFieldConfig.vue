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
  <div class="text-field-config">
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
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.defaultValue"
        @input="updateConfig('defaultValue', ($event.target as HTMLInputElement).value)"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('prefix') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.prefix"
        @input="updateConfig('prefix', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Text to display before the field value.
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('suffix') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.suffix"
        @input="updateConfig('suffix', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Text to display after the field value.
      </small>
    </div>
  </div>
</template>

