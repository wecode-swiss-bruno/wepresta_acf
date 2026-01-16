<template>
  <div class="cpt-post-list">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button class="btn btn-outline-secondary btn-sm mr-2" @click="cptStore.goToList()">
            <i class="material-icons">arrow_back</i>
          </button>
          <h3 class="card-header-title mb-0">
            {{ cptStore.currentType?.name }} {{ t('posts') }}
          </h3>
        </div>
        <button class="btn btn-primary" @click="addPost">
          <i class="material-icons">add</i>
          {{ t('addNew') }} {{ cptStore.currentType?.name }}
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
          {{ t('noPostsFound', 'No posts found for this type.') }}
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th style="width: 50px">ID</th>
                <th>{{ t('title') }}</th>
                <th>{{ t('slug') }}</th>
                <th>{{ t('status') }}</th>
                <th class="text-right">{{ t('actions') }}</th>
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
                  <span v-if="post.status === 'published'" class="badge badge-success">{{ t('published') }}</span>
                  <span v-else-if="post.status === 'draft'" class="badge badge-warning">{{ t('draft') }}</span>
                  <span v-else class="badge badge-secondary">{{ post.status }}</span>
                </td>
                <td class="text-right">
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" :title="t('edit')" @click="editPost(post)">
                      <i class="material-icons">edit</i>
                    </button>
                    <a v-if="post.view_url" :href="post.view_url" target="_blank" class="btn btn-sm btn-outline-info" :title="t('viewOnFront')">
                      <i class="material-icons">visibility</i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(post)" :title="t('delete')">
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
import { onMounted } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useTranslations } from '../../composables/useTranslations'

const cptStore = useCptStore()
const { t } = useTranslations()

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
  if (confirm(t('confirmDeletePost', 'Delete post "{title}"? This cannot be undone.', { title: post.title }))) {
    cptStore.deletePost(post.id, cptStore.currentType!.slug)
  }
}
</script>
