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
const prefix = ref(props.config?.prefix || '')
const suffix = ref(props.config?.suffix || '')

// Watch for prop changes to sync local values
watch(() => props.config?.placeholder, (newVal) => {
  placeholder.value = newVal || ''
})

watch(() => props.config?.prefix, (newVal) => {
  prefix.value = newVal || ''
})

watch(() => props.config?.suffix, (newVal) => {
  suffix.value = newVal || ''
})

// Watch local values to emit updates
watch(placeholder, (newVal) => {
  updateConfig('placeholder', newVal || undefined)
})

watch(prefix, (newVal) => {
  updateConfig('prefix', newVal || undefined)
})

watch(suffix, (newVal) => {
  updateConfig('suffix', newVal || undefined)
})
</script>

<template>
  <div class="text-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input
        v-model="placeholder"
        type="text"
        class="form-control"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('prefix') }}</label>
      <input
        v-model="prefix"
        type="text"
        class="form-control"
      >
      <small class="form-text text-muted">
        Text to display before the field value.
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('suffix') }}</label>
      <input
        v-model="suffix"
        type="text"
        class="form-control"
      >
      <small class="form-text text-muted">
        Text to display after the field value.
      </small>
    </div>
  </div>
</template>

