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
const placeholder = ref(props.config?.placeholder || '')
const rows = ref(props.config?.rows || 4)

// Watch for prop changes to sync local values
watch(() => props.config?.placeholder, (newVal) => {
  placeholder.value = newVal || ''
})

watch(() => props.config?.rows, (newVal) => {
  rows.value = newVal || 4
})

// Watch local values to emit updates
watch(placeholder, (newVal) => {
  updateConfig('placeholder', newVal || undefined)
})

watch(rows, (newVal) => {
  updateConfig('rows', newVal || 4)
})
</script>

<template>
  <div class="textarea-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input
        v-model="placeholder"
        type="text"
        class="form-control"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">Rows</label>
      <input
        v-model.number="rows"
        type="number"
        class="form-control"
        min="2"
        max="20"
      >
      <small class="form-text text-muted">
        Number of visible text lines (2-20).
      </small>
    </div>
  </div>
</template>

