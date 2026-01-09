<script setup lang="ts">
import { ref, computed } from 'vue'
import { v4 as uuidv4 } from 'uuid'
import type { AcfField } from '@/types'
import FileUploadField from '@/components/ui/FileUploadField.vue'

interface RepeaterRow {
  row_id: string
  collapsed: boolean
  values: Record<string, any>
}

const props = defineProps<{
  field: AcfField
  modelValue: any
  languages: any[]
  defaultLanguage: any
}>()

const emit = defineEmits<{
  'update:modelValue': [value: any]
}>()

const rows = ref<RepeaterRow[]>([])
const currentLangByField = ref<Record<string, number>>({})
const expandedRows = ref<Set<string>>(new Set())

// Load initial rows
const init = () => {
  const value = props.modelValue
  if (!value) {
    rows.value = []
    return
  }

  if (typeof value === 'string') {
    try {
      rows.value = JSON.parse(value)
    } catch {
      rows.value = []
    }
  } else if (Array.isArray(value)) {
    rows.value = value
  } else {
    rows.value = []
  }

  // Initialize language tracking for translatable subfields
  const subfields = props.field.children || []
  rows.value.forEach((row: RepeaterRow) => {
    subfields.forEach((subfield: AcfField) => {
      if (isTranslatable(subfield)) {
        currentLangByField.value[`${row.row_id}-${subfield.slug}`] = props.defaultLanguage?.id_lang || 1
      }
    })
  })
}

init()

// Get repeater config
const repeaterConfig = computed(() => ({
  min: props.field.config?.min || 0,
  max: props.field.config?.max || 0,
  displayMode: props.field.config?.displayMode || 'table',
  buttonLabel: props.field.config?.buttonLabel || 'Add Row',
  rowTitle: props.field.config?.rowTitle || 'Row {#}',
  collapsed: props.field.config?.collapsed || false,
}))

// Get subfields
const subfields = computed(() => props.field.children || [])

// Check if can add rows
const canAddRow = computed(() => {
  const max = repeaterConfig.value.max
  return max === 0 || rows.value.length < max
})

// Check if can remove rows
const canRemoveRow = (_index: number) => {
  const min = repeaterConfig.value.min
  return rows.value.length > min
}

// Check if field is translatable
function isTranslatable(field: AcfField): boolean {
  return !!field.value_translatable || !!field.valueTranslatable || !!field.translatable
}

// Get field value for display
function getSubfieldValue(row: RepeaterRow, subfield: AcfField): any {
  if (isTranslatable(subfield)) {
    const langId = currentLangByField.value[`${row.row_id}-${subfield.slug}`] ?? props.defaultLanguage?.id_lang
    const values = row.values[subfield.slug]
    if (typeof values === 'object' && values !== null) {
      return values[langId] ?? ''
    }
    return ''
  }
  return row.values[subfield.slug] ?? getDefaultValue(subfield)
}

// Get default value for a field type
function getDefaultValue(field: AcfField): any {
  switch (field.type) {
    case 'boolean':
      return false
    case 'number':
      return 0
    case 'select':
    case 'radio':
    case 'text':
    case 'textarea':
    case 'email':
    case 'url':
    case 'date':
    case 'color':
      return ''
    default:
      return null
  }
}

// Set subfield value
function setSubfieldValue(row: RepeaterRow, subfield: AcfField, value: any): void {
  if (isTranslatable(subfield)) {
    const langId = currentLangByField.value[`${row.row_id}-${subfield.slug}`] ?? props.defaultLanguage?.id_lang
    if (!row.values[subfield.slug] || typeof row.values[subfield.slug] !== 'object') {
      row.values[subfield.slug] = {}
    }
    row.values[subfield.slug][langId] = value
  } else {
    row.values[subfield.slug] = value
  }
  emitUpdate()
}

// Toggle checkbox value (for multiple selection)
function toggleCheckboxValue(row: RepeaterRow, subfield: AcfField, value: string, checked: boolean): void {
  const currentValues = getSubfieldValue(row, subfield) || []
  const valuesArray = Array.isArray(currentValues) ? [...currentValues] : []

  if (checked) {
    if (!valuesArray.includes(value)) {
      valuesArray.push(value)
    }
  } else {
    const index = valuesArray.indexOf(value)
    if (index > -1) {
      valuesArray.splice(index, 1)
    }
  }

  setSubfieldValue(row, subfield, valuesArray)
}

