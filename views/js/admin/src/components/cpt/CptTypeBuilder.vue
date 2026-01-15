<template>
  <div class="cpt-type-builder">
    <div class="card">
      <div class="card-header">
        <h3 class="card-header-title">
          {{ isEdit ? 'Edit CPT Type' : 'New CPT Type' }}
        </h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="handleSubmit">
          <div v-if="cptStore.error" class="alert alert-danger">
            {{ cptStore.error }}
          </div>
          <!-- General Settings -->
          <div class="form-section">
            <h4>General Settings</h4>

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
                    <label :for="`type_name_${lang.id_lang}`">Name * ({{ lang.iso_code.toUpperCase() }})</label>
                    <input
                      :id="`type_name_${lang.id_lang}`"
                      v-model="translations[lang.id_lang].name"
                      type="text"
                      class="form-control"
                      :required="lang.is_default"
                      @input="lang.is_default && generateSlug()"
                    />
                  </div>
                  <div class="form-group">
                    <label :for="`type_description_${lang.id_lang}`">Description ({{ lang.iso_code.toUpperCase() }})</label>
                    <textarea
                      :id="`type_description_${lang.id_lang}`"
                      v-model="translations[lang.id_lang].description"
                      class="form-control"
                      rows="3"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>
            <!-- Single language fallback -->
            <div v-else>
              <div class="form-group">
                <label for="type_name">Name *</label>
                <input
                  id="type_name"
                  v-model="formData.name"
                  type="text"
                  class="form-control"
                  required
                  @input="generateSlug"
                />
              </div>
              <div class="form-group">
                <label for="type_description">Description</label>
                <textarea
                  id="type_description"
                  v-model="formData.description"
                  class="form-control"
                  rows="3"
                ></textarea>
              </div>
            </div>

            <!-- Slug field (after translations) -->
            <div class="form-group mt-3">
              <label for="type_slug">Slug *</label>
              <input
                id="type_slug"
                v-model="formData.slug"
                type="text"
                class="form-control"
                required
                pattern="[a-z0-9_-]+"
                :disabled="isEdit"
              />
              <small v-if="!isEdit" class="form-text text-muted">
                Lowercase letters, numbers, hyphens and underscores only
              </small>
              <small v-else class="form-text text-muted">
                <i class="material-icons" style="font-size: 14px; vertical-align: text-bottom;">lock</i>
                Slug cannot be modified after creation to ensure DB integrity.
              </small>
            </div>

            <div class="form-group">
              <label for="type_icon">Icon</label>
              <input
                id="type_icon"
                v-model="formData.icon"
                type="text"
                class="form-control"
                placeholder="article"
              />
              <small class="form-text text-muted">
                Material icon name (e.g., article, event, folder)
              </small>
            </div>
          </div>

          <!-- URL Settings -->
          <div class="form-section mt-4">
            <h4>URL Settings</h4>

            <div class="form-group">
              <label for="url_prefix">URL Prefix *</label>
              <input
                id="url_prefix"
                v-model="formData.url_prefix"
                type="text"
                class="form-control"
                required
              />
              <small class="form-text text-muted">
                Posts will be accessible at: /{{ formData.url_prefix }}/post-slug
              </small>
            </div>

            <div class="form-check">
              <input
                id="has_archive"
                v-model="formData.has_archive"
                type="checkbox"
                class="form-check-input"
              />
              <label for="has_archive" class="form-check-label">
                Enable Archive Page
              </label>
            </div>

            <div v-if="formData.has_archive" class="form-group mt-2">
              <label for="archive_slug">Archive Slug (optional)</label>
              <input
                id="archive_slug"
                v-model="formData.archive_slug"
                type="text"
                class="form-control"
                placeholder="Same as URL prefix"
              />
            </div>
          </div>

          <!-- SEO Settings -->
          <div class="form-section mt-4">
            <h4>SEO Settings</h4>

            <div class="form-group">
              <label for="seo_title_pattern">Title Pattern</label>
              <input
                id="seo_title_pattern"
                v-model="seoConfig.title_pattern"
                type="text"
                class="form-control"
                placeholder="{title} - {shop_name}"
              />
            </div>

            <div class="form-group">
              <label for="seo_desc_pattern">Description Pattern</label>
              <input
                id="seo_desc_pattern"
                v-model="seoConfig.description_pattern"
                type="text"
                class="form-control"
                placeholder="{title} - Read more on {shop_name}"
              />
            </div>
          </div>



          <!-- Taxonomies Selection -->
          <div class="form-section mt-4">
            <h4>Taxonomies</h4>
            <p class="text-muted">Select which taxonomies to use with this post type</p>
            
            <div v-if="cptStore.taxonomies.length > 0" class="taxonomies-list">
              <div v-for="taxonomy in cptStore.taxonomies" :key="taxonomy.id" class="form-check">
                <input
                  :id="`tax_${taxonomy.id}`"
                  v-model="formData.taxonomies"
                  type="checkbox"
                  class="form-check-input"
                  :value="taxonomy.id"
                />
                <label :for="`tax_${taxonomy.id}`" class="form-check-label">
                  {{ taxonomy.name }}
                </label>
              </div>
            </div>
            <div v-else class="alert alert-info">
              No taxonomies available. Create taxonomies first.
            </div>
          </div>

          <!-- Actions -->
          <div class="form-actions mt-4">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving">Saving...</span>
              <span v-else>{{ isEdit ? 'Update' : 'Create' }} Type</span>
            </button>
            <button type="button" class="btn btn-secondary ml-2" @click="$emit('cancel')">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useCptStore } from '../../stores/cptStore'

