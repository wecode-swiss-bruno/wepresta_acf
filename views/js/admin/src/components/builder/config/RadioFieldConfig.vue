<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FieldConfig, FieldChoice } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'
import ChoicesEditor from './ChoicesEditor.vue'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { updateConfig } = useFieldConfig(props, emit)

/**
 * Parse choices from config, ensuring translations are preserved.
 */
function parseChoices(input: unknown): FieldChoice[] {
  if (!input) return []
  
  if (Array.isArray(input)) {
    return input.map((item) => {
      if (typeof item === 'object' && item !== null) {
        return {
          value: item.value || '',
          label: item.label || '',
          translations: item.translations || {}
        }
      }
      return { value: String(item), label: String(item), translations: {} }
    })
  }
  
  return []
}

// Choices with v-model on ChoicesEditor
const choices = ref<FieldChoice[]>(parseChoices(props.config.choices))
const isUpdatingChoices = ref(false)

watch(() => props.config.choices, (newChoices) => {
  if (!isUpdatingChoices.value) {
    choices.value = parseChoices(newChoices)
  }
})

watch(choices, (newChoices) => {
  isUpdatingChoices.value = true
  updateConfig('choices', newChoices)
  setTimeout(() => {
    isUpdatingChoices.value = false
  }, 0)
}, { deep: true })
</script>

<template>
  <div class="radio-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('choices') }}</label>
      <ChoicesEditor
        v-model="choices"
        :empty-message="t('noChoices')"
        :add-button-label="t('addOption')"
      />
      <small class="form-text text-muted d-block mt-2">
        {{ t('radioHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('layout') }}</label>
      <select
        class="form-control"
        :value="config.layout || 'vertical'"
        @change="updateConfig('layout', ($event.target as HTMLSelectElement).value)"
      >
        <option value="vertical">{{ t('vertical') }}</option>
        <option value="horizontal">{{ t('horizontal') }}</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <select
        class="form-control"
        :value="config.defaultValue"
        @change="updateConfig('defaultValue', ($event.target as HTMLSelectElement).value)"
      >
        <option value="">-- {{ t('none') }} --</option>
        <option v-for="choice in choices" :key="choice.value" :value="choice.value">
          {{ choice.label || choice.value }}
        </option>
      </select>
    </div>
  </div>
</template>