// Set current language for a subfield in a specific row
function setCurrentLanguage(rowId: string, subfieldSlug: string, langId: number): void {
  currentLangByField.value[`${rowId}-${subfieldSlug}`] = langId
}

// Add new row
function addRow(): void {
  if (!canAddRow.value) return

  const newRow: RepeaterRow = {
    row_id: uuidv4(),
    collapsed: Boolean(repeaterConfig.value.collapsed),
    values: {},
  }

  // Initialize default values
  subfields.value.forEach((field: AcfField) => {
    const defaultVal = getDefaultValue(field)
    if (isTranslatable(field)) {
      newRow.values[field.slug] = {}
      props.languages.forEach((lang: any) => {
        newRow.values[field.slug][lang.id_lang] = defaultVal
      })
    } else {
      newRow.values[field.slug] = defaultVal
    }
  })

  rows.value.push(newRow)
  expandedRows.value.add(newRow.row_id)
  emitUpdate()
}

// Remove row
function removeRow(index: number): void {
  if (!canRemoveRow(index)) return

  const rowId = rows.value[index].row_id
  expandedRows.value.delete(rowId)
  rows.value.splice(index, 1)
  emitUpdate()
}

// Toggle row expanded/collapsed
function toggleRow(rowId: string): void {
  if (expandedRows.value.has(rowId)) {
    expandedRows.value.delete(rowId)
  } else {
    expandedRows.value.add(rowId)
  }
}

// Reorder rows (drag & drop support)
function moveRow(fromIndex: number, toIndex: number): void {
  if (toIndex < 0 || toIndex >= rows.value.length) return
  const [movedRow] = rows.value.splice(fromIndex, 1)
  rows.value.splice(toIndex, 0, movedRow)
  emitUpdate()
}

// Get row title
function getRowTitle(index: number, row: RepeaterRow): string {
  let title = String(repeaterConfig.value.rowTitle || 'Row {#}')
  title = title.replace('{#}', String(index + 1))

  // Replace field placeholders with values
  subfields.value.forEach((field: AcfField) => {
    const value = getSubfieldValue(row, field)
    const stringValue = typeof value === 'string' ? value : (value ? String(value) : '')
    title = title.replace(`{${field.slug}}`, stringValue)
  })

  return title
}

// Emit update
function emitUpdate(): void {
  emit('update:modelValue', rows.value.length > 0 ? rows.value : null)
}

// Parse choices for select fields
function parseChoices(choices: any): Array<{ value: string; label: string }> {
  if (!choices) return []

  if (Array.isArray(choices)) {
    return choices.map((item: any) => {
      if (typeof item === 'object' && item !== null) {
        return { value: item.value || '', label: item.label || '' }
      }
      return { value: String(item), label: String(item) }
    })
  }

  if (typeof choices === 'string') {
    return choices
      .split('\n')
      .map((line: string) => line.trim())
      .filter((line: string) => line.length > 0)
      .map((line: string) => {
        const parts = line.split(':').map((p: string) => p.trim())
        return {
          value: parts[0] || '',
          label: parts[1] || parts[0] || '',
        }
      })
  }

  return []
}
</script>

