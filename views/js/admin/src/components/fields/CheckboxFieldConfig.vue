<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'

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
const choices = ref<Choice[]>((props.config.choices as Choice[]) || [])

// Sync local choices to props
watch(
  () => props.config.choices,
  (newChoices) => {
    choices.value = (newChoices as Choice[]) || []
  }
)

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
  emit('update:config', { ...props.config, choices: [...choices.value] })
}
</script>

<template>
  <div class="checkbox-field-config">
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
      
      <button 
        type="button" 
        class="btn btn-sm btn-outline-secondary mt-2"
        @click="addChoice"
      >
        <span class="material-icons">add</span>
        {{ t('addOption') }}
      </button>
      
      <small class="form-text text-muted d-block mt-2">
        Users can select multiple options.
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

