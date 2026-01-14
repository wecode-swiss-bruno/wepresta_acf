<template>
  <div class="cpt-type-list">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-header-title">Custom Post Types</h3>
        <button class="btn btn-primary" @click="cptStore.createNewType()">
          <i class="material-icons">add</i>
          New CPT Type
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
          No CPT types yet. Create your first one!
        </div>

        <div v-else class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Icon</th>
                <th>Name</th>
                <th>Slug</th>
                <th>URL Prefix</th>
                <th>Archive</th>
                <th>Status</th>
                <th>Actions</th>
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
                  <span v-if="type.has_archive" class="badge badge-success">Enabled</span>
                  <span v-else class="badge badge-secondary">Disabled</span>
                </td>
                <td>
                  <span v-if="type.active" class="badge badge-success">Active</span>
                  <span v-else class="badge badge-secondary">Inactive</span>
                </td>
                <td>
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" @click="cptStore.editType(type.id)" title="Edit">
                      <i class="material-icons">edit</i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" @click="managePosts(type)" title="Manage Posts">
                      <i class="material-icons">list</i>
                    </button>
                    <a v-if="type.view_url" :href="type.view_url" target="_blank" class="btn btn-sm btn-outline-info" title="View on Front">
                      <i class="material-icons">visibility</i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(type)" title="Delete">
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

const cptStore = useCptStore()
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
  if (confirm(`Delete ${type.name}? This will delete all posts.`)) {
    cptStore.deleteType(type.id)
  }
}
</script>
