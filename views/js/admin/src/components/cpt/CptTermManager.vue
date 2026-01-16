<template>
  <div class="cpt-term-manager">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button class="btn btn-sm btn-outline-secondary mr-3" @click="cptStore.goToTaxonomiesList()">
            <i class="material-icons">arrow_back</i>
          </button>
          <h3 class="card-header-title mb-0">
            <i class="material-icons mr-1">category</i>
            {{ t('termsFor') }} <strong>{{ cptStore.currentTaxonomy?.name }}</strong>
          </h3>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          <i class="material-icons">add</i>
          {{ t('newTerm') }}
        </button>
      </div>
      <div class="card-body">
        <!-- Loading state -->
        <div v-if="cptStore.loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">{{ t('loading') }}</span>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="cptStore.terms.length === 0" class="text-center py-5">
          <i class="material-icons text-muted mb-3" style="font-size: 64px;">label</i>
          <p class="text-muted mb-3">{{ t('noTermsFoundForTaxonomy') }}</p>
          <div class="d-flex justify-content-center">
            <button class="btn btn-outline-primary" @click="openCreateModal">
              <i class="material-icons">add</i>
              {{ t('newTerm') }}
            </button>
          </div>
        </div>

        <template v-else>
          <!-- Bulk Actions Bar -->
          <div v-if="hasSelectedTerms" class="alert alert-light border d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
              <i class="material-icons text-primary mr-2">check_circle</i>
              <span class="font-weight-semibold">
                {{ selectedCount }} {{ selectedCount === 1 ? t('termSelected') : t('termsSelected') }}
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
          <div class="grid js-grid" id="cpt_term_grid" data-grid-id="cpt_term">
            <div class="table-responsive">
              <table class="grid-table js-grid-table table" id="cpt_term_grid_table">
                <thead class="thead-default">
                  <tr class="column-headers">
                    <th scope="col" data-type="selector" data-column-id="select" class="text-center">
                      <input
                        type="checkbox"
                        class="form-check-input header-checkbox"
                        :checked="isAllSelected"
                        :indeterminate.prop="isIndeterminate"
                        @change="toggleSelectAll"
                        id="select-all-terms"
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
                  <tr v-for="term in cptStore.terms" :key="term.id">
                    <td class="selector-type column-select text-center">
                      <div class="form-check mb-0">
                        <input
                          type="checkbox"
                          class="form-check-input"
                          :checked="selectedTerms.has(term.id!)"
                          @change="toggleTermSelection(term.id!)"
                          :id="'select-term-' + term.id"
                        />
                      </div>
                    </td>
                    <td data-identifier class="identifier-type column-id">
                      {{ term.id }}
                    </td>
                    <td class="data-type column-name text-left">
                      <a
                        href="#"
                        class="text-primary font-weight-bold"
                        @click.prevent="editTerm(term)"
                      >
                        {{ term.name }}
                      </a>
                    </td>
                    <td class="data-type column-slug text-left">
                      <code class="text-muted">{{ term.slug }}</code>
                    </td>
                    <td class="data-type column-description text-left">
                      <small class="text-muted">{{ term.description || '-' }}</small>
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
                            @click.prevent="editTerm(term)"
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
                            @click.prevent="handleDelete(term)"
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

    <!-- Create/Edit Term Modal -->
    <div v-if="showCreateTerm" class="modal fade show" style="display: block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title d-flex align-items-center">
              <i class="material-icons mr-2">label</i>
              {{ isEdit ? t('editTerm') : t('newTerm') }}
            </h5>
            <button type="button" class="close" @click="showCreateTerm = false">
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
                    <label :for="`term_name_${lang.id_lang}`">{{ t('name') }} * ({{ lang.iso_code.toUpperCase() }})</label>
                    <input
                      :id="`term_name_${lang.id_lang}`"
                      v-model="termTranslations[lang.id_lang].name"
                      type="text"
                      class="form-control"
                      :required="lang.is_default"
                      @input="lang.is_default && generateSlug()"
                    />
                  </div>
                  <div class="form-group">
                    <label :for="`term_description_${lang.id_lang}`">{{ t('description') }} ({{ lang.iso_code.toUpperCase() }})</label>
                    <textarea
                      :id="`term_description_${lang.id_lang}`"
                      v-model="termTranslations[lang.id_lang].description"
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
                <label for="term_name">{{ t('name') }} *</label>
                <input
                  id="term_name"
                  v-model="termForm.name"
                  type="text"
                  class="form-control"
                  required
                  @input="generateSlug"
                />
              </div>
              <div class="form-group">
                <label for="term_description">{{ t('description') }}</label>
                <textarea
                  id="term_description"
                  v-model="termForm.description"
                  class="form-control"
                  rows="2"
                ></textarea>
              </div>
            </div>

            <div class="form-group mt-3">
              <label for="term_slug">{{ t('slug') }} *</label>
              <input
                id="term_slug"
                v-model="termForm.slug"
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
            <button type="button" class="btn btn-outline-secondary" @click="showCreateTerm = false">
              {{ t('cancel') }}
            </button>
            <button type="button" class="btn btn-primary" :disabled="saving" @click="handleSave">
              <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
              <i v-else class="material-icons mr-1">check</i>
              {{ saving ? t('saving') : (isEdit ? t('save') : t('create')) }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showCreateTerm" class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useTranslations } from '../../composables/useTranslations'
import { useApi } from '../../composables/useApi'
import type { CptTerm } from '../../types/cpt'

const cptStore = useCptStore()
const { t } = useTranslations()
const api = useApi()
const showCreateTerm = ref(false)
const saving = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)

