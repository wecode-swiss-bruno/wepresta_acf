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

// Get min/max dates from config
const minDate = computed(() => config.value.minDate || config.value.min || '')
const maxDate = computed(() => config.value.maxDate || config.value.max || '')

// Convert timestamp or date string to YYYY-MM-DD format for the input
const displayValue = computed(() => {
  const value = props.modelValue
  if (!value) return ''
  
  // If it's a Unix timestamp (number or numeric string)
  if (typeof value === 'number' || (typeof value === 'string' && /^\d{10}$/.test(value))) {
    const timestamp = typeof value === 'number' ? value : parseInt(value, 10)
    const date = new Date(timestamp * 1000) // Unix timestamps are in seconds
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }
  
  // If it's already a date string (YYYY-MM-DD)
  if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
    return value.substring(0, 10) // Take just the date part
  }
  
  return ''
})

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
      :value="displayValue"
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

