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

const iconSetOptions = [
  { value: 'material', label: t('iconSetMaterial') },
  { value: 'fontawesome', label: t('iconSetFontawesome') },
  { value: 'custom', label: t('iconSetCustom') },
]

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}
</script>

<template>
  <div class="list-field-config">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">{{ t('minItems') }}</label>
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
          <label class="form-control-label">{{ t('maxItems') }}</label>
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
    <h6 class="text-muted mb-3">{{ t('itemOptions') || 'Item Options' }}</h6>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.showIcon === true"
          @change="updateConfig('showIcon', ($event.target as HTMLInputElement).checked)"
        >
        {{ t('enableIconField') }}
      </label>
      <small class="form-text text-muted">
        {{ t('enableIconHelp') }}
      </small>
    </div>

    <div v-if="config.showIcon" class="form-group ms-4">
      <label class="form-control-label">{{ t('iconSet') }}</label>
      <select 
        class="form-control"
        :value="config.iconSet || 'material'"
        @change="updateConfig('iconSet', ($event.target as HTMLSelectElement).value)"
      >
        <option 
          v-for="option in iconSetOptions" 
          :key="option.value" 
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.showLink === true"
          @change="updateConfig('showLink', ($event.target as HTMLInputElement).checked)"
        >
        {{ t('enableLinkField') }}
      </label>
      <small class="form-text text-muted">
        {{ t('enableLinkHelp') }}
      </small>
    </div>

    <hr class="my-3">
    
    <div class="form-group">
      <label class="form-control-label">{{ t('placeholderText') }}</label>
      <input 
        type="text"
        class="form-control"
        :value="config.placeholder || ''"
        :placeholder="t('placeholderExample')"
        @input="updateConfig('placeholder', ($event.target as HTMLInputElement).value)"
      >
    </div>
  </div>
</template>
