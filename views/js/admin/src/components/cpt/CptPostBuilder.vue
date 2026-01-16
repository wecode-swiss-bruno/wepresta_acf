<template>
  <div class="cpt-post-builder">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-header-title mb-0">
          {{ isEdit ? t('edit') : t('addNew') }} {{ cptStore.currentType?.name }}
        </h3>
        <button class="btn btn-outline-secondary btn-sm" @click="cancel">
          {{ t('cancel') }}
        </button>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
        </div>

        <form v-else @submit.prevent="handleSubmit">
          <div class="row">
            <!-- Main Content -->
            <div class="col-md-9">
              <!-- Translation Tabs -->
              <div class="translations tabbable mb-3" v-if="languages.length > 1">
                  <ul class="translationsLocales nav nav-pills mb-3">
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
              </div>

              <div class="tab-content">
                <div 
                  v-for="lang in languages" 
                  :key="lang.id_lang" 
                  class="tab-pane fade" 
                  :class="{ 'show active': currentLangCode === lang.iso_code }"
                >
                  <div class="form-group">
                    <label class="form-control-label">{{ t('title') }} <span v-if="lang.id_lang === defaultLanguage.id_lang" class="text-danger">*</span></label>
                    <input 
                      v-if="translations[lang.id_lang]"
                      v-model="translations[lang.id_lang].title" 
                      type="text" 
                      class="form-control" 
                      :placeholder="t('enterTitleHere')"
                      :required="lang.id_lang === defaultLanguage.id_lang"
                      @input="onTitleInput($event, lang.id_lang)"
                    >
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-control-label">{{ t('permalink') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">{{ cptStore.currentType?.url_prefix }}/</span>
                  </div>
                  <input v-model="formData.slug" type="text" class="form-control" required>
                </div>
              </div>

               <!-- ACF Fields -->
               <div v-if="acfGroups.length > 0" class="mt-4">
                  <h4 class="mb-3">{{ t('customFields') }}</h4>
                  <AcfEntityFields
                    :groups="acfGroups"
                    :initial-values="formData.acf"
                    :entity-type="'cpt_post'"
                    :entity-id="isEdit ? parseInt(props.postId!.toString()) : 0"
                    :languages="languages"
                    :default-language="defaultLanguage"
                    :hide-toolbar="true"
                    :auto-save="false"
                    @update:values="(val) => formData.acf = val"
                  />
               </div>

              <!-- Meta Box: SEO -->
              <div class="card mt-4">
                <div class="card-header">{{ t('seoMetadata') }}</div>
                <div class="card-body">
                   <div class="tab-content">
                    <div 
                      v-for="lang in languages" 
                      :key="lang.id_lang" 
                      class="tab-pane fade" 
                      :class="{ 'show active': currentLangCode === lang.iso_code }"
                    >
                      <div class="form-group">
                        <label class="form-control-label">{{ t('seoTitle') }}</label>
                        <input 
                          v-if="translations[lang.id_lang]"
                          v-model="translations[lang.id_lang].seo_title" 
                          type="text" 
                          class="form-control" 
                          :placeholder="translations[lang.id_lang].title"
                        >
                      </div>
                      <div class="form-group">
                        <label class="form-control-label">{{ t('seoDescription') }}</label>
                        <textarea 
                          v-if="translations[lang.id_lang]"
                          v-model="translations[lang.id_lang].seo_description" 
                          class="form-control" 
                          rows="3"
                        ></textarea>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-3">
              <div class="card">
                <div class="card-header">{{ t('publish') }}</div>
                <div class="card-body">
                  <div class="form-group">
                    <label class="form-control-label">{{ t('status') }}</label>
                    <select v-model="formData.status" class="form-control">
                      <option value="draft">{{ t('draft') }}</option>
                      <option value="published">{{ t('published') }}</option>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-primary btn-block" :disabled="saving">
                    {{ saving ? t('saving') : (isEdit ? t('update') : t('publish')) }}
                  </button>
                </div>
              </div>

              <!-- Taxonomies -->
              <div v-for="taxonomy in relatedTaxonomies" :key="taxonomy.id" class="card mt-3">
                <div class="card-header">{{ taxonomy.name }}</div>
                <div class="card-body scrollable-checkboxes" style="max-height: 200px; overflow-y: auto;">
                  <div v-for="term in taxonomy.terms" :key="term.id" class="custom-control custom-checkbox">
                    <input 
                      :id="'term-' + term.id"
                      v-model="formData.terms" 
                      type="checkbox" 
                      class="custom-control-input" 
                      :value="term.id"
                    >
                    <label class="custom-control-label" :for="'term-' + term.id">{{ term.name }}</label>
                  </div>
                  <div v-if="!taxonomy.terms?.length" class="text-muted small">
                    {{ t('noTermsFound') }}
                  </div>
                </div>
              </div>

              <!-- Relations -->
              <div v-for="relation in activeRelations" :key="relation.id" class="card mt-3">
                <div class="card-header">{{ relation.name }}</div>
                <div class="card-body">
                  <!-- Selected Items -->
                  <div class="mb-2">
                    <div v-for="post in selectedRelationsDisplay[relation.id]" :key="post.id" class="badge badge-primary mr-1 mb-1 p-2 d-inline-flex align-items-center">
                      <span class="mr-2">{{ post.title }}</span>
                      <span class="sr-only">{{ t('loading') }}</span>
                      <span class="cursor-pointer font-weight-bold" @click="removeRelation(relation.id, post.id)" style="cursor: pointer;">&times;</span>
                    </div>
                  </div>
                  
                  <!-- Search Input -->
                  <div class="input-group input-group-sm">
                    <input 
                      v-model="searchQueries[relation.id]" 
                      type="text" 
                      class="form-control" 
                      :placeholder="t('searchPlaceholder', 'Search {name}...', { name: relation.target_type_name || 'posts' })"
                      @input="handleRelationSearch(relation.id, relation.target_type_slug)"
                    >
                  </div>
                  
                  <!-- Search Results -->
                  <div v-if="searchResults[relation.id]?.length" class="list-group mt-2 shadow-sm" style="max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; width: 85%;">
                    <a 
                      v-for="post in searchResults[relation.id]" 
                      :key="post.id" 
                      href="#" 
                      class="list-group-item list-group-item-action py-2"
                      @click.prevent="addRelation(relation.id, post)"
                    >
                      <small>{{ post.title }}</small>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, inject } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useApi } from '../../composables/useApi'
