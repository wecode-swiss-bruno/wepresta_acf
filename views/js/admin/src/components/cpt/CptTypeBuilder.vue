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
              <label for="type_slug">Slug *</label>
              <input
                id="type_slug"
                v-model="formData.slug"
                type="text"
                class="form-control"
                required
                pattern="[a-z0-9_-]+"
              />
              <small class="form-text text-muted">
                Lowercase letters, numbers, hyphens and underscores only
              </small>
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

          <!-- ACF Groups Selection -->
          <div class="form-section mt-4">
            <h4>ACF Groups</h4>
            <p class="text-muted">Select which ACF groups to display when editing posts of this type</p>
            
            <div v-if="availableGroups.length > 0" class="acf-groups-list">
              <div v-for="group in availableGroups" :key="group.id" class="form-check">
                <input
                  :id="`group_${group.id}`"
                  v-model="formData.acf_groups"
                  type="checkbox"
                  class="form-check-input"
                  :value="group.id"
                />
                <label :for="`group_${group.id}`" class="form-check-label">
                  {{ group.title }}
                </label>
              </div>
            </div>
            <div v-else class="alert alert-info">
              No ACF groups available. Create ACF groups first.
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
import { useBuilderStore } from '../../stores/builderStore'
import type { CptType } from '../../types/cpt'

const props = defineProps<{
  typeId?: number
}>()

const emit = defineEmits<{
  (e: 'saved', type: CptType): void
  (e: 'cancel'): void
}>()

const cptStore = useCptStore()
const builderStore = useBuilderStore()

const isEdit = computed(() => !!props.typeId)
const saving = ref(false)
const availableGroups = ref<any[]>([])

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
  acf_groups: [],
  taxonomies: []
})

const seoConfig = reactive({
  title_pattern: '{title} - {shop_name}',
  description_pattern: ''
})

onMounted(async () => {
  // Fetch available ACF groups
  await builderStore.loadGroups()
  availableGroups.value = builderStore.groups

  // Fetch available taxonomies
  await cptStore.fetchTaxonomies()

  // Load existing type if editing
  if (isEdit.value || cptStore.currentType) {
    const type = cptStore.currentType || (props.typeId ? await cptStore.fetchType(props.typeId) : null)
    if (type) {
      Object.assign(formData, type)
      if (type.seo_config) {
        Object.assign(seoConfig, type.seo_config)
      }
    }
  }
})

function generateSlug() {
  if (!isEdit.value) {
    formData.slug = formData.name
      ?.toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_|_$/g, '') || ''
    
    formData.url_prefix = formData.slug
  }
}

async function handleSubmit() {
  saving.value = true

  try {
    // Prepare data
    const typeData = {
      ...formData,
      seo_config: seoConfig
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

.acf-groups-list,
.taxonomies-list {
  max-height: 300px;
  overflow-y: auto;
}
</style>
