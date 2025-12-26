<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { useApi } from '@/composables/useApi'
import SyncStatusBadge from './SyncStatusBadge.vue'

const store = useBuilderStore()
const { t } = useTranslations()
const api = useApi()

// Sync state
const syncEnabled = ref(false)
const syncStatus = ref<Record<number, { status: string }>>({})
const themeOnlyGroups = ref<Array<{ slug: string; title: string }>>([])

// Load sync status on mount
onMounted(async () => {
  await loadSyncStatus()
})

async function loadSyncStatus(): Promise<void> {
  try {
    const response = await api.fetchJson('/api/sync/status')
    if (response.success && response.data) {
      syncEnabled.value = response.data.enabled
      
      // Map group sync statuses
      if (response.data.groups) {
        const statusMap: Record<number, { status: string }> = {}
        const themeOnly: Array<{ slug: string; title: string }> = []
        
        for (const group of response.data.groups) {
          if (group.id) {
            statusMap[group.id] = { status: group.status }
          } else if (group.status === 'theme_only') {
            themeOnly.push({ slug: group.slug, title: group.title || group.slug })
          }
        }
        
        syncStatus.value = statusMap
        themeOnlyGroups.value = themeOnly
      }
    }
  } catch (e) {
    // Sync not available, ignore
    console.warn('Could not load sync status:', e)
  }
}

function confirmDelete(id: number): void {
  if (confirm(t('confirmDeleteGroup'))) {
    store.deleteGroup(id)
  }
}