import { useTranslations } from '../../composables/useTranslations'
import AcfEntityFields from '@/components/renderer/AcfEntityFields.vue'

const props = defineProps<{
  postId?: number
}>()

const emit = defineEmits(['cancel', 'saved'])

const cptStore = useCptStore()
const { t } = useTranslations()
const { slugify } = useApi()
const config = inject('cptConfig') as any

const isEdit = computed(() => !!props.postId)
const loading = ref(false)
const saving = ref(false)
const activeRelations = ref<any[]>([])
const acfGroups = ref<any[]>([])
const languages = ref<any[]>([])
const defaultLanguage = ref<any>(null)
const currentLangCode = ref('en')
const translations = ref<Record<number, any>>({})

const formData = ref({
  title: '',
  slug: '',
  status: 'draft',
  seo_title: '',
  seo_description: '',
  terms: [] as number[],
  relations: {} as Record<number, number[]>,
  acf: {} as Record<number, any>
})

const relatedTaxonomies = computed(() => {
  if (!cptStore.currentType?.taxonomies) return []
  
  return cptStore.taxonomies.filter(tax => {
    return cptStore.currentType?.taxonomies?.includes(tax.id!)
  })
})

onMounted(async () => {
  // Fetch languages from injected config
  if (config && config.languages) {
     languages.value = config.languages
     defaultLanguage.value = languages.value.find((l: any) => l.id_lang === config.defaultLangId) || languages.value[0]
     currentLangCode.value = defaultLanguage.value.iso_code
  } else {
     // Fallback if config issues
     defaultLanguage.value = { id_lang: 1, iso_code: 'en', is_default: 1 }
     languages.value = [defaultLanguage.value]
  }

  // Initialize translations
  languages.value.forEach((lang: any) => {
    translations.value[lang.id_lang] = { 
      title: '', 
      seo_title: '', 
      seo_description: '' 
    }
  })
  
  console.log('[CptPostBuilder] Initialized translations for languages:', languages.value, 'translations:', translations.value)

  // Fetch relations for this type
  if (cptStore.currentType?.id) {
    activeRelations.value = await cptStore.fetchRelations(cptStore.currentType.id)
  }

  if (isEdit.value) {
    loading.value = true
    try {
      const post = await cptStore.fetchPost(props.postId!)
      if (post) {
        formData.value = {
          title: post.title,
          slug: post.slug,
          status: post.status,
          seo_title: post.seo_title || '',
          seo_description: post.seo_description || '',
          terms: post.terms || [],
          relations: {}, // Initialize empty
          acf: post.acf_values || {} 
        }

        // Populate translations
        if (post.translations) {
            Object.keys(post.translations).forEach((langId) => {
                const id = parseInt(langId)
                if (translations.value[id]) {
                    translations.value[id] = {
                        title: post.translations[langId].title || '',
                        seo_title: post.translations[langId].seo_title || '',
                        seo_description: post.translations[langId].seo_description || ''
                    }
                }
            })
        }

        // Fallback for current language if empty (legacy data)
        const defId = defaultLanguage.value?.id_lang
        if (defId && (!translations.value[defId]?.title || translations.value[defId].title === '') && post.title) {
           translations.value[defId] = {
               title: post.title,
               seo_title: post.seo_title || '',
               seo_description: post.seo_description || ''
           }
        }

        // Set ACF Groups definition
        if (post.acf_groups) {
             acfGroups.value = post.acf_groups
        }

        // Populate relations
        if (post.relations) {
          // post.relations is { relationId: [{id, title, ...}] }
          Object.entries(post.relations).forEach(([relId, posts]: [string, any]) => {
            const rId = parseInt(relId)
            formData.value.relations[rId] = posts.map((p: any) => p.id)
            selectedRelationsDisplay.value[rId] = posts
          })
        }
      }
    } finally {
      loading.value = false
    }
  } else {
      // Create mode - we need to fetch ACF groups for this type
      // Currently API listByType doesn't return groups, but we need them.
      // Maybe we can fetch "empty" post structure or just fetch type definition with groups?
      // CptType includes acf_groups ID list.
      // We need the full group definition.
      // Ideally API should provide a "schema" endpoint or we assume groups are loaded otherwise.
      // For now, let's try to fetch type details which might include group details or use a helper.
      // If CptType has group IDs, we can fetch groups individually if needed, OR update CptType API to return full groups.
      // Or just wait for store to have them?
      // The Implementation Plan said update API show method. 
      // For CREATE, we might need a "prepare" endpoint.
      // Or we can rely on `cptStore.currentType` having generic info, and maybe fetch groups via builderStore?
      // `builderStore.loadGroups()` loads all groups.
      // Let's use builderStore to get groups for this type.
      
      /* 
      const builderStore = useBuilderStore() - need to import it
      await builderStore.loadGroups()
      const typeGroupIds = cptStore.currentType?.acf_groups || []
      acfGroups.value = builderStore.groups.filter(g => typeGroupIds.includes(g.id))
      */
  }

  // Ensure full type details are loaded (specifically taxonomies list)
  if (cptStore.currentType?.id && !cptStore.currentType.taxonomies) {
    await cptStore.fetchType(cptStore.currentType.id)
  }

  // Ensure taxonomies definitions are loaded
  if (cptStore.taxonomies.length === 0) {
    await cptStore.fetchTaxonomies()
  }
})

