<script setup lang="ts">
import type { FieldConfig } from '@/types'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const iconSetOptions = [
  { value: 'material', label: 'Material Icons' },
  { value: 'fontawesome', label: 'Font Awesome' },
  { value: 'custom', label: 'Custom (text input)' },
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
          <label class="form-control-label">Minimum Items</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.min || ''"
            placeholder="No minimum"
            @input="updateConfig('min', parseInt(($event.target as HTMLInputElement).value) || 0)"
          >
          <small class="form-text text-muted">{{ t('0 = no minimum') }}</small>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">Maximum Items</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.max || ''"
            placeholder="Unlimited"
            @input="updateConfig('max', parseInt(($event.target as HTMLInputElement).value) || 0)"
          >
          <small class="form-text text-muted">{{ t('0 = unlimited') }}</small>
        </div>
      </div>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">Item Options</h6>

    <div class="form-group">
      <label class="form-control-label d-flex align-items-center gap-2">
        <input 
          type="checkbox"
          class="form-check-input"
          :checked="config.showIcon === true"
          @change="updateConfig('showIcon', ($event.target as HTMLInputElement).checked)"
        >
        Enable Icon Field
      </label>
      <small class="form-text text-muted">
        Allow users to add an icon for each item.
      </small>
    </div>

    <div v-if="config.showIcon" class="form-group ms-4">
      <label class="form-control-label">Icon Set</label>
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
        Enable Link Field
      </label>
      <small class="form-text text-muted">
        Allow users to add a URL link for each item.
      </small>
    </div>

    <hr class="my-3">
    
    <div class="form-group">
      <label class="form-control-label">Placeholder Text</label>
      <input 
        type="text"
        class="form-control"
        :value="config.placeholder || ''"
        placeholder="e.g., Enter a feature..."
        @input="updateConfig('placeholder', ($event.target as HTMLInputElement).value)"
      >
    </div>
  </div>
</template>
