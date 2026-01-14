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

const updateValue = (e: Event) => {
  emit('update:modelValue', (e.target as HTMLTextAreaElement).value)
}
</script>

<template>
  <textarea
    class="form-control"
    :value="modelValue"
    @input="updateValue"
    :rows="config.rows || 4"
    :placeholder="config.placeholder || ''"
    :required="field.required"
    :maxlength="config.maxlength || undefined"
  ></textarea>
</template>
