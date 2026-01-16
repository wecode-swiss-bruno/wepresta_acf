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

// Get min/max time from config
const minTime = computed(() => config.value.minTime || config.value.min || '')
const maxTime = computed(() => config.value.maxTime || config.value.max || '')

// Get step (minute interval) from config - convert to seconds for HTML input
const stepSeconds = computed(() => {
  const minuteStep = Number(config.value.minuteStep || config.value.step || 1)
  return minuteStep * 60 // Convert minutes to seconds
})

// Get time format info
const timeFormat = computed(() => config.value.timeFormat || config.value.format || '24h')

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
      :min="minTime"
      :max="maxTime"
      :step="stepSeconds"
      @input="onInput"
    >
    <small v-if="minTime || maxTime || timeFormat !== '24h'" class="form-text text-muted">
      <span v-if="minTime && maxTime">
        Entre {{ minTime }} et {{ maxTime }}
      </span>
      <span v-else-if="minTime">
        Minimum : {{ minTime }}
      </span>
      <span v-else-if="maxTime">
        Maximum : {{ maxTime }}
      </span>
      <span v-if="timeFormat === '12h'"> (Format 12h)</span>
    </small>
  </div>
</template>
