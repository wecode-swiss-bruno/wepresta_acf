<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: string | number | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const config = computed(() => props.field.config || {})

const parsedChoices = computed(() => {
  const choices = config.value.choices
  if (!choices) return []

  if (Array.isArray(choices)) {
    return choices.map((item: any) => {
      if (typeof item === 'object' && item !== null) {
        return { value: item.value || '', label: item.value || '' } 
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

const updateValue = (val: string) => {
  emit('update:modelValue', val)
}
</script>

<template>
  <div class="acf-radio-group">
    <div 
      v-for="choice in parsedChoices" 
      :key="choice.value"
      class="custom-control custom-radio mb-1"
    >
      <input 
        type="radio" 
        :id="`radio_${field.id}_${choice.value}`" 
        :name="`radio_${field.id}`"
        class="custom-control-input"
        :value="choice.value"
        :checked="modelValue == choice.value"
        @change="updateValue(choice.value)"
      >
      <label class="custom-control-label" :for="`radio_${field.id}_${choice.value}`">
        {{ choice.label }}
      </label>
    </div>
  </div>
</template>
