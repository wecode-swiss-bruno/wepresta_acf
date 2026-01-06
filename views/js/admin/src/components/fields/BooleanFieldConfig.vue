<script setup lang="ts">
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'
import PsSwitch from '@/components/ui/PsSwitch.vue'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { updateConfig, createBooleanRef, createStringRef } = useFieldConfig(props, emit)

// Boolean with auto-conversion
const defaultValue = createBooleanRef('defaultValue')

// String refs for labels
const trueLabel = createStringRef('trueLabel')
const falseLabel = createStringRef('falseLabel')
</script>

<template>
  <div class="boolean-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <PsSwitch
        v-model="defaultValue"
        id="boolean-default"
      />
      <small class="form-text text-muted">Default to checked</small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('trueLabel') || 'True Label' }}</label>
      <input
        v-model="trueLabel"
        type="text"
        class="form-control"
        :placeholder="t('yes') || 'Yes'"
      >
      <small class="form-text text-muted">
        {{ t('trueLabelHelp') || 'Text to display when value is true/checked.' }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('falseLabel') || 'False Label' }}</label>
      <input
        v-model="falseLabel"
        type="text"
        class="form-control"
        :placeholder="t('no') || 'No'"
      >
      <small class="form-text text-muted">
        {{ t('falseLabelHelp') || 'Text to display when value is false/unchecked.' }}
      </small>
    </div>
  </div>
</template>

<style scoped>
/* Global .ps-switch styles from admin.scss */
</style>

