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
  <div class="acf-color-field">
    <div class="input-group">
      <input 
        type="color" 
        class="form-control form-control-color" 
        :value="modelValue || '#000000'"
        title="Choose your color"
        @input="onInput"
        style="max-width: 60px; padding: 5px; height: 38px;"
      >
      <input 
        type="text" 
        class="form-control" 
        :value="modelValue || '#000000'"
        @input="onInput"
        maxlength="7"
      >
    </div>
  </div>
</template>

<style scoped>
.form-control-color {
  width: 3rem; 
}
</style>
