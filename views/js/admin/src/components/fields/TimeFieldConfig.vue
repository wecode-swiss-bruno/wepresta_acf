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
  <div class="time-field-config">
    <div class="form-group">
      <label class="form-control-label">Time Format</label>
      <select
        class="form-control"
        :value="config.format || '24h'"
        @change="updateConfig('format', ($event.target as HTMLSelectElement).value)"
      >
        <option value="24h">24 Hour (14:30)</option>
        <option value="12h">12 Hour (2:30 PM)</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">Minute Step</label>
      <select
        class="form-control"
        :value="config.step || 1"
        @change="updateConfig('step', parseInt(($event.target as HTMLSelectElement).value))"
      >
        <option value="1">1 minute</option>
        <option value="5">5 minutes</option>
        <option value="15">15 minutes</option>
        <option value="30">30 minutes</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        type="time"
        class="form-control"
        :value="config.defaultValue"
        @input="updateConfig('defaultValue', ($event.target as HTMLInputElement).value)"
      >
    </div>
  </div>
</template>

