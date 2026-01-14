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

const config = computed(() => (props.field.config || {}) as any)

const updateValue = (e: Event) => {
  emit('update:modelValue', (e.target as HTMLTextAreaElement).value)
}

const validationText = computed(() => {
  const parts: string[] = []
  if (config.value.maxlength) parts.push(`Max length: ${config.value.maxlength}`)
  if (config.value.rows) parts.push(`Rows: ${config.value.rows}`)
  
  if (parts.length === 0) return ''
  return parts.join(', ') + '.'
})
</script>

<template>
  <textarea
    class="form-control"
    :value="modelValue"
    @input="updateValue"
    :rows="config.rows || 4"
    :placeholder="config.placeholder || ''"
    :required="field.required"
    :maxlength="config.maxlength || undefined"
  ></textarea>
  <small v-if="validationText" class="form-text text-muted">
    {{ validationText }}
  </small>
</template>
