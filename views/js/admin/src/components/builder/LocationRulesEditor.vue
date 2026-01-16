<script setup lang="ts">
import { computed, ref } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import type { JsonLogicRule } from '@/types'
import type { LocationOption } from '@/types/api'

const emit = defineEmits<{
  'next-step': []
  'prev-step': []
}>()

const store = useBuilderStore()
const { t } = useTranslations()

// Auto-save state for rule addition
const savingRule = ref(false)

const selectedEntityType = ref<string>('')
const selectedOperator = ref<string>('==')

// Get locations from window config
const locations = computed<Record<string, LocationOption[]>>(() => {
  return window.acfConfig?.locations || {}
})

// Grouped locations for display - enabled items first
const locationGroups = computed(() => {
  return Object.entries(locations.value).map(([groupName, items]) => {
    // Sort items: enabled first, then disabled
    const sortedItems = [...(items as LocationOption[])].sort((a, b) => {
      const aEnabled = a.enabled !== false
      const bEnabled = b.enabled !== false
      
      if (aEnabled === bEnabled) {
        return (a.label || '').localeCompare(b.label || '')
      }
      return aEnabled ? -1 : 1
    })
    
    return {
      name: groupName,
      items: sortedItems
    }
  })
})

// Location rule operators
const operators = [
  { value: '==', label: 'equals' },
  { value: '!=', label: 'notEquals' },
]

// Get rules directly from store (ensure it's always an array)
const rules = computed(() => {
  const lr = store.currentGroup?.locationRules
  return Array.isArray(lr) ? lr : []
})

// Check if at least one entity type is defined
const hasEntityType = computed(() => rules.value.length > 0)



// Validation: can proceed to next step if entity types are selected
const canProceedToFields = computed(() => hasEntityType.value)



async function addRule(event?: Event): Promise<void> {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }

  if (!selectedEntityType.value || !store.currentGroup) return

  const newRule: JsonLogicRule = {
    [selectedOperator.value]: [
      { 'var': 'entity_type' },
      selectedEntityType.value
    ]
  }

  // Ensure locationRules is an array
  if (!Array.isArray(store.currentGroup.locationRules)) {
    store.currentGroup.locationRules = []
  }

  // Add rule locally first
  store.currentGroup.locationRules.push(newRule)

  // Reset selection immediately for better UX
  const ruleType = selectedEntityType.value
  const ruleOperator = selectedOperator.value
  selectedEntityType.value = ''

  // Auto-save to database
  savingRule.value = true
  try {
    await store.saveGroup()
  } catch (error) {
    console.error('❌ Failed to save location rule:', error)
    // Remove the rule from local state if save failed
    if (Array.isArray(store.currentGroup.locationRules)) {
      store.currentGroup.locationRules.pop()
    }
    // Restore selection so user can try again
    selectedEntityType.value = ruleType
    selectedOperator.value = ruleOperator

    alert(t('saveError'))
  } finally {
    savingRule.value = false
  }
}

async function removeRule(index: number): Promise<void> {
  if (!store.currentGroup || !Array.isArray(store.currentGroup.locationRules)) return
  
  store.currentGroup.locationRules.splice(index, 1)
  
  // Auto-save after removal
  try {
    await store.saveGroup()
  } catch (error) {
    console.error('❌ Failed to save after rule removal:', error)
  }
}

/**
 * Get human-readable label for a rule
 */
function getRuleLabel(rule: JsonLogicRule): string {
  if (rule['=='] && Array.isArray(rule['=='])) {
    const entityValue = rule['=='][1]
    const location = findLocationByValue(entityValue)
    if (location) {
      return t('showOnEntity', undefined, { entity: location.label })
    }
    return `${t('entityType')} ${t('entityEquals')} ${entityValue}`
  }
  if (rule['!='] && Array.isArray(rule['!='])) {
    const entityValue = rule['!='][1]
    const location = findLocationByValue(entityValue)
    if (location) {
      return t('excludeEntity', undefined, { entity: location.label })
    }
    return `${t('entityType')} ${t('entityNotEquals')} ${entityValue}`
  }
  return JSON.stringify(rule)
}

/**
 * Find location by value across all groups
 */
function findLocationByValue(value: string): LocationOption | undefined {
  for (const group of locationGroups.value) {
    const found = group.items.find(l => l.value === value)
    if (found) return found
  }
  return undefined
}


</script>

