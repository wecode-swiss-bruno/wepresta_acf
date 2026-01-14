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
  <div class="acf-time-field">
    <input 
      type="time" 
      class="form-control" 
      :value="modelValue"
      @input="onInput"
    >
  </div>
</template>
