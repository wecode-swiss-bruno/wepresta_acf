<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'
import ChoicesEditor, { type Choice } from './ChoicesEditor.vue'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { updateConfig } = useFieldConfig(props, emit)

// Choices with v-model on ChoicesEditor
const choices = ref<Choice[]>((props.config.choices as Choice[]) || [])

watch(() => props.config.choices, (newChoices) => {
  choices.value = (newChoices as Choice[]) || []
})

watch(choices, (newChoices) => {
  updateConfig('choices', newChoices)
}, { deep: true })
</script>

<template>
  <div class="radio-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('choices') }}</label>
      <ChoicesEditor
        v-model="choices"
        :empty-message="t('noChoices') || 'No options defined yet.'"
        :add-button-label="t('addOption') || 'Add Option'"
      />
      <small class="form-text text-muted d-block mt-2">
        {{ t('radioHelp') || 'User can select only one option.' }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('layout') || 'Layout' }}</label>
      <select
        class="form-control"
        :value="config.layout || 'vertical'"
        @change="updateConfig('layout', ($event.target as HTMLSelectElement).value)"
      >
        <option value="vertical">{{ t('vertical') || 'Vertical' }}</option>
        <option value="horizontal">{{ t('horizontal') || 'Horizontal' }}</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <select
        class="form-control"
        :value="config.defaultValue"
        @change="updateConfig('defaultValue', ($event.target as HTMLSelectElement).value)"
      >
        <option value="">-- {{ t('none') || 'None' }} --</option>
        <option v-for="choice in choices" :key="choice.value" :value="choice.value">
          {{ choice.label || choice.value }}
        </option>
      </select>
    </div>
  </div>
</template>
