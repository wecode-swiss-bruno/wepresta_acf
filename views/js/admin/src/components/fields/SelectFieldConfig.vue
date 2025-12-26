<script setup lang="ts">
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}
</script>

<template>
  <div class="select-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('choices') }} *</label>
      <textarea 
        class="form-control"
        rows="6"
        :value="config.choices"
        @input="updateConfig('choices', ($event.target as HTMLTextAreaElement).value)"
      />
      <small class="form-text text-muted">
        {{ t('choicesHelp') }}
      </small>
      <div class="choices-example mt-2">
        <code class="d-block text-muted">
          red : Red Color<br>
          blue : Blue Color<br>
          green : Green Color
        </code>
      </div>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.defaultValue"
        @input="updateConfig('defaultValue', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Enter the value (left side) of the default choice.
      </small>
    </div>

    <div class="form-group">
      <div class="ps-switch">
        <input 
          id="select-allow-multiple"
          type="checkbox"
          :checked="config.allowMultiple"
          @change="updateConfig('allowMultiple', ($event.target as HTMLInputElement).checked)"
        >
        <label for="select-allow-multiple">{{ t('allowMultiple') }}</label>
      </div>
      <small class="form-text text-muted">
        Allow selecting multiple values (renders as checkboxes).
      </small>
    </div>
  </div>
</template>

<style scoped>
.choices-example {
  padding: 0.5rem;
  background: var(--light-bg, #f8f9fa);
  border-radius: 4px;
}
</style>