<template>
  <div class="repeater-global-field">
    <!-- Info alert -->
    <div class="alert alert-info mb-3">
      <i class="material-icons mr-2" style="vertical-align: middle; font-size: 18px;">info</i>
      <strong>Repeater Field</strong>
      <p class="mb-0 mt-2 small">
        Add multiple rows of {{ subfields.length }} field{{ subfields.length !== 1 ? 's' : '' }}.
        These rows will be displayed for all entities globally.
      </p>
    </div>

    <!-- No subfields warning -->
    <div v-if="!subfields || subfields.length === 0" class="alert alert-warning">
      <i class="material-icons mr-2" style="vertical-align: middle;">warning</i>
      No subfields defined for this repeater. Please add subfields in the field builder.
    </div>

    <!-- Rows container -->
    <div v-else class="repeater-rows-container">
      <!-- Empty state -->
      <div v-if="rows.length === 0" class="alert alert-secondary">
        <i class="material-icons mr-2" style="vertical-align: middle;">info</i>
        No rows added yet. Click "{{ repeaterConfig.buttonLabel }}" to add the first row.
      </div>

      <!-- Rows -->
      <div v-else class="repeater-rows">
        <div
          v-for="(row, index) in rows"
          :key="row.row_id"
          class="repeater-row-card"
          :class="{ collapsed: !expandedRows.has(row.row_id) }"
        >
          <!-- Row header -->
          <div class="repeater-row-header">
            <button
              type="button"
              class="repeater-toggle-btn"
              @click="toggleRow(row.row_id)"
              title="Toggle expand/collapse"
            >
              <i class="material-icons">
                {{ expandedRows.has(row.row_id) ? 'expand_more' : 'chevron_right' }}
              </i>
            </button>

            <span class="repeater-row-title">
              {{ getRowTitle(index, row) }}
            </span>

            <div class="repeater-row-actions ml-auto">
              <button
                v-if="index > 0"
                type="button"
                class="btn btn-link btn-sm p-0 mr-2"
                @click="moveRow(index, index - 1)"
                title="Move up"
              >
                <i class="material-icons">arrow_upward</i>
              </button>

              <button
                v-if="index < rows.length - 1"
                type="button"
                class="btn btn-link btn-sm p-0 mr-2"
                @click="moveRow(index, index + 1)"
                title="Move down"
              >
                <i class="material-icons">arrow_downward</i>
              </button>

              <button
                v-if="canRemoveRow(index)"
                type="button"
                class="btn btn-link btn-sm text-danger p-0"
                @click="removeRow(index)"
                title="Delete row"
              >
                <i class="material-icons">delete</i>
              </button>
            </div>
          </div>

          <!-- Row content (expanded) -->
          <div v-show="expandedRows.has(row.row_id)" class="repeater-row-content">
            <div class="repeater-subfields">
              <div
                v-for="subfield in subfields"
                :key="subfield.uuid"
                class="repeater-subfield"
              >
                <!-- Subfield label -->
                <label class="form-control-label">
                  <strong>{{ subfield.title }}</strong>
                  <span
                    v-if="isTranslatable(subfield)"
                    class="badge badge-info ml-2"
                    style="font-size: 0.7rem;"
                  >
                    <i class="material-icons" style="font-size: 12px; vertical-align: middle;">
                      language
                    </i>
                    Translatable
                  </span>
                </label>

                <!-- Language tabs (translatable fields) -->
                <div
                  v-if="isTranslatable(subfield) && languages.length > 1"
                  class="acf-lang-tabs mb-2"
                >
                  <button
                    v-for="lang in languages"
                    :key="lang.id_lang"
                    type="button"
                    class="acf-lang-tab"
                    :class="{
                      active:
                        (currentLangByField[`${row.row_id}-${subfield.slug}`] ??
                          defaultLanguage?.id_lang) === lang.id_lang,
                      'is-default': lang.is_default,
                    }"
                    @click="setCurrentLanguage(row.row_id, subfield.slug, lang.id_lang)"
                  >
                    {{ lang.iso_code.toUpperCase() }}
                    <span v-if="lang.is_default" class="material-icons" style="font-size: 12px;">
                      star
                    </span>
                  </button>
                </div>

                <!-- Subfield inputs by type -->
                <!-- Text, Email, URL -->
                <input
                  v-if="subfield.type === 'text' || subfield.type === 'email' || subfield.type === 'url'"
                  :type="subfield.type"
                  class="form-control form-control-sm"
                  :value="getSubfieldValue(row, subfield)"
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLInputElement).value,
                    )
                  "
                  :placeholder="subfield.config?.placeholder || ''"
                />

                <!-- Textarea -->
                <textarea
                  v-else-if="subfield.type === 'textarea'"
                  class="form-control form-control-sm"
                  :rows="subfield.config?.rows || 2"
                  :value="getSubfieldValue(row, subfield)"
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLTextAreaElement).value,
                    )
                  "
                  :placeholder="subfield.config?.placeholder || ''"
                />

                <!-- Number -->
                <input
                  v-else-if="subfield.type === 'number'"
                  type="number"
                  class="form-control form-control-sm"
                  :value="getSubfieldValue(row, subfield)"
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLInputElement).value,
                    )
                  "
                  :min="subfield.validation?.min ?? subfield.config?.min"
                  :max="subfield.validation?.max ?? subfield.config?.max"
                  :step="subfield.config?.step || 1"
                />

                <!-- Date -->
                <input
                  v-else-if="subfield.type === 'date'"
                  type="date"
                  class="form-control form-control-sm"
                  :value="getSubfieldValue(row, subfield)"
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLInputElement).value,
                    )
                  "
                />

                <!-- Color -->
                <input
                  v-else-if="subfield.type === 'color'"
                  type="color"
                  class="form-control form-control-sm"
                  :value="getSubfieldValue(row, subfield) || '#000000'"
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLInputElement).value,
                    )
                  "
                />

                <!-- Boolean (checkbox) -->
                <div v-else-if="subfield.type === 'boolean'" class="form-check">
                  <input
                    type="checkbox"
                    class="form-check-input"
                    :id="`${field.slug}-${row.row_id}-${subfield.slug}`"
                    :checked="!!getSubfieldValue(row, subfield)"
                    @change="
                      setSubfieldValue(
                        row,
                        subfield,
                        ($event.target as HTMLInputElement).checked,
                      )
                    "
                  />
                  <label
                    class="form-check-label"
                    :for="`${field.slug}-${row.row_id}-${subfield.slug}`"
                  >
                    {{ subfield.config?.label || 'Enable' }}
                  </label>
                </div>

                <!-- Select -->
                <select
                  v-else-if="subfield.type === 'select'"
                  class="form-control form-control-sm"
                  :value="getSubfieldValue(row, subfield)"
                  @change="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLSelectElement).value,
                    )
                  "
                >
                  <option value="">-- None --</option>
                  <option
                    v-for="choice in parseChoices(subfield.config?.choices)"
                    :key="choice.value"
                    :value="choice.value"
                  >
                    {{ choice.label }}
                  </option>
                </select>

                <!-- File Upload -->
                <FileUploadField
                  v-else-if="subfield.type === 'file'"
                  :model-value="getSubfieldValue(row, subfield)"
                  :field-slug="`${field.slug}-${row.row_id}-${subfield.slug}`"
                  field-type="file"
                  @update:model-value="setSubfieldValue(row, subfield, $event)"
                />

                <!-- Image Upload -->
                <FileUploadField
                  v-else-if="subfield.type === 'image'"
                  :model-value="getSubfieldValue(row, subfield)"
                  :field-slug="`${field.slug}-${row.row_id}-${subfield.slug}`"
                  field-type="image"
                  accept="image/*"
                  @update:model-value="setSubfieldValue(row, subfield, $event)"
                />

                <!-- Video Upload -->
                <FileUploadField
                  v-else-if="subfield.type === 'video'"
                  :model-value="getSubfieldValue(row, subfield)"
                  :field-slug="`${field.slug}-${row.row_id}-${subfield.slug}`"
                  field-type="video"
                  accept="video/*"
                  @update:model-value="setSubfieldValue(row, subfield, $event)"
                />

                <!-- Radio -->
                <div v-else-if="subfield.type === 'radio'" class="radio-group">
                  <div
                    v-for="choice in parseChoices(subfield.config?.choices)"
                    :key="choice.value"
                    class="form-check"
                  >
                    <input
                      type="radio"
                      class="form-check-input"
                      :name="`${field.slug}-${row.row_id}-${subfield.slug}`"
                      :id="`${field.slug}-${row.row_id}-${subfield.slug}-${choice.value}`"
                      :value="choice.value"
                      :checked="getSubfieldValue(row, subfield) === choice.value"
                      @change="setSubfieldValue(row, subfield, choice.value)"
                    />
                    <label
                      class="form-check-label"
                      :for="`${field.slug}-${row.row_id}-${subfield.slug}-${choice.value}`"
                    >
                      {{ choice.label }}
                    </label>
                  </div>
                </div>

                <!-- Checkbox (multiple) -->
                <div v-else-if="subfield.type === 'checkbox'" class="checkbox-group">
                  <div
                    v-for="choice in parseChoices(subfield.config?.choices)"
                    :key="choice.value"
                    class="form-check"
                  >
                    <input
                      type="checkbox"
                      class="form-check-input"
                      :id="`${field.slug}-${row.row_id}-${subfield.slug}-${choice.value}`"
                      :value="choice.value"
                      :checked="(getSubfieldValue(row, subfield) || []).includes(choice.value)"
                      @change="
                        toggleCheckboxValue(
                          row,
                          subfield,
                          choice.value,
                          ($event.target as HTMLInputElement).checked,
                        )
                      "
                    />
                    <label
                      class="form-check-label"
                      :for="`${field.slug}-${row.row_id}-${subfield.slug}-${choice.value}`"
                    >
                      {{ choice.label }}
                    </label>
                  </div>
                </div>

                <!-- Datetime -->
                <input
                  v-else-if="subfield.type === 'datetime'"
                  type="datetime-local"
                  class="form-control form-control-sm"
                  :value="getSubfieldValue(row, subfield)"
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLInputElement).value,
                    )
                  "
                />

                <!-- Time -->
                <input
                  v-else-if="subfield.type === 'time'"
                  type="time"
                  class="form-control form-control-sm"
                  :value="getSubfieldValue(row, subfield)"
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLInputElement).value,
                    )
                  "
                />

                <!-- RichText (simplified textarea for now) -->
                <textarea
                  v-else-if="subfield.type === 'richtext'"
                  class="form-control form-control-sm richtext-simple"
                  :rows="subfield.config?.rows || 4"
                  :value="getSubfieldValue(row, subfield)"
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLTextAreaElement).value,
                    )
                  "
                  placeholder="Enter HTML content..."
                />

                <!-- Star Rating -->
                <div v-else-if="subfield.type === 'star_rating'" class="star-rating-field">
                  <div class="star-rating-container">
                    <button
                      v-for="star in (subfield.config?.max || 5)"
                      :key="star"
                      type="button"
                      class="star-btn"
                      :class="{ active: star <= (getSubfieldValue(row, subfield) || 0) }"
                      @click="setSubfieldValue(row, subfield, star)"
                    >
                      <i class="material-icons">
                        {{ star <= (getSubfieldValue(row, subfield) || 0) ? 'star' : 'star_border' }}
                      </i>
                    </button>
                    <button
                      v-if="getSubfieldValue(row, subfield)"
                      type="button"
                      class="btn btn-link btn-sm text-muted p-0 ml-2"
                      @click="setSubfieldValue(row, subfield, null)"
                    >
                      <i class="material-icons" style="font-size: 16px;">clear</i>
                    </button>
                  </div>
                </div>

                <!-- List (simple comma/line separated values) -->
                <textarea
                  v-else-if="subfield.type === 'list'"
                  class="form-control form-control-sm"
                  :rows="3"
                  :value="
                    Array.isArray(getSubfieldValue(row, subfield))
                      ? getSubfieldValue(row, subfield).join('\n')
                      : getSubfieldValue(row, subfield) || ''
                  "
                  @input="
                    setSubfieldValue(
                      row,
                      subfield,
                      ($event.target as HTMLTextAreaElement).value
                        .split('\n')
                        .filter((v: string) => v.trim()),
                    )
                  "
                  placeholder="One value per line"
                />

                <!-- Files (multiple) -->
                <FileUploadField
                  v-else-if="subfield.type === 'files'"
                  :model-value="getSubfieldValue(row, subfield)"
                  :field-slug="`${field.slug}-${row.row_id}-${subfield.slug}`"
                  field-type="file"
                  @update:model-value="setSubfieldValue(row, subfield, $event)"
                />

                <!-- Gallery (multiple images) -->
                <FileUploadField
                  v-else-if="subfield.type === 'gallery'"
                  :model-value="getSubfieldValue(row, subfield)"
                  :field-slug="`${field.slug}-${row.row_id}-${subfield.slug}`"
                  field-type="image"
                  accept="image/*"
                  @update:model-value="setSubfieldValue(row, subfield, $event)"
                />

                <!-- Relation (simplified - show IDs) -->
                <div v-else-if="subfield.type === 'relation'" class="relation-field-placeholder">
                  <small class="text-muted">
                    <i class="material-icons" style="font-size: 14px; vertical-align: middle;">link</i>
                    Relation field ({{ subfield.config?.entityType || 'entity' }})
                    - Use entity editor for full selection
                  </small>
                </div>

                <!-- Unsupported type -->
                <div v-else class="alert alert-warning">
                  <small class="text-muted">
                    Subfield type <code>{{ subfield.type }}</code> not yet supported
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add row button -->
      <button
        v-if="canAddRow"
        type="button"
        class="btn btn-outline-secondary btn-sm mt-3"
        @click="addRow"
      >
        <i class="material-icons">add</i>
        {{ repeaterConfig.buttonLabel }}
      </button>

      <!-- Limits info -->
      <small v-if="repeaterConfig.min > 0 || repeaterConfig.max > 0" class="form-text text-muted d-block mt-2">
        <i class="material-icons" style="font-size: 12px; vertical-align: middle;">info</i>
        <span v-if="repeaterConfig.min > 0 && repeaterConfig.max > 0">
          Between {{ repeaterConfig.min }} and {{ repeaterConfig.max }} rows
        </span>
        <span v-else-if="repeaterConfig.min > 0">
          Minimum {{ repeaterConfig.min }} row{{ repeaterConfig.min !== 1 ? 's' : '' }}
        </span>
        <span v-else>
          Maximum {{ repeaterConfig.max }} row{{ repeaterConfig.max !== 1 ? 's' : '' }}
        </span>
      </small>
    </div>
  </div>
