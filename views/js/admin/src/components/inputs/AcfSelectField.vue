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

// Parse choices logic (reused from previous renderer)
const parsedChoices = computed(() => {
  const choices = config.value.choices
  if (!choices) return []

  if (Array.isArray(choices)) {
    return choices.map((item: any) => {
      if (typeof item === 'object' && item !== null) {
        return { value: item.value || '', label: item.value || '' } 
      }
      return { value: String(item), label: String(item) }
    })
  }

  if (typeof choices === 'string') {
    return choices
      .split('\n')
      .map((line: string) => line.trim())
      .filter((line: string) => line.length > 0)
      .map((line: string) => {
        const parts = line.split(':').map((p: string) => p.trim())
        return {
          value: parts[0] || '',
          label: parts[1] || parts[0] || '',
        }
      })
  }
  return []
})

const updateValue = (e: Event) => {
  emit('update:modelValue', (e.target as HTMLSelectElement).value)
}
</script>

<template>
  <select
    class="form-control"
    :value="modelValue"
    @change="updateValue"
    :required="field.required"
    :multiple="!!config.multiple"
  >
    <option v-if="config.allow_null || !field.required" value="">
      -- {{ config.placeholder || 'Select' }} --
    </option>
    <option v-for="choice in parsedChoices" :key="choice.value" :value="choice.value">
      {{ choice.label }}
    </option>
  </select>
</template>