<template>
  <div class="location-rules-editor">
    <!-- Introduction -->
    <div class="mb-4">
      <p class="text-muted mb-4" style="font-size: 0.95rem; line-height: 1.5;">
        <i class="material-icons mr-1" style="vertical-align: middle; font-size: 16px; color: #6c757d;">location_on</i>
        {{ t('locationRulesHelp') }}
      </p>
    </div>

    <!-- Current rules -->
    <div v-if="rules.length > 0" class="mb-4">
      <h4 class="mb-3">
        <i class="material-icons text-success mr-2" style="vertical-align: middle;">check_circle</i>
        {{ t('activeLocationRules') }}
      </h4>
      <p class="text-muted mb-3">{{ t('locationRulesDescription') }}</p>
      <div class="list-group">
        <div
          v-for="(rule, index) in rules"
          :key="index"
          class="list-group-item d-flex justify-content-between align-items-center"
        >
          <span>
            <i class="material-icons text-primary mr-2" style="vertical-align: middle;">location_on</i>
            {{ getRuleLabel(rule) }}
          </span>
          <button
            class="btn btn-sm btn-outline-danger"
            @click="removeRule(index)"
            :title="t('removeRule')"
          >
            <i class="material-icons" style="font-size: 18px;">delete</i>
          </button>
        </div>
      </div>
    </div>

    <!-- Add new rule -->
    <div class="card">
      <div class="card-header bg-light">
        <h4 class="mb-0">
          <i class="material-icons mr-2" style="vertical-align: middle;">add_location</i>
          {{ t('addLocationRule') }}
        </h4>
      </div>
      <div class="card-body">
        <div class="form-group mb-3">
          <label class="form-control-label">
            <i class="material-icons mr-1" style="font-size: 16px; vertical-align: middle;">category</i>
            {{ t('applyTo') }}
            <span class="text-danger">*</span>
          </label>
          <select
            v-model="selectedEntityType"
            class="form-control"
          >
            <option value="">{{ t('selectEntityType') }}</option>
            <optgroup
              v-for="group in locationGroups"
              :key="group.name"
              :label="group.name"
            >
              <option
                v-for="location in group.items"
                :key="location.value"
                :value="location.enabled ? location.value : ''"
                :disabled="!location.enabled"
              >
                {{ location.label }}
                {{ !location.enabled ? t('comingSoon') : '' }}
                {{ location.enabled && location.integration_type === 'legacy' ? t('legacy') : '' }}
              </option>
            </optgroup>
          </select>
          <small class="form-text text-muted">
            {{ t('contentTypeDescription') }}
          </small>
        </div>

        <div class="form-group mb-3">
          <label class="form-control-label">
            <i class="material-icons mr-1" style="font-size: 16px; vertical-align: middle;">filter_list</i>
            {{ t('operator') }}
          </label>
          <select v-model="selectedOperator" class="form-control">
            <option
              v-for="op in operators"
              :key="op.value"
              :value="op.value"
            >
              {{ t(op.label, op.label) }}
            </option>
          </select>
          <small class="form-text text-muted">
            {{ t('conditionDescription') }}
          </small>
        </div>

        <button
          type="button"
          class="btn btn-primary btn-lg"
          :disabled="!selectedEntityType || savingRule"
          @click="addRule"
        >
          <span v-if="savingRule">
            <i class="material-icons mr-1" style="font-size: 18px; vertical-align: middle;">sync</i>
            {{ t('saving') }}
          </span>
          <span v-else>
            <i class="material-icons mr-1" style="font-size: 18px; vertical-align: middle;">add</i>
            {{ t('addRule') }}
          </span>
        </button>
      </div>
    </div>



    <!-- Step Navigation -->
    <div class="acfps-step-navigation">
      <button class="btn btn-outline-secondary" @click="emit('prev-step')">
        <span class="material-icons">arrow_back</span>
        {{ t('general') }}
      </button>
      <button 
        class="btn btn-primary"
        :disabled="!canProceedToFields"
        @click="emit('next-step')"
        :title="!hasEntityType ? t('defineEntityTypeFirst') : ''"
      >
        {{ t('next') }} {{ t('fields') }}
        <span class="material-icons">arrow_forward</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.location-rules-editor {
  padding: 1.5rem;
}

.location-rules-editor .card {
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
}

.location-rules-editor .card-header {
  border-bottom: 1px solid #dee2e6;
  font-weight: 600;
  background-color: #f8f9fa;
}

.location-rules-editor .card-header.bg-light {
  background-color: #f8f9fa !important;
}

.location-rules-editor .form-control-label {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
}

.location-rules-editor .form-control-label .material-icons {
  color: #6c757d;
}

.location-rules-editor .form-text {
  color: #6c757d;
  font-size: 0.875rem;
}

.location-rules-editor .list-group-item {
  border: 1px solid rgba(0, 0, 0, 0.125);
  margin-bottom: 0.5rem;
  border-radius: 0.375rem;
}

.location-rules-editor .btn-primary.btn-lg {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
}

.location-rules-editor .alert-warning {
  border-left: 3px solid #ffc107;
  background-color: #fffbf0;
  color: #856404;
  border-radius: 0.25rem;
}

.acfps-step-navigation {
  display: flex;
  justify-content: space-between;
  padding: 1.5rem;
  border-top: 1px solid #dee2e6;
  margin-top: 2rem;
  background: #f8f9fa;
}

.acfps-step-navigation .btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.acfps-step-navigation .btn .material-icons {
  font-size: 18px;
}

/* Responsive design */
@media (max-width: 768px) {
  .location-rules-editor .row .col-md-6 {
    margin-bottom: 1rem;
  }

  .acfps-step-navigation {
    flex-direction: column;
    gap: 1rem;
  }

  .acfps-step-navigation .btn {
    justify-content: center;
  }
}
</style>
