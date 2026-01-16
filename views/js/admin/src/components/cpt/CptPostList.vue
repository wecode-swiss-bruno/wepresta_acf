<template>
  <div class="cpt-post-list">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button class="btn btn-outline-secondary btn-sm mr-2" @click="cptStore.goToList()">
            <i class="material-icons">arrow_back</i>
          </button>
          <h3 class="card-header-title mb-0">
            <i class="material-icons mr-1">{{ cptStore.currentType?.icon || 'article' }}</i>
            {{ cptStore.currentType?.name }} {{ t('posts') }}
          </h3>
        </div>
        <button class="btn btn-primary" @click="addPost">
          <i class="material-icons">add</i>
          {{ t('addNew') }}
        </button>
      </div>
      <div class="card-body">
        <!-- Loading state -->
        <div v-if="cptStore.loadingPosts" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
        </div>

        <!-- Error state -->
        <div v-else-if="cptStore.error" class="alert alert-danger">
          {{ cptStore.error }}
        </div>

        <!-- Empty state -->
        <div v-else-if="cptStore.posts.length === 0" class="text-center py-5">
          <i class="material-icons text-muted mb-3" style="font-size: 64px;">{{  cptStore.currentType?.icon || 'article' }}</i>
          <p class="text-muted mb-3">{{ t('noPostsFound', 'No posts found for this type.') }}</p>
          <div class="d-flex justify-content-center">
            <button class="btn btn-outline-primary" @click="addPost">
              <i class="material-icons">add</i>
              {{ t('addNew') }} {{ cptStore.currentType?.name }}
            </button>
          </div>
        </div>

        <template v-else>
          <!-- Bulk Actions Bar -->
          <div v-if="hasSelectedPosts" class="alert alert-light border d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
              <i class="material-icons text-primary mr-2">check_circle</i>
              <span class="font-weight-semibold">
                {{ selectedCount }} {{ selectedCount === 1 ? t('postSelected') : t('postsSelected') }}
              </span>
            </div>
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="material-icons mr-1">settings</i>
                {{ t('actions') }}
              </button>
              <div class="dropdown-menu dropdown-menu-right">
                <button class="dropdown-item" @click="bulkChangeStatus('published')">
                  <i class="material-icons text-success mr-2">publish</i>
                  {{ t('publish') }}
                </button>
                <button class="dropdown-item" @click="bulkChangeStatus('draft')">
                  <i class="material-icons text-warning mr-2">drafts</i>
                  {{ t('setAsDraft') }}
                </button>
                <div class="dropdown-divider"></div>
                <button class="dropdown-item text-danger" @click="confirmBulkDelete">
                  <i class="material-icons mr-2">delete</i>
                  {{ t('delete') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Native PrestaShop Grid -->
          <div class="grid js-grid" id="cpt_post_grid" data-grid-id="cpt_post">
            <div class="table-responsive">
              <table class="grid-table js-grid-table table" id="cpt_post_grid_table">
                <thead class="thead-default">
                  <tr class="column-headers">
                    <th scope="col" data-type="selector" data-column-id="select" class="text-center">
                      <input
                        type="checkbox"
                        class="form-check-input header-checkbox"
                        :checked="isAllSelected"
                        :indeterminate.prop="isIndeterminate"
                        @change="toggleSelectAll"
                        id="select-all-posts"
                      />
                      <label class="sr-only" for="select-all-posts">
                        {{ t('selectAll') }}
                      </label>
                    </th>
                    <th scope="col" data-type="identifier" data-column-id="id">
                      <span role="columnheader">ID</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="title">
                      <span role="columnheader">{{ t('title') }}</span>
                    </th>
                    <th scope="col" data-type="data" data-column-id="slug">
                      <span role="columnheader">{{ t('slug') }}</span>
                    </th>
                    <th scope="col" data-type="boolean" data-column-id="status" class="text-center">
                      <span role="columnheader">{{ t('status') }}</span>
                    </th>
                    <th scope="col" data-type="action" data-column-id="actions">
                      <div class="grid-actions-header-text">{{ t('actions') }}</div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="post in cptStore.posts" :key="post.id">
                    <td class="selector-type column-select text-center">
                      <div class="form-check mb-0">
                        <input
                          type="checkbox"
                          class="form-check-input"
                          :checked="selectedPosts.has(post.id)"
                          @change="togglePostSelection(post.id)"
                          :id="'select-post-' + post.id"
                        />
                        <label class="form-check-label sr-only" :for="'select-post-' + post.id">
                          {{ t('selectPost') }} {{ post.title }}
                        </label>
                      </div>
                    </td>
                    <td data-identifier class="identifier-type column-id">
                      {{ post.id }}
                    </td>
                    <td class="data-type column-title text-left">
                      <a
                        href="#"
                        class="text-primary font-weight-bold"
                        @click.prevent="editPost(post)"
                      >
                        {{ post.title }}
                      </a>
                    </td>
                    <td class="data-type column-slug text-left">
                      <code class="text-muted">{{ post.slug }}</code>
                    </td>
                    <td class="boolean-type column-status text-center">
                      <span v-if="post.status === 'published'" class="badge badge-success">
                        <i class="material-icons mr-1" style="font-size: 12px;">check</i>
                        {{ t('published') }}
                      </span>
                      <span v-else-if="post.status === 'draft'" class="badge badge-warning">
                        <i class="material-icons mr-1" style="font-size: 12px;">edit</i>
                        {{ t('draft') }}
                      </span>
                      <span v-else class="badge badge-secondary">{{ post.status }}</span>
                    </td>
                    <td class="action-type column-actions">
                      <div class="btn-group-action text-right">
                        <div class="btn-group d-flex justify-content-end">
                          <!-- Edit -->
                          <a
                            href="#"
                            class="btn tooltip-link dropdown-item inline-dropdown-item"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('edit')"
                            @click.prevent="editPost(post)"
                          >
                            <i class="material-icons">edit</i>
                          </a>
                          <!-- View on Front -->
                          <a
                            v-if="post.view_url"
                            :href="post.view_url"
                            target="_blank"
                            class="btn tooltip-link dropdown-item inline-dropdown-item"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('viewOnFront')"
                          >
                            <i class="material-icons">visibility</i>
                          </a>
                          <!-- Delete -->
                          <a
                            href="#"
                            class="btn tooltip-link dropdown-item inline-dropdown-item text-danger"
                            data-toggle="pstooltip"
                            data-placement="top"
                            :data-original-title="t('delete')"
                            @click.prevent="confirmDelete(post)"
                          >
                            <i class="material-icons">delete</i>
                          </a>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import { useTranslations } from '../../composables/useTranslations'
import { useApi } from '../../composables/useApi'

const cptStore = useCptStore()
const { t } = useTranslations()
const api = useApi()

// Selection state
const selectedPosts = ref<Set<number>>(new Set())

// Computed properties for selection
const hasSelectedPosts = computed(() => selectedPosts.value.size > 0)
const selectedCount = computed(() => selectedPosts.value.size)
const isAllSelected = computed(() => {
  return cptStore.posts.length > 0 && selectedPosts.value.size === cptStore.posts.length
})
const isIndeterminate = computed(() => {
  return selectedPosts.value.size > 0 && selectedPosts.value.size < cptStore.posts.length
})

onMounted(() => {
  if (cptStore.currentType) {
    cptStore.fetchPostsByType(cptStore.currentType.slug)
  }
})

// Selection methods
function togglePostSelection(postId: number): void {
  if (selectedPosts.value.has(postId)) {
    selectedPosts.value.delete(postId)
  } else {
    selectedPosts.value.add(postId)
  }
}

function toggleSelectAll(): void {
  if (isAllSelected.value) {
    selectedPosts.value.clear()
  } else {
    selectedPosts.value.clear()
    cptStore.posts.forEach(post => {
      if (post.id) {
        selectedPosts.value.add(post.id)
      }
    })
  }
}

function clearSelection(): void {
  selectedPosts.value.clear()
}

// Bulk action methods
async function bulkChangeStatus(status: 'published' | 'draft'): Promise<void> {
  const postIds = Array.from(selectedPosts.value)
  if (postIds.length === 0 || !cptStore.currentType) return

  try {
    const response = await api.fetchJson(`/cpt/posts/${cptStore.currentType.slug}/bulk-status`, {
      method: 'POST',
      body: JSON.stringify({ postIds, status })
    })

    if (response.success) {
      await cptStore.fetchPostsByType(cptStore.currentType.slug)
      clearSelection()
      alert(t('bulkActionSuccess'))
    } else {
      alert(t('bulkActionError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('bulkActionError') + ': ' + (e as Error).message)
  }
}

function confirmBulkDelete(): void {
  const postIds = Array.from(selectedPosts.value)
  if (postIds.length === 0) return

  const message = postIds.length === 1
    ? t('confirmDeletePost')
    : t('confirmDeletePosts', 'Delete {count} posts? This cannot be undone.', { count: postIds.length.toString() })

  if (confirm(message)) {
    bulkDelete()
  }
}

async function bulkDelete(): Promise<void> {
  const postIds = Array.from(selectedPosts.value)
  if (postIds.length === 0 || !cptStore.currentType) return

  try {
    const response = await api.fetchJson(`/cpt/posts/${cptStore.currentType.slug}/bulk-delete`, {
      method: 'POST',
      body: JSON.stringify({ postIds })
    })

    if (response.success) {
      await cptStore.fetchPostsByType(cptStore.currentType.slug)
      clearSelection()
      alert(t('bulkDeleteSuccess'))
    } else {
      alert(t('bulkDeleteError') + ': ' + (response.error || 'Unknown error'))
    }
  } catch (e) {
    alert(t('bulkDeleteError') + ': ' + (e as Error).message)
  }
}

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

<style scoped>
/* Native PrestaShop Grid styles */
.grid-table th {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.grid-table td {
  vertical-align: middle;
}

.grid-table .column-select {
  width: 60px;
  text-align: center;
}

.grid-table .column-id {
  width: 60px;
}

.grid-table .column-status {
  width: 120px;
}

.grid-table .column-actions {
  width: 120px;
}

/* Action buttons */
.btn-group-action .btn,
.btn-group-action .dropdown-item.inline-dropdown-item {
  padding: 0.25rem 0.5rem;
  background: transparent;
  border: none;
}

.btn-group-action .btn:hover,
.btn-group-action .dropdown-item.inline-dropdown-item:hover {
  background: #f8f9fa;
}

.btn-group-action .material-icons {
  font-size: 20px;
}

.btn-group-action .text-danger .material-icons {
  color: #dc3545;
}

/* Dropdown styles */
.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
}

.dropdown-item .material-icons {
  font-size: 18px;
}

/* Checkboxes */
.grid-table .column-select .form-check {
  margin-bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.grid-table thead .header-checkbox {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(1.1);
  margin: 0;
}

.grid-table thead .column-select {
  position: relative;
}

/* Status badges */
.badge .material-icons {
  vertical-align: middle;
}

/* Empty state */
.text-center.py-5 .material-icons {
  display: block;
  margin: 0 auto 1rem;
}

/* Header title */
.card-header-title .material-icons {
  font-size: 20px;
  vertical-align: middle;
}
</style>
