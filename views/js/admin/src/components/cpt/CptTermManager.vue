<template>
  <div class="cpt-term-manager">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button class="btn btn-sm btn-outline-secondary mr-3" @click="cptStore.goToTaxonomiesList()">
            <i class="material-icons">arrow_back</i>
          </button>
          <h3 class="card-header-title">
            {{ t('termsFor') }} <strong>{{ cptStore.currentTaxonomy?.name }}</strong>
          </h3>
        </div>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          <i class="material-icons">add</i>
          {{ t('newTerm') }}
        </button>
      </div>
      <div class="card-body">
        <div v-if="cptStore.loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">{{ t('loading') }}</span>
          </div>
        </div>

        <div v-else-if="cptStore.terms.length === 0" class="alert alert-info">
          {{ t('noTermsFoundForTaxonomy') }}
        </div>

        <table v-else class="table">
          <thead>
            <tr>
              <th>{{ t('name') }}</th>
              <th>{{ t('slug') }}</th>
              <th>{{ t('description') }}</th>
              <th class="text-right">{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="term in cptStore.terms" :key="term.id">
              <td><strong>{{ term.name }}</strong></td>
              <td><code>{{ term.slug }}</code></td>
              <td>{{ term.description || '-' }}</td>
              <td class="text-right">
                <button class="btn btn-sm btn-outline-primary mr-1" @click="editTerm(term)">
                  <i class="material-icons">edit</i>
                </button>
                <button
                  class="btn btn-sm btn-outline-danger"
                  @click="handleDelete(term)"
                  :title="t('delete')"
                >
                  <i class="material-icons">delete</i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Term Modal -->
    <div v-if="showCreateTerm" class="modal" style="display: block">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">{{ isEdit ? t('editTerm') : t('newTerm') }}</h4>
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
              <div class="translationsFields tab-content mt-2">
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
            <button type="button" class="btn btn-secondary" @click="showCreateTerm = false">
              {{ t('cancel') }}
            </button>
            <button type="button" class="btn btn-primary" :disabled="saving" @click="handleSave">
              {{ saving ? t('saving') : (isEdit ? t('save') : t('create')) }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useTranslations } from '../../composables/useTranslations'
import type { CptTerm } from '../../types/cpt'

const cptStore = useCptStore()
const { t } = useTranslations()
const showCreateTerm = ref(false)
const saving = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)

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

// Initialize translations when modal opens
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
  
  // Reset translations
  Object.keys(termTranslations).forEach((key) => {
    termTranslations[parseInt(key)] = { name: '', description: '' }
  })
}

function openCreateModal() {
  resetForm()
  showCreateTerm.value = true
}

// Function to edit term (populate form)
async function editTerm(term: CptTerm) {
  resetForm()
  isEdit.value = true
  editId.value = term.id || null
  
  // Ensure translations are initialized
  languages.value.forEach((lang: any) => {
    if (!termTranslations[lang.id_lang]) {
      termTranslations[lang.id_lang] = { name: '', description: '' }
    }
  })
  
  // Basic fill from list item (might be partial)
  termForm.name = term.name
  termForm.slug = term.slug
  termForm.description = term.description || ''

  // Fetch full details including translations
  const fullTerm = term.id ? await cptStore.fetchTerm(term.id) : null
  
  if (fullTerm) {
    // Update main fields in case list was outdated
    termForm.name = fullTerm.name
    termForm.slug = fullTerm.slug
    termForm.description = fullTerm.description || ''

    // Populate translations
    if ((fullTerm as any).translations) {
      const translations = (fullTerm as any).translations
      Object.keys(translations).forEach((langId) => {
        const lid = parseInt(langId)
        if (termTranslations[lid])   {
           termTranslations[lid].name = translations[langId].name
           termTranslations[lid].description = translations[langId].description
        }
      })
    }
  }
  
  // Fallback for legacy data (if translations missing)
  const defaultLangId = defaultLanguage.value?.id_lang
  if (defaultLangId && (!termTranslations[defaultLangId]?.name || termTranslations[defaultLangId].name === '') && termForm.name) {
      if (termTranslations[defaultLangId]) {
        termTranslations[defaultLangId].name = termForm.name
        if (termForm.description) {
           termTranslations[defaultLangId].description = termForm.description
        }
      }
  }
  
  showCreateTerm.value = true
}

async function handleSave() {
  if (!cptStore.currentTaxonomy) return
  
  // Sync form name/desc with default lang
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

// Expose openCreateModal to template
// Wait, we need to expose editTerm too
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1050;
}
.mr-3 {
  margin-right: 1rem;
}
</style>
