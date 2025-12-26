<script setup lang="ts">
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'

const store = useBuilderStore()
const { t } = useTranslations()

function confirmDelete(id: number): void {
  if (confirm(t('confirmDeleteGroup'))) {
    store.deleteGroup(id)
  }
}
</script>

<template>
  <div class="acfps-group-list">
    <!-- Header with add button -->
    <div class="acfps-list-header">
      <button class="btn btn-primary" @click="store.createNewGroup">
        <span class="material-icons">add</span>
        {{ t('addGroup') }}
      </button>
    </div>

    <!-- Empty state -->
    <div v-if="store.groups.length === 0" class="acfps-empty-state">
      <span class="material-icons">widgets</span>
      <p>{{ t('noGroups') }}</p>
      <button class="btn btn-outline-primary" @click="store.createNewGroup">
        <span class="material-icons">add</span>
        {{ t('addGroup') }}
      </button>
    </div>

    <!-- Groups table -->
    <table v-else class="acfps-group-table">
      <thead>
        <tr>
          <th>{{ t('groupTitle') }}</th>
          <th>{{ t('groupSlug') }}</th>
          <th>{{ t('fields') }}</th>
          <th>{{ t('placementTab') }}</th>
          <th>{{ t('active') }}</th>
          <th>{{ t('options') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="group in store.groups" :key="group.id">
          <td>
            <a 
              href="#" 
              class="group-title-link"
              @click.prevent="store.loadGroup(group.id!)"
            >
              {{ group.title || t('untitled') }}
            </a>
          </td>
          <td>
            <code>{{ group.slug }}</code>
          </td>
          <td>{{ group.fieldCount || 0 }}</td>
          <td>
            <span class="badge badge-secondary">{{ group.placementTab }}</span>
          </td>
          <td>
            <span 
              class="acfps-status-badge"
              :class="group.active ? 'active' : 'inactive'"
            >
              {{ group.active ? t('active') : 'Inactive' }}
            </span>
          </td>
          <td>
            <div class="btn-group btn-group-sm">
              <button 
                class="btn btn-outline-secondary"
                :title="t('edit')"
                @click="store.loadGroup(group.id!)"
              >
                <span class="material-icons">edit</span>
              </button>
              <button 
                class="btn btn-outline-secondary"
                :title="t('duplicate')"
                @click="store.duplicateGroup(group.id!)"
              >
                <span class="material-icons">content_copy</span>
              </button>
              <button 
                class="btn btn-outline-danger"
                :title="t('delete')"
                @click="confirmDelete(group.id!)"
              >
                <span class="material-icons">delete</span>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.acfps-group-list {
  padding: 0;
}

.acfps-list-header {
  padding: 1rem;
  border-bottom: 1px solid var(--border-color, #e9e9e9);
  display: flex;
  justify-content: flex-end;
}

.acfps-list-header .btn .material-icons {
  font-size: 18px;
  vertical-align: middle;
  margin-right: 0.25rem;
}

.group-title-link {
  font-weight: 500;
  color: var(--primary, #25b9d7);
  text-decoration: none;
}

.group-title-link:hover {
  text-decoration: underline;
}

.btn-group .material-icons {
  font-size: 16px;
}
</style>

