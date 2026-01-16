<template>
  <div class="cpt-taxonomy-manager">
    <div class="card">
      <!-- Card Header with Actions -->
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">
          <i class="material-icons mr-2" style="vertical-align: middle;">category</i>
          {{ t('taxonomies') }}
        </h4>
        <button class="btn btn-primary" @click="openCreateModal">
          <i class="material-icons mr-1">add</i>
          {{ t('newTaxonomy') }}
        </button>
      </div>
      <div class="card-body">
        <!-- Empty state -->
        <div v-if="cptStore.taxonomies.length === 0" class="text-center py-5">
          <i class="material-icons text-muted mb-3" style="font-size: 64px;">category</i>
          <p class="text-muted mb-3">{{ t('noTaxonomiesYet') }}</p>
          <div class="d-flex justify-content-center">
            <button class="btn btn-outline-primary" @click="openCreateModal">
              <i class="material-icons">add</i>
              {{ t('newTaxonomy') }}
            </button>
          </div>
        </div>

        <template v-else>
          <!-- Bulk Actions Bar -->
          <div v-if="hasSelectedTaxonomies" class="alert alert-light border d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
              <i class="material-icons text-primary mr-2">check_circle</i>
              <span class="font-weight-semibold">
                {{ selectedCount }} {{ selectedCount === 1 ? t('taxonomySelected') : t('taxonomiesSelected') }}
              </span>
            </div>
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-danger btn-sm" @click="confirmBulkDelete">
                <i class="material-icons mr-1">delete</i>
                {{ t('delete') }}
              </button>
            </div>
          </div>

          <!-- Native PrestaShop Grid -->
          <div class="grid js-grid" id="cpt_taxonomy_grid" data-grid-id="cpt_taxonomy">
            <div class="table-responsive">
              <table class="grid-table js-grid-table table" id="cpt_taxonomy_grid_table">
                <thead class="thead-default">
                  <tr class="column-headers">
                    <th scope="col" data-type="selector" data-column-id="select" class="text-center">
                      <input
                        type="checkbox"
                        class="form-check-input header-checkbox"
                        :checked="isAllSelected"
                        :indeterminate.prop="isIndeterminate"
                        @change="toggleSelectAll"
                        id="select-all-taxonomies"
                      />
                    </th>
                    <th scope="col" data-type="identifier" data-column-id="id">
                      <span role="columnheader">ID</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="name">
                      <span role="columnheader">{{ t('name') }}</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="slug">
                      <span role="columnheader">{{ t('slug') }}</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="description">
                      <span role="columnheader">{{ t('description') }}</span>
                    </th>
                    <th scope="col" data-type="action" data-column-id="actions">
                      <div class="grid-actions-header-text">{{ t('actions') }}</div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="taxonomy in cptStore.taxonomies" :key="taxonomy.id">
                    <td class="selector-type column-select text-center">
                      <div class="form-check mb-0">
                        <input
                          type="checkbox"
                          class="form-check-input"
                          :checked="selectedTaxonomies.has(taxonomy.id!)"
                          @change="toggleTaxonomySelection(taxonomy.id!)"
                          :id="'select-taxonomy-' + taxonomy.id"
                        />
                      </div>
                    </td>
                    <td data-identifier class="identifier-type column-id">
                      {{ taxonomy.id }}
                    </td>
                    <td class="data-type column-name text-left">
                      <a
                        href="#"
                        class="text-primary font-weight-bold"
                        @click.prevent="editTaxonomy(taxonomy)"
                      >
                        {{ taxonomy.name }}
                      </a>
                    </td>
                    <td class="data-type column-slug text-left">
                      <code class="text-muted">{{ taxonomy.slug }}</code>
                    </td>
                    <td class="data-type column-description text-left">
                      <small class="text-muted">{{ taxonomy.description || '-' }}</small>
                    </td>
                    <td class="action-type column-actions">
                      <div class="btn-group-action text-right">
                        <div class="btn-group d-flex justify-content-end">
                          <!-- Manage Terms -->
                          <a
                            href="#"
                            class="btn tooltip-link dropdown-item inline-dropdown-item"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('manageTerms')"
                            @click.prevent="manageTerms(taxonomy)"
                          >
                            <i class="material-icons">category</i>
                          </a>
                          <!-- Edit -->
                          <a
                            href="#"
                            class="btn tooltip-link dropdown-item inline-dropdown-item"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('edit')"
                            @click.prevent="editTaxonomy(taxonomy)"
                          >
                            <i class="material-icons">edit</i>
                          </a>
                          <!-- Delete -->
                          <a
                            href="#"
                            class="btn tooltip-link dropdown-item inline-dropdown-item text-danger"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('delete')"
                            @click.prevent="deleteTaxonomy(taxonomy)"
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

    <!-- Create/Edit Taxonomy Modal -->
    <div v-if="showCreateTaxonomy" class="modal fade show" style="display: block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title d-flex align-items-center">
              <i class="material-icons mr-2">category</i>
              {{ isEdit ? t('editTaxonomy') : t('newTaxonomy') }}
            </h5>
            <button type="button" class="close" @click="showCreateTaxonomy = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Translatable Fields -->
            <div class="translations tabbable" v-if="languages.length > 1">
              <ul class="translationsLocales nav nav-pills">
                <li v-for="lang in languages" :key="lang.id_lang" class="nav-item">
                  <a
                    href="#"
                    class="nav-link"
                    :class="{ active: currentLangCode === lang.iso_code }"
                    @click.prevent="currentLangCode = lang.iso_code"
                  >
                    {{ lang.iso_code.toUpperCase() }}
                  </a>
                </li>
              </ul>
              <div class="translationsFields tab-content mt-3">
                <div
                  v-for="lang in languages"
                  :key="lang.id_lang"
                  class="tab-pane"
                  :class="{ active: currentLangCode === lang.iso_code, show: currentLangCode === lang.iso_code }"
                >
                  <div class="form-group">
                    <label :for="`tax_name_${lang.id_lang}`">{{ t('name') }} * ({{ lang.iso_code.toUpperCase() }})</label>
                    <input
                      :id="`tax_name_${lang.id_lang}`"
                      v-model="taxTranslations[lang.id_lang].name"
                      type="text"
                      class="form-control"
                      :required="lang.is_default"
                      @input="lang.is_default && generateTaxSlug()"
                    />
                  </div>
                  <div class="form-group">
                    <label :for="`tax_description_${lang.id_lang}`">{{ t('description') }} ({{ lang.iso_code.toUpperCase() }})</label>
                    <textarea
                      :id="`tax_description_${lang.id_lang}`"
                      v-model="taxTranslations[lang.id_lang].description"
                      class="form-control"
                      rows="2"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>
            <!-- Single language fallback -->
            <div v-else>
              <div class="form-group">
                <label for="tax_name">{{ t('name') }} *</label>
                <input
                  id="tax_name"
                  v-model="taxonomyForm.name"
                  type="text"
                  class="form-control"
                  required
                  @input="generateTaxSlug"
                />
              </div>
              <div class="form-group">
                <label for="tax_description">{{ t('description') }}</label>
                <textarea
                  id="tax_description"
                  v-model="taxonomyForm.description"
                  class="form-control"
                  rows="2"
                ></textarea>
              </div>
            </div>

            <div class="form-group mt-3">
              <label for="taxonomy_slug">{{ t('slug') }} *</label>
              <input
                id="taxonomy_slug"
                v-model="taxonomyForm.slug"
                type="text"
                class="form-control"
                required
                :disabled="isEdit"
              />
              <small v-if="isEdit" class="form-text text-muted">
                <i class="material-icons" style="font-size: 14px; vertical-align: text-bottom;">lock</i>
                {{ t('slugLockHelper') }}
              </small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showCreateTaxonomy = false">
              {{ t('cancel') }}
            </button>
            <button type="button" class="btn btn-primary" @click="saveTaxonomy">
              <i class="material-icons mr-1">check</i>
              {{ isEdit ? t('update') : t('create') }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showCreateTaxonomy" class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useTranslations } from '../../composables/useTranslations'
import { useApi } from '../../composables/useApi'

const cptStore = useCptStore()
const { t } = useTranslations()
const api = useApi()
const showCreateTaxonomy = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)

