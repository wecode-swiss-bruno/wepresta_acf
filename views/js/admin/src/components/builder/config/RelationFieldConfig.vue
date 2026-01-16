<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'
import PsSwitch from '@/components/common/PsSwitch.vue'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { updateConfig, createBooleanRef } = useFieldConfig(props, emit)

// Boolean switches with auto-conversion
const multiple = createBooleanRef('multiple')

// Filter booleans (nested in filters object) - need manual handling
const filterActive = ref(props.config.filters?.active !== false)
const filterInStock = ref(props.config.filters?.in_stock === true)
const filterExcludeCurrent = ref(props.config.filters?.exclude_current !== false)

// Sync filter refs with props
watch(() => props.config.filters?.active, (val) => {
  filterActive.value = val !== false
})
watch(() => props.config.filters?.in_stock, (val) => {
  filterInStock.value = val === true
})
watch(() => props.config.filters?.exclude_current, (val) => {
  filterExcludeCurrent.value = val !== false
})

// Update filters on change with boolean conversion
watch(filterActive, (val) => updateFilter('active', !!val))
watch(filterInStock, (val) => updateFilter('in_stock', !!val))
watch(filterExcludeCurrent, (val) => updateFilter('exclude_current', !!val))

function updateFilter(key: string, value: any): void {
  const filters = { ...(props.config.filters || {}), [key]: value }
  emit('update:config', { ...props.config, filters })
}
</script>

<template>
  <div class="relation-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('entityType') }} *</label>
      <select
        class="form-control"
        :value="config.entityType || 'product'"
        @change="updateConfig('entityType', ($event.target as HTMLSelectElement).value)"
      >
        <option value="product">{{ t('entityProduct') }}</option>
        <option value="category">{{ t('entityCategory') }}</option>
      </select>
      <small class="form-text text-muted">
        {{ t('entityTypeHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('allowMultiple') }}</label>
      <PsSwitch
        v-model="multiple"
        id="relation-multiple"
      />
      <small class="form-text text-muted">
        {{ t('allowMultipleRelHelp') }}
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">{{ t('displayFormat') }}</label>
      <select
        class="form-control"
        :value="config.displayFormat || 'name'"
        @change="updateConfig('displayFormat', ($event.target as HTMLSelectElement).value)"
      >
        <option value="name">{{ t('formatNameOnly') }}</option>
        <option value="name_reference">{{ t('formatNameRef') }}</option>
        <option value="thumbnail_name">{{ t('formatThumbName') }}</option>
      </select>
      <small class="form-text text-muted">
        {{ t('displayFormatHelp') }}
      </small>
    </div>

    <div v-if="config.multiple" class="form-row">
      <div class="form-group col-6">
        <label class="form-control-label">{{ t('minItems') }}</label>
        <input
          type="number"
          class="form-control"
          min="0"
          :value="config.min || 0"
          @input="updateConfig('min', parseInt(($event.target as HTMLInputElement).value) || 0)"
        >
      </div>
      <div class="form-group col-6">
        <label class="form-control-label">{{ t('maxItems') }}</label>
        <input
          type="number"
          class="form-control"
          min="0"
          :value="config.max || ''"
          @input="updateConfig('max', parseInt(($event.target as HTMLInputElement).value) || null)"
        >
        <small class="form-text text-muted">{{ t('leaveEmptyUnlimited') }}</small>
      </div>
    </div>

    <hr>

    <h5>{{ t('filters') }}</h5>

    <div class="form-group">
      <label class="form-control-label">{{ t('activeOnly') }}</label>
      <PsSwitch
        v-model="filterActive"
        id="filter-active"
      />
      <small class="form-text text-muted">
        {{ t('activeOnlyHelp') }}
      </small>
    </div>

    <div v-if="config.entityType === 'product'" class="form-group">
      <label class="form-control-label">{{ t('inStockOnly') }}</label>
      <PsSwitch
        v-model="filterInStock"
        id="filter-stock"
      />
      <small class="form-text text-muted">
        {{ t('inStockOnlyHelp') }}
      </small>
    </div>

    <div v-if="config.entityType === 'product'" class="form-group">
      <label class="form-control-label">{{ t('excludeCurrent') }}</label>
      <PsSwitch
        v-model="filterExcludeCurrent"
        id="filter-exclude"
      />
      <small class="form-text text-muted">
        {{ t('excludeCurrentHelp') }}
      </small>
    </div>

    <div v-if="config.entityType === 'product'" class="form-group">
      <label class="form-control-label">{{ t('limitToCategories') }}</label>
      <input
        type="text"
        class="form-control"
        :placeholder="t('categoriesExample')"
        :value="config.filters?.categories || ''"
        @input="updateFilter('categories', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        {{ t('limitToCategoriesHelp') }}
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
