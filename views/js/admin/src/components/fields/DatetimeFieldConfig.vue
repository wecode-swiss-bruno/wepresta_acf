<script setup lang="ts">
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { createLocalRef } = useFieldConfig(props, emit)

// Local reactive values using the composable
const minDate = createLocalRef('minDate', '')
const maxDate = createLocalRef('maxDate', '')
</script>

<template>
  <div class="datetime-field-config">
    <div class="form-group">
      <label class="form-control-label">Minimum Date/Time</label>
      <input
        v-model="minDate"
        type="datetime-local"
        class="form-control"
      >
      <small class="form-text text-muted">
        Earliest selectable date and time (optional).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">Maximum Date/Time</label>
      <input
        v-model="maxDate"
        type="datetime-local"
        class="form-control"
      >
      <small class="form-text text-muted">
        Latest selectable date and time (optional).
      </small>
    </div>
  </div>
</template>

