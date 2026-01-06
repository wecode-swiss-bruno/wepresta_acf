<script setup lang="ts">
import { ref, watch } from 'vue'
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

// Local reactive values for v-model binding
const placeholder = ref(props.config?.placeholder || '')
const min = ref(props.config?.min ?? '')
const max = ref(props.config?.max ?? '')
const step = ref(props.config?.step ?? '')
const prefix = ref(props.config?.prefix || '')
const suffix = ref(props.config?.suffix || '')

// Watch for prop changes to sync local values
watch(() => props.config?.placeholder, (newVal) => {
  placeholder.value = newVal || ''
})

watch(() => props.config?.min, (newVal) => {
  min.value = (newVal !== null && newVal !== undefined) ? String(newVal) : ''
})

watch(() => props.config?.max, (newVal) => {
  max.value = (newVal !== null && newVal !== undefined) ? String(newVal) : ''
})

watch(() => props.config?.step, (newVal) => {
  step.value = (newVal !== null && newVal !== undefined) ? String(newVal) : ''
})

watch(() => props.config?.prefix, (newVal) => {
  prefix.value = newVal || ''
})

watch(() => props.config?.suffix, (newVal) => {
  suffix.value = newVal || ''
})

// Watch local values to emit updates
watch(placeholder, (newVal) => {
  updateConfig('placeholder', newVal || undefined)
})

watch(min, (newVal) => {
  const numVal = newVal === '' ? undefined : Number(newVal)
  updateConfig('min', numVal)
})

watch(max, (newVal) => {
  const numVal = newVal === '' ? undefined : Number(newVal)
  updateConfig('max', numVal)
})

watch(step, (newVal) => {
  updateConfig('step', newVal === '' ? undefined : Number(newVal))
})

watch(prefix, (newVal) => {
  updateConfig('prefix', newVal || undefined)
})

watch(suffix, (newVal) => {
  updateConfig('suffix', newVal || undefined)
})

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

