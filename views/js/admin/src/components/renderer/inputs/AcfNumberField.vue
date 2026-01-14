<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: string | number | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const config = computed(() => props.field.config || {})

// Has Addons?
const hasPrefix = computed(() => !!config.value.prefix)
const hasSuffix = computed(() => !!config.value.suffix)
const hasAddons = computed(() => hasPrefix.value || hasSuffix.value)

const updateValue = (e: Event) => {
  emit('update:modelValue', (e.target as HTMLInputElement).value)
}

const validationText = computed(() => {
  const parts: string[] = []
  if (config.value.min !== undefined && config.value.min !== null && String(config.value.min) !== '') parts.push(`Min: ${config.value.min}`)
  if (config.value.max !== undefined && config.value.max !== null && String(config.value.max) !== '') parts.push(`Max: ${config.value.max}`)
  if (config.value.step !== undefined && config.value.step !== null && String(config.value.step) !== '') parts.push(`Step: ${config.value.step}`)
  
  if (parts.length === 0) return ''
  return parts.join(', ') + '.'
})
</script>

<template>
  <div :class="{ 'input-group': hasAddons }">
    <!-- Prefix -->
    <div v-if="hasPrefix" class="input-group-prepend">
      <span class="input-group-text">{{ config.prefix }}</span>
    </div>

    <input
      type="number"
      class="form-control"
      :value="modelValue"
      @input="updateValue"
      :placeholder="config.placeholder || ''"
      :min="config.min"
      :max="config.max"
      :step="config.step"
      :required="field.required"
    />

    <!-- Suffix -->
    <div v-if="hasSuffix" class="input-group-append">
      <span class="input-group-text">{{ config.suffix }}</span>
    </div>
  </div>
  
  <!-- Validation/Config Spec -->
  <small v-if="validationText" class="form-text text-muted">
    {{ validationText }}
  </small>
</template>
