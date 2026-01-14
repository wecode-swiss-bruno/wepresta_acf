<template>
  <div class="cpt-post-builder">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-header-title mb-0">
          {{ isEdit ? 'Edit' : 'Add New' }} {{ cptStore.currentType?.name }}
        </h3>
        <button class="btn btn-outline-secondary btn-sm" @click="cancel">
          Cancel
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
              <div class="form-group">
                <label class="form-control-label">Title</label>
                <input 
                  v-model="formData.title" 
                  type="text" 
                  class="form-control" 
                  placeholder="Enter title here"
                  required
                  @input="handleTitleInput"
                >
              </div>

              <div class="form-group">
                <label class="form-control-label">Permalink</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">{{ cptStore.currentType?.url_prefix }}/</span>
                  </div>
                  <input v-model="formData.slug" type="text" class="form-control" required>
                </div>
              </div>

               <!-- ACF Fields -->
               <div v-if="acfGroups.length > 0" class="acf-fields-section mt-4">
                  <h4 class="mb-3">Custom Fields</h4>
                  <div v-for="group in acfGroups" :key="group.id" class="card mb-3">
                    <div class="card-header font-weight-bold">
                      {{ group.title || 'Fields' }}
                    </div>
                    <div class="card-body">
                      <AcfFieldRenderer
                        v-for="field in group.fields"
                        :key="field.id"
                        :field="field"
                        :model-value="formData.acf[field.slug]"
                        :languages="languages"
                        :default-language="defaultLanguage"
                        @update:model-value="(val) => formData.acf[field.slug] = val"
                      />
                    </div>
                  </div>
               </div>

              <!-- Meta Box: SEO -->
              <div class="card mt-4">
                <div class="card-header">SEO Metadata</div>
                <div class="card-body">
                  <div class="form-group">
                    <label class="form-control-label">SEO Title</label>
                    <input v-model="formData.seo_title" type="text" class="form-control" :placeholder="formData.title">
                  </div>
                  <div class="form-group">
                    <label class="form-control-label">SEO Description</label>
                    <textarea v-model="formData.seo_description" class="form-control" rows="3"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-3">
              <div class="card">
                <div class="card-header">Publish</div>
                <div class="card-body">
                  <div class="form-group">
                    <label class="form-control-label">Status</label>
                    <select v-model="formData.status" class="form-control">
                      <option value="draft">Draft</option>
                      <option value="published">Published</option>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-primary btn-block" :disabled="saving">
                    {{ saving ? 'Saving...' : (isEdit ? 'Update' : 'Publish') }}
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
                    No terms found.
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
                      <span class="cursor-pointer font-weight-bold" @click="removeRelation(relation.id, post.id)" style="cursor: pointer;">&times;</span>
                    </div>
                  </div>
                  
                  <!-- Search Input -->
                  <div class="input-group input-group-sm">
                    <input 
                      v-model="searchQueries[relation.id]" 
                      type="text" 
                      class="form-control" 
                      :placeholder="'Search ' + (relation.target_type_name || 'posts') + '...'"
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
import { ref, computed, onMounted, watch } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useApi } from '../../composables/useApi'
import AcfFieldRenderer from '@/components/renderer/AcfFieldRenderer.vue'

const props = defineProps<{
  postId?: number
}>()

const emit = defineEmits(['cancel', 'saved'])

const cptStore = useCptStore()
const { slugify } = useApi()

const isEdit = computed(() => !!props.postId)
const loading = ref(false)
const saving = ref(false)
const activeRelations = ref<any[]>([])
const acfGroups = ref<any[]>([])
const languages = ref<any[]>([])
const defaultLanguage = ref<any>(null)

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
  return cptStore.taxonomies.filter(tax => 
    cptStore.currentType?.taxonomies?.includes(tax.id!)
  )
})

onMounted(async () => {
  // Fetch languages (assuming available via window or API - for now simple mockup or get from context)
  // TODO: Proper language fetching from API
  if ((window as any).psLanguages) {
     languages.value = (window as any).psLanguages
     defaultLanguage.value = languages.value.find((l: any) => l.is_default) || languages.value[0]
  } else {
     // Fallback to single language if not found
     defaultLanguage.value = { id_lang: 1, iso_code: 'en', is_default: 1 }
     languages.value = [defaultLanguage.value]
  }

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

  // Ensure taxonomies are loaded
  if (cptStore.taxonomies.length === 0) {
    cptStore.fetchTaxonomies()
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

async function handleTitleInput() {
  if (!isEdit.value && formData.value.title) {
    formData.value.slug = await slugify(formData.value.title)
  }
}

async function handleSubmit() {
  saving.value = true
  try {
    if (isEdit.value) {
      await cptStore.updatePost(props.postId!, cptStore.currentType!.slug, formData.value)
    } else {
      await cptStore.createPost(cptStore.currentType!.slug, formData.value)
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