// Relation Management
const searchQueries = ref<Record<number, string>>({})
const searchResults = ref<Record<number, any[]>>({})
const selectedRelationsDisplay = ref<Record<number, any[]>>({})

async function handleRelationSearch(relationId: number, targetTypeSlug: string) {
  const query = searchQueries.value[relationId]
  if (!query || query.length < 2) {
    searchResults.value[relationId] = []
    return
  }
  
  const results = await cptStore.searchPosts(targetTypeSlug, query)
  // Filter out already selected
  const selectedIds = formData.value.relations[relationId] || []
  searchResults.value[relationId] = results.filter((p: any) => !selectedIds.includes(p.id))
}

function addRelation(relationId: number, post: any) {
  if (!formData.value.relations[relationId]) {
    formData.value.relations[relationId] = []
  }
  if (!selectedRelationsDisplay.value[relationId]) {
    selectedRelationsDisplay.value[relationId] = []
  }
  
  formData.value.relations[relationId].push(post.id)
  selectedRelationsDisplay.value[relationId].push(post)
  
  // Clear search
  searchQueries.value[relationId] = ''
  searchResults.value[relationId] = []
}

function removeRelation(relationId: number, postId: number) {
  if (formData.value.relations[relationId]) {
    formData.value.relations[relationId] = formData.value.relations[relationId].filter(id => id !== postId)
  }
  if (selectedRelationsDisplay.value[relationId]) {
    selectedRelationsDisplay.value[relationId] = selectedRelationsDisplay.value[relationId].filter(p => p.id !== postId)
  }
}

