<template>
  <div class="cpt-post-list">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button class="btn btn-outline-secondary btn-sm mr-2" @click="cptStore.goToList()">
            <i class="material-icons">arrow_back</i>
          </button>
          <h3 class="card-header-title mb-0">
            {{ cptStore.currentType?.name }} Posts
          </h3>
        </div>
        <button class="btn btn-primary" @click="addPost">
          <i class="material-icons">add</i>
          Add New {{ cptStore.currentType?.name }}
        </button>
      </div>
      <div class="card-body">
        <div v-if="cptStore.loadingPosts" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
        </div>

        <div v-else-if="cptStore.error" class="alert alert-danger">
          {{ cptStore.error }}
        </div>

        <div v-else-if="cptStore.posts.length === 0" class="alert alert-info text-center">
          No posts found for {{ cptStore.currentType?.name }}.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th style="width: 50px">ID</th>
                <th>Title</th>
                <th>Slug</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="post in cptStore.posts" :key="post.id">
                <td>{{ post.id }}</td>
                <td>
                  <strong>{{ post.title }}</strong>
                </td>
                <td>
                  <code>{{ post.slug }}</code>
                </td>
                <td>
                  <span v-if="post.status === 'published'" class="badge badge-success">Published</span>
                  <span v-else-if="post.status === 'draft'" class="badge badge-warning">Draft</span>
                  <span v-else class="badge badge-secondary">{{ post.status }}</span>
                </td>
                <td class="text-right">
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" title="Edit" @click="editPost(post)">
                      <i class="material-icons">edit</i>
                    </button>
                    <a v-if="post.view_url" :href="post.view_url" target="_blank" class="btn btn-sm btn-outline-info" title="View on Front">
                      <i class="material-icons">visibility</i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(post)" title="Delete">
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
import { computed, onMounted } from 'vue'
import { useCptStore } from '../../stores/cptStore'

const cptStore = useCptStore()

onMounted(() => {
  if (cptStore.currentType) {
    cptStore.fetchPostsByType(cptStore.currentType.slug)
  }
})

function addPost() {
  cptStore.currentPost = null
  // @ts-ignore
  cptStore.viewMode = 'post-build'
}

function editPost(post: any) {
  cptStore.currentPost = post
  // @ts-ignore
  cptStore.viewMode = 'post-build'
}

function confirmDelete(post: any) {
  if (confirm(`Delete post "${post.title}"? This cannot be undone.`)) {
    cptStore.deletePost(post.id, cptStore.currentType!.slug)
  }
}
</script>