// Selection state
const selectedTerms = ref<Set<number>>(new Set())
const hasSelectedTerms = computed(() => selectedTerms.value.size > 0)
const selectedCount = computed(() => selectedTerms.value.size)
const isAllSelected = computed(() => {
  return cptStore.terms.length > 0 && selectedTerms.value.size === cptStore.terms.length
})
const isIndeterminate = computed(() => {
  return selectedTerms.value.size > 0 && selectedTerms.value.size < cptStore.terms.length
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
const termTranslations = reactive<Record<number, { name: string; description: string }>>({})

watch(showCreateTerm, (show) => {
  if (show) {
    languages.value.forEach((lang: any) => {
      if (!termTranslations[lang.id_lang]) {
        termTranslations[lang.id_lang] = { name: '', description: '' }
      }
    })
  }
})

const termForm = reactive({
  name: '',
  slug: '',
  description: ''
})

// Selection methods
function toggleTermSelection(id: number): void {
  if (selectedTerms.value.has(id)) {
    selectedTerms.value.delete(id)
  } else {
    selectedTerms.value.add(id)
  }
}

function toggleSelectAll(): void {
  if (isAllSelected.value) {
    selectedTerms.value.clear()
  } else {
    selectedTerms.value.clear()
    cptStore.terms.forEach(term => {
      if (term.id) selectedTerms.value.add(term.id)
    })
  }
}

function clearSelection(): void {
  selectedTerms.value.clear()
}

// Bulk delete
function confirmBulkDelete(): void {
  const ids = Array.from(selectedTerms.value)
  if (ids.length === 0) return
  
  const message = ids.length === 1
    ? t('confirmDeleteTerm')
    : t('confirmDeleteTerms', 'Delete {count} terms?', { count: ids.length.toString() })
  
  if (confirm(message)) {
    bulkDelete(ids)
  }
}

async function bulkDelete(ids: number[]): Promise<void> {
  try {
    const response = await api.fetchJson('/cpt/terms/bulk-delete', {
      method: 'POST',
      body: JSON.stringify({ termIds: ids })
    })
    
    if (response.success) {
      if (cptStore.currentTaxonomy?.id) {
        await cptStore.fetchTermsByTaxonomy(cptStore.currentTaxonomy.id)
      }
      clearSelection()
      alert(t('bulkDeleteSuccess'))
    } else {
      alert(t('bulkDeleteError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('bulkDeleteError') + ': ' + (e as Error).message)
  }
}

function generateSlug() {
  if (!isEdit.value) {
    const defaultLangId = defaultLanguage.value?.id_lang
    const sourceName = defaultLangId && termTranslations[defaultLangId]
      ? termTranslations[defaultLangId].name
      : termForm.name

    termForm.slug = (sourceName || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '')
  }
}

function resetForm() {
  termForm.name = ''
  termForm.slug = ''
  termForm.description = ''
  isEdit.value = false
  editId.value = null
  
  Object.keys(termTranslations).forEach((key) => {
    termTranslations[parseInt(key)] = { name: '', description: '' }
  })
}

function openCreateModal() {
  resetForm()
  showCreateTerm.value = true
}

async function editTerm(term: CptTerm) {
  resetForm()
  isEdit.value = true
  editId.value = term.id || null
  
  languages.value.forEach((lang: any) => {
    if (!termTranslations[lang.id_lang]) {
      termTranslations[lang.id_lang] = { name: '', description: '' }
    }
  })
  
  termForm.name = term.name
  termForm.slug = term.slug
  termForm.description = term.description || ''

  const fullTerm = term.id ? await cptStore.fetchTerm(term.id) : null
  
  if (fullTerm) {
    termForm.name = fullTerm.name
    termForm.slug = fullTerm.slug
    termForm.description = fullTerm.description || ''

    if ((fullTerm as any).translations) {
      const translations = (fullTerm as any).translations
      Object.keys(translations).forEach((langId) => {
        const lid = parseInt(langId)
        if (termTranslations[lid]) {
          termTranslations[lid].name = translations[langId].name
          termTranslations[lid].description = translations[langId].description
        }
      })
    }
  }
  
  const defaultLangId = defaultLanguage.value?.id_lang
  if (defaultLangId && (!termTranslations[defaultLangId]?.name) && termForm.name) {
    termTranslations[defaultLangId] = {
      name: termForm.name,
      description: termForm.description
    }
  }
  
  showCreateTerm.value = true
}

async function handleSave() {
  if (!cptStore.currentTaxonomy) return
  
  const defaultLangId = defaultLanguage.value?.id_lang
  if (defaultLangId && termTranslations[defaultLangId]) {
    termForm.name = termTranslations[defaultLangId].name
    termForm.description = termTranslations[defaultLangId].description
  }

  saving.value = true
  const payload = {
    ...termForm,
    translations: { ...termTranslations }
  }

  let success = false
  if (isEdit.value && editId.value) {
    success = await cptStore.updateTerm(editId.value, payload)
  } else if (cptStore.currentTaxonomy?.id) {
    success = await cptStore.createTerm(cptStore.currentTaxonomy.id, payload)
  }
  
  saving.value = false
  
  if (success) {
    showCreateTerm.value = false
    resetForm()
  }
}

async function handleDelete(term: CptTerm) {
  if (term.id && confirm(t('confirmDeleteTerm', 'Delete term "{name}"?', { name: term.name }))) {
    await cptStore.deleteTerm(term.id)
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
  width: 100px;
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

/* Header */
.card-header-title .material-icons {
  font-size: 20px;
  vertical-align: middle;
}

.mr-3 {
  margin-right: 1rem;
}
</style>
