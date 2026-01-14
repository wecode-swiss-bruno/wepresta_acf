<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'
import PsSwitch from '@/components/ui/PsSwitch.vue'

const props = defineProps<{
  field: AcfField
  modelValue: boolean | number | string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean | number]
}>()

const config = computed(() => props.field.config || {})

// Normalize value to boolean for the switch
const isChecked = computed({
  get: () => {
    if (typeof props.modelValue === 'string') return props.modelValue === '1' || props.modelValue === 'true'
    return !!props.modelValue
  },
  set: (val: boolean) => {
    emit('update:modelValue', val ? 1 : 0) // ACF usually stores 1/0
  }
})
</script>

<template>
  <PsSwitch
    v-model="isChecked"
    :id="`switch_${field.id}_${Math.random().toString(36).substr(2, 9)}`"
    :label-on="config.text_on || 'Yes'"
    :label-off="config.text_off || 'No'"
  />
</template>
