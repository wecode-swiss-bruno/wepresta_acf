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
const format = ref(props.config?.format || '24h')
const step = ref(props.config?.step || 1)

// Watch for prop changes to sync local values
watch(() => props.config?.format, (newVal) => {
  format.value = newVal || '24h'
})

watch(() => props.config?.step, (newVal) => {
  step.value = newVal || 1
})

// Watch local values to emit updates
watch(format, (newVal) => {
  updateConfig('format', newVal)
})

watch(step, (newVal) => {
  updateConfig('step', newVal)
})
</script>

<template>
  <div class="time-field-config">
    <div class="form-group">
      <label class="form-control-label">Time Format</label>
      <select
        v-model="format"
        class="form-control"
      >
        <option value="24h">24 Hour (14:30)</option>
        <option value="12h">12 Hour (2:30 PM)</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">Minute Step</label>
      <select
        v-model.number="step"
        class="form-control"
      >
        <option value="1">1 minute</option>
        <option value="5">5 minutes</option>
        <option value="15">15 minutes</option>
        <option value="30">30 minutes</option>
      </select>
    </div>
  </div>
</template>

