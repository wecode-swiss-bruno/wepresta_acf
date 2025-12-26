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

function updateFilter(key: string, value: unknown): void {
  const filters = { ...(props.config.filters || {}), [key]: value }
  emit('update:config', { ...props.config, filters })
}
</script>

<template>
  <div class="relation-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('entityType') || 'Entity Type' }} *</label>
      <select 
        class="form-control"
        :value="config.entityType || 'product'"
        @change="updateConfig('entityType', ($event.target as HTMLSelectElement).value)"
      >
        <option value="product">Product</option>
        <option value="category">Category</option>
      </select>
      <small class="form-text text-muted">
        Select the type of entity to relate to.
      </small>
    </div>

    <div class="form-group">
      <div class="ps-switch">
        <input 
          id="relation-multiple"
          type="checkbox"
          :checked="config.multiple"
          @change="updateConfig('multiple', ($event.target as HTMLInputElement).checked)"
        >
        <label for="relation-multiple">{{ t('allowMultiple') || 'Allow Multiple' }}</label>
      </div>
      <small class="form-text text-muted">
        Allow selecting multiple items.
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('displayFormat') || 'Display Format' }}</label>
      <select 
        class="form-control"
        :value="config.displayFormat || 'name'"
        @change="updateConfig('displayFormat', ($event.target as HTMLSelectElement).value)"
      >
        <option value="name">Name only</option>
        <option value="name_reference">Name + Reference</option>
        <option value="thumbnail_name">Thumbnail + Name</option>
      </select>
      <small class="form-text text-muted">
        How to display selected items in the back-office.
      </small>
    </div>

    <div v-if="config.multiple" class="form-row">
      <div class="form-group col-6">
        <label class="form-control-label">{{ t('minItems') || 'Minimum items' }}</label>
        <input 
          type="number"
          class="form-control"
          min="0"
          :value="config.min || 0"
          @input="updateConfig('min', parseInt(($event.target as HTMLInputElement).value) || 0)"
        >
      </div>
      <div class="form-group col-6">
        <label class="form-control-label">{{ t('maxItems') || 'Maximum items' }}</label>
        <input 
          type="number"
          class="form-control"
          min="0"
          :value="config.max || ''"
          @input="updateConfig('max', parseInt(($event.target as HTMLInputElement).value) || null)"
        >
        <small class="form-text text-muted">Leave empty for unlimited.</small>
      </div>
    </div>

    <hr>

    <h5>{{ t('filters') || 'Filters' }}</h5>

    <div class="form-group">
      <div class="ps-switch">
        <input 
          id="filter-active"
          type="checkbox"
          :checked="config.filters?.active !== false"
          @change="updateFilter('active', ($event.target as HTMLInputElement).checked)"
        >
        <label for="filter-active">{{ t('activeOnly') || 'Active only' }}</label>
      </div>
      <small class="form-text text-muted">
        Only show active products/categories.
      </small>
    </div>

    <div v-if="config.entityType === 'product'" class="form-group">
      <div class="ps-switch">
        <input 
          id="filter-stock"
          type="checkbox"
          :checked="config.filters?.in_stock === true"
          @change="updateFilter('in_stock', ($event.target as HTMLInputElement).checked)"
        >
        <label for="filter-stock">{{ t('inStockOnly') || 'In stock only' }}</label>
      </div>
      <small class="form-text text-muted">
        Only show products with stock available.
      </small>
    </div>

    <div v-if="config.entityType === 'product'" class="form-group">
      <div class="ps-switch">
        <input 
          id="filter-exclude"
          type="checkbox"
          :checked="config.filters?.exclude_current !== false"
          @change="updateFilter('exclude_current', ($event.target as HTMLInputElement).checked)"
        >
        <label for="filter-exclude">{{ t('excludeCurrent') || 'Exclude current product' }}</label>
      </div>
      <small class="form-text text-muted">
        Don't show the current product in the search results.
      </small>
    </div>

    <div v-if="config.entityType === 'product'" class="form-group">
      <label class="form-control-label">{{ t('limitCategories') || 'Limit to categories' }}</label>
      <input 
        type="text"
        class="form-control"
        placeholder="e.g., 3, 5, 12"
        :value="config.filters?.categories || ''"
        @input="updateFilter('categories', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Comma-separated category IDs. Leave empty for all categories.
      </small>
    </div>
  </div>
</template>

<style scoped>
.relation-field-config hr {
  margin: 1.5rem 0;
  border-color: #eee;
}

.relation-field-config h5 {
  margin-bottom: 1rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: #666;
}

.form-row {
  display: flex;
  margin: 0 -0.5rem;
}

.form-row .form-group {
  padding: 0 0.5rem;
}

.col-6 {
  flex: 0 0 50%;
  max-width: 50%;
}
</style>
