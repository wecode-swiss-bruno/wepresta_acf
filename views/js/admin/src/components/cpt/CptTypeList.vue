<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useTranslations } from '../../composables/useTranslations'
import { useApi } from '../../composables/useApi'

const cptStore = useCptStore()
const { t } = useTranslations()
const api = useApi()

const loading = ref(false)
const error = ref<string | null>(null)

// Selection state
const selectedTypes = ref<Set<number>>(new Set())
const selectAll = ref(false)

// Computed properties for selection
const hasSelectedTypes = computed(() => selectedTypes.value.size > 0)
const selectedCount = computed(() => selectedTypes.value.size)
const isAllSelected = computed(() => {
  return cptStore.sortedTypes.length > 0 && selectedTypes.value.size === cptStore.sortedTypes.length
})
const isIndeterminate = computed(() => {
  return selectedTypes.value.size > 0 && selectedTypes.value.size < cptStore.sortedTypes.length
})

onMounted(async () => {
  loading.value = true
  await cptStore.fetchTypes()
  await cptStore.fetchTaxonomies()
  loading.value = false
})

// Selection methods
function toggleTypeSelection(typeId: number): void {
  if (selectedTypes.value.has(typeId)) {
    selectedTypes.value.delete(typeId)
  } else {
    selectedTypes.value.add(typeId)
  }
  updateSelectAllState()
}

function toggleSelectAll(): void {
  if (isAllSelected.value) {
    selectedTypes.value.clear()
  } else {
    selectedTypes.value.clear()
    cptStore.sortedTypes.forEach(type => {
      if (type.id) {
        selectedTypes.value.add(type.id)
      }
    })
  }
  updateSelectAllState()
}

function updateSelectAllState(): void {
  selectAll.value = isAllSelected.value
}

function clearSelection(): void {
  selectedTypes.value.clear()
  selectAll.value = false
}

