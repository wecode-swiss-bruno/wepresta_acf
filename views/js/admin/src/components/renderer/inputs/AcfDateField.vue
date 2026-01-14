<script setup lang="ts">
import type { AcfField } from '@/types'

defineProps<{
  field: AcfField
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function onInput(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="acf-date-field">
    <input 
      type="date" 
      class="form-control" 
      :value="modelValue"
      @input="onInput"
    >
  </div>
</template>
