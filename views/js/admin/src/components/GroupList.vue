<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { useApi } from '@/composables/useApi'
import SyncStatusBadge from './SyncStatusBadge.vue'

const store = useBuilderStore()
const { t } = useTranslations()
const api = useApi()

// Selection state
const selectedGroups = ref<Set<number>>(new Set())
const selectAll = ref(false)

// Sync state
const syncEnabled = ref(false)
const syncStatus = ref<Record<number, { status: string }>>({})
const themeOnlyGroups = ref<Array<{ slug: string; title: string }>>([])

// Computed properties for selection
const hasSelectedGroups = computed(() => selectedGroups.value.size > 0)
const selectedCount = computed(() => selectedGroups.value.size)
const isAllSelected = computed(() => {
  return store.groups.length > 0 && selectedGroups.value.size === store.groups.length
})
const isIndeterminate = computed(() => {
  return selectedGroups.value.size > 0 && selectedGroups.value.size < store.groups.length
})

// Load sync status on mount
onMounted(async () => {
  await loadSyncStatus()
})

async function loadSyncStatus(): Promise<void> {
  try {
    const response = await api.fetchJson('/sync/status')
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
    const response = await api.fetchJson(`/sync/push/${groupId}`, { method: 'POST' })
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
    const response = await api.fetchJson(`/sync/pull/${slug}`, { method: 'POST' })
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
    const response = await api.fetchJson(`/sync/export/${groupId}`)
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


// Selection methods
function toggleGroupSelection(groupId: number): void {
  if (selectedGroups.value.has(groupId)) {
    selectedGroups.value.delete(groupId)
  } else {
    selectedGroups.value.add(groupId)
  }
  updateSelectAllState()
}

function toggleSelectAll(): void {
  if (isAllSelected.value) {
    selectedGroups.value.clear()
  } else {
    selectedGroups.value.clear()
    store.groups.forEach(group => {
      if (group.id) {
        selectedGroups.value.add(group.id)
      }
    })
  }
  updateSelectAllState()
}

function updateSelectAllState(): void {
  selectAll.value = isAllSelected.value
}

function clearSelection(): void {
  selectedGroups.value.clear()
  selectAll.value = false
}

// Bulk action methods
async function bulkToggleActive(active: boolean): Promise<void> {
  const groupIds = Array.from(selectedGroups.value)
  if (groupIds.length === 0) return

  try {
    const response = await api.fetchJson('/groups/bulk-toggle-active', {
      method: 'POST',
      body: JSON.stringify({ groupIds, active })
    })

    if (response.success) {
      // Reload groups to reflect changes
      await store.loadGroups()
      clearSelection()
      alert(t('bulkActionSuccess'))
    } else {
      alert(t('bulkActionError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('bulkActionError') + ': ' + (e as Error).message)
  }
}

function confirmBulkDelete(): void {
  const groupIds = Array.from(selectedGroups.value)
  if (groupIds.length === 0) return

  const message = groupIds.length === 1
    ? t('confirmDeleteGroup')
    : t('confirmDeleteGroups').replace('{count}', groupIds.length.toString())

  if (confirm(message)) {
    bulkDelete()
  }
}

async function bulkDelete(): Promise<void> {
  const groupIds = Array.from(selectedGroups.value)
  if (groupIds.length === 0) return

  try {
    const response = await api.fetchJson('/groups/bulk-delete', {
      method: 'POST',
      body: JSON.stringify({ groupIds })
    })

    if (response.success) {
      // Reload groups to reflect changes
      await store.loadGroups()
      clearSelection()
      alert(t('bulkDeleteSuccess'))
    } else {
      alert(t('bulkDeleteError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('bulkDeleteError') + ': ' + (e as Error).message)
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
        </div>
      </div>

      <!-- Bulk Actions Bar -->
      <div v-if="hasSelectedGroups" class="alert alert-light border d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
          <i class="material-icons text-primary mr-2">check_circle</i>
          <span class="font-weight-semibold">
            {{ selectedCount }} {{ selectedCount === 1 ? t('groupSelected') : t('groupsSelected') }}
          </span>
        </div>
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons mr-1">settings</i>
            {{ t('actions') }}
          </button>
          <div class="dropdown-menu dropdown-menu-right">
            <button class="dropdown-item" @click="bulkToggleActive(true)">
              <i class="material-icons text-success mr-2">check_circle</i>
              {{ t('activate') }}
            </button>
            <button class="dropdown-item" @click="bulkToggleActive(false)">
              <i class="material-icons text-warning mr-2">cancel</i>
              {{ t('deactivate') }}
            </button>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item text-danger" @click="confirmBulkDelete">
              <i class="material-icons mr-2">delete</i>
              {{ t('delete') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Native PrestaShop Grid -->
      <div v-if="store.groups.length > 0" class="grid js-grid" id="acf_group_grid" data-grid-id="acf_group">
        <div class="table-responsive">
          <table class="grid-table js-grid-table table" id="acf_group_grid_table">
            <thead class="thead-default">
              <tr class="column-headers">
                <th scope="col" data-type="selector" data-column-id="select" class="text-center">
                  <input
                    type="checkbox"
                    class="form-check-input header-checkbox"
                    :checked="isAllSelected"
                    :indeterminate.prop="isIndeterminate"
                    @change="toggleSelectAll"
                    id="select-all-groups"
                  />
                  <label class="sr-only" for="select-all-groups">
                    {{ t('selectAll') }}
                  </label>
                </th>
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
                <td class="selector-type column-select text-center">
                  <div class="form-check mb-0">
                    <input
                      type="checkbox"
                      class="form-check-input"
                      :checked="selectedGroups.has(group.id!)"
                      @change="toggleGroupSelection(group.id!)"
                      :id="'select-group-' + group.id"
                    />
                    <label class="form-check-label sr-only" :for="'select-group-' + group.id">
                      {{ t('selectGroup') }} {{ group.title || t('untitled') }}
                    </label>
                  </div>
                </td>
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

.grid-table .column-select {
  width: 60px;
  text-align: center;
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

/* Bulk actions bar */
.alert .btn-group .btn {
  margin-left: 0.25rem;
}

.alert .btn-group .btn:first-child {
  margin-left: 0;
}

/* Checkboxes */
.form-check-input {
  margin-top: 0;
}

.form-check-input[indeterminate] {
  background-color: #007bff;
  border-color: #007bff;
}

/* Better checkbox alignment in table cells */
.grid-table .column-select .form-check {
  margin-bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.grid-table .column-select .form-check-input {
  margin: 0;
}

/* Header checkbox specific styling */
.grid-table thead .column-select {
  vertical-align: middle;
  position: relative;
}

.grid-table thead .column-select .header-checkbox {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(1.1);
  margin: 0;
}
</style>
