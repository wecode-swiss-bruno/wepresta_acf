<script setup lang="ts">
import { computed } from 'vue'
import AcfFieldWrapper from './AcfFieldWrapper.vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  value: any
  locale?: string
  error?: string
  apiConfig?: any
}>()

const emit = defineEmits<{
  (e: 'update:value', value: any): void
}>()

const fieldValue = computed({
  get: () => props.value,
  set: (val) => emit('update:value', val)
})

const helperText = computed(() => {
  // Try to find config in field.config or direct properties
  // The type AcfField might have config property
  const conf = (props.field as any).config || props.field
  
  if (conf.maxlength) {
    return `Max length: ${conf.maxlength} characters`
  }
  return ''
})
</script>

<template>
  <AcfFieldWrapper 
    :field="field" 
    :locale="locale"
    :error="error"
  >
    <input
      type="text"
      class="form-control"
      :id="`${field.key}_${locale || 'default'}`"
      :name="`${field.name}${locale ? '_' + locale : ''}`"
      v-model="fieldValue"
      :placeholder="field.placeholder"
      :maxlength="field.maxlength"
      :required="field.required"
      :disabled="field.readonly"
    />
    
    <template #append v-if="field.append">
      <div class="input-group-append">
        <span class="input-group-text">{{ field.append }}</span>
      </div>
    </template>
    
    <template #prepend v-if="field.prepend">
      <div class="input-group-prepend">
        <span class="input-group-text">{{ field.prepend }}</span>
      </div>
    </template>

    <template #after-input>
      <small v-if="helperText" class="form-text text-muted">
        {{ helperText }}
      </small>
    </template>
  </AcfFieldWrapper>
</template>
