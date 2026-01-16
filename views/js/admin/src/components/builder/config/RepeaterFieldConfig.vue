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

const displayModeOptions = [
  { value: 'table', label: t('displayModeTable') },
  { value: 'cards', label: t('displayModeCards') },
]

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}
</script>

<template>
  <div class="repeater-field-config">
    <div class="alert alert-info mb-3">
      <strong>{{ t('repeaterAlertTitle') }}</strong><br>
      {{ t('repeaterAlertContent') }}
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">{{ t('minRows') }}</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.min || ''"
            :placeholder="t('noMinimum')"
            @input="updateConfig('min', parseInt(($event.target as HTMLInputElement).value) || 0)"
          >
          <small class="form-text text-muted">{{ t('noMinimum') }}</small>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">{{ t('maxRows') }}</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.max || ''"
            :placeholder="t('unlimited')"
            @input="updateConfig('max', parseInt(($event.target as HTMLInputElement).value) || 0)"
          >
          <small class="form-text text-muted">{{ t('unlimitedZero') }}</small>
        </div>
      </div>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">{{ t('displayOptions') }}</h6>

    <div class="form-group">
      <label class="form-control-label">{{ t('displayMode') }}</label>
      <select 
        class="form-control"
        :value="config.displayMode || 'table'"
        @change="updateConfig('displayMode', ($event.target as HTMLSelectElement).value)"
      >
        <option 
          v-for="option in displayModeOptions" 
          :key="option.value" 
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      <small class="form-text text-muted">
        {{ t('displayModeHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.collapsed === true"
          @change="updateConfig('collapsed', ($event.target as HTMLInputElement).checked)"
        >
        {{ t('collapsedByDefault') }}
      </label>
      <small class="form-text text-muted">
        {{ t('collapsedHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('rowTitleTemplate') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.rowTitle || ''"
        :placeholder="t('rowTitlePlaceholder')"
        @input="updateConfig('rowTitle', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        <span v-html="t('rowTitleHelp')"></span>
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('addButtonLabel') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.buttonLabel || t('addRow')"
        :placeholder="t('addRow')"
        @input="updateConfig('buttonLabel', ($event.target as HTMLInputElement).value)"
      >
    </div>
  </div>
</template>
