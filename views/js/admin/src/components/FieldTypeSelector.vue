<script setup lang="ts">
import { computed } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { fieldTypeCategories, type FieldTypeDefinition } from '@/types'

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  close: []
  select: [type: string]
}>()

const store = useBuilderStore()
const { t } = useTranslations()

// Group field types by category
const groupedTypes = computed(() => {
  const groups: Record<string, FieldTypeDefinition[]> = {}
  
  for (const type of store.fieldTypes) {
    const category = type.category || 'basic'
    if (!groups[category]) {
      groups[category] = []
    }
    groups[category].push(type)
  }
  
  return groups
})

function selectType(type: string): void {
  emit('select', type)
}
</script>

<template>
  <div v-if="show" class="acfps-modal-overlay" @click.self="emit('close')">
    <div class="acfps-modal">
      <div class="modal-header">
        <h5 class="modal-title">{{ t('addField') }}</h5>
        <button type="button" class="close" @click="emit('close')">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div 
          v-for="(types, category) in groupedTypes" 
          :key="category"
          class="type-category"
        >
          <h6 class="category-title">{{ fieldTypeCategories[category as keyof typeof fieldTypeCategories] || category }}</h6>
          <div class="acfps-type-grid">
            <button
              v-for="fieldType in types"
              :key="fieldType.type"
              class="acfps-type-card"
              @click="selectType(fieldType.type)"
            >
              <span class="material-icons">{{ fieldType.icon }}</span>
              <span class="type-label">{{ fieldType.label }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.acfps-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.acfps-modal {
  background: var(--card-bg, #fff);
  border-radius: 4px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
  max-width: 600px;
  width: 100%;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid var(--border-color, #e9e9e9);
}

.modal-title {
  margin: 0;
  font-size: 1.1rem;
}

.modal-header .close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  color: var(--gray, #6c757d);
}

.modal-body {
  padding: 1rem;
  overflow-y: auto;
}

.type-category {
  margin-bottom: 1.5rem;
}

.type-category:last-child {
  margin-bottom: 0;
}

.category-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--gray, #6c757d);
  margin-bottom: 0.75rem;
}
</style>

