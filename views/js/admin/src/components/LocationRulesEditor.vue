<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { useApi } from '@/composables/useApi'
import type { JsonLogicRule, FrontHookOption } from '@/types'
import type { LocationOption } from '@/types/api'

const emit = defineEmits<{
  'next-step': []
  'prev-step': []
}>()

const store = useBuilderStore()
const { t } = useTranslations()
const api = useApi()

// Access group for placement settings
const group = computed(() => store.currentGroup)

// Auto-save state for rule addition
const savingRule = ref(false)

// Front hooks state - now per entity type
const availableFrontHooks = ref<Record<string, FrontHookOption[]>>({})
const loadingHooks = ref<Record<string, boolean>>({})
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

// Get ALL entity types from rules (not just the first one!)
const activeEntityTypes = computed(() => {
  const types: string[] = []
  for (const rule of rules.value) {
    if (rule['=='] && Array.isArray(rule['=='])) {
      const entityType = rule['=='][1] as string
      if (entityType && !types.includes(entityType)) {
        types.push(entityType)
      }
    }
  }
  return types
})

// Check if ALL entity types have a display hook selected
const hasAllDisplayHooks = computed(() => {
  if (activeEntityTypes.value.length === 0) return false
  
  const displayHooks = group.value?.foOptions?.displayHooks || {}
  
  for (const entityType of activeEntityTypes.value) {
    if (!displayHooks[entityType]) {
      return false
    }
  }
  
  return true
})

// Validation: can proceed to next step only if entity types AND all display hooks are selected
const canProceedToFields = computed(() => hasEntityType.value && hasAllDisplayHooks.value)

// Load available front hooks for a specific entity type
async function loadHooksForEntityType(entityType: string): Promise<void> {
  if (!entityType || loadingHooks.value[entityType]) return
  
  loadingHooks.value[entityType] = true
  
  try {
    const { hooks, defaultHook } = await api.getFrontHooksForEntity(entityType)
    availableFrontHooks.value[entityType] = hooks
    
    // Initialize displayHooks object if not exists
    if (group.value && !group.value.foOptions) {
      group.value.foOptions = {}
    }
    if (group.value && !group.value.foOptions!.displayHooks) {
      group.value.foOptions!.displayHooks = {}
    }
    
    // Auto-select default hook if no hook is selected yet for this entity
    if (group.value && !group.value.foOptions!.displayHooks![entityType] && defaultHook) {
      group.value.foOptions!.displayHooks![entityType] = defaultHook
      // Auto-save
      await store.saveGroup()
    }
  } catch (error) {
    console.error(`Failed to load front hooks for ${entityType}:`, error)
    availableFrontHooks.value[entityType] = []
  } finally {
    loadingHooks.value[entityType] = false
  }
}

