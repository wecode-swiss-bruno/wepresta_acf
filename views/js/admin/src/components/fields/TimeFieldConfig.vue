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
const format = createStringRef('format')
const step = createNumberRef('step')
const defaultValue = createStringRef('defaultValue')
</script>

<template>
  <div class="time-field-config">
    <div class="form-group">
      <label class="form-control-label">Time Format</label>
      <select
        v-model="format"
        class="form-control"
      >
        <option value="24h">24 Hour (14:30)</option>
        <option value="12h">12 Hour (2:30 PM)</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">Minute Step</label>
      <select
        v-model.number="step"
        class="form-control"
      >
        <option value="1">1 minute</option>
        <option value="5">5 minutes</option>
        <option value="15">15 minutes</option>
        <option value="30">30 minutes</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        v-model="defaultValue"
        type="time"
        class="form-control"
      >
      <small class="form-text text-muted">
        {{ t('defaultValueHelp') || 'Used when the entity has no specific value.' }}
      </small>
    </div>
  </div>
</template>

