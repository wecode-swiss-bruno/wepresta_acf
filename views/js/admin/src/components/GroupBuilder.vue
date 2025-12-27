<script setup lang="ts">
import { ref } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import FieldList from '@/components/FieldList.vue'
import FieldConfigurator from '@/components/FieldConfigurator.vue'
import GroupSettings from '@/components/GroupSettings.vue'

const store = useBuilderStore()
const { t } = useTranslations()

const activeTab = ref<'fields' | 'settings' | 'location'>('fields')
</script>

<template>
  <div class="acfps-group-builder">
    <!-- Group title indicator -->
    <div class="acfps-builder-title-bar">
      <h5 class="mb-0">
        <span class="material-icons text-muted mr-2">folder</span>
        {{ store.currentGroup?.title || t('addGroup') }}
        <span v-if="store.saving" class="spinner-border spinner-border-sm ml-2"></span>
      </h5>
    </div>

    <!-- Tabs -->
    <div class="acfps-builder-tabs">
      <button 
        class="tab-btn"
        :class="{ active: activeTab === 'fields' }"
        @click="activeTab = 'fields'"
      >
        <span class="material-icons">list</span>
        {{ t('fields') }}
      </button>
      <button 
        class="tab-btn"
        :class="{ active: activeTab === 'settings' }"
        @click="activeTab = 'settings'"
      >
        <span class="material-icons">settings</span>
        {{ t('general') }}
      </button>
      <button 
        class="tab-btn"
        :class="{ active: activeTab === 'location' }"
        @click="activeTab = 'location'"
      >
        <span class="material-icons">place</span>
        {{ t('location') }}
      </button>
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
      </template>

      <!-- Settings tab -->
      <template v-else-if="activeTab === 'settings'">
        <GroupSettings />
      </template>

      <!-- Location tab -->
      <template v-else-if="activeTab === 'location'">
        <div class="acfps-form-section">
          <h4>{{ t('location') }}</h4>
          <p class="text-muted">{{ t('locationRulesHelp') }}</p>
          <!-- TODO: Location rules editor will be added later -->
          <div class="alert alert-info">
            Location rules editor coming soon. For now, all field groups will appear on all products.
          </div>
        </div>
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

.acfps-builder-tabs {
  display: flex;
  border-bottom: 1px solid var(--border-color, #e9e9e9);
  background: var(--card-bg, #fff);
  padding: 0 1rem;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  color: var(--gray, #6c757d);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: all 0.15s ease;
}

.tab-btn:hover {
  color: var(--text-color, #363a41);
}

.tab-btn.active {
  color: var(--primary, #25b9d7);
  border-bottom-color: var(--primary, #25b9d7);
}

.tab-btn .material-icons {
  font-size: 18px;
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
</style>

