<template>
  <div class="card">
    <div class="card-header">
      <h3 class="card-header-title">{{ typeName }} - Posts</h3>
      <div class="card-header-toolbar">
         <router-link to="/new" class="btn btn-primary">
            <i class="material-icons">add</i>
            New Post
         </router-link>
      </div>
    </div>
    <div class="card-body">
      <div v-if="loading" class="text-center my-5">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
      
      <div v-else-if="error" class="alert alert-danger">
        {{ error }}
      </div>

      <div v-else>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="post in posts" :key="post.id">
                <td>{{ post.id }}</td>
                <td>{{ post.title }}</td>
                <td><code>{{ post.slug }}</code></td>
                <td>
                  <span :class="['badge', post.status === 'published' ? 'badge-success' : 'badge-secondary']">
                    {{ post.status }}
                  </span>
                </td>
                <td>
                  <router-link :to="`/edit/${post.id}`" class="btn btn-sm btn-outline-secondary mr-1">
                    <i class="material-icons">edit</i>
                  </router-link>
                  <button @click="deletePost(post.id)" class="btn btn-sm btn-outline-danger" :disabled="deleting === post.id">
                    <i class="material-icons">delete</i>
                  </button>
                </td>
              </tr>
              <tr v-if="posts.length === 0">
                 <td colspan="5" class="text-center text-muted py-3">No posts found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, onMounted } from 'vue'
import { useApi } from '../../composables/useApi'

const config = inject('cptConfig') as any
const api = useApi()

const posts = ref<any[]>([])
const loading = ref(true)
const error = ref('')
const deleting = ref<number | null>(null)
const typeName = config?.typeName || 'CPT'

async function fetchPosts() {
  loading.value = true
  try {
    const data = await api.getCpts(config.typeSlug)
    posts.value = data.posts || []
  } catch (err: any) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

async function deletePost(id: number) {
  if (!confirm('Are you sure you want to delete this post?')) return
  
  deleting.value = id
  try {
    await api.deleteCpt(id)
    await fetchPosts()
  } catch (err: any) {
    alert('Failed to delete: ' + err.message)
  } finally {
    deleting.value = null
  }
}

onMounted(() => {
  if (config?.typeSlug) {
    fetchPosts()
  } else {
    error.value = 'Config Error: Missing type slug.'
    loading.value = false
  }
})
</script>
