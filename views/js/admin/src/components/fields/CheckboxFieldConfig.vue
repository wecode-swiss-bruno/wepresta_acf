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
  <div class="checkbox-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('choices') }}</label>
      <ChoicesEditor
        v-model="choices"
        :empty-message="t('noChoices') || 'No options defined yet.'"
        :add-button-label="t('addOption') || 'Add Option'"
      />
      <small class="form-text text-muted d-block mt-2">
        {{ t('checkboxHelp') || 'Users can select multiple options.' }}
      </small>
    </div>
  </div>
</template>
