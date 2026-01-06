<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import PsSwitch from '@/components/ui/PsSwitch.vue'

interface Choice {
  value: string
  label: string
}

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()

// Local choices state
const choices = ref<Choice[]>(parseChoices(props.config.choices))

// Parse choices from various formats (array of objects or string)
function parseChoices(input: unknown): Choice[] {
  if (!input) return []
  
  // Already an array of Choice objects
  if (Array.isArray(input)) {
    return input.map((item) => {
      if (typeof item === 'object' && item !== null) {
        return { value: item.value || '', label: item.label || '' }
      }
      return { value: String(item), label: String(item) }
    })
  }
  
  // Legacy format: string with "value : label" lines
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

// Sync local choices from props
watch(
  () => props.config.choices,
  (newChoices) => {
    choices.value = parseChoices(newChoices)
  }
)

const allowMultiple = ref(props.config.allowMultiple === true)

watch(
  () => props.config.allowMultiple,
  (val) => { allowMultiple.value = val === true }
)

watch(allowMultiple, (val) => {
  const boolVal = !!val
  emit('update:config', { ...props.config, allowMultiple: boolVal })
})

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}

function addChoice(): void {
  choices.value.push({ value: '', label: '' })
  emitChoices()
}

function removeChoice(index: number): void {
  choices.value.splice(index, 1)
  emitChoices()
}

function updateChoice(index: number, field: 'value' | 'label', value: string): void {
  choices.value[index][field] = value
  emitChoices()
}

function emitChoices(): void {
  emit('update:config', { ...props.config, choices: choices.value as any })
}
</script>

<template>
  <div class="select-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('choices') }}</label>
      
      <div 
        v-for="(choice, index) in choices" 
        :key="index"
        class="choice-row"
      >
        <input 
          type="text"
          class="form-control"
          placeholder="Value"
          :value="choice.value"
          @input="updateChoice(index, 'value', ($event.target as HTMLInputElement).value)"
        >
        <input 
          type="text"
          class="form-control"
          placeholder="Label"
          :value="choice.label"
          @input="updateChoice(index, 'label', ($event.target as HTMLInputElement).value)"
        >
        <button 
          type="button" 
          class="btn btn-link text-danger"
          @click="removeChoice(index)"
        >
          <span class="material-icons">delete</span>
        </button>
      </div>
      
      <div v-if="choices.length === 0" class="text-muted mb-2">
        {{ t('noChoices') || 'No options defined yet.' }}
      </div>
      
      <button 
        type="button" 
        class="btn btn-sm btn-outline-secondary mt-2"
        @click="addChoice"
      >
        <span class="material-icons">add</span>
        {{ t('addOption') }}
      </button>
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
        {{ t('allowMultipleHelp') || 'Allow selecting multiple values (renders as checkboxes).' }}
      </small>
    </div>
  </div>
</template>

<style scoped>
.choice-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  align-items: center;
}

.choice-row .form-control {
  flex: 1;
}

.choice-row .btn-link {
  padding: 0.25rem;
}
</style>