// Watch active entity types and load hooks for each
watch(activeEntityTypes, async (newTypes, oldTypes) => {
  // Load hooks for new entity types
  for (const entityType of newTypes) {
    if (!oldTypes?.includes(entityType)) {
      await loadHooksForEntityType(entityType)
    }
  }
  
  // Clean up hooks for removed entity types
  if (group.value?.foOptions?.displayHooks) {
    for (const entityType of Object.keys(group.value.foOptions.displayHooks)) {
      if (!newTypes.includes(entityType)) {
        delete group.value.foOptions.displayHooks[entityType]
      }
    }
  }
}, { immediate: true, deep: true })

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
    console.log(`✅ Rule "${ruleOperator} ${ruleType}" saved automatically`)
  } catch (error) {
    console.error('❌ Failed to save location rule:', error)
    // Remove the rule from local state if save failed
    if (Array.isArray(store.currentGroup.locationRules)) {
      store.currentGroup.locationRules.pop()
    }
    // Restore selection so user can try again
    selectedEntityType.value = ruleType
    selectedOperator.value = ruleOperator

    alert(t('saveError') || 'Failed to save location rule. Please try again.')
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
    console.log('✅ Rule removed and saved')
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
      return `Show on ${location.label}`
    }
    return `Entity = ${entityValue}`
  }
  if (rule['!='] && Array.isArray(rule['!='])) {
    const entityValue = rule['!='][1]
    const location = findLocationByValue(entityValue)
    if (location) {
      return `Exclude ${location.label}`
    }
    return `Entity ≠ ${entityValue}`
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

/**
 * Handle display hook change for a specific entity type - auto-save
 */
async function handleDisplayHookChange(entityType: string): Promise<void> {
  if (!group.value) return
  
  try {
    await store.saveGroup()
    console.log(`✅ Display hook for ${entityType} saved:`, group.value.foOptions?.displayHooks?.[entityType])
  } catch (error) {
    console.error(`❌ Failed to save display hook for ${entityType}:`, error)
    alert(`Failed to save display hook for ${entityType}. Please try again.`)
  }
}

/**
 * Get label for entity type
 */
function getEntityTypeLabel(entityType: string): string {
  const location = findLocationByValue(entityType)
  return location?.label || entityType
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
        Active Display Rules
      </h4>
      <p class="text-muted mb-3">Your custom fields will appear on these content types:</p>
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
            title="Remove this rule"
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
                {{ !location.enabled ? ' (coming soon...)' : '' }}
                {{ location.enabled && location.integration_type === 'legacy' ? ' (legacy)' : '' }}
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
            {{ t('operator') || 'Condition' }}
          </label>
          <select v-model="selectedOperator" class="form-control">
            <option
              v-for="op in operators"
              :key="op.value"
              :value="op.value"
            >
              {{ t(op.label) || op.label }}
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
            {{ t('saving') || 'Saving...' }}
          </span>
          <span v-else>
            <i class="material-icons mr-1" style="font-size: 18px; vertical-align: middle;">add</i>
            {{ t('addRule') || 'Add Display Rule' }}
          </span>
        </button>
      </div>
    </div>

    <!-- Front-Office Display Hook Selection - One per entity type -->
    <div v-if="group && activeEntityTypes.length > 0" class="card mt-4 border-primary">
      <div class="card-header bg-light">
        <h4 class="mb-0">
          <i class="material-icons mr-2" style="vertical-align: middle;">tune</i>
          {{ t('presentation') || 'Presentation Settings' }}
          <span class="badge badge-primary ml-2">Required</span>
        </h4>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3">
          <i class="material-icons mr-1" style="vertical-align: middle; font-size: 16px;">visibility</i>
          Choose where your custom fields will be displayed for each content type.
        </p>

        <!-- Display hook selector for EACH entity type -->
        <div
          v-for="entityType in activeEntityTypes"
          :key="entityType"
          class="form-group mb-3"
        >
          <label class="form-control-label">
            <i class="material-icons mr-1" style="font-size: 16px; vertical-align: middle;">insert_link</i>
            Display Hook for <strong>{{ getEntityTypeLabel(entityType) }}</strong>
            <span class="text-danger">*</span>
          </label>
          
          <select
            v-if="!loadingHooks[entityType]"
            v-model="group.foOptions!.displayHooks![entityType]"
            class="form-control"
            :class="{ 'is-invalid': !group.foOptions?.displayHooks?.[entityType] }"
            @change="handleDisplayHookChange(entityType)"
          >
            <option value="">{{ t('selectDisplayHook') || 'Choose display location...' }}</option>
            <option
              v-for="hook in availableFrontHooks[entityType] || []"
              :key="hook.value"
              :value="hook.value"
            >
              {{ hook.label }}
              <template v-if="hook.description"> - {{ hook.description }}</template>
            </option>
          </select>
          
          <div v-else class="form-control">
            <i class="material-icons" style="font-size: 18px; vertical-align: middle;">sync</i>
            Loading hooks for {{ entityType }}...
          </div>
          
          <small class="form-text text-muted">
            Where fields will appear on {{ getEntityTypeLabel(entityType) }} pages.
          </small>
          
          <div v-if="!group.foOptions?.displayHooks?.[entityType]" class="invalid-feedback d-block">
            Please select a display hook for {{ getEntityTypeLabel(entityType) }}.
          </div>
        </div>
      </div>
    </div>

    <!-- Warning if no entity type selected -->
    <div v-if="group && activeEntityTypes.length === 0" class="card mt-4 border-warning">
      <div class="card-body">
        <div class="alert alert-warning mb-0">
          <i class="material-icons mr-2" style="vertical-align: middle; font-size: 18px;">warning</i>
          <strong>Content Type Required</strong><br>
          <small>Please select a content type above before choosing a display hook.</small>
        </div>
      </div>
    </div>

    <!-- Help Section -->
    <div class="mt-4 p-3 bg-light rounded">
      <h5 class="mb-3 text-muted">
        <i class="material-icons mr-2" style="vertical-align: middle;">help_outline</i>
        How it works
      </h5>
      <div class="row">
        <div class="col-md-6">
          <ul class="mb-0 small text-muted">
            <li>Select content types where your fields should appear</li>
            <li>Add multiple rules to show fields on different content types</li>
            <li>Each content type can have its own display hook</li>
            <li>Use "Exclude" to hide fields from specific content types</li>
          </ul>
        </div>
        <div class="col-md-6">
          <div class="small text-muted">
            <strong>Examples:</strong><br>
            • Products: Add custom fields to product pages<br>
            • Categories: Add fields specific to category pages<br>
            • Multiple types: Each with its own display location
          </div>
        </div>
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
        :title="!hasEntityType ? t('defineEntityTypeFirst') : !hasAllDisplayHooks ? 'Please select display hooks for all content types' : ''"
      >
        Next: {{ t('fields') }}
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
