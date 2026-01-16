<template>
  <div class="cpt-type-builder">
    <!-- Title bar with unsaved indicator and save button -->
    <div class="acfps-builder-title-bar">
      <h5 class="mb-0">
        <span class="material-icons text-muted mr-2">article</span>
        {{ formData.name || t('newCptType') }}
        <span v-if="saving" class="spinner-border spinner-border-sm ml-2"></span>
        <span v-if="hasUnsavedChanges && !saving" class="badge badge-warning ml-2" :title="t('unsavedChanges')">
          <span class="material-icons" style="font-size: 14px; vertical-align: middle;">warning</span>
          {{ t('notSaved') }}
        </span>
      </h5>
      <div class="d-flex align-items-center">
        <!-- Save button always visible -->
        <button 
          type="button" 
          class="btn btn-primary btn-sm mr-2" 
          @click="handleSave"
          :disabled="saving || !hasUnsavedChanges"
        >
          <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
          <span class="material-icons mr-1" v-else>save</span>
          {{ saving ? t('saving') : t('save') }}
        </button>
        <button class="btn btn-outline-secondary btn-sm" @click="$emit('cancel')">
          <span class="material-icons">close</span>
        </button>
      </div>
    </div>

    <!-- Wizard Progress Bar -->
    <div class="acfps-wizard-steps">
      <div 
        class="wizard-step" 
        :class="{ completed: step1Complete, active: activeStep === 1 }"
        @click="goToStep(1)"
      >
        <div class="step-number">
          <span v-if="step1Complete && activeStep !== 1" class="material-icons">check</span>
          <span v-else>1</span>
        </div>
        <div class="step-label">{{ t('general') }}</div>
      </div>
      
      <div class="wizard-connector" :class="{ completed: step1Complete }"></div>
      
      <div 
        class="wizard-step" 
        :class="{ 
          completed: step2Complete, 
          active: activeStep === 2, 
          locked: !canAccessStep2 
        }"
        @click="canAccessStep2 && goToStep(2)"
        :title="!canAccessStep2 ? t('completeStep1First') : ''"
      >
        <div class="step-number">
          <span v-if="step2Complete && activeStep !== 2" class="material-icons">check</span>
          <span v-else>2</span>
        </div>
        <div class="step-label">{{ t('urlSeo') }}</div>
      </div>
      
      <div class="wizard-connector" :class="{ completed: step2Complete }"></div>
      
      <div 
        class="wizard-step" 
        :class="{ 
          active: activeStep === 3, 
          locked: !canAccessStep3 
        }"
        @click="canAccessStep3 && goToStep(3)"
        :title="!canAccessStep3 ? t('completePreviousStepsFirst') : ''"
      >
        <div class="step-number">3</div>
        <div class="step-label">{{ t('taxonomies') }}</div>
      </div>
    </div>

    <!-- Content -->
    <div class="acfps-builder-content">
      <div class="card">
        <div class="card-body">
          <div v-if="cptStore.error" class="alert alert-danger">
            {{ cptStore.error }}
          </div>

          <form @submit.prevent="handleSubmit">
            <!-- Step 1: General Settings -->
            <div v-show="activeStep === 1" class="wizard-step-content">
              <h4 class="section-title">
                <span class="material-icons mr-2">settings</span>
                {{ t('generalSettings') }}
              </h4>

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
                      <label :for="`type_name_${lang.id_lang}`">{{ t('name') }} * ({{ lang.iso_code.toUpperCase() }})</label>
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
                      <label :for="`type_description_${lang.id_lang}`">{{ t('description') }} ({{ lang.iso_code.toUpperCase() }})</label>
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
                  <label for="type_name">{{ t('name') }} *</label>
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
                  <label for="type_description">{{ t('description') }}</label>
                  <textarea
                    id="type_description"
                    v-model="formData.description"
                    class="form-control"
                    rows="3"
                  ></textarea>
                </div>
              </div>

              <!-- Slug field -->
              <div class="form-group mt-3">
                <label for="type_slug">{{ t('slug') }} *</label>
                <input
                  id="type_slug"
                  v-model="formData.slug"
                  type="text"
                  class="form-control"
                  required
                  pattern="[a-z0-9_\-]+"
                  :disabled="isEdit"
                />
                <small v-if="!isEdit" class="form-text text-muted">
                  {{ t('slugHelper') }}
                </small>
                <small v-else class="form-text text-muted">
                  <i class="material-icons" style="font-size: 14px; vertical-align: text-bottom;">lock</i>
                  {{ t('slugLockHelper') }}
                </small>
              </div>

              <div class="form-group">
                <label for="type_icon">{{ t('icon') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">
                      <i class="material-icons">{{ formData.icon || 'article' }}</i>
                    </span>
                  </div>
                  <input
                    id="type_icon"
                    v-model="formData.icon"
                    type="text"
                    class="form-control"
                    placeholder="article"
                  />
                </div>
                <small class="form-text text-muted">
                  {{ t('iconHelper') }}
                </small>
              </div>
            </div>

            <!-- Step 2: URL & SEO Settings -->
            <div v-show="activeStep === 2" class="wizard-step-content">
              <h4 class="section-title">
                <span class="material-icons mr-2">link</span>
                {{ t('urlSettings') }}
              </h4>

              <div class="form-group">
                <label for="url_prefix">{{ t('urlPrefix') }} *</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">/</span>
                  </div>
                  <input
                    id="url_prefix"
                    v-model="formData.url_prefix"
                    type="text"
                    class="form-control"
                    required
                  />
                  <div class="input-group-append">
                    <span class="input-group-text">/post-slug</span>
                  </div>
                </div>
                <small class="form-text text-muted">
                  {{ t('urlPrefixHelper', 'Posts will be accessible at: /{prefix}/post-slug', { prefix: formData.url_prefix || 'prefix' }) }}
                </small>
              </div>

              <div class="form-check mb-3">
                <input
                  id="has_archive"
                  v-model="formData.has_archive"
                  type="checkbox"
                  class="form-check-input"
                />
                <label for="has_archive" class="form-check-label">
                  {{ t('enableArchivePage') }}
                </label>
              </div>

              <div v-if="formData.has_archive" class="form-group">
                <label for="archive_slug">{{ t('archiveSlugOptional') }}</label>
                <input
                  id="archive_slug"
                  v-model="formData.archive_slug"
                  type="text"
                  class="form-control"
                  :placeholder="formData.url_prefix"
                />
              </div>

              <hr class="my-4">

              <h4 class="section-title">
                <span class="material-icons mr-2">search</span>
                {{ t('seoSettings') }}
              </h4>

              <div class="form-group">
                <label for="seo_title_pattern">{{ t('titlePattern') }}</label>
                <input
                  id="seo_title_pattern"
                  v-model="seoConfig.title_pattern"
                  type="text"
                  class="form-control"
                  placeholder="{title} - {shop_name}"
                />
                <small class="form-text text-muted">
                  {{ t('patternVariables') }}: {title}, {shop_name}, {type_name}
                </small>
              </div>

              <div class="form-group">
                <label for="seo_desc_pattern">{{ t('descriptionPattern') }}</label>
                <input
                  id="seo_desc_pattern"
                  v-model="seoConfig.description_pattern"
                  type="text"
                  class="form-control"
                  placeholder="{title} - Read more on {shop_name}"
                />
              </div>
            </div>

            <!-- Step 3: Taxonomies Selection -->
            <div v-show="activeStep === 3" class="wizard-step-content">
              <h4 class="section-title">
                <span class="material-icons mr-2">category</span>
                {{ t('taxonomies') }}
              </h4>
              <p class="text-muted mb-3">{{ t('selectTaxonomiesHelper') }}</p>
              
              <div v-if="cptStore.taxonomies.length > 0" class="taxonomies-grid">
                <div v-for="taxonomy in cptStore.taxonomies" :key="taxonomy.id" class="taxonomy-card" :class="{ selected: formData.taxonomies?.includes(taxonomy.id!) }">
                  <label class="taxonomy-label">
                    <input
                      v-model="formData.taxonomies"
                      type="checkbox"
                      class="taxonomy-checkbox"
                      :value="taxonomy.id"
                    />
                    <div class="taxonomy-content">
                      <span class="material-icons taxonomy-icon">category</span>
                      <div class="taxonomy-info">
                        <strong>{{ taxonomy.name }}</strong>
                        <small class="text-muted d-block">{{ taxonomy.slug }}</small>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
              <div v-else class="alert alert-info">
                <i class="material-icons mr-2">info</i>
                {{ t('noTaxonomiesAvailable') }}
              </div>
            </div>

            <!-- Step Navigation -->
            <div class="acfps-step-navigation">
              <button 
                v-if="activeStep > 1" 
                type="button" 
                class="btn btn-outline-secondary" 
                @click="goToPreviousStep"
              >
                <span class="material-icons">arrow_back</span>
                {{ activeStep === 2 ? t('general') : t('urlSeo') }}
              </button>
              <div v-else></div>
              
              <div class="d-flex align-items-center">
                <!-- Step indicator -->
                <span class="text-muted mr-3">
                  {{ t('step') }} {{ activeStep }} / 3
                </span>
                
                <button 
                  v-if="activeStep < 3" 
                  type="button" 
                  class="btn btn-primary" 
                  @click="goToNextStep"
                  :disabled="!canProceedToNextStep"
                >
                  {{ t('next') }}: {{ activeStep === 1 ? t('urlSeo') : t('taxonomies') }}
                  <span class="material-icons">arrow_forward</span>
                </button>
                <button 
                  v-else 
                  type="submit" 
                  class="btn btn-success" 
                  :disabled="saving"
                >
                  <span v-if="saving">
                    <span class="spinner-border spinner-border-sm mr-1"></span>
                    {{ t('saving') }}
                  </span>
                  <span v-else>
                    <span class="material-icons">check</span>
                    {{ isEdit ? t('update') : t('create') }} {{ t('type') }}
                  </span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useTranslations } from '../../composables/useTranslations'

import type { CptType } from '../../types/cpt'

const props = defineProps<{
  typeId?: number
}>()

const emit = defineEmits<{
  (e: 'saved', type: CptType): void
  (e: 'cancel'): void
}>()

const cptStore = useCptStore()
const { t } = useTranslations()

const isEdit = computed(() => !!props.typeId)
const saving = ref(false)
const activeStep = ref<1 | 2 | 3>(1)
const initialFormData = ref<string>('')

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

// Unsaved changes detection
const hasUnsavedChanges = computed(() => {
  return JSON.stringify({ formData, translations, seoConfig }) !== initialFormData.value
})

// Step completion checks
const step1Complete = computed(() => {
  // In single-language mode, check formData directly
  if (languages.value.length === 1) {
    return !!formData.name && !!formData.slug
  }
  
  // In multi-language mode, check translations
  const defaultLangId = defaultLanguage.value?.id_lang
  const hasName = defaultLangId && translations[defaultLangId]
    ? !!translations[defaultLangId].name
    : !!formData.name
  return hasName && !!formData.slug
})

const step2Complete = computed(() => !!formData.url_prefix)

// Step access checks
const canAccessStep2 = computed(() => step1Complete.value)
const canAccessStep3 = computed(() => step1Complete.value && step2Complete.value)

const canProceedToNextStep = computed(() => {
  if (activeStep.value === 1) return step1Complete.value
  if (activeStep.value === 2) return step2Complete.value
  return true
})

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

      // Start at step 3 if editing existing complete type
      if (step1Complete.value && step2Complete.value) {
        activeStep.value = 3
      }
    }
  }

  // Save initial state for dirty checking
  initialFormData.value = JSON.stringify({ formData, translations, seoConfig })
}

