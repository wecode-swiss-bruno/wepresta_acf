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
const placeholder = createLocalRef('placeholder', '')
</script>

<template>
  <div class="email-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholder') }}</label>
      <input
        v-model="placeholder"
        type="text"
        class="form-control"
        placeholder="email@example.com"
      >
    </div>
  </div>
</template>

