<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const config = computed(() => props.field.config || {})

// Get min/max dates from config
const minDate = computed(() => config.value.minDate || config.value.min || '')
const maxDate = computed(() => config.value.maxDate || config.value.max || '')

// Format date for display in helper text
const formatDateDisplay = (dateStr: string): string => {
  if (!dateStr) return ''
  try {
    const date = new Date(dateStr)
    return date.toLocaleDateString()
  } catch {
    return dateStr
  }
}

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
      :min="minDate"
      :max="maxDate"
      @input="onInput"
    >
    <small v-if="minDate || maxDate" class="form-text text-muted">
      <span v-if="minDate && maxDate">
        Date entre {{ formatDateDisplay(minDate) }} et {{ formatDateDisplay(maxDate) }}
      </span>
      <span v-else-if="minDate">
        Date minimum : {{ formatDateDisplay(minDate) }}
      </span>
      <span v-else-if="maxDate">
        Date maximum : {{ formatDateDisplay(maxDate) }}
      </span>
    </small>
  </div>
</template>
