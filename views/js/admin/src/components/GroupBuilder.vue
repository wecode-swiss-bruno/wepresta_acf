<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import FieldList from '@/components/FieldList.vue'
import FieldConfigurator from '@/components/FieldConfigurator.vue'
import GroupSettings from '@/components/GroupSettings.vue'
import LocationRulesEditor from '@/components/LocationRulesEditor.vue'

const store = useBuilderStore()
const { t } = useTranslations()

const activeTab = ref<'settings' | 'location' | 'fields'>('settings')

// Check if group has been saved to database (has an ID)
const isGeneralSettingsComplete = computed(() => {
  const group = store.currentGroup
  return group && group.id !== undefined && group.id !== null
})

// Check if location rules have at least one entity type defined
const hasEntityTypeDefined = computed(() => {
  const group = store.currentGroup
  const rules = group?.locationRules
  return Array.isArray(rules) && rules.length > 0
})

// Check if location tab can be accessed (requires completed general settings)
const canAccessLocation = computed(() => isGeneralSettingsComplete.value)

// Check if fields tab can be accessed (requires completed general settings AND entity type)
const canAccessFields = computed(() => isGeneralSettingsComplete.value && hasEntityTypeDefined.value)


// Step status for wizard
const stepStatus = computed(() => ({
  settings: {
    completed: isGeneralSettingsComplete.value,
    current: activeTab.value === 'settings'
  },
  location: {
    completed: hasEntityTypeDefined.value,
    current: activeTab.value === 'location',
    locked: !canAccessLocation.value
  },
  fields: {
    completed: false,
    current: activeTab.value === 'fields',
    locked: !canAccessFields.value
  },
}))

// Navigation functions
async function goToNextStep(): Promise<void> {
  // Always save before navigating to next step
  if (store.currentGroup) {
    await store.saveGroup()
    
    // If there was an error saving, don't proceed
    if (store.error) {
      console.warn('Cannot proceed to next step - save failed:', store.error)
      return
    }
  }
  
  if (activeTab.value === 'settings' && canAccessLocation.value) {
    activeTab.value = 'location'
  } else if (activeTab.value === 'location' && canAccessFields.value) {
    activeTab.value = 'fields'
  }
}

function goToPreviousStep(): void {
  if (activeTab.value === 'fields') {
    activeTab.value = 'location'
  } else if (activeTab.value === 'location') {
    activeTab.value = 'settings'
  }
}

// Watch for group changes to set initial active tab
// Only change tab when group is first loaded, not when it's updated during editing
watch(() => store.currentGroup, (newGroup, oldGroup) => {
  if (newGroup) {
    // Only auto-change tab on initial load (when oldGroup is null/undefined)
    // OR when switching to a different group (different ID)
    // Don't change tab when group is updated during editing (e.g., when adding a rule)
    const isNewGroup = !oldGroup || (oldGroup.id !== newGroup.id)
    
    if (isNewGroup) {
      // Determine the appropriate starting tab based on group state
      if (!newGroup.id) {
        // New group - start with settings
        activeTab.value = 'settings'
      } else if (!hasEntityTypeDefined.value) {
        // Existing group without location rules - start with settings
        activeTab.value = 'settings'
      } else {
        // Existing group with location rules - start with fields
        activeTab.value = 'fields'
      }
    }
    // If not a new group, keep the current tab (user is editing)
  }
}, { immediate: true })

// Force return to appropriate tab if trying to access tabs without requirements
watch(activeTab, (newTab) => {
  if (newTab === 'location' && !canAccessLocation.value) {
    activeTab.value = 'settings'
  } else if (newTab === 'fields' && !canAccessFields.value) {
    // If fields can't be accessed but location can, go to location
    if (canAccessLocation.value) {
      activeTab.value = 'location'
    } else {
      activeTab.value = 'settings'
    }
  }
})
</script>