</template>

<style scoped>
.repeater-global-field {
  padding: 0;
}

.repeater-rows-container {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  padding: 1rem;
}

.repeater-rows {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.repeater-row-card {
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  background: #f8f9fa;
  overflow: hidden;
}

.repeater-row-card.collapsed {
  background: #e9ecef;
}

.repeater-row-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  cursor: pointer;
  user-select: none;
}

.repeater-row-card.collapsed .repeater-row-header {
  border-bottom: none;
}

.repeater-toggle-btn {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
}

.repeater-toggle-btn:hover {
  color: #495057;
}

.repeater-toggle-btn i {
  font-size: 20px;
}

.repeater-row-title {
  font-weight: 500;
  color: #495057;
  flex-grow: 1;
}

.repeater-row-actions {
  display: flex;
  gap: 0.25rem;
}

.repeater-row-actions .btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  width: 24px;
  height: 24px;
}

.repeater-row-actions .btn i {
  font-size: 18px;
}

.repeater-row-content {
  padding: 1rem;
}

.repeater-subfields {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.repeater-subfield {
  padding: 0.75rem;
  background: white;
  border-radius: 0.25rem;
  border: 1px solid #e9ecef;
}

.repeater-subfield .form-control-label {
  margin-bottom: 0.5rem;
  display: block;
  font-weight: 600;
  color: #495057;
}

.repeater-subfield .badge-info {
  background-color: #17a2b8;
  color: white;
  padding: 0.35rem 0.5rem;
  font-size: 0.7rem;
  border-radius: 0.25rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.form-control-sm {
  font-size: 0.875rem;
  padding: 0.375rem 0.75rem;
}

.alert {
  margin-bottom: 1rem;
}

/* Language tabs */
.acf-lang-tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.5rem;
}

.acf-lang-tab {
  padding: 0.375rem 0.75rem;
  border: 1px solid #dee2e6;
  background: white;
  border-radius: 0.25rem;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.75rem;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.acf-lang-tab:hover {
  border-color: #25b9d7;
  color: #25b9d7;
}

.acf-lang-tab.active {
  background: #25b9d7;
  color: white;
  border-color: #25b9d7;
  font-weight: 600;
}

.acf-lang-tab.is-default {
  border-color: #ffc107;
}

.acf-lang-tab.is-default.active {
  background: #ffc107;
  color: #000;
}

.acf-lang-tab i {
  font-size: 12px;
}

/* Radio & Checkbox groups */
.radio-group,
.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.radio-group .form-check,
.checkbox-group .form-check {
  margin: 0;
}

/* Star Rating */
.star-rating-field {
  display: flex;
  align-items: center;
}

.star-rating-container {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.star-btn {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: #dee2e6;
  transition: color 0.2s;
}

.star-btn:hover,
.star-btn.active {
  color: #ffc107;
}

.star-btn i {
  font-size: 24px;
}

/* RichText simplified */
.richtext-simple {
  font-family: monospace;
  font-size: 0.85rem;
}

/* Relation placeholder */
.relation-field-placeholder {
  padding: 0.5rem;
  background: #f8f9fa;
  border: 1px dashed #dee2e6;
  border-radius: 0.25rem;
}
</style>
