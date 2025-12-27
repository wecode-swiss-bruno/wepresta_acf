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
const seeding = ref(false)

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

async function runSeed(): Promise<void> {
  if (!confirm(t('confirmSeed'))) {
    return
  }

  seeding.value = true
  try {
    const response = await api.fetchJson('/api/seed', { method: 'POST' })
    if (response.success) {
      if (response.skipped) {
        alert(t('seedSkipped'))
      } else {
        alert(t('seedSuccess') + ` (${response.data.fields_inserted} fields)`)
      }
      // Reload groups list
      await store.loadGroups()
    } else {
      alert(t('seedError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('seedError') + ': ' + (e as Error).message)
  } finally {
    seeding.value = false
  }
}

function getGroupSyncStatus(groupId: number): string {
  return syncStatus.value[groupId]?.status || 'unknown'
}
</script>

<template>
  <div class="card">

    <div class="card-body">
      <!-- Theme-only groups (not yet imported) -->
      <div v-if="syncEnabled && themeOnlyGroups.length > 0" class="alert alert-info d-flex align-items-start mb-3">
        <i class="material-icons mr-2">cloud_download</i>
        <div class="flex-grow-1">
          <strong>{{ t('themeOnlyGroups') }}</strong>
          <div class="mt-2">
            <div v-for="group in themeOnlyGroups" :key="group.slug" class="d-flex align-items-center justify-content-between mb-2 p-2 bg-white border rounded">
              <div>
                <i class="material-icons text-muted" style="font-size: 16px; vertical-align: middle;">folder</i>
                <strong class="ml-1">{{ group.title }}</strong>
                <code class="ml-2 small">{{ group.slug }}</code>
                <SyncStatusBadge status="theme_only" :show-label="true" class="ml-2" />
              </div>
              <button class="btn btn-sm btn-info" @click="pullFromTheme(group.slug)">
                <i class="material-icons">cloud_download</i>
                {{ t('import') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!store.loading && store.groups.length === 0 && themeOnlyGroups.length === 0" class="text-center py-5">
        <i class="material-icons text-muted mb-3" style="font-size: 64px;">widgets</i>
        <p class="text-muted mb-3">{{ t('noGroups') }}</p>
        <div class="d-flex justify-content-center">
          <button class="btn btn-outline-primary mr-2" @click="store.createNewGroup">
            <i class="material-icons">add</i>
            {{ t('addGroup') }}
          </button>
          <button class="btn btn-outline-info" @click="runSeed" :disabled="seeding">
            <i class="material-icons" v-if="!seeding">science</i>
            <span v-else class="spinner-border spinner-border-sm" role="status"></span>
            {{ seeding ? t('seeding') : t('seedTestData') }}
          </button>
        </div>
      </div>

      <!-- Native PrestaShop Grid -->
      <div v-else-if="store.groups.length > 0" class="grid js-grid" id="acf_group_grid" data-grid-id="acf_group">
        <div class="table-responsive">
          <table class="grid-table js-grid-table table" id="acf_group_grid_table">
            <thead class="thead-default">
              <tr class="column-headers">
                <th scope="col" data-type="identifier" data-column-id="id">
                  <span role="columnheader">ID</span>
                </th>
                <th scope="col" data-type="data" data-column-id="title">
                  <span role="columnheader">{{ t('groupTitle') }}</span>
                </th>
                <th scope="col" data-type="data" data-column-id="slug">
                  <span role="columnheader">{{ t('groupSlug') }}</span>
                </th>
                <th scope="col" data-type="data" data-column-id="fields" class="text-center">
                  <span role="columnheader">{{ t('fields') }}</span>
                </th>
                <th scope="col" data-type="data" data-column-id="tab">
                  <span role="columnheader">{{ t('placementTab') }}</span>
                </th>
                <th v-if="syncEnabled" scope="col" data-type="data" data-column-id="sync">
                  <span role="columnheader">{{ t('sync') }}</span>
                </th>
                <th scope="col" data-type="boolean" data-column-id="active" class="text-center">
                  <span role="columnheader">{{ t('active') }}</span>
                </th>
                <th scope="col" data-type="action" data-column-id="actions">
                  <div class="grid-actions-header-text">Actions</div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="group in store.groups" :key="group.id">
                <td data-identifier class="identifier-type column-id">
                  {{ group.id }}
                </td>
                <td class="data-type column-title text-left">
                  <a
                    href="#"
                    class="text-primary font-weight-bold"
                    @click.prevent="store.loadGroup(group.id!)"
                  >
                    {{ group.title || t('untitled') }}
                  </a>
                </td>
                <td class="data-type column-slug text-left">
                  <code class="text-muted">{{ group.slug }}</code>
                </td>
                <td class="data-type column-fields text-center">
                  <span class="badge badge-pill badge-secondary">{{ group.fieldCount || 0 }}</span>
                </td>
                <td class="data-type column-tab text-left">
                  <span class="badge badge-info">{{ group.placementTab || 'extra' }}</span>
                </td>
                <td v-if="syncEnabled" class="data-type column-sync text-left">
                  <SyncStatusBadge
                    :status="getGroupSyncStatus(group.id!)"
                    :sync-enabled="syncEnabled"
                  />
                </td>
                <td class="boolean-type column-active text-center">
                  <span v-if="group.active" class="text-success">
                    <i class="material-icons">check_circle</i>
                  </span>
                  <span v-else class="text-danger">
                    <i class="material-icons">cancel</i>
                  </span>
                </td>
                <td class="action-type column-actions">
                  <div class="btn-group-action text-right">
                    <div class="btn-group d-flex justify-content-end">
                      <!-- Edit -->
                      <a
                        href="#"
                        class="btn tooltip-link dropdown-item inline-dropdown-item"
                        data-toggle="pstooltip"
                        data-placement="top"
                        :data-original-title="t('edit')"
                        @click.prevent="store.loadGroup(group.id!)"
                      >
                        <i class="material-icons">edit</i>
                      </a>
                      <!-- Duplicate -->
                      <a
                        href="#"
                        class="btn tooltip-link dropdown-item inline-dropdown-item"
                        data-toggle="pstooltip"
                        data-placement="top"
                        :data-original-title="t('duplicate')"
                        @click.prevent="store.duplicateGroup(group.id!)"
                      >
                        <i class="material-icons">content_copy</i>
                      </a>

                      <!-- Sync Actions Dropdown -->
                      <div v-if="syncEnabled" class="btn-group">
                        <a
                          href="#"
                          class="btn tooltip-link dropdown-item inline-dropdown-item dropdown-toggle"
                          data-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false"
                          @click.prevent
                        >
                          <i class="material-icons">sync</i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                          <button class="dropdown-item" @click="pushToTheme(group.id!)">
                            <i class="material-icons">cloud_upload</i>
                            {{ t('pushToTheme') }}
                          </button>
                          <button
                            class="dropdown-item"
                            @click="pullFromTheme(group.slug)"
                            :disabled="getGroupSyncStatus(group.id!) === 'need_push'"
                          >
                            <i class="material-icons">cloud_download</i>
                            {{ t('pullFromTheme') }}
                          </button>
                          <div class="dropdown-divider"></div>
                          <button class="dropdown-item" @click="exportGroup(group.id!)">
                            <i class="material-icons">download</i>
                            {{ t('exportJson') }}
                          </button>
                        </div>
                      </div>

                      <!-- Delete -->
                      <a
                        href="#"
                        class="btn tooltip-link dropdown-item inline-dropdown-item text-danger"
                        data-toggle="pstooltip"
                        data-placement="top"
                        :data-original-title="t('delete')"
                        @click.prevent="confirmDelete(group.id!)"
                      >
                        <i class="material-icons">delete</i>
                      </a>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Native PrestaShop Grid styles - minimal overrides */
.grid-table th {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.grid-table td {
  vertical-align: middle;
}

.grid-table .column-id {
  width: 60px;
}

.grid-table .column-active {
  width: 80px;
}

.grid-table .column-fields {
  width: 80px;
}

.grid-table .column-actions {
  width: 140px;
}

/* Action buttons - native PS styling */
.btn-group-action .btn,
.btn-group-action .dropdown-item.inline-dropdown-item {
  padding: 0.25rem 0.5rem;
  background: transparent;
  border: none;
}

.btn-group-action .btn:hover,
.btn-group-action .dropdown-item.inline-dropdown-item:hover {
  background: #f8f9fa;
}

.btn-group-action .material-icons {
  font-size: 20px;
}

.btn-group-action .text-danger .material-icons {
  color: #dc3545;
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

/* Card header title */
.card-header-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
}
</style>
