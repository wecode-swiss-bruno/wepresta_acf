<template>
  <div class="cpt-taxonomy-manager">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-header-title">{{ t('taxonomies') }}</h3>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">
          <i class="material-icons">add</i>
          {{ t('newTaxonomy') }}
        </button>
      </div>
      <div class="card-body">
        <div v-if="cptStore.taxonomies.length === 0" class="alert alert-info">
          {{ t('noTaxonomiesYet') }}
        </div>

        <div v-else class="list-group">
          <div
            v-for="taxonomy in cptStore.taxonomies"
            :key="taxonomy.id"
            class="list-group-item d-flex justify-content-between align-items-center"
          >
            <div>
              <strong>{{ taxonomy.name }}</strong>
              <br>
              <code>{{ taxonomy.slug }}</code>
              <small v-if="taxonomy.description" class="text-muted d-block">
                {{ taxonomy.description }}
              </small>
            </div>
            <div class="btn-group">
              <button
                class="btn btn-sm btn-outline-secondary"
                @click="manageTerms(taxonomy)"
                :title="t('manageTerms')"
              >
                <i class="material-icons">category</i>
                {{ t('terms') }}
              </button>
              <button
                class="btn btn-sm btn-outline-primary"
                @click="editTaxonomy(taxonomy)"
                :title="t('edit')"
              >
                <i class="material-icons">edit</i>
              </button>
              <button
                class="btn btn-sm btn-outline-danger"
                @click="deleteTaxonomy(taxonomy)"
                :title="t('delete')"
              >
                <i class="material-icons">delete</i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Taxonomy Modal -->
    <div v-if="showCreateTaxonomy" class="modal" style="display: block">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEdit ? t('editTaxonomy') : t('newTaxonomy') }}</h5>
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
              <div class="translationsFields tab-content mt-2">
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
            <button type="button" class="btn btn-secondary" @click="showCreateTaxonomy = false">
              {{ t('cancel') }}
            </button>
            <button type="button" class="btn btn-primary" @click="saveTaxonomy">
              {{ isEdit ? t('update') : t('create') }}
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

const cptStore = useCptStore()
const { t } = useTranslations()
const showCreateTaxonomy = ref(false)
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

function generateTaxSlug() {
  if (!isEdit.value) {
    const defaultLangId = defaultLanguage.value?.id_lang
    const sourceName = defaultLangId && taxTranslations[defaultLangId]
      ? taxTranslations[defaultLangId].name
      : taxonomyForm.name
    
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
  
  // Reset translations
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
  
  // Ensure translations are initialized
  languages.value.forEach((lang: any) => {
    if (!taxTranslations[lang.id_lang]) {
      taxTranslations[lang.id_lang] = { name: '', description: '' }
    }
  })
  
  // Fetch full taxonomy data including translations
  const fullTaxonomy = await cptStore.fetchTaxonomy(taxonomy.id)
  
  if (fullTaxonomy) {
    taxonomyForm.name = fullTaxonomy.name
    taxonomyForm.slug = fullTaxonomy.slug
    taxonomyForm.description = fullTaxonomy.description || ''
    taxonomyForm.hierarchical = fullTaxonomy.hierarchical !== false
    
    // Load translations
    if (fullTaxonomy.translations) {
      const translations = fullTaxonomy.translations
      Object.keys(translations).forEach((langId) => {
        const id = parseInt(langId)
        if (taxTranslations[id as unknown as number]) {
          taxTranslations[id as unknown as number] = {
            name: translations[langId]?.name || '',
            description: translations[langId]?.description || ''
          }
        }
      })
    }

    // Fallback for legacy data (if translations missing)
    const defaultLangId = defaultLanguage.value?.id_lang
    if (defaultLangId && (!taxTranslations[defaultLangId]?.name || taxTranslations[defaultLangId].name === '') && fullTaxonomy.name) {
      if (taxTranslations[defaultLangId]) {
        taxTranslations[defaultLangId].name = fullTaxonomy.name
        if (fullTaxonomy.description) {
           taxTranslations[defaultLangId].description = fullTaxonomy.description
        }
      }
    }
    
    showCreateTaxonomy.value = true
  }
}

async function saveTaxonomy() {
  // Set default name/description from default language translation
  const defaultLangId = defaultLanguage.value?.id_lang
  if (defaultLangId && taxTranslations[defaultLangId]) {
    taxonomyForm.name = taxTranslations[defaultLangId].name
    taxonomyForm.description = taxTranslations[defaultLangId].description
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
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.form-section {
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 1.5rem;
}

.form-section:last-child {
  border-bottom: none;
}
</style>
