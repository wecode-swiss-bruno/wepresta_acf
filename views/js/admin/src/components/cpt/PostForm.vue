<template>
  <div class="row justify-content-center">
    <!-- Main Content -->
    <div class="col-xl-9 col-lg-8">
      <div class="card">
        <div class="card-header">
           <h3 class="card-header-title">
             {{ isEdit ? 'Edit Post' : 'New ' + typeName }}
           </h3>
           <div class="card-header-actions">
             <router-link to="/" class="btn btn-outline-secondary btn-sm">
                <i class="material-icons">arrow_back</i> Back to List
             </router-link>
           </div>
        </div>
        <div class="card-body">
             <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary"></div>
             </div>

             <div v-else-if="error" class="alert alert-danger">
                {{ error }}
             </div>

             <div v-else>
               <!-- Title -->
               <div class="form-group">
                 <label class="required">Title</label>
                 <!-- Simple input for non-multilingual title for now, or TranslatableInput if API supports it -->
                 <!-- The legacy controller used TranslatableInput logic manually via setTranslations -->
                 <!-- Here we will simply map to 'title' prop, backend handles mapping to translations if needed or we send translations object -->
                 <input type="text" class="form-control" v-model="formData.title" required>
                 <small class="form-text text-muted">Title is currently global (not translatable in this MVP SPA).</small>
               </div>
               
               <!-- Slug -->
               <div class="form-group">
                  <label>Slug</label>
                  <input type="text" class="form-control" v-model="formData.slug">
                  <small class="form-text text-muted">Leave empty to auto-generate.</small>
               </div>

               <!-- ACF Fields -->
               <div v-if="acfGroups.length > 0" class="mt-4">
                  <h4 class="mb-3">Custom Fields</h4>
                  <AcfEntityFields
                      :groups="acfGroups"
                      :initialValues="acfValues"
                      :entityType="'cpt_post'"
                      :entityId="isEdit ? parseInt(props.id) : 0"
                      :languages="config.languages"
                      :defaultLanguage="defaultLanguage"
                      :shopId="config.shopId"
                      :api-url="config.apiUrl"
                      :csrf-token="config.token"
                      :auto-save="false"
                      @update:values="onAcfValuesUpdate"
                  />
               </div>

               <!-- SEO -->
               <div class="mt-4 border-top pt-3">
                 <h4>SEO</h4>
                 <div class="form-group">
                    <label>SEO Title</label>
                    <input type="text" class="form-control" v-model="formData.seo_title">
                 </div>
                 <div class="form-group">
                    <label>SEO Description</label>
                    <textarea class="form-control" rows="3" v-model="formData.seo_description"></textarea>
                 </div>
               </div>
             </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-lg-4" v-if="!loading">
      <div class="card">
         <div class="card-header">
           <h3 class="card-header-title">Publish</h3>
         </div>
         <div class="card-body">
            <div class="form-group">
               <label>Status</label>
               <select class="form-control custom-select" v-model="formData.status">
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
               </select>
            </div>
         </div>
         <div class="card-footer">
            <button class="btn btn-primary btn-block" @click="save" :disabled="saving">
               {{ saving ? 'Saving...' : 'Save' }}
            </button>
            <div v-if="saveError" class="text-danger small mt-2">
               <i class="material-icons" style="font-size: 14px; vertical-align: middle;">error</i>
               {{ saveError }}
            </div>
         </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, computed, onMounted } from 'vue'
import { useApi, ApiError } from '../../composables/useApi'
import { useRouter } from 'vue-router'
import AcfEntityFields from '@/components/renderer/AcfEntityFields.vue'

const props = defineProps(['id'])
const config = inject('cptConfig') as any
const api = useApi()
const router = useRouter()

const isEdit = computed(() => !!props.id)
const typeName = config?.typeName || 'Post'

const formData = ref({
  title: '',
  slug: '',
  seo_title: '',
  seo_description: '',
  status: 'draft',
})

const acfGroups = ref<any[]>([])
const acfValues = ref<any>({})
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const saveError = ref('')

const defaultLanguage = computed(() => {
  return config.languages.find((l: any) => l.id_lang === config.defaultLangId) || config.languages[0]
})

// Current ACF values buffer
const currentAcfValues = ref<any>({})

function onAcfValuesUpdate(values: any) {
  currentAcfValues.value = values
}

async function loadData() {
  loading.value = true
  try {
    if (isEdit.value) {
       const post = await api.getCpt(parseInt(props.id))
       formData.value = {
         title: post.title,
         slug: post.slug,
         seo_title: post.seo_title,
         seo_description: post.seo_description,
         status: post.status,
       }
       // Process ACF Groups and Values
       // The API 'show' endpoint returns 'acf_groups' and 'acf_values'
       acfGroups.value = post.acf_groups || []
       acfValues.value = post.acf_values || {}
       // Also initialize buffer
       currentAcfValues.value = { ...post.acf_values }
    } else {
       // For Create, we need to fetch groups separately or use an endpoint helper
    }
  } catch (err: any) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  saveError.value = ''
  try {
    const payload = {
       ...formData.value,
       acf: currentAcfValues.value
    }
    
    if (isEdit.value) {
       await api.updateCpt(parseInt(props.id), payload)
       // Refresh
       await loadData()
       // Optional: clear error if successful
    } else {
       // Create
       // We must pass type slug
       const response = await api.createCpt(config.typeSlug, payload)
       // Redirect to edit
       router.push(`/edit/${response.id}`)
    }
  } catch (err: any) {
    if (err instanceof ApiError && err.errors) {
       // Format validation errors
       const messages = Object.values(err.errors).flat()
       saveError.value = 'Validation failed: ' + messages.join('. ')
    } else {
       saveError.value = 'Save failed: ' + err.message
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>
