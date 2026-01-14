<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: string | null
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
  <div class="acf-rich-text">
    <textarea
      class="form-control"
      :value="modelValue"
      @input="updateValue"
      :rows="config.rows || 10"
      placeholder="HTML Content..."
    ></textarea>
    <small class="text-muted">Rich Text Editor (TinyMCE integration pending)</small>
  </div>
</template>
