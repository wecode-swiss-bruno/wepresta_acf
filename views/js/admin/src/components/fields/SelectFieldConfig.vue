<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'
import PsSwitch from '@/components/ui/PsSwitch.vue'
import ChoicesEditor, { type Choice } from './ChoicesEditor.vue'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { updateConfig, createBooleanRef } = useFieldConfig(props, emit)

// Parse choices from various formats
function parseChoices(input: unknown): Choice[] {
  if (!input) return []
  
  if (Array.isArray(input)) {
    return input.map((item) => {
      if (typeof item === 'object' && item !== null) {
        return { value: item.value || '', label: item.label || '' }
      }
      return { value: String(item), label: String(item) }
    })
  }
  
  if (typeof input === 'string') {
    return input.split('\n')
      .map((line) => line.trim())
      .filter((line) => line.length > 0)
      .map((line) => {
        const parts = line.split(':').map((p) => p.trim())
        return {
          value: parts[0] || '',
          label: parts[1] || parts[0] || ''
        }
      })
  }
  
  return []
}

// Choices with v-model on ChoicesEditor
const choices = ref<Choice[]>(parseChoices(props.config.choices))
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
        :empty-message="t('noChoices') || 'No options defined yet.'"
        :add-button-label="t('addOption') || 'Add Option'"
      />
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <select
        class="form-control"
        :value="config.defaultValue"
        @change="updateConfig('defaultValue', ($event.target as HTMLSelectElement).value)"
      >
        <option value="">-- {{ t('none') || 'None' }} --</option>
        <option v-for="choice in choices" :key="choice.value" :value="choice.value">
          {{ choice.label || choice.value }}
        </option>
      </select>
      <small class="form-text text-muted">
        {{ t('selectDefaultHelp') || 'Select the default option.' }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('allowMultiple') }}</label>
      <PsSwitch
        v-model="allowMultiple"
        id="select-allow-multiple"
      />
      <small class="form-text text-muted">
        {{ t('allowMultipleHelp') || 'Allow selecting multiple values.' }}
      </small>
    </div>
  </div>
</template>
