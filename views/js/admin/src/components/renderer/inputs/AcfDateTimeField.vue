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

// Get min/max datetime from config
const minDateTime = computed(() => config.value.minDateTime || config.value.min || '')
const maxDateTime = computed(() => config.value.maxDateTime || config.value.max || '')

// Format datetime for display in helper text
const formatDateTimeDisplay = (dateStr: string): string => {
  if (!dateStr) return ''
  try {
    const date = new Date(dateStr)
    return date.toLocaleString()
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
  <div class="acf-datetime-field">
    <input 
      type="datetime-local" 
      class="form-control" 
      :value="modelValue"
      :min="minDateTime"
      :max="maxDateTime"
      @input="onInput"
    >
    <small v-if="minDateTime || maxDateTime" class="form-text text-muted">
      <span v-if="minDateTime && maxDateTime">
        Entre {{ formatDateTimeDisplay(minDateTime) }} et {{ formatDateTimeDisplay(maxDateTime) }}
      </span>
      <span v-else-if="minDateTime">
        Minimum : {{ formatDateTimeDisplay(minDateTime) }}
      </span>
      <span v-else-if="maxDateTime">
        Maximum : {{ formatDateTimeDisplay(maxDateTime) }}
      </span>
    </small>
  </div>
</template>
