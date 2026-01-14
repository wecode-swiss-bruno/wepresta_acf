<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useCptStore } from '@/stores/cptStore'
import { useTranslations } from '@/composables/useTranslations'
import GroupList from '@/components/builder/GroupList.vue'
import GroupBuilder from '@/components/builder/GroupBuilder.vue'
import CptTypeList from '@/components/cpt/CptTypeList.vue'
import CptTypeBuilder from '@/components/cpt/CptTypeBuilder.vue'
import CptTaxonomyManager from '@/components/cpt/CptTaxonomyManager.vue'
import CptTermManager from '@/components/cpt/CptTermManager.vue'
import CptPostList from '@/components/cpt/CptPostList.vue'
import CptPostBuilder from '@/components/cpt/CptPostBuilder.vue'

const store = useBuilderStore()
const cptStore = useCptStore()
const { t } = useTranslations()

// Navigation state
const activeTab = ref<'acf' | 'cpt'>('acf')
const cptView = ref<'types' | 'taxonomies' | 'posts' | 'post-build'>('types')

watch(() => cptStore.viewMode, (mode) => {
  if (mode === 'posts') {
    cptView.value = 'posts'
  } else if (mode === 'post-build' as any) {
    cptView.value = 'post-build'
  } else if (mode === 'list' && (cptView.value === 'posts' || cptView.value === 'post-build')) {
    cptView.value = 'types'
  }
})

function setupExternalAlerts(): void {
  const alertsContainer = document.getElementById('acf-alerts-container')
  if (!alertsContainer) return

  watch(() => store.error, () => {
    renderAlerts(alertsContainer)
  })

  watch(() => store.successMessage, () => {
    renderAlerts(alertsContainer)
  })
}

function renderAlerts(container: HTMLElement): void {
  container.innerHTML = ''

  if (store.error) {
    const errorAlert = document.createElement('div')
    errorAlert.className = 'alert alert-danger alert-dismissible fade show'
    errorAlert.innerHTML = `
      ${store.error}
      <button type="button" class="close" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    `
    errorAlert.querySelector('.close')?.addEventListener('click', () => {
      store.clearError()
      errorAlert.remove()
    })
    container.appendChild(errorAlert)
  }

  if (store.successMessage) {
    const successAlert = document.createElement('div')
    successAlert.className = 'alert alert-success alert-dismissible fade show'
    successAlert.innerHTML = `
      ${store.successMessage}
      <button type="button" class="close" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    `
    successAlert.querySelector('.close')?.addEventListener('click', () => {
      store.clearSuccess()
      successAlert.remove()
    })
    container.appendChild(successAlert)

    setTimeout(() => {
      store.clearSuccess()
      successAlert.remove()
    }, 5000)
  }
}

function setupToolbarButtons(): void {
  const btnAddGroup = document.querySelector('[id*="add-group"]') as HTMLElement
  const btnBack = document.querySelector('[id*="go-back"]') as HTMLElement
  const btnSave = document.querySelector('[id*="save-group"]') as HTMLElement

  const listBtns = document.querySelectorAll('.acf-toolbar-list-btn')
  const editBtns = document.querySelectorAll('.acf-toolbar-edit-btn')

  btnAddGroup?.addEventListener('click', (e) => {
    e.preventDefault()
    if (activeTab.value === 'acf') {
      store.createNewGroup()
    }
  })
  btnBack?.addEventListener('click', (e) => {
    e.preventDefault()
    store.goToList()
  })
  btnSave?.addEventListener('click', (e) => {
    e.preventDefault()
    store.saveGroup()
  })

  watch(() => store.viewMode, (mode) => {
    listBtns.forEach(btn => {
      btn.classList.toggle('d-none', mode !== 'list')
    })
    editBtns.forEach(btn => {
      btn.classList.toggle('d-none', mode !== 'edit')
    })
  }, { immediate: true })
}

onMounted(() => {
  store.loadGroups()
  setupToolbarButtons()
  setupExternalAlerts()
})
</script>

<template>
  <div class="acfps-app">
    <!-- Tab Navigation -->
    <div class="nav nav-tabs mb-4" role="tablist">
      <button
        class="nav-link"
        :class="{ active: activeTab === 'acf' }"
        @click="activeTab = 'acf'"
        type="button"
      >
        <i class="material-icons">view_list</i>
        ACF
      </button>
      <button
        class="nav-link"
        :class="{ active: activeTab === 'cpt' }"
        @click="activeTab = 'cpt'"
        type="button"
      >
        <i class="material-icons">article</i>
        CPT
      </button>
    </div>

    <!-- Loading state -->
    <div v-if="store.loading && activeTab === 'acf'" class="acfps-loading">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">{{ t('loading') }}</span>
      </div>
    </div>

    <!-- ACF Groups Tab -->
    <template v-if="activeTab === 'acf' && !store.loading">
      <GroupList v-if="store.viewMode === 'list'" />
      <GroupBuilder v-else-if="store.viewMode === 'edit'" />
    </template>

    <!-- CPT Tab -->
    <template v-if="activeTab === 'cpt'">
      <div class="cpt-content">
        <!-- CPT Sub-Navigation -->
        <div class="btn-group mb-3" role="group">
          <button
            type="button"
            class="btn btn-sm"
            :class="cptView === 'types' ? 'btn-primary' : 'btn-outline-secondary'"
            @click="cptView = 'types'"
          >
            <i class="material-icons">article</i>
            Post Types
          </button>
          <button
            type="button"
            class="btn btn-sm"
            :class="cptView === 'taxonomies' ? 'btn-primary' : 'btn-outline-secondary'"
            @click="cptView = 'taxonomies'"
          >
            <i class="material-icons">category</i>
            Taxonomies
          </button>
        </div>

        <!-- CPT Views -->
        <template v-if="cptView === 'types'">
          <CptTypeList v-if="cptStore.viewMode === 'list'" />
          <CptTypeBuilder 
            v-else-if="cptStore.viewMode === 'edit'" 
            :type-id="cptStore.currentType?.id"
            @cancel="cptStore.goToList()" 
            @saved="cptStore.goToList()" 
          />
        </template>
        <template v-else-if="cptView === 'taxonomies'">
          <CptTaxonomyManager v-if="cptStore.taxonomyViewMode === 'list'" />
          <CptTermManager v-else-if="cptStore.taxonomyViewMode === 'terms'" />
        </template>
        <template v-else-if="cptView === 'posts'">
          <CptPostList />
        </template>
        <template v-else-if="cptView === 'post-build'">
           <CptPostBuilder 
            :post-id="cptStore.currentPost?.id"
            @cancel="cptStore.viewMode = 'posts'"
            @saved="cptStore.viewMode = 'posts'" 
           />
        </template>
      </div>
    </template>
  </div>
</template>

<style scoped>
.acfps-app {
  min-height: 400px;
}

.acfps-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 300px;
}

.nav-tabs {
  border-bottom: 2px solid #e9ecef;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  background: transparent;
  color: #6c757d;
  cursor: pointer;
  transition: all 0.2s;
}

.nav-link:hover {
  color: #495057;
  background-color: #f8f9fa;
}

.nav-link.active {
  color: #007bff;
  border-bottom: 2px solid #007bff;
  font-weight: 600;
}

.nav-link .material-icons {
  font-size: 20px;
}

.cpt-content {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