async function pushToTheme(groupId: number): Promise<void> {
  try {
    const response = await api.fetchJson(`/api/sync/push/${groupId}`, { method: 'POST' })
    if (response.success) {
      alert(t('groupPushedSuccess'))
      await loadSyncStatus()
    } else {
      alert(t('syncError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('syncError') + ': ' + (e as Error).message)
  }
}

async function pullFromTheme(slug: string): Promise<void> {
  if (!confirm(t('confirmPullFromTheme'))) return
  
  try {
    const response = await api.fetchJson(`/api/sync/pull/${slug}`, { method: 'POST' })
    if (response.success) {
      alert(t('groupPulledSuccess'))
      // Reload groups list
      await store.loadGroups()
      await loadSyncStatus()
    } else {
      alert(t('syncError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('syncError') + ': ' + (e as Error).message)
  }
}

async function exportGroup(groupId: number): Promise<void> {
  try {
    const response = await api.fetchJson(`/api/sync/export/${groupId}`)
    if (response.success && response.data) {
      // Download as JSON file
      const blob = new Blob([JSON.stringify(response.data.content, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = response.data.filename
      a.click()
      URL.revokeObjectURL(url)
    } else {
      alert(t('exportError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('exportError') + ': ' + (e as Error).message)
  }
}

function getGroupSyncStatus(groupId: number): string {
  return syncStatus.value[groupId]?.status || 'unknown'
}
</script>

<template>
  <div class="acfps-group-list">
    <!-- Header with add button -->
    <div class="acfps-list-header">
      <button class="btn btn-primary" @click="store.createNewGroup">
        <span class="material-icons">add</span>
        {{ t('addGroup') }}
      </button>
    </div>

    <!-- Theme-only groups (not yet imported) -->
    <div v-if="syncEnabled && themeOnlyGroups.length > 0" class="acfps-theme-only-section">
      <h5 class="acfps-section-title">
        <span class="material-icons">cloud_download</span>
        {{ t('themeOnlyGroups') }}
      </h5>
      <div class="acfps-theme-only-list">
        <div v-for="group in themeOnlyGroups" :key="group.slug" class="acfps-theme-only-item">
          <div class="acfps-theme-only-info">
            <span class="material-icons">folder</span>
            <strong>{{ group.title }}</strong>
            <code class="ml-2">{{ group.slug }}</code>
            <SyncStatusBadge status="theme_only" :show-label="true" class="ml-2" />
          </div>
          <button class="btn btn-sm btn-info" @click="pullFromTheme(group.slug)">
            <span class="material-icons">cloud_download</span>
            {{ t('import') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="store.groups.length === 0 && themeOnlyGroups.length === 0" class="acfps-empty-state">
      <span class="material-icons">widgets</span>
      <p>{{ t('noGroups') }}</p>
      <button class="btn btn-outline-primary" @click="store.createNewGroup">
        <span class="material-icons">add</span>
        {{ t('addGroup') }}
      </button>
    </div>

    <!-- Groups table -->
    <table v-else-if="store.groups.length > 0" class="acfps-group-table">
      <thead>
        <tr>
          <th>{{ t('groupTitle') }}</th>
          <th>{{ t('groupSlug') }}</th>
          <th>{{ t('fields') }}</th>
          <th>{{ t('placementTab') }}</th>
          <th v-if="syncEnabled">{{ t('sync') }}</th>
          <th>{{ t('active') }}</th>
          <th>{{ t('options') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="group in store.groups" :key="group.id">
          <td>
            <a 
              href="#" 
              class="group-title-link"
              @click.prevent="store.loadGroup(group.id!)"
            >
              {{ group.title || t('untitled') }}
            </a>
          </td>
          <td>
            <code>{{ group.slug }}</code>
          </td>
          <td>{{ group.fieldCount || 0 }}</td>
          <td>
            <span class="badge badge-secondary">{{ group.placementTab }}</span>
          </td>
          <td v-if="syncEnabled">
            <SyncStatusBadge 
              :status="getGroupSyncStatus(group.id!)" 
              :sync-enabled="syncEnabled"
            />
          </td>
          <td>
            <span 
              class="acfps-status-badge"
              :class="group.active ? 'active' : 'inactive'"
            >
              {{ group.active ? t('active') : 'Inactive' }}
            </span>
          </td>
          <td>
            <div class="btn-group btn-group-sm">
              <button 
                class="btn btn-outline-secondary"
                :title="t('edit')"
                @click="store.loadGroup(group.id!)"
              >
                <span class="material-icons">edit</span>
              </button>
              <button 
                class="btn btn-outline-secondary"
                :title="t('duplicate')"
                @click="store.duplicateGroup(group.id!)"
              >
                <span class="material-icons">content_copy</span>
              </button>
              
              <!-- Sync Actions Dropdown -->
              <div v-if="syncEnabled" class="btn-group btn-group-sm">
                <button 
                  class="btn btn-outline-secondary dropdown-toggle"
                  data-toggle="dropdown"
                  :title="t('syncActions')"
                >
                  <span class="material-icons">sync</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                  <button class="dropdown-item" @click="pushToTheme(group.id!)">
                    <span class="material-icons">cloud_upload</span>
                    {{ t('pushToTheme') }}
                  </button>
                  <button 
                    class="dropdown-item" 
                    @click="pullFromTheme(group.slug)"
                    :disabled="getGroupSyncStatus(group.id!) === 'need_push'"
                  >
                    <span class="material-icons">cloud_download</span>
                    {{ t('pullFromTheme') }}
                  </button>
                  <div class="dropdown-divider"></div>
                  <button class="dropdown-item" @click="exportGroup(group.id!)">
                    <span class="material-icons">download</span>
                    {{ t('exportJson') }}
                  </button>
                </div>
              </div>
              
              <button 
                class="btn btn-outline-danger"
                :title="t('delete')"
                @click="confirmDelete(group.id!)"
              >
                <span class="material-icons">delete</span>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.acfps-group-list {
  padding: 0;
}

.acfps-list-header {
  padding: 1rem;
  border-bottom: 1px solid var(--border-color, #e9e9e9);
  display: flex;
  justify-content: flex-end;
}

.acfps-list-header .btn .material-icons {
  font-size: 18px;
  vertical-align: middle;
  margin-right: 0.25rem;
}

.group-title-link {
  font-weight: 500;
  color: var(--primary, #25b9d7);
  text-decoration: none;
}

.group-title-link:hover {
  text-decoration: underline;
}

.btn-group .material-icons {
  font-size: 16px;
}

/* Theme-only groups section */
.acfps-theme-only-section {
  padding: 1rem;
  background: #f8f9fa;
  border-bottom: 1px solid var(--border-color, #e9e9e9);
}

.acfps-section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 14px;
  color: #6c757d;
}

.acfps-section-title .material-icons {
  font-size: 18px;
}

.acfps-theme-only-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.acfps-theme-only-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}

.acfps-theme-only-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.acfps-theme-only-info .material-icons {
  color: #6c757d;
}

/* Dropdown styles */
.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
}

.dropdown-item .material-icons {
  font-size: 18px;
}

.dropdown-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
