<script setup lang="ts">
import { ref, watch, computed } from 'vue'
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

// Default values (array of selected choice values)
const defaultValues = computed<string[]>({
  get: () => {
    const val = props.config.defaultValue
    if (Array.isArray(val)) return val as string[]
    if (typeof val === 'string' && val) return [val]
    return []
  },
  set: (val: string[]) => {
    updateConfig('defaultValue', val.length > 0 ? val : undefined)
  }
})

function toggleDefault(value: string) {
  const current = [...defaultValues.value]
  const idx = current.indexOf(value)
  if (idx >= 0) {
    current.splice(idx, 1)
  } else {
    current.push(value)
  }
  defaultValues.value = current
}
</script>

<template>
  <div class="checkbox-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('choices') }}</label>
      <ChoicesEditor
        v-model="choices"
        :empty-message="t('noChoices') || 'No options defined yet.'"
        :add-button-label="t('addOption') || 'Add Option'"
      />
      <small class="form-text text-muted d-block mt-2">
        {{ t('checkboxHelp') || 'Users can select multiple options.' }}
      </small>
    </div>

    <div class="form-group" v-if="choices.length > 0">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <div class="default-choices">
        <div 
          v-for="choice in choices" 
          :key="choice.value" 
          class="form-check"
        >
          <input 
            type="checkbox"
            class="form-check-input"
            :id="'default-' + choice.value"
            :checked="defaultValues.includes(choice.value)"
            @change="toggleDefault(choice.value)"
          >
          <label class="form-check-label" :for="'default-' + choice.value">
            {{ choice.label || choice.value }}
          </label>
        </div>
      </div>
      <small class="form-text text-muted">
        {{ t('defaultValueHelp') || 'Select default checked options for new entities.' }}
      </small>
    </div>
  </div>
</template>

<style scoped>
.default-choices {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 0.5rem;
}
</style>