import type { CptType } from '../../types/cpt'

const props = defineProps<{
  typeId?: number
}>()

const emit = defineEmits<{
  (e: 'saved', type: CptType): void
  (e: 'cancel'): void
}>()

const cptStore = useCptStore()

const isEdit = computed(() => !!props.typeId)
const saving = ref(false)

// Languages
const languages = computed(() => (window as any).acfConfig?.languages || [])
const defaultLanguage = computed(() => languages.value.find((l: any) => l.is_default) || languages.value[0])
const currentLangCode = ref('')

// Initialize currentLangCode when languages load
watch(languages, (langs) => {
  if (langs.length > 0 && !currentLangCode.value) {
    currentLangCode.value = defaultLanguage.value?.iso_code || langs[0].iso_code
  }
}, { immediate: true })

// Translations state
const translations = reactive<Record<number, { name: string; description: string }>>({})

const formData = reactive<Partial<CptType>>({
  name: '',
  slug: '',
  description: '',
  url_prefix: '',
  has_archive: true,
  archive_slug: '',
  icon: 'article',
  position: 0,
  active: true,
  taxonomies: []
})

const seoConfig = reactive({
  title_pattern: '{title} - {shop_name}',
  description_pattern: ''
})

import { watch } from 'vue'

const initForm = async () => {
  // Fetch available taxonomies
  await cptStore.fetchTaxonomies()

  // Initialize translations for all languages
  languages.value.forEach((lang: any) => {
    if (!translations[lang.id_lang]) {
      translations[lang.id_lang] = { name: '', description: '' }
    }
  })

  // Load existing type if editing
  if (isEdit.value || cptStore.currentType) {
    const type = cptStore.currentType || (props.typeId ? await cptStore.fetchType(props.typeId) : null)
    if (type) {
      Object.assign(formData, type)
      
      // Fix taxonomies to be array of IDs (API returns objects)
      if (type.taxonomies && Array.isArray(type.taxonomies)) {
        formData.taxonomies = type.taxonomies.map((t: any) => {
          return typeof t === 'object' ? (t.id_wepresta_acf_cpt_taxonomy || t.id) : t
        })
      }

      if (type.seo_config) {
        Object.assign(seoConfig, type.seo_config)
      }
      // Load translations
      if (type.translations) {
        Object.keys(type.translations).forEach((langId) => {
          const id = parseInt(langId)
          translations[id] = {
            name: type.translations[id]?.name || '',
            description: type.translations[id]?.description || ''
          }
        })
      }
    }
  }
}

watch(() => props.typeId, initForm)

onMounted(initForm)

function generateSlug() {
  if (!isEdit.value) {
    // Use default language name for slug
    const defaultLangId = defaultLanguage.value?.id_lang
    const sourceName = defaultLangId && translations[defaultLangId]
      ? translations[defaultLangId].name
      : formData.name
    
    formData.slug = (sourceName || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_|_$/g, '')
    
    formData.url_prefix = formData.slug
  }
}

async function handleSubmit() {
  saving.value = true

  try {
    // Set default name/description from default language translation
    const defaultLangId = defaultLanguage.value?.id_lang
    if (defaultLangId && translations[defaultLangId]) {
      formData.name = translations[defaultLangId].name
      formData.description = translations[defaultLangId].description
    }

    // Prepare data
    const typeData = {
      ...formData,
      seo_config: seoConfig,
      translations: { ...translations }
    }

    let result
    if (isEdit.value && props.typeId) {
      result = await cptStore.updateType(props.typeId, typeData)
    } else {
      result = await cptStore.createType(typeData)
    }

    if (result) {
      emit('saved', result)
    }
  } catch (e: any) {
    cptStore.error = e.message
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.form-section {
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 1.5rem;
}

.form-section:last-child {
  border-bottom: none;
}

.taxonomies-list {
  max-height: 300px;
  overflow-y: auto;
}
</style>
