<template>
  <div class="cpt-type-list">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-header-title">{{ t('customPostTypes') }}</h3>
        <button class="btn btn-primary" @click="cptStore.createNewType()">
          <i class="material-icons">add</i>
          {{ t('newCptType') }}
        </button>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center">
          <div class="spinner-border" role="status"></div>
        </div>

        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <div v-else-if="cptStore.sortedTypes.length === 0" class="alert alert-info">
          {{ t('noCptTypeSaved') }}
        </div>

        <div v-else class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{{ t('icon') }}</th>
                <th>{{ t('name') }}</th>
                <th>{{ t('slug') }}</th>
                <th>{{ t('urlPrefix') }}</th>
                <th>{{ t('archive') }}</th>
                <th>{{ t('status') }}</th>
                <th>{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="type in cptStore.sortedTypes" :key="type.id">
                <td>
                  <i class="material-icons">{{ type.icon || 'article' }}</i>
                </td>
                <td>
                  <strong>{{ type.name }}</strong>
                  <br>
                  <small class="text-muted">{{ type.description }}</small>
                </td>
                <td>
                  <code>{{ type.slug }}</code>
                </td>
                <td>
                  <code>/{{ type.url_prefix }}</code>
                </td>
                <td>
                  <span v-if="type.has_archive" class="badge badge-success">{{ t('enabled') }}</span>
                  <span v-else class="badge badge-secondary">{{ t('disabled') }}</span>
                </td>
                <td>
                  <span v-if="type.active" class="badge badge-success">{{ t('active') }}</span>
                  <span v-else class="badge badge-secondary">{{ t('inactive') }}</span>
                </td>
                <td>
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" @click="cptStore.editType(type.id)" :title="t('edit')">
                      <i class="material-icons">edit</i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" @click="managePosts(type)" :title="t('managePosts')">
                      <i class="material-icons">list</i>
                    </button>
                    <a v-if="type.view_url" :href="type.view_url" target="_blank" class="btn btn-sm btn-outline-info" :title="t('viewOnFront')">
                      <i class="material-icons">visibility</i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(type)" :title="t('delete')">
                      <i class="material-icons">delete</i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useTranslations } from '../../composables/useTranslations'

const cptStore = useCptStore()
const { t } = useTranslations()
const loading = ref(false)
const error = ref<string | null>(null)

onMounted(async () => {
  loading.value = true
  await cptStore.fetchTypes()
  await cptStore.fetchTaxonomies()
  loading.value = false
})

function managePosts(type: any) {
  cptStore.currentType = type
  cptStore.viewMode = 'posts' as any
  cptStore.fetchPostsByType(type.slug)
}

function confirmDelete(type: any) {
  if (confirm(t('confirmDeleteType', 'Delete {name}? This will delete all posts.', { name: type.name }))) {
    cptStore.deleteType(type.id)
  }
}
</script>
