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
const { createStringRef } = useFieldConfig(props, emit)

// Local reactive values using the composable
const minDate = createStringRef('minDate')
const maxDate = createStringRef('maxDate')
const defaultValue = createStringRef('defaultValue')
</script>

<template>
  <div class="date-field-config">
    <div class="form-group">
      <label class="form-control-label">Minimum Date</label>
      <input 
        v-model="minDate"
        type="date"
        class="form-control"
      >
      <small class="form-text text-muted">
        Earliest selectable date (optional).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">Maximum Date</label>
      <input 
        v-model="maxDate"
        type="date"
        class="form-control"
      >
      <small class="form-text text-muted">
        Latest selectable date (optional).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        v-model="defaultValue"
        type="date"
        class="form-control"
      >
      <small class="form-text text-muted">
        {{ t('defaultValueHelp') || 'Used when the entity has no specific value.' }}
      </small>
    </div>
  </div>
</template>

