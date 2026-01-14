<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: any
  locale?: string
  error?: string
  apiConfig?: any
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
}>()

const fieldValue = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const config = computed(() => props.field.config || {})
const validation = computed(() => props.field.validation || {})

const helperText = computed(() => {
  if (validation.value.maxLength) {
    return `Max length: ${validation.value.maxLength} characters`
  }
  return ''
})

const hasPrefix = computed(() => !!config.value.prefix)
const hasSuffix = computed(() => !!config.value.suffix)
const hasAddons = computed(() => hasPrefix.value || hasSuffix.value)
</script>

<template>
  <div :class="{ 'input-group': hasAddons }">
    <!-- Prefix -->
    <div v-if="hasPrefix" class="input-group-prepend">
      <span class="input-group-text">{{ config.prefix }}</span>
    </div>

    <input
      :type="field.type === 'password' ? 'password' : 'text'"
      class="form-control"
      :id="`${field.slug}_${locale || 'default'}`"
      :name="`${field.slug}${locale ? '_' + locale : ''}`"
      v-model="fieldValue"
      :placeholder="config.placeholder || ''"
      :maxlength="(validation.maxLength as number) || undefined"
      :required="!!validation.required"
      :disabled="!!field.config.readonly"
    />

    <!-- Suffix -->
    <div v-if="hasSuffix" class="input-group-append">
      <span class="input-group-text">{{ config.suffix }}</span>
    </div>
  </div>

  <!-- Helper Text -->
  <small v-if="helperText" class="form-text text-muted mt-1">
    {{ helperText }}
  </small>
</template>
