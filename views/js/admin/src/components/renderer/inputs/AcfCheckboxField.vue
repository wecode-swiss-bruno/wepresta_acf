<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: any[] | string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: any[]]
}>()

const config = computed(() => props.field.config || {})

// Get current language ID from global config
const currentLangId = computed(() => {
  return (window as any).acfConfig?.currentLangId || '1'
})

// Resolve label from translations or fallback
const resolveLabel = (item: any): string => {
  const langId = String(currentLangId.value)
  
  // Try translations first
  if (item.translations && typeof item.translations === 'object') {
    const translatedLabel = item.translations[langId]
    if (translatedLabel && translatedLabel.trim() !== '') {
      return translatedLabel
    }
  }
  
  // Fallback to label
  if (item.label && item.label.trim() !== '') {
    return item.label
  }
  
  // Fallback to value
  return item.value || ''
}

const parsedChoices = computed(() => {
  const choices = config.value.choices
  if (!choices) return []

  if (Array.isArray(choices)) {
    return choices.map((item: any) => {
      if (typeof item === 'object' && item !== null) {
        return { value: item.value || '', label: resolveLabel(item) } 
      }
      return { value: String(item), label: String(item) }
    })
  }

  if (typeof choices === 'string') {
    return choices
      .split('\n')
      .map((line: string) => line.trim())
      .filter((line: string) => line.length > 0)
      .map((line: string) => {
        const parts = line.split(':').map((p: string) => p.trim())
        return {
          value: parts[0] || '',
          label: parts[1] || parts[0] || '',
        }
      })
  }
  return []
})

// Normalize modelValue to array
const currentValues = computed(() => {
  if (Array.isArray(props.modelValue)) return props.modelValue
  if (props.modelValue === null || props.modelValue === undefined) return []
  return [props.modelValue]
})

const updateValue = (msgValue: string, checked: boolean) => {
  let newValues = [...currentValues.value]
  if (checked) {
    if (!newValues.includes(msgValue)) {
      newValues.push(msgValue)
    }
  } else {
    newValues = newValues.filter(v => v !== msgValue)
  }
  emit('update:modelValue', newValues)
}
</script>

<template>
  <div class="acf-checkbox-group">
    <div 
      v-for="choice in parsedChoices" 
      :key="choice.value"
      class="custom-control custom-checkbox mb-1"
    >
      <input 
        type="checkbox" 
        :id="`cb_${field.id}_${choice.value}`" 
        class="custom-control-input"
        :value="choice.value"
        :checked="currentValues.includes(choice.value)"
        @change="updateValue(choice.value, ($event.target as HTMLInputElement).checked)"
      >
      <label class="custom-control-label" :for="`cb_${field.id}_${choice.value}`">
        {{ choice.label }}
      </label>
    </div>
  </div>
</template>