watch(() => props.typeId, initForm)

onMounted(initForm)

function generateSlug() {
  if (!isEdit.value) {
    // In single-language mode, use formData.name directly
    let sourceName = formData.name
    
    // In multi-language mode, use translations
    if (languages.value.length > 1) {
      const defaultLangId = defaultLanguage.value?.id_lang
      if (defaultLangId && translations[defaultLangId] && translations[defaultLangId].name) {
        sourceName = translations[defaultLangId].name
      }
    }
    
    formData.slug = (sourceName || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_|_$/g, '')
    
    formData.url_prefix = formData.slug
  }
}

function goToStep(step: 1 | 2 | 3) {
  if (step === 2 && !canAccessStep2.value) return
  if (step === 3 && !canAccessStep3.value) return
  activeStep.value = step
}

async function goToNextStep() {
  if (activeStep.value < 3 && canProceedToNextStep.value) {
    // Just navigate, don't save
    activeStep.value = (activeStep.value + 1) as 1 | 2 | 3
  }
}

function goToPreviousStep() {
  if (activeStep.value > 1) {
    activeStep.value = (activeStep.value - 1) as 1 | 2 | 3
  }
}

async function handleSave() {
  await saveData()
}

async function saveData() {
  saving.value = true

  try {
    // Sync data between formData and translations
    const defaultLangId = defaultLanguage.value?.id_lang
    if (defaultLangId) {
      // In single-language mode, sync formData to translations first
      if (languages.value.length === 1) {
        if (!translations[defaultLangId]) {
          translations[defaultLangId] = { name: '', description: '' }
        }
        translations[defaultLangId].name = formData.name || ''
        translations[defaultLangId].description = formData.description || ''
      }
      
      // Then sync translations back to formData (for multi-language or to ensure consistency)
      if (translations[defaultLangId]) {
        formData.name = translations[defaultLangId].name
        formData.description = translations[defaultLangId].description
      }
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
    } else if (cptStore.currentType?.id) {
      result = await cptStore.updateType(cptStore.currentType.id, typeData)
    } else {
      result = await cptStore.createType(typeData)
      if (result) {
        cptStore.currentType = result
      }
    }

    // Update initial state after successful save
    initialFormData.value = JSON.stringify({ formData, translations, seoConfig })
    
    return result
  } catch (e: any) {
    cptStore.error = e.message
    return null
  } finally {
    saving.value = false
  }
}

