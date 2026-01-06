<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}

// Local reactive values for v-model binding
const minDate = ref(props.config?.minDate || '')
const maxDate = ref(props.config?.maxDate || '')

// Watch for prop changes to sync local values
watch(() => props.config?.minDate, (newVal) => {
  minDate.value = newVal || ''
})

watch(() => props.config?.maxDate, (newVal) => {
  maxDate.value = newVal || ''
})

// Watch local values to emit updates
watch(minDate, (newVal) => {
  updateConfig('minDate', newVal || undefined)
})

watch(maxDate, (newVal) => {
  updateConfig('maxDate', newVal || undefined)
})
</script>

<template>
  <div class="datetime-field-config">
    <div class="form-group">
      <label class="form-control-label">Minimum Date/Time</label>
      <input
        v-model="minDate"
        type="datetime-local"
        class="form-control"
      >
      <small class="form-text text-muted">
        Earliest selectable date and time (optional).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">Maximum Date/Time</label>
      <input
        v-model="maxDate"
        type="datetime-local"
        class="form-control"
      >
      <small class="form-text text-muted">
        Latest selectable date and time (optional).
      </small>
    </div>
  </div>
</template>

