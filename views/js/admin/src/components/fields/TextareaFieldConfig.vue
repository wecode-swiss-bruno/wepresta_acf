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
const { createStringRef, createNumberRef } = useFieldConfig(props, emit)

// Local reactive values using the composable
const placeholder = createStringRef('placeholder')
const rows = createNumberRef('rows')
const defaultValue = createStringRef('defaultValue')
</script>

<template>
  <div class="textarea-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input 
        v-model="placeholder"
        type="text"
        class="form-control"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">Rows</label>
      <input 
        v-model.number="rows"
        type="number"
        class="form-control"
        min="2"
        max="20"
      >
      <small class="form-text text-muted">
        Number of visible text lines (2-20).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <textarea 
        v-model="defaultValue"
        class="form-control"
        rows="3"
        :placeholder="t('defaultValuePlaceholder') || 'Default value for all entities'"
      ></textarea>
      <small class="form-text text-muted">
        {{ t('defaultValueHelp') || 'Used when the entity has no specific value.' }}
      </small>
    </div>
  </div>
</template>

