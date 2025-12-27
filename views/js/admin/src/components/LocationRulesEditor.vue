<script setup lang="ts">
import { computed, ref } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import type { JsonLogicRule } from '@/types'
import type { LocationOption } from '@/types/api'

const store = useBuilderStore()
const { t } = useTranslations()

// Access group for placement settings
const group = computed(() => store.currentGroup)
const productTabs = computed(() => window.acfConfig?.productTabs || [])

// Get locations from window config
const locations = computed<Record<string, LocationOption[]>>(() => {
  return window.acfConfig?.locations || {}
})

// Grouped locations for display
const locationGroups = computed(() => {
  return Object.entries(locations.value).map(([groupName, items]) => ({
    name: groupName,
    items: items as LocationOption[]
  }))
})

// Location rule operators
const operators = [
  { value: '==', label: 'equals' },
  { value: '!=', label: 'notEquals' },
]

// Current rule being edited
const selectedEntityType = ref<string>('')
const selectedOperator = ref<string>('==')

// Get rules directly from store (ensure it's always an array)
const rules = computed(() => {
  const lr = store.currentGroup?.locationRules
  return Array.isArray(lr) ? lr : []
})

function addRule(): void {
  if (!selectedEntityType.value || !store.currentGroup) return

  const newRule: JsonLogicRule = {
    [selectedOperator.value]: [
      { 'var': 'entity_type' },
      selectedEntityType.value
    ]
  }

  // Ensure locationRules is an array (might be {} or undefined)
  if (!Array.isArray(store.currentGroup.locationRules)) {
    store.currentGroup.locationRules = []
  }
  store.currentGroup.locationRules.push(newRule)

  // Reset selection
  selectedEntityType.value = ''
}

function removeRule(index: number): void {
  if (!store.currentGroup || !Array.isArray(store.currentGroup.locationRules)) return
  store.currentGroup.locationRules.splice(index, 1)
}
</script>

<template>
  <div class="location-rules-editor">
    <p class="text-muted mb-3">{{ t('locationRulesHelp') }}</p>

    <!-- Current rules -->
    <div v-if="rules.length > 0" class="mb-3">
      <h5 class="mb-2">{{ t('currentRules') || 'Current Rules' }}</h5>
      <div class="list-group">
        <div
          v-for="(rule, index) in rules"
          :key="index"
          class="list-group-item d-flex justify-content-between align-items-center"
        >
          <span>
            <code>{{ JSON.stringify(rule) }}</code>
          </span>
          <button
            class="btn btn-sm btn-outline-danger"
            @click="removeRule(index)"
          >
            <i class="material-icons" style="font-size: 18px;">delete</i>
          </button>
        </div>
      </div>
    </div>

    <!-- Add new rule -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">{{ t('addLocationRule') || 'Add Location Rule' }}</h5>
      </div>
      <div class="card-body">
        <div class="form-group mb-3">
          <label>{{ t('entityType') || 'Entity Type' }}</label>
          <select
            v-model="selectedEntityType"
            class="form-control"
          >
            <option value="">{{ t('selectEntityType') || 'Select an entity type...' }}</option>
            <optgroup
              v-for="group in locationGroups"
              :key="group.name"
              :label="group.name"
            >
              <option
                v-for="location in group.items"
                :key="location.value"
                :value="location.value"
              >
                {{ location.label }}
                <span v-if="location.description" class="text-muted"> - {{ location.description }}</span>
              </option>
            </optgroup>
          </select>
        </div>

        <div class="form-group mb-3">
          <label>{{ t('operator') || 'Operator' }}</label>
          <select v-model="selectedOperator" class="form-control">
            <option
              v-for="op in operators"
              :key="op.value"
              :value="op.value"
            >
              {{ t(op.label) || op.label }}
            </option>
          </select>
        </div>

        <button
          class="btn btn-primary"
          :disabled="!selectedEntityType"
          @click="addRule"
        >
          <i class="material-icons" style="font-size: 18px; vertical-align: middle;">add</i>
          {{ t('addRule') || 'Add Rule' }}
        </button>
      </div>
    </div>

    <!-- Placement Settings -->
    <div v-if="group" class="card mt-4">
      <div class="card-header">
        <h5 class="mb-0">{{ t('presentation') || 'Presentation' }}</h5>
      </div>
      <div class="card-body">
        <div class="form-group mb-3">
          <label>{{ t('placementTab') || 'Tab' }}</label>
          <select v-model="group.placementTab" class="form-control">
            <option
              v-for="tab in productTabs"
              :key="tab.value"
              :value="tab.value"
            >
              {{ tab.label }}
            </option>
          </select>
          <small class="form-text text-muted">
            Product page tab where this field group will appear.
          </small>
        </div>

        <div class="form-group mb-3">
          <label>{{ t('priority') || 'Priority' }}</label>
          <input
            v-model.number="group.priority"
            type="number"
            class="form-control"
            min="0"
            max="100"
          >
          <small class="form-text text-muted">
            Lower numbers appear first. Default is 10.
          </small>
        </div>
      </div>
    </div>

    <!-- Info -->
    <div class="alert alert-info mt-3">
      <strong>{{ t('info') || 'Info' }}</strong><br>
      {{ t('locationRulesInfo') || 'Select entity types where this field group should appear. If no rules are defined, the group will appear on all entities.' }}
    </div>
  </div>
</template>

<style scoped>
.location-rules-editor {
  padding: 1.5rem;
}

.available-operators span {
  margin-left: 0.5rem;
}

.available-operators span::after {
  content: ',';
}

.available-operators span:last-child::after {
  content: '';
}
</style>

