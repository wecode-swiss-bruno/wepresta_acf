<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: string | string[] | number | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | string[]]
}>()

const selectRef = ref<HTMLSelectElement | null>(null)
const config = computed(() => props.field.config || {})

// Check if multiple selection is enabled
const isMultiple = computed(() => {
  return !!(config.value.allowMultiple || config.value.multiple)
})

// Get current language ID from global config
const currentLangId = computed(() => {
  return (window as any).acfConfig?.currentLangId || '1'
})

// Normalize value to array for multiple, string for single
const normalizedValue = computed(() => {
  if (isMultiple.value) {
    if (Array.isArray(props.modelValue)) return props.modelValue
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return []
    return [String(props.modelValue)]
  }
  if (Array.isArray(props.modelValue)) return props.modelValue[0] || ''
  return props.modelValue ?? ''
})

// Resolve label from translations or fallback
const resolveLabel = (item: any): string => {
  const langId = String(currentLangId.value)
  
  if (item.translations && typeof item.translations === 'object') {
    const translatedLabel = item.translations[langId]
    if (translatedLabel && translatedLabel.trim() !== '') {
      return translatedLabel
    }
  }
  
  if (item.label && item.label.trim() !== '') {
    return item.label
  }
  
  return item.value || ''
}

// Parse choices
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
        return { value: parts[0] || '', label: parts[1] || parts[0] || '' }
      })
  }
  return []
})

// Check if option is selected
const isSelected = (value: string): boolean => {
  if (isMultiple.value) {
    return Array.isArray(normalizedValue.value) && normalizedValue.value.includes(value)
  }
  return normalizedValue.value === value
}

// Handle change for single select
const handleSingleChange = (e: Event) => {
  const select = e.target as HTMLSelectElement
  emit('update:modelValue', select.value)
}

// Handle change for multiple select
const handleMultipleChange = (e: Event) => {
  const select = e.target as HTMLSelectElement
  const selectedValues = Array.from(select.selectedOptions).map(opt => opt.value)
  emit('update:modelValue', selectedValues)
}

// Sync selection state for multiple select
const syncSelection = () => {
  if (!selectRef.value || !isMultiple.value) return
  const values = Array.isArray(normalizedValue.value) ? normalizedValue.value : []
  
  Array.from(selectRef.value.options).forEach(option => {
    option.selected = values.includes(option.value)
  })
}

onMounted(() => {
  syncSelection()
})

watch(() => props.modelValue, () => {
  syncSelection()
}, { deep: true })
</script>

<template>
  <select
    v-if="!isMultiple"
    class="form-control"
    :value="normalizedValue"
    @change="handleSingleChange"
    :required="field.required"
  >
    <option v-if="config.allow_null || config.allowNull || !field.required" value="">
      -- {{ config.placeholder || 'Select' }} --
    </option>
    <option v-for="choice in parsedChoices" :key="choice.value" :value="choice.value">
      {{ choice.label }}
    </option>
  </select>
  
  <select
    v-else
    ref="selectRef"
    class="form-control"
    multiple
    :size="Math.min(parsedChoices.length, 6)"
    @change="handleMultipleChange"
    :required="field.required"
  >
    <option v-for="choice in parsedChoices" :key="choice.value" :value="choice.value" :selected="isSelected(choice.value)">
      {{ choice.label }}
    </option>
  </select>
</template>

<style scoped>
select[multiple] {
  height: auto;
  min-height: 100px;
}
</style>