// Selection state
const selectedTaxonomies = ref<Set<number>>(new Set())
const hasSelectedTaxonomies = computed(() => selectedTaxonomies.value.size > 0)
const selectedCount = computed(() => selectedTaxonomies.value.size)
const isAllSelected = computed(() => {
  return cptStore.taxonomies.length > 0 && selectedTaxonomies.value.size === cptStore.taxonomies.length
})
const isIndeterminate = computed(() => {
  return selectedTaxonomies.value.size > 0 && selectedTaxonomies.value.size < cptStore.taxonomies.length
})

// Languages
const languages = computed(() => (window as any).acfConfig?.languages || [])
const defaultLanguage = computed(() => languages.value.find((l: any) => l.is_default) || languages.value[0])
const currentLangCode = ref('')

watch(languages, (langs) => {
  if (langs.length > 0 && !currentLangCode.value) {
    currentLangCode.value = defaultLanguage.value?.iso_code || langs[0].iso_code
  }
}, { immediate: true })

// Translations state
const taxTranslations = reactive<Record<number, { name: string; description: string }>>({})

// Initialize translations when modal opens
watch(showCreateTaxonomy, (show) => {
  if (show) {
    languages.value.forEach((lang: any) => {
      if (!taxTranslations[lang.id_lang]) {
        taxTranslations[lang.id_lang] = { name: '', description: '' }
      }
    })
  }
})