<template>
  <div class="acfps-group-builder">
    <!-- Group title indicator -->
    <div class="acfps-builder-title-bar">
      <h5 class="mb-0">
        <span class="material-icons text-muted mr-2">folder</span>
        {{ store.currentGroup?.title || t('addGroup') }}
        <span v-if="store.saving" class="spinner-border spinner-border-sm ml-2"></span>
        <!-- ✅ Badge unsaved changes -->
        <span v-if="store.hasUnsavedChanges && !store.saving" class="badge badge-warning ml-2" title="Unsaved changes">
          <span class="material-icons" style="font-size: 14px; vertical-align: middle;">warning</span>
          Not saved
        </span>
      </h5>
    </div>

    <!-- Wizard Progress Bar -->
    <div class="acfps-wizard-steps">
      <div 
        class="wizard-step" 
        :class="{ completed: stepStatus.settings.completed, active: stepStatus.settings.current }"
        @click="activeTab = 'settings'"
      >
        <div class="step-number">
          <span v-if="stepStatus.settings.completed" class="material-icons">check</span>
          <span v-else>1</span>
        </div>
        <div class="step-label">{{ t('general') }}</div>
      </div>
      
      <div class="wizard-connector" :class="{ completed: stepStatus.settings.completed }"></div>
      
      <div 
        class="wizard-step" 
        :class="{ 
          completed: stepStatus.location.completed, 
          active: stepStatus.location.current, 
          locked: stepStatus.location.locked 
        }"
        @click="canAccessLocation && (activeTab = 'location')"
        :title="stepStatus.location.locked ? t('completeGeneralSettingsFirst') : ''"
      >
        <div class="step-number">
          <span v-if="stepStatus.location.completed" class="material-icons">check</span>
          <span v-else>2</span>
        </div>
        <div class="step-label">{{ t('location') }}</div>
      </div>
      
      <div class="wizard-connector" :class="{ completed: stepStatus.location.completed }"></div>
      
      <div 
        class="wizard-step" 
        :class="{ 
          active: stepStatus.fields.current, 
          locked: stepStatus.fields.locked 
        }"
        @click="canAccessFields && (activeTab = 'fields')"
        :title="stepStatus.fields.locked ? (canAccessLocation ? t('defineEntityTypeFirst') : t('completeGeneralSettingsFirst')) : ''"
      >
        <div class="step-number">3</div>
        <div class="step-label">{{ t('fields') }}</div>
      </div>

    </div>

    <!-- Content -->
    <div class="acfps-builder-content">
      <!-- Fields tab: two-panel layout -->
      <template v-if="activeTab === 'fields'">
        <div class="acfps-builder-layout">
          <div class="acfps-field-list-panel">
            <FieldList />
          </div>
          <div class="acfps-field-config-panel">
            <FieldConfigurator />
          </div>
        </div>

        <!-- Step Navigation for Fields tab -->
        <div class="acfps-step-navigation">
          <button class="btn btn-outline-secondary" @click="goToPreviousStep">
            <span class="material-icons">arrow_back</span>
            {{ t('location') }}
          </button>
          <div></div> <!-- Spacer -->
        </div>
      </template>

      <!-- Settings tab -->
      <template v-else-if="activeTab === 'settings'">
        <GroupSettings @next-step="goToNextStep" />
      </template>

      <!-- Location tab -->
      <template v-else-if="activeTab === 'location'">
        <LocationRulesEditor @next-step="goToNextStep" @prev-step="goToPreviousStep" />
      </template>

    </div>
  </div>
</template>

<style scoped>
.acfps-group-builder {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.acfps-builder-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid var(--border-color, #e9e9e9);
  background: var(--card-bg, #fff);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-left .btn-link {
  padding: 0.25rem 0.5rem;
  color: var(--gray, #6c757d);
}

.header-left .btn-link .material-icons {
  font-size: 20px;
  vertical-align: middle;
}

.group-title {
  margin: 0;
  font-size: 1.1rem;
  color: var(--text-color, #363a41);
}

.header-right .btn .material-icons {
  font-size: 18px;
  vertical-align: middle;
  margin-right: 0.25rem;
}

/* Wizard Steps Bar */
.acfps-wizard-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem 2rem;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.wizard-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s;
}

.wizard-step.locked {
  cursor: not-allowed;
  opacity: 0.5;
}

.wizard-step .step-number {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #e9ecef;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.1rem;
  transition: all 0.3s;
  border: 3px solid transparent;
}

.wizard-step .step-number .material-icons {
  font-size: 24px;
}

.wizard-step.active .step-number {
  background: #25b9d7;
  color: white;
  border-color: #25b9d7;
  box-shadow: 0 0 0 4px rgba(37, 185, 215, 0.2);
  transform: scale(1.1);
}

.wizard-step.completed .step-number {
  background: #70b580;
  color: white;
  border-color: #70b580;
}

.wizard-step.completed:hover .step-number {
  transform: scale(1.05);
}

.wizard-step .step-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6c757d;
  transition: color 0.3s;
}

.wizard-step.active .step-label {
  color: #25b9d7;
  font-weight: 600;
}

.wizard-step.completed .step-label {
  color: #70b580;
}

.wizard-connector {
  width: 80px;
  height: 3px;
  background: #e9ecef;
  margin: 0 1rem;
  margin-bottom: 1.75rem;
  transition: background 0.3s;
  position: relative;
}

.wizard-connector::after {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 0;
  background: #70b580;
  transition: width 0.5s ease;
}

.wizard-connector.completed::after {
  width: 100%;
}

.acfps-builder-content {
  flex: 1;
  overflow: hidden;
}

.acfps-builder-layout {
  display: flex;
  height: 100%;
  min-height: 500px;
}

.acfps-field-list-panel {
  flex: 0 0 50%;
  border-right: 1px solid var(--border-color, #e9e9e9);
  overflow-y: auto;
  background: var(--card-bg, #fff);
}

.acfps-field-config-panel {
  flex: 0 0 50%;
  overflow-y: auto;
  background: var(--light-bg, #fafbfc);
}

/* ✅ Badge unsaved changes */
.acfps-builder-title-bar .badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  vertical-align: middle;
  animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.acfps-builder-title-bar .mr-2 {
  margin-right: 0.5rem;
}

.acfps-builder-title-bar .ml-2 {
  margin-left: 0.5rem;
}

/* Step Navigation for Fields tab */
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
  .acfps-step-navigation {
    flex-direction: column;
    gap: 1rem;
  }

  .acfps-step-navigation .btn {
    justify-content: center;
  }
}
</style>

