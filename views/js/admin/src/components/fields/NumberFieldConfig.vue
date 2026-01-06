<script setup lang="ts">
import { watch } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { createStringRef, createNumberRef } = useFieldConfig(props, emit)

// Local reactive values using the composable
const placeholder = createStringRef('placeholder')
const min = createNumberRef('min')
const max = createNumberRef('max')
const step = createNumberRef('step')
const prefix = createStringRef('prefix')
const suffix = createStringRef('suffix')

// Validate min/max consistency
watch([min, max], ([newMin, newMax]) => {
  const minVal = newMin === '' ? undefined : Number(newMin)
  const maxVal = newMax === '' ? undefined : Number(newMax)

  if (minVal !== undefined && maxVal !== undefined && minVal >= maxVal) {
    console.warn('[NumberFieldConfig] Warning: min value is greater than or equal to max value')
}
})
</script>

<template>
  <div class="number-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input 
        v-model="placeholder"
        type="text"
        class="form-control"
      >
    </div>

    <div class="form-row">
      <div class="form-group col-4">
        <label class="form-control-label">{{ t('minValue') }}</label>
        <input 
          v-model.number="min"
          type="number"
          class="form-control"
          :max="max || undefined"
        >
        <small class="form-text text-muted">
          Minimum allowed value (optional).
        </small>
      </div>
      <div class="form-group col-4">
        <label class="form-control-label">{{ t('maxValue') }}</label>
        <input 
          v-model.number="max"
          type="number"
          class="form-control"
          :min="min || undefined"
        >
        <small class="form-text text-muted">
          Maximum allowed value (optional).
        </small>
      </div>
      <div class="form-group col-4">
        <label class="form-control-label">{{ t('step') }}</label>
        <input 
          v-model.number="step"
          type="number"
          class="form-control"
          step="any"
          min="0"
        >
        <small class="form-text text-muted">
          Increment step (optional).
        </small>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-6">
        <label class="form-control-label">{{ t('prefix') }}</label>
        <input 
          v-model="prefix"
          type="text"
          class="form-control"
          placeholder="$"
        >
      </div>
      <div class="form-group col-6">
        <label class="form-control-label">{{ t('suffix') }}</label>
        <input 
          v-model="suffix"
          type="text"
          class="form-control"
          placeholder="kg"
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

