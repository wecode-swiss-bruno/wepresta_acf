<script setup lang="ts">
import { computed, ref } from 'vue'
import { useTranslations } from '@/composables/useTranslations'
import type { JsonLogicRule } from '@/types'
import type { LocationOption } from '@/types/api'

const props = defineProps<{
  rules: JsonLogicRule[]
}>()

const emit = defineEmits<{
  'update:rules': [rules: JsonLogicRule[]]
}>()

const { t } = useTranslations()

// Get locations from window config
const locations = computed<Record<string, LocationOption[]>>(() => {
  return window.acfConfig?.locations || {}
})

// Grouped locations for display
const locationGroups = computed(() => {
  return Object.entries(locations.value).map(([group, items]) => ({
    name: group,
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

function addRule(): void {
  if (!selectedEntityType.value) return

  const newRule: JsonLogicRule = {
    '==': [
      { 'var': 'entity_type' },
      selectedEntityType.value
    ]
  }

  const updatedRules = [...props.rules, newRule]
  emit('update:rules', updatedRules)
  selectedEntityType.value = ''
}

function removeRule(index: number): void {
  const updatedRules = props.rules.filter((_, i) => i !== index)
  emit('update:rules', updatedRules)
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

    <!-- Debug: Show available locations count -->
    <div v-if="locationGroups.length === 0" class="alert alert-warning mt-3">
      <strong>Debug:</strong> No locations loaded from backend. Check window.acfConfig.locations
    </div>

    <!-- Info -->
    <div class="alert alert-info mt-3">
      <strong>{{ t('info') || 'Info' }}</strong><br>
      {{ t('locationRulesInfo') || 'Select entity types where this field group should appear. If no rules are defined, the group will appear on all entities.' }}
      <br><small class="text-muted">Available groups: {{ locationGroups.length }}</small>
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