async function handleSubmit() {
  const result = await saveData()
  if (result) {
    emit('saved', result)
  }
}
</script>

<style scoped>
.cpt-type-builder {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Title bar */
.acfps-builder-title-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.acfps-builder-title-bar h5 {
  display: flex;
  align-items: center;
}

.acfps-builder-title-bar .badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  vertical-align: middle;
  animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Wizard Steps Bar */
.acfps-wizard-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem 2rem;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.wizard-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s;
}

.wizard-step.locked {
  cursor: not-allowed;
  opacity: 0.5;
}

.wizard-step .step-number {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #e9ecef;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.1rem;
  transition: all 0.3s;
  border: 3px solid transparent;
}

.wizard-step .step-number .material-icons {
  font-size: 24px;
}

.wizard-step.active .step-number {
  background: #25b9d7;
  color: white;
  border-color: #25b9d7;
  box-shadow: 0 0 0 4px rgba(37, 185, 215, 0.2);
  transform: scale(1.1);
}

.wizard-step.completed .step-number {
  background: #70b580;
  color: white;
  border-color: #70b580;
}

.wizard-step.completed:hover .step-number {
  transform: scale(1.05);
}

.wizard-step .step-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6c757d;
  transition: color 0.3s;
}

.wizard-step.active .step-label {
  color: #25b9d7;
  font-weight: 600;
}

