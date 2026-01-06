<script setup lang="ts">
import { ref, watch } from 'vue'
import { useTranslations } from '@/composables/useTranslations'

export interface Choice {
  value: string
  label: string
}

const props = defineProps<{
  modelValue: Choice[]
  label?: string
  helpText?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [choices: Choice[]]
}>()

const { t } = useTranslations()

const localChoices = ref<Choice[]>([...props.modelValue])

// Sync props -> local
watch(
  () => props.modelValue,
  (newVal) => {
    localChoices.value = [...newVal]
  },
  { deep: true }
)

// Sync local -> props
watch(
  localChoices,
  (newVal) => {
    emit('update:modelValue', newVal)
  },
  { deep: true }
)

function addChoice(): void {
  localChoices.value.push({ value: '', label: '' })
}

function removeChoice(index: number): void {
  localChoices.value.splice(index, 1)
}

function updateChoice(index: number, field: 'value' | 'label', value: string): void {
  localChoices.value[index][field] = value
}
</script>

<template>
  <div class="acf-choices-editor">
    <div class="form-group">
      <label v-if="label" class="form-control-label">{{ label }}</label>
      
      <div 
        v-for="(choice, index) in localChoices" 
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
      
      <div v-if="localChoices.length === 0" class="text-muted mb-2">
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
      
      <small v-if="helpText" class="form-text text-muted d-block mt-2">
        {{ helpText }}
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

