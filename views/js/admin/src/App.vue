<script setup lang="ts">
import { onMounted } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import GroupList from '@/components/GroupList.vue'
import GroupBuilder from '@/components/GroupBuilder.vue'

const store = useBuilderStore()
const { t } = useTranslations()

onMounted(() => {
  store.loadGroups()
})
</script>

<template>
  <div class="acfps-app">
    <!-- Error/Success alerts -->
    <div v-if="store.error" class="alert alert-danger alert-dismissible">
      {{ store.error }}
      <button type="button" class="close" @click="store.clearError">
        <span>&times;</span>
      </button>
    </div>
    
    <div v-if="store.successMessage" class="alert alert-success alert-dismissible">
      {{ store.successMessage }}
      <button type="button" class="close" @click="store.clearSuccess">
        <span>&times;</span>
      </button>
    </div>

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

.alert {
  margin: 1rem;
}
</style>