const taxonomyForm = reactive({
  name: '',
  slug: '',
  description: '',
  hierarchical: true
})

// Selection methods
function toggleTaxonomySelection(id: number): void {
  if (selectedTaxonomies.value.has(id)) {
    selectedTaxonomies.value.delete(id)
  } else {
    selectedTaxonomies.value.add(id)
  }
}

function toggleSelectAll(): void {
  if (isAllSelected.value) {
    selectedTaxonomies.value.clear()
  } else {
    selectedTaxonomies.value.clear()
    cptStore.taxonomies.forEach(tax => {
      if (tax.id) selectedTaxonomies.value.add(tax.id)
    })
  }
}

function clearSelection(): void {
  selectedTaxonomies.value.clear()
}

// Bulk delete
function confirmBulkDelete(): void {
  const ids = Array.from(selectedTaxonomies.value)
  if (ids.length === 0) return
  
  const message = ids.length === 1
    ? t('confirmDeleteTaxonomy')
    : t('confirmDeleteTaxonomies', 'Delete {count} taxonomies?', { count: ids.length.toString() })
  
  if (confirm(message)) {
    bulkDelete(ids)
  }
}

async function bulkDelete(ids: number[]): Promise<void> {
  try {
    const response = await api.fetchJson('/cpt/taxonomies/bulk-delete', {
      method: 'POST',
      body: JSON.stringify({ taxonomyIds: ids })
    })
    
    if (response.success) {
      await cptStore.fetchTaxonomies()
      clearSelection()
      alert(t('bulkDeleteSuccess'))
    } else {
      alert(t('bulkDeleteError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('bulkDeleteError') + ': ' + (e as Error).message)
  }
}

function generateTaxSlug() {
  if (!isEdit.value) {
    // In single-language mode, use taxonomyForm.name directly
    let sourceName = taxonomyForm.name
    
    // In multi-language mode, use translations
    if (languages.value.length > 1) {
      const defaultLangId = defaultLanguage.value?.id_lang
      if (defaultLangId && taxTranslations[defaultLangId] && taxTranslations[defaultLangId].name) {
        sourceName = taxTranslations[defaultLangId].name
      }
    }
    
    taxonomyForm.slug = (sourceName || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_|_$/g, '')
  }
}

function resetForm() {
  taxonomyForm.name = ''
  taxonomyForm.slug = ''
  taxonomyForm.description = ''
  taxonomyForm.hierarchical = true
  isEdit.value = false
  editId.value = null
  
  Object.keys(taxTranslations).forEach((key) => {
    taxTranslations[parseInt(key)] = { name: '', description: '' }
  })
}

function openCreateModal() {
  resetForm()
  showCreateTaxonomy.value = true
}

async function editTaxonomy(taxonomy: any) {
  resetForm()
  isEdit.value = true
  editId.value = taxonomy.id
  
  languages.value.forEach((lang: any) => {
    if (!taxTranslations[lang.id_lang]) {
      taxTranslations[lang.id_lang] = { name: '', description: '' }
    }
  })
  
  const fullTaxonomy = await cptStore.fetchTaxonomy(taxonomy.id)
  
  if (fullTaxonomy) {
    taxonomyForm.name = fullTaxonomy.name
    taxonomyForm.slug = fullTaxonomy.slug
    taxonomyForm.description = fullTaxonomy.description || ''
    taxonomyForm.hierarchical = fullTaxonomy.hierarchical !== false
    
    if (fullTaxonomy.translations) {
      Object.keys(fullTaxonomy.translations).forEach((langId) => {
        const id = parseInt(langId)
        if (taxTranslations[id]) {
          taxTranslations[id] = {
            name: fullTaxonomy.translations[langId]?.name || '',
            description: fullTaxonomy.translations[langId]?.description || ''
          }
        }
      })
    }

    const defaultLangId = defaultLanguage.value?.id_lang
    if (defaultLangId && (!taxTranslations[defaultLangId]?.name) && fullTaxonomy.name) {
      taxTranslations[defaultLangId] = {
        name: fullTaxonomy.name,
        description: fullTaxonomy.description || ''
      }
    }
    
    showCreateTaxonomy.value = true
  }
}

async function saveTaxonomy() {
  const defaultLangId = defaultLanguage.value?.id_lang
  if (defaultLangId) {
    // In single-language mode, sync taxonomyForm to taxTranslations first
    if (languages.value.length === 1) {
      if (!taxTranslations[defaultLangId]) {
        taxTranslations[defaultLangId] = { name: '', description: '' }
      }
      taxTranslations[defaultLangId].name = taxonomyForm.name || ''
      taxTranslations[defaultLangId].description = taxonomyForm.description || ''
    }
    
    // Then sync translations back to taxonomyForm
    if (taxTranslations[defaultLangId]) {
      taxonomyForm.name = taxTranslations[defaultLangId].name
      taxonomyForm.description = taxTranslations[defaultLangId].description
    }
  }

  const payload = {
    ...taxonomyForm,
    translations: { ...taxTranslations }
  }

  let success = false
  if (isEdit.value && editId.value) {
    success = await cptStore.updateTaxonomy(editId.value, payload)
  } else {
    success = await cptStore.createTaxonomy(payload)
  }
  
  if (success) {
    showCreateTaxonomy.value = false
    resetForm()
  }
}

function manageTerms(taxonomy: any) {
  cptStore.manageTerms(taxonomy)
}

function deleteTaxonomy(taxonomy: any) {
  if (confirm(t('confirmDeleteTaxonomy', 'Delete taxonomy "{name}"?', { name: taxonomy.name }))) {
    cptStore.deleteTaxonomy(taxonomy.id)
  }
}
</script>

<style scoped>
/* Native PrestaShop Grid styles */
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
}

.grid-table .column-id {
  width: 60px;
}

.grid-table .column-actions {
  width: 140px;
}

/* Action buttons */
.btn-group-action .btn,
.btn-group-action .dropdown-item.inline-dropdown-item {
  padding: 0.25rem 0.5rem;
  background: transparent;
  border: none;
}

.btn-group-action .btn:hover {
  background: #f8f9fa;
}

.btn-group-action .material-icons {
  font-size: 20px;
}

/* Checkboxes */
.grid-table .column-select .form-check {
  margin-bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.grid-table thead .header-checkbox {
  margin: 0;
}

/* Modal backdrop */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}

.modal {
  z-index: 1050;
}

/* Empty state */
.text-center.py-5 .material-icons {
  display: block;
  margin: 0 auto 1rem;
}
</style>
