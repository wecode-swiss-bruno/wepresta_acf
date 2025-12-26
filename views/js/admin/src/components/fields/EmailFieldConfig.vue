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
  <div class="email-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.placeholder || 'email@example.com'"
        @input="updateConfig('placeholder', ($event.target as HTMLInputElement).value)"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        type="email"
        class="form-control"
        :value="config.defaultValue"
        @input="updateConfig('defaultValue', ($event.target as HTMLInputElement).value)"
      >
    </div>
  </div>
</template>

