<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import GroupList from '@/components/GroupList.vue'
import GroupBuilder from '@/components/GroupBuilder.vue'

const store = useBuilderStore()
const { t } = useTranslations()

// Render alerts to external container (native PrestaShop styling)
function setupExternalAlerts(): void {
  const alertsContainer = document.getElementById('acf-alerts-container')
  if (!alertsContainer) return

  // Watch for error changes
  watch(() => store.error, (error) => {
    renderAlerts(alertsContainer)
  })

  // Watch for success changes
  watch(() => store.successMessage, (msg) => {
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

    // Auto-dismiss success after 5s
    setTimeout(() => {
      store.clearSuccess()
      successAlert.remove()
    }, 5000)
  }
}

// Connect to Twig toolbar buttons (rendered by PS Toolbar component)
// PS Toolbar generates IDs like: page-header-desc-configuration-{key}
function setupToolbarButtons(): void {
  // Find buttons by partial ID match (PS generates IDs like page-header-desc-configuration-add-group)
  const btnAddGroup = document.querySelector('[id*="add-group"]') as HTMLElement
  const btnBack = document.querySelector('[id*="go-back"]') as HTMLElement
  const btnSave = document.querySelector('[id*="save-group"]') as HTMLElement

  // Also collect buttons by CSS class for visibility toggling
  const listBtns = document.querySelectorAll('.acf-toolbar-list-btn')
  const editBtns = document.querySelectorAll('.acf-toolbar-edit-btn')

  console.debug('[ACF] Toolbar buttons found:', {
    addGroup: !!btnAddGroup,
    back: !!btnBack,
    save: !!btnSave,
    listBtns: listBtns.length,
    editBtns: editBtns.length
  })

  // Button click handlers
  btnAddGroup?.addEventListener('click', (e) => {
    e.preventDefault()
    store.createNewGroup()
  })
  btnBack?.addEventListener('click', (e) => {
    e.preventDefault()
    store.goToList()
  })
  btnSave?.addEventListener('click', (e) => {
    e.preventDefault()
    store.saveGroup()
  })

  // Toggle toolbar visibility based on view mode
  watch(() => store.viewMode, (mode) => {
    console.debug('[ACF] View mode changed to:', mode)

    // Show/hide list view buttons (Add Group)
    listBtns.forEach(btn => {
      btn.classList.toggle('d-none', mode !== 'list')
    })
    // Show/hide edit view buttons (Back, Save)
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
    <!-- Loading state -->
    <div v-if="store.loading" class="acfps-loading">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">{{ t('loading') }}</span>
      </div>
    </div>

    <!-- Main content -->
    <template v-else>
      <!-- Group list view -->
      <GroupList v-if="store.viewMode === 'list'" />

      <!-- Group edit/create view -->
      <GroupBuilder v-else-if="store.viewMode === 'edit'" />
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
</style>

