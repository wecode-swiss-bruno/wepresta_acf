<script setup lang="ts">
import { ref, watch, nextTick, computed } from 'vue'
import type { FieldConfig, FieldChoice } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'
import PsSwitch from '@/components/common/PsSwitch.vue'
import ChoicesEditor from './ChoicesEditor.vue'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { updateConfig, createBooleanRef } = useFieldConfig(props, emit)

// Access global languages config
const languages = computed(() => window.acfConfig?.languages || [])

/**
 * Parse choices from various formats while preserving all data.
 * Supports: Array of FieldChoice objects, Array of strings, Newline-separated string
 */
function parseChoices(input: unknown): FieldChoice[] {
  if (!input) return []

  if (Array.isArray(input)) {
    return input.map((item) => {
      if (typeof item === 'object' && item !== null) {
        // Preserve ALL properties including translations
        const choice: FieldChoice = {
          value: item.value || '',
          label: item.label || '',
          translations: item.translations || {}
        }

        // Fix: Ensure label is synced with default language translation if empty
        const defaultLang = languages.value.find((l: any) => l.is_default)
        if (defaultLang && !choice.label && choice.translations && choice.translations[defaultLang.id]) {
          choice.label = choice.translations[defaultLang.id]
        }

        // Fix: Ensure default language translation is synced with label if empty
        if (defaultLang && choice.label && choice.translations && !choice.translations[defaultLang.id]) {
          choice.translations[defaultLang.id] = choice.label
        }

        return choice
      }
      // Simple string value
      return { value: String(item), label: String(item), translations: {} }
    })
  }

  // Legacy: string format "value : Label" per line
  if (typeof input === 'string') {
    return input.split('\n')
      .map((line) => line.trim())
      .filter((line) => line.length > 0)
      .map((line) => {
        const parts = line.split(':').map((p) => p.trim())
        return {
          value: parts[0] || '',
          label: parts[1] || parts[0] || '',
          translations: {}
        }
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
  // Reset flag on next tick
  nextTick(() => {
    isUpdatingChoices.value = false
  })
}, { deep: true })

// Boolean with auto-conversion
const allowMultiple = createBooleanRef('allowMultiple')
</script>

<template>
  <div class="select-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('choices') }}</label>
      <ChoicesEditor
        v-model="choices"
        :empty-message="t('noChoices')"
        :add-button-label="t('addOption')"
      />
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
      <small class="form-text text-muted">
        {{ t('selectDefaultHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('allowMultiple') }}</label>
      <PsSwitch
        v-model="allowMultiple"
        id="select-allow-multiple"
      />
      <small class="form-text text-muted">
        {{ t('allowMultipleHelp') }}
      </small>
    </div>
  </div>
</template>
