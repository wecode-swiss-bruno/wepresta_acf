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
const placeholder = createStringRef('placeholder')
const prefix = createStringRef('prefix')
const suffix = createStringRef('suffix')
const defaultValue = createStringRef('defaultValue')
</script>

<template>
  <div class="text-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input 
        v-model="placeholder"
        type="text"
        class="form-control"
      >
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('prefix') }}</label>
      <input 
        v-model="prefix"
        type="text"
        class="form-control"
      >
      <small class="form-text text-muted">
        {{ t('prefixHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('suffix') }}</label>
      <input 
        v-model="suffix"
        type="text"
        class="form-control"
      >
      <small class="form-text text-muted">
        {{ t('suffixHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        v-model="defaultValue"
        type="text"
        class="form-control"
        :placeholder="t('defaultValuePlaceholder')"
      >
      <small class="form-text text-muted">
        {{ t('defaultValueHelp') }}
      </small>
    </div>
  </div>
</template>