// Deep watch to fetch terms for taxonomies that might not have them
watch(relatedTaxonomies, (taxos) => {
  taxos.forEach(tax => {
    if (tax.id && (!tax.terms || tax.terms.length === 0)) {
       // We don't have a direct fetch terms for a specific tax in the store that returns them
       // but cptStore.fetchTermsByTaxonomy(id) populates cptStore.terms
       // For simplicity in the builder, we might need terms populated in the taxonomy object
       // or just use cptStore.terms filtered by taxonomy_id
    }
  })
}, { immediate: true })

async function handleTitleInput(title: string) {
  if (!isEdit.value && title) {
    formData.value.slug = await slugify(title)
  }
}

function onTitleInput(event: Event, langId: number) {
    if (defaultLanguage.value && langId === defaultLanguage.value.id_lang) {
        const target = event.target as HTMLInputElement
        handleTitleInput(target.value)
    }
}

async function handleSubmit() {
  saving.value = true
  try {
    console.log('[CptPostBuilder] Before submit - translations:', JSON.stringify(translations.value))
    console.log('[CptPostBuilder] Before submit - formData:', JSON.stringify(formData.value))
    
    // Add translations to payload
    const payload = {
        ...formData.value,
        translations: translations.value
    }
    // Update main fields from default language for backward compatibility / list view
    if (defaultLanguage.value && translations.value[defaultLanguage.value.id_lang]) {
        const defTrans = translations.value[defaultLanguage.value.id_lang]
        payload.title = defTrans.title
        payload.seo_title = defTrans.seo_title
        payload.seo_description = defTrans.seo_description
    }
    
    console.log('[CptPostBuilder] Final payload:', JSON.stringify(payload))

    if (isEdit.value) {
      await cptStore.updatePost(props.postId!, cptStore.currentType!.slug, payload)
    } else {
      await cptStore.createPost(cptStore.currentType!.slug, payload)
    }
    emit('saved')
  } catch (e) {
    console.error('Save failed', e)
  } finally {
    saving.value = false
  }
}

function cancel() {
  emit('cancel')
}
</script>

<style scoped>
.scrollable-checkboxes {
  padding-left: 5px;
}
.custom-control-label {
  cursor: pointer;
}
</style>
