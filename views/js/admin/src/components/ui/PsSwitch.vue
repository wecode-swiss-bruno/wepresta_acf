<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  modelValue: boolean
  id: string
  labelOn?: string
  labelOff?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const labelOnText = computed(() => props.labelOn || 'Oui')
const labelOffText = computed(() => props.labelOff || 'Non')

const uniqueId = computed(() => props.id || `switch-${Math.random().toString(36).substr(2, 9)}`)

function onChange(value: boolean) {
  if (!props.disabled) {
    emit('update:modelValue', value)
  }
}
</script>

<template>
  <span class="ps-switch" :id="uniqueId" :class="{ disabled }">
    <!-- Non (value=0) first, then Oui (value=1) - matches PS9 order -->
    <input
      :id="`${uniqueId}_0`"
      class="ps-switch"
      type="radio"
      :name="uniqueId"
      value="0"
      :checked="modelValue === false"
      :disabled="disabled"
      @change="onChange(false)"
    >
    <label :for="`${uniqueId}_0`">{{ labelOffText }}</label>
    <input
      :id="`${uniqueId}_1`"
      class="ps-switch"
      type="radio"
      :name="uniqueId"
      value="1"
      :checked="modelValue === true"
      :disabled="disabled"
      @change="onChange(true)"
    >
    <label :for="`${uniqueId}_1`">{{ labelOnText }}</label>
    <span class="slide-button"></span>
  </span>
</template>

