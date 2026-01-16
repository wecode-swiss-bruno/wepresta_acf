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
const target = ref(props.config?.target || '_blank')
const linkText = ref(props.config?.linkText || '')

// Watch for prop changes to sync local values
watch(() => props.config?.placeholder, (newVal) => {
  placeholder.value = newVal || ''
})

watch(() => props.config?.target, (newVal) => {
  target.value = newVal || '_blank'
})

watch(() => props.config?.linkText, (newVal) => {
  linkText.value = newVal || ''
})

// Watch local values to emit updates
watch(placeholder, (newVal) => {
  updateConfig('placeholder', newVal || undefined)
})

watch(target, (newVal) => {
  updateConfig('target', newVal)
})

watch(linkText, (newVal) => {
  updateConfig('linkText', newVal || undefined)
})
</script>

<template>
  <div class="url-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input 
        v-model="placeholder"
        type="text"
        class="form-control"
        placeholder="https://example.com"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('linkTarget') }}</label>
      <select
        v-model="target"
        class="form-control"
      >
        <option value="_blank">{{ t('linkTargetBlank') }}</option>
        <option value="_self">{{ t('linkTargetSelf') }}</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('linkText') }}</label>
      <input 
        v-model="linkText"
        type="text"
        class="form-control"
      >
      <small class="form-text text-muted">
        {{ t('linkTextHelp') }}
      </small>
    </div>
  </div>
</template>

