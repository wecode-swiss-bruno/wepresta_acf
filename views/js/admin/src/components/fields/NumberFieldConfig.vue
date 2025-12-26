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
  <div class="number-field-config">
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
        type="number"
        class="form-control"
        :value="config.defaultValue"
        @input="updateConfig('defaultValue', Number(($event.target as HTMLInputElement).value))"
      >
    </div>

    <div class="form-row">
      <div class="form-group col-4">
        <label class="form-control-label">{{ t('minValue') }}</label>
        <input 
          type="number"
          class="form-control"
          :value="config.min"
          @input="updateConfig('min', Number(($event.target as HTMLInputElement).value))"
        >
      </div>
      <div class="form-group col-4">
        <label class="form-control-label">{{ t('maxValue') }}</label>
        <input 
          type="number"
          class="form-control"
          :value="config.max"
          @input="updateConfig('max', Number(($event.target as HTMLInputElement).value))"
        >
      </div>
      <div class="form-group col-4">
        <label class="form-control-label">{{ t('step') }}</label>
        <input 
          type="number"
          class="form-control"
          :value="config.step"
          step="any"
          @input="updateConfig('step', Number(($event.target as HTMLInputElement).value))"
        >
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-6">
        <label class="form-control-label">{{ t('prefix') }}</label>
        <input 
          type="text"
          class="form-control"
          :value="config.prefix"
          placeholder="$"
          @input="updateConfig('prefix', ($event.target as HTMLInputElement).value)"
        >
      </div>
      <div class="form-group col-6">
        <label class="form-control-label">{{ t('suffix') }}</label>
        <input 
          type="text"
          class="form-control"
          :value="config.suffix"
          placeholder="kg"
          @input="updateConfig('suffix', ($event.target as HTMLInputElement).value)"
        >
      </div>
    </div>
  </div>
</template>

<style scoped>
.form-row {
  display: flex;
  margin: 0 -0.5rem;
}

.form-row .form-group {
  padding: 0 0.5rem;
}

.col-4 {
  flex: 0 0 33.333%;
  max-width: 33.333%;
}

.col-6 {
  flex: 0 0 50%;
  max-width: 50%;
}
</style>