.wizard-step.completed .step-label {
  color: #70b580;
}

.wizard-connector {
  width: 80px;
  height: 3px;
  background: #e9ecef;
  margin: 0 1rem;
  margin-bottom: 1.75rem;
  transition: background 0.3s;
  position: relative;
}

.wizard-connector::after {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 0;
  background: #70b580;
  transition: width 0.5s ease;
}

.wizard-connector.completed::after {
  width: 100%;
}

/* Content */
.acfps-builder-content {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.wizard-step-content {
  padding: 1rem 0;
}

.section-title {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
  color: #363a41;
  font-size: 1.1rem;
  font-weight: 600;
}

.section-title .material-icons {
  color: #25b9d7;
}

/* Taxonomies Grid */
.taxonomies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.taxonomy-card {
  border: 2px solid #e9ecef;
  border-radius: 8px;
  transition: all 0.2s;
  background: white;
}

.taxonomy-card:hover {
  border-color: #25b9d7;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.taxonomy-card.selected {
  border-color: #70b580;
  background: #f0fff4;
}

.taxonomy-label {
  display: flex;
  align-items: flex-start;
  padding: 1rem;
  margin: 0;
  cursor: pointer;
}

.taxonomy-checkbox {
  margin-right: 0.75rem;
  margin-top: 0.25rem;
}

.taxonomy-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.taxonomy-icon {
  color: #6c757d;
  font-size: 24px;
}

.taxonomy-card.selected .taxonomy-icon {
  color: #70b580;
}

/* Step Navigation */
.acfps-step-navigation {
  display: flex;
  justify-content: space-between;
  padding: 1.5rem 0;
  border-top: 1px solid #dee2e6;
  margin-top: 2rem;
}

.acfps-step-navigation .btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.acfps-step-navigation .btn .material-icons {
  font-size: 18px;
}

/* Responsive */
@media (max-width: 768px) {
  .acfps-wizard-steps {
    padding: 1rem;
  }
  
  .wizard-connector {
    width: 40px;
  }
  
  .wizard-step .step-label {
    font-size: 0.75rem;
  }
  
  .taxonomies-grid {
    grid-template-columns: 1fr;
  }
}
</style>
