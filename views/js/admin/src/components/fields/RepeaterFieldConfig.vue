<script setup lang="ts">
import type { FieldConfig } from '@/types'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const displayModeOptions = [
  { value: 'table', label: 'Table (rows)' },
  { value: 'cards', label: 'Cards' },
]

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}
</script>

<template>
  <div class="repeater-field-config">
    <div class="alert alert-info mb-3">
      <strong>Repeater Field</strong><br>
      Add subfields to this repeater using the "Add Subfield" button below after saving.
      Subfields will be repeated for each row.
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">Minimum Rows</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.min || ''"
            placeholder="No minimum"
            @input="updateConfig('min', parseInt(($event.target as HTMLInputElement).value) || 0)"
          >
          <small class="form-text text-muted">0 = no minimum</small>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label">Maximum Rows</label>
          <input 
            type="number"
            class="form-control"
            min="0"
            :value="config.max || ''"
            placeholder="Unlimited"
            @input="updateConfig('max', parseInt(($event.target as HTMLInputElement).value) || 0)"
          >
          <small class="form-text text-muted">0 = unlimited</small>
        </div>
      </div>
    </div>

    <hr class="my-3">
    <h6 class="text-muted mb-3">Display Options</h6>

    <div class="form-group">
      <label class="form-control-label">Display Mode</label>
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
        Table: fields in columns like a spreadsheet. Cards: each row as a separate card.
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
        Collapsed by Default
      </label>
      <small class="form-text text-muted">
        New rows will be collapsed when added (cards mode only).
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">Row Title Template</label>
      <input 
        type="text"
        class="form-control"
        :value="config.rowTitle || ''"
        placeholder="Row {#}"
        @input="updateConfig('rowTitle', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Use <code>{`{#}`}</code> for row number or <code>{`{field_slug}`}</code> to display a field value.<br>
        Example: <code>{`{title}`}</code> will show the value of the "title" subfield.
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">Add Button Label</label>
      <input 
        type="text"
        class="form-control"
        :value="config.buttonLabel || 'Add Row'"
        placeholder="Add Row"
        @input="updateConfig('buttonLabel', ($event.target as HTMLInputElement).value)"
      >
    </div>
  </div>
</template>
