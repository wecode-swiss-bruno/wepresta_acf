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
  <div class="color-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <div class="acf-color-input-group">
        <input 
          type="color"
          class="form-control acf-color-picker"
          :value="config.defaultValue || '#000000'"
          @input="updateConfig('defaultValue', ($event.target as HTMLInputElement).value)"
        >
        <input 
          type="text"
          class="form-control"
          :value="config.defaultValue || '#000000'"
          pattern="#[0-9A-Fa-f]{6}"
          maxlength="7"
          @input="updateConfig('defaultValue', ($event.target as HTMLInputElement).value)"
        >
      </div>
    </div>

    <div class="form-group">
      <div class="form-check">
        <input 
          type="checkbox"
          class="form-check-input"
          id="showHex"
          :checked="config.showHex !== false"
          @change="updateConfig('showHex', ($event.target as HTMLInputElement).checked)"
        >
        <label class="form-check-label" for="showHex">
          Show Hex Value
        </label>
      </div>
      <small class="form-text text-muted">
        Display the hex code alongside the color swatch on the frontend.
      </small>
    </div>
  </div>
</template>

<style scoped>
.acf-color-input-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.acf-color-picker {
  width: 60px;
  height: 38px;
  padding: 2px;
  cursor: pointer;
}
</style>

