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
      <label class="form-control-label">{{ t('timeFormat') }}</label>
      <select
        v-model="format"
        class="form-control"
      >
        <option value="24h">{{ t('format24h') }}</option>
        <option value="12h">{{ t('format12h') }}</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('minuteStep') }}</label>
      <select
        v-model.number="step"
        class="form-control"
      >
        <option value="1">1 {{ t('minuteLabel') }}</option>
        <option value="5">5 {{ t('minutesLabel') }}</option>
        <option value="15">15 {{ t('minutesLabel') }}</option>
        <option value="30">30 {{ t('minutesLabel') }}</option>
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
        {{ t('defaultValueHelp') }}
      </small>
    </div>
  </div>
</template>

