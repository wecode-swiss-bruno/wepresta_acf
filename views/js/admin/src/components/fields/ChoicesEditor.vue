<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'

export interface Choice {
  value: string
  label: string
}

interface Props {
  modelValue: Choice[]
  emptyMessage?: string
  addButtonLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  emptyMessage: 'No options defined yet.',
  addButtonLabel: 'Add Option'
})

const emit = defineEmits<{
  'update:modelValue': [choices: Choice[]]
}>()

// Local choices state
const choices = ref<Choice[]>([...props.modelValue])
const isUpdating = ref(false)

// Sync from props
watch(() => props.modelValue, (newVal) => {
  if (!isUpdating.value) {
    choices.value = [...newVal]
  }
})

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
  isUpdating.value = true
  emit('update:modelValue', [...choices.value])
  // Reset flag on next tick to allow prop updates
  nextTick(() => {
    isUpdating.value = false
  })
}
</script>

<template>
  <div class="choices-editor">
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
      {{ props.emptyMessage }}
    </div>
    
    <button 
      type="button" 
      class="btn btn-sm btn-outline-secondary mt-2"
      @click="addChoice"
    >
      <span class="material-icons">add</span>
      {{ props.addButtonLabel }}
    </button>
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