// Bulk action methods
async function bulkToggleActive(active: boolean): Promise<void> {
  const typeIds = Array.from(selectedTypes.value)
  if (typeIds.length === 0) return

  try {
    const response = await api.fetchJson('/cpt/types/bulk-toggle-active', {
      method: 'POST',
      body: JSON.stringify({ typeIds, active })
    })

    if (response.success) {
      await cptStore.fetchTypes()
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
  const typeIds = Array.from(selectedTypes.value)
  if (typeIds.length === 0) return

  const message = typeIds.length === 1
    ? t('confirmDeleteType')
    : t('confirmDeleteTypes', 'Delete {count} types? This will delete all their posts.', { count: typeIds.length.toString() })

  if (confirm(message)) {
    bulkDelete()
  }
}

async function bulkDelete(): Promise<void> {
  const typeIds = Array.from(selectedTypes.value)
  if (typeIds.length === 0) return

  try {
    const response = await api.fetchJson('/cpt/types/bulk-delete', {
      method: 'POST',
      body: JSON.stringify({ typeIds })
    })

    if (response.success) {
      await cptStore.fetchTypes()
      clearSelection()
      alert(t('bulkDeleteSuccess'))
    } else {
      alert(t('bulkDeleteError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('bulkDeleteError') + ': ' + (e as Error).message)
  }
}

function managePosts(type: any) {
  cptStore.currentType = type
  cptStore.viewMode = 'posts' as any
  cptStore.fetchPostsByType(type.slug)
}

function confirmDelete(type: any) {
  if (confirm(t('confirmDeleteType', 'Delete {name}? This will delete all posts.', { name: type.name }))) {
    cptStore.deleteType(type.id)
  }
}
</script>

<template>
  <div class="cpt-type-list">
    <div class="card">
      <!-- Card Header with Actions -->
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">
          <i class="material-icons mr-2" style="vertical-align: middle;">article</i>
          {{ t('cptTypes') }}
        </h4>
        <button class="btn btn-primary" @click="cptStore.createNewType()">
          <i class="material-icons mr-1">add</i>
          {{ t('newCptType') }}
        </button>
      </div>
      <div class="card-body">
        <!-- Loading state -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <!-- Empty state -->
        <div v-else-if="cptStore.sortedTypes.length === 0" class="text-center py-5">
          <i class="material-icons text-muted mb-3" style="font-size: 64px;">article</i>
          <p class="text-muted mb-3">{{ t('noCptTypeSaved') }}</p>
          <div class="d-flex justify-content-center">
            <button class="btn btn-outline-primary" @click="cptStore.createNewType()">
              <i class="material-icons">add</i>
              {{ t('newCptType') }}
            </button>
          </div>
        </div>

        <template v-else>
          <!-- Bulk Actions Bar -->
          <div v-if="hasSelectedTypes" class="alert alert-light border d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
              <i class="material-icons text-primary mr-2">check_circle</i>
              <span class="font-weight-semibold">
                {{ selectedCount }} {{ selectedCount === 1 ? t('typeSelected') : t('typesSelected') }}
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
          <div class="grid js-grid" id="cpt_type_grid" data-grid-id="cpt_type">
            <div class="table-responsive">
              <table class="grid-table js-grid-table table" id="cpt_type_grid_table">
                <thead class="thead-default">
                  <tr class="column-headers">
                    <th scope="col" data-type="selector" data-column-id="select" class="text-center">
                      <input
                        type="checkbox"
                        class="form-check-input header-checkbox"
                        :checked="isAllSelected"
                        :indeterminate.prop="isIndeterminate"
                        @change="toggleSelectAll"
                        id="select-all-types"
                      />
                      <label class="sr-only" for="select-all-types">
                        {{ t('selectAll') }}
                      </label>
                    </th>
                    <th scope="col" data-type="identifier" data-column-id="id">
                      <span role="columnheader">ID</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="icon" class="text-center">
                      <span role="columnheader">{{ t('icon') }}</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="name">
                      <span role="columnheader">{{ t('name') }}</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="slug">
                      <span role="columnheader">{{ t('slug') }}</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="url_prefix">
                      <span role="columnheader">{{ t('urlPrefix') }}</span>
                    </th>
                    <th scope="col" data-type="boolean" data-column-id="archive" class="text-center">
                      <span role="columnheader">{{ t('archive') }}</span>
                    </th>
                    <th scope="col" data-type="boolean" data-column-id="active" class="text-center">
                      <span role="columnheader">{{ t('status') }}</span>
                    </th>
                    <th scope="col" data-type="action" data-column-id="actions">
                      <div class="grid-actions-header-text">{{ t('actions') }}</div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="type in cptStore.sortedTypes" :key="type.id">
                    <td class="selector-type column-select text-center">
                      <div class="form-check mb-0">
                        <input
                          type="checkbox"
                          class="form-check-input"
                          :checked="selectedTypes.has(type.id!)"
                          @change="toggleTypeSelection(type.id!)"
                          :id="'select-type-' + type.id"
                        />
                        <label class="form-check-label sr-only" :for="'select-type-' + type.id">
                          {{ t('selectType') }} {{ type.name }}
                        </label>
                      </div>
                    </td>
                    <td data-identifier class="identifier-type column-id">
                      {{ type.id }}
                    </td>
                    <td class="data-type column-icon text-center">
                      <i class="material-icons">{{ type.icon || 'article' }}</i>
                    </td>
                    <td class="data-type column-name text-left">
                      <a
                        href="#"
                        class="text-primary font-weight-bold"
                        @click.prevent="cptStore.editType(type.id)"
                      >
                        {{ type.name }}
                      </a>
                      <small v-if="type.description" class="text-muted d-block">{{ type.description }}</small>
                    </td>
                    <td class="data-type column-slug text-left">
                      <code class="text-muted">{{ type.slug }}</code>
                    </td>
                    <td class="data-type column-url text-left">
                      <code>/{{ type.url_prefix }}</code>
                    </td>
                    <td class="boolean-type column-archive text-center">
                      <span v-if="type.has_archive" class="text-success">
                        <i class="material-icons">check_circle</i>
                      </span>
                      <span v-else class="text-danger">
                        <i class="material-icons">cancel</i>
                      </span>
                    </td>
                    <td class="boolean-type column-active text-center">
                      <span v-if="type.active" class="text-success">
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
                            @click.prevent="cptStore.editType(type.id)"
                          >
                            <i class="material-icons">edit</i>
                          </a>
                          <!-- Manage Posts -->
                          <a
                            href="#"
                            class="btn tooltip-link dropdown-item inline-dropdown-item"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('managePosts')"
                            @click.prevent="managePosts(type)"
                          >
                            <i class="material-icons">list</i>
                          </a>
                          <!-- View on Front -->
                          <a
                            v-if="type.view_url"
                            :href="type.view_url"
                            target="_blank"
                            class="btn tooltip-link dropdown-item inline-dropdown-item"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('viewOnFront')"
                          >
                            <i class="material-icons">visibility</i>
                          </a>
                          <!-- Delete -->
                          <a
                            href="#"
                            class="btn tooltip-link dropdown-item inline-dropdown-item text-danger"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('delete')"
                            @click.prevent="confirmDelete(type)"
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
        </template>
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

.grid-table .column-icon {
  width: 60px;
}

.grid-table .column-archive,
.grid-table .column-active {
  width: 80px;
}

.grid-table .column-actions {
  width: 160px;
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

/* Checkboxes */
.form-check-input {
  margin-top: 0;
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

.grid-table thead .header-checkbox {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(1.1);
  margin: 0;
}

/* Bulk actions bar */
.alert .btn-group .btn {
  margin-left: 0.25rem;
}

.alert .btn-group .btn:first-child {
  margin-left: 0;
}

/* Status icons */
.boolean-type .material-icons {
  font-size: 20px;
}

/* Empty state */
.text-center.py-5 .material-icons {
  display: block;
  margin: 0 auto 1rem;
}
</style>
