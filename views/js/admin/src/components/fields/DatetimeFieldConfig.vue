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
  <div class="datetime-field-config">
    <div class="form-group">
      <label class="form-control-label">Minimum Date/Time</label>
      <input 
        type="datetime-local"
        class="form-control"
        :value="config.minDate"
        @input="updateConfig('minDate', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Earliest selectable date and time (optional).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">Maximum Date/Time</label>
      <input 
        type="datetime-local"
        class="form-control"
        :value="config.maxDate"
        @input="updateConfig('maxDate', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Latest selectable date and time (optional).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        type="datetime-local"
        class="form-control"
        :value="config.defaultValue"
        @input="updateConfig('defaultValue', ($event.target as HTMLInputElement).value)"
      >
    </div>
  </div>
</template>

