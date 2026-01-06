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

// Watch for prop changes to sync local values
watch(() => props.config?.placeholder, (newVal) => {
  placeholder.value = newVal || ''
})

// Watch local values to emit updates
watch(placeholder, (newVal) => {
  updateConfig('placeholder', newVal || undefined)
})
</script>

<template>
  <div class="email-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input
        v-model="placeholder"
        type="text"
        class="form-control"
        placeholder="email@example.com"
      >
    </div>
  </div>
</template>

