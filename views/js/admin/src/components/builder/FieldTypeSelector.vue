<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { fieldTypeCategories, type FieldTypeDefinition } from '@/types'

const props = defineProps<{
  show: boolean
  disabledFieldTypes?: string[]
}>()

const emit = defineEmits<{
  close: []
  select: [type: string]
}>()

const store = useBuilderStore()
const { t } = useTranslations()

// Search and navigation state
const searchQuery = ref('')
const selectedIndex = ref(0)
const focusedCategory = ref<string | null>(null)

// Recently used types (stored in localStorage, max 4)
const recentlyUsed = ref<string[]>([])

// Category order (custom at the end)
const categoryOrder = ['basic', 'choice', 'content', 'media', 'relational', 'layout', 'custom']

// Category colors and icons mapping
const categoryStyles = {
  basic: { color: '#007bff', icon: 'text_fields', bgColor: '#e7f3ff' },
  choice: { color: '#28a745', icon: 'list', bgColor: '#e8f5e8' },
  content: { color: '#fd7e14', icon: 'article', bgColor: '#fef3e7' },
  media: { color: '#6f42c1', icon: 'perm_media', bgColor: '#f3e8ff' },
  relational: { color: '#e83e8c', icon: 'link', bgColor: '#fce4ec' },
  layout: { color: '#20c997', icon: 'view_module', bgColor: '#e8f8f5' },
  custom: { color: '#6c757d', icon: 'extension', bgColor: '#f8f9fa' }
}

// Load recently used types from localStorage
function loadRecentlyUsed(): void {
  const stored = localStorage.getItem('acf_recently_used_types')
  if (stored) {
    try {
      recentlyUsed.value = JSON.parse(stored).slice(0, 4) // Max 4 items
    } catch (e) {
      recentlyUsed.value = []
    }
  }
}

// Save recently used type
function saveRecentlyUsed(type: string): void {
  const current = recentlyUsed.value.filter(t => t !== type)
  current.unshift(type)
  recentlyUsed.value = current.slice(0, 4) // Keep only 4 most recent
  localStorage.setItem('acf_recently_used_types', JSON.stringify(recentlyUsed.value))
}

// Get recently used field types
const recentlyUsedTypes = computed(() => {
  return recentlyUsed.value
    .map(type => store.fieldTypes.find(ft => ft.type === type))
    .filter(Boolean) as FieldTypeDefinition[]
})

// Filtered and grouped field types
const groupedTypes = computed(() => {
  const query = searchQuery.value.toLowerCase()
  const groups: Record<string, FieldTypeDefinition[]> = {}
  const disabledTypes = props.disabledFieldTypes || []

  // Filter types based on search query and disabled list
  const filteredTypes = store.fieldTypes.filter(type =>
    !disabledTypes.includes(type.type) && (
      !query ||
      type.label.toLowerCase().includes(query) ||
      type.type.toLowerCase().includes(query) ||
      fieldTypeCategories[type.category as keyof typeof fieldTypeCategories]?.toLowerCase().includes(query)
    )
  )

  // Group filtered types by category
  for (const type of filteredTypes) {
    const category = type.category || 'basic'
    if (!groups[category]) {
      groups[category] = []
    }
    groups[category].push(type)
  }

  // Sort by category order and return only non-empty groups
  const sorted: Record<string, FieldTypeDefinition[]> = {}
  for (const cat of categoryOrder) {
    if (groups[cat] && groups[cat].length > 0) {
      sorted[cat] = groups[cat]
    }
  }

  return sorted
})

// All visible types for keyboard navigation
const allVisibleTypes = computed(() => {
  const types: { type: FieldTypeDefinition; category: string }[] = []

  // Add recently used types first
  if (!searchQuery.value && recentlyUsedTypes.value.length > 0) {
    types.push(...recentlyUsedTypes.value.map(type => ({ type, category: 'recent' })))
  }

  // Add categorized types
  for (const [category, categoryTypes] of Object.entries(groupedTypes.value)) {
    types.push(...categoryTypes.map(type => ({ type, category })))
  }

  return types
})

// Keyboard navigation
function handleKeydown(event: KeyboardEvent): void {
  if (!props.show) return

  const types = allVisibleTypes.value
  if (types.length === 0) return

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      selectedIndex.value = Math.min(selectedIndex.value + 1, types.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      selectedIndex.value = Math.max(selectedIndex.value - 1, 0)
      break
    case 'Enter':
      event.preventDefault()
      if (types[selectedIndex.value]) {
        selectType(types[selectedIndex.value].type.type)
      }
      break
    case 'Escape':
      event.preventDefault()
      emit('close')
      break
  }

  // Scroll selected item into view
  nextTick(() => {
    const selectedElement = document.querySelector('.acfps-type-card--selected')
    if (selectedElement) {
      selectedElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  })
}

function selectType(type: string): void {
  saveRecentlyUsed(type)
  emit('select', type)
}

function clearSearch(): void {
  searchQuery.value = ''
  selectedIndex.value = 0
}

function getGlobalIndex(category: string, typeIndex: number): number {
  let offset = recentlyUsedTypes.value.length

  for (const cat of categoryOrder) {
    if (cat === category) break
    if (groupedTypes.value[cat]) {
      offset += groupedTypes.value[cat].length
    }
  }

  return offset + typeIndex
}

function getFieldTypeDescription(type: FieldTypeDefinition): string {
  const descriptions: Record<string, string> = {
    text: 'Single line text input for short content',
    textarea: 'Multi-line text area for longer content',
    number: 'Numeric input with validation options',
    email: 'Email address input with validation',
    url: 'Website URL input with validation',
    select: 'Dropdown selection from predefined options',
    checkbox: 'Multiple choice checkboxes',
    radio: 'Single choice radio buttons',
    'true_false': 'Boolean toggle switch',
    star_rating: 'Star rating input (1-5 stars)',
    date: 'Date picker calendar',
    time: 'Time picker',
    datetime: 'Combined date and time picker',
    color: 'Color picker with palette',
    wysiwyg: 'Rich text editor with formatting',
    file: 'Single file upload',
    image: 'Image upload with preview',
    video: 'Video file upload',
    gallery: 'Multiple image gallery',
    files: 'Multiple file upload',
    relation: 'Link to other content types',
    list: 'Repeatable list of subfields',
    repeater: 'Advanced repeater with flexible layouts'
  }
  return descriptions[type.type] || `Configure ${type.label} field settings`
}

// Lifecycle
onMounted(() => {
  loadRecentlyUsed()
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})

// Reset state when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    searchQuery.value = ''
    selectedIndex.value = 0
    focusedCategory.value = null
  }
})
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
        <!-- Search Bar -->
        <div class="search-container">
          <div class="input-group">
            <span class="input-group-text">
              <span class="material-icons">search</span>
            </span>
            <input
              v-model="searchQuery"
              type="text"
              class="form-control"
              :placeholder="t('searchFieldTypes') || 'Search field types...'"
              @input="selectedIndex = 0"
            >
            <button
              v-if="searchQuery"
              class="btn btn-outline-secondary"
              type="button"
              @click="clearSearch"
            >
              <span class="material-icons">clear</span>
            </button>
          </div>
        </div>

        <!-- Recently Used Section -->
        <div v-if="!searchQuery && recentlyUsedTypes.length > 0" class="recently-used-section">
          <div class="category-header">
            <span class="material-icons category-icon" style="color: #6c757d;">history</span>
            <h6 class="category-title">{{ t('recentlyUsed') || 'Recently Used' }}</h6>
          </div>
          <div class="acfps-type-grid">
            <button
              v-for="(fieldType, index) in recentlyUsedTypes"
              :key="`recent-${fieldType.type}`"
              class="acfps-type-card acfps-type-card--recent"
              :class="{ 'acfps-type-card--selected': selectedIndex === index }"
              @click="selectType(fieldType.type)"
            >
              <span class="material-icons card-icon">{{ fieldType.icon }}</span>
              <span class="type-label">{{ fieldType.label }}</span>
              <div class="card-tooltip">{{ getFieldTypeDescription(fieldType) }}</div>
            </button>
          </div>
        </div>

        <!-- Categories -->
        <div
          v-for="(types, category) in groupedTypes"
          :key="category"
          class="type-category"
          :class="{ 'type-category--custom': category === 'custom' }"
        >
          <div class="category-header">
            <div class="category-icon-wrapper" :style="{ backgroundColor: categoryStyles[category as keyof typeof categoryStyles]?.bgColor }">
              <span
                class="material-icons category-icon"
                :style="{ color: categoryStyles[category as keyof typeof categoryStyles]?.color }"
              >
                {{ categoryStyles[category as keyof typeof categoryStyles]?.icon }}
              </span>
            </div>
            <h6 class="category-title">
              {{ fieldTypeCategories[category as keyof typeof fieldTypeCategories] || category }}
            </h6>
            <span class="category-count">{{ types.length }}</span>
          </div>
          <div class="acfps-type-grid">
            <button
              v-for="(fieldType, typeIndex) in types"
              :key="fieldType.type"
              class="acfps-type-card"
              :class="{
                'acfps-type-card--selected': selectedIndex === (recentlyUsedTypes.length + getGlobalIndex(category, typeIndex))
              }"
              @click="selectType(fieldType.type)"
              @mouseenter="focusedCategory = category"
              @mouseleave="focusedCategory = null"
            >
              <span class="material-icons card-icon">{{ fieldType.icon }}</span>
              <span class="type-label">{{ fieldType.label }}</span>
              <div class="card-tooltip">{{ getFieldTypeDescription(fieldType) }}</div>
            </button>
          </div>
        </div>

        <!-- No Results -->
        <div v-if="Object.keys(groupedTypes).length === 0 && searchQuery" class="no-results">
          <div class="no-results-content">
            <span class="material-icons">search_off</span>
            <h6>{{ t('noFieldTypesFound') || 'No field types found' }}</h6>
            <p>{{ t('tryDifferentSearch') || 'Try a different search term' }}</p>
            <button class="btn btn-outline-primary btn-sm" @click="clearSearch">
              {{ t('clearSearch') || 'Clear search' }}
            </button>
          </div>
        </div>

        <!-- Keyboard Navigation Hint -->
        <div class="keyboard-hint">
          <small class="text-muted">
            <span class="material-icons">keyboard</span>
            {{ t('useArrowsToNavigate') || 'Use ↑↓ arrows to navigate, Enter to select, Esc to close' }}
          </small>
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
  backdrop-filter: blur(2px);
}

.acfps-modal {
  background: var(--card-bg, #fff);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  max-width: 700px;
  width: 100%;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transform: scale(0.95);
  opacity: 0;
  animation: modal-appear 0.2s ease-out forwards;
}

@keyframes modal-appear {
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-color, #e9e9e9);
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.modal-title {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 600;
  color: #495057;
}

.modal-header .close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.25rem;
  line-height: 1;
  color: var(--gray, #6c757d);
  border-radius: 4px;
  transition: all 0.2s ease;
}

.modal-header .close:hover {
  background-color: #f8f9fa;
  color: #495057;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  max-height: calc(80vh - 80px);
}

/* Search Container */
.search-container {
  margin-bottom: 1.5rem;
}

.input-group {
  position: relative;
}

.input-group-text {
  background: #f8f9fa;
  border: 1px solid #ced4da;
  border-right: none;
  color: #6c757d;
  padding: 0.5rem 0.75rem;
}

.input-group .form-control {
  border-left: none;
  border-radius: 0 0.375rem 0.375rem 0;
  padding: 0.5rem 1rem;
  font-size: 0.95rem;
}

.input-group .form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.input-group .btn {
  border-left: none;
  border-radius: 0;
}

/* Recently Used Section */
.recently-used-section {
  margin-bottom: 2rem;
  padding: 1rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.category-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.category-icon-wrapper {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.category-icon {
  font-size: 16px;
  opacity: 0.8;
}

.category-title {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #495057;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.category-count {
  background: #6c757d;
  color: white;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 10px;
  margin-left: auto;
}

/* Type Categories */
.type-category {
  margin-bottom: 2rem;
}

.type-category:last-child {
  margin-bottom: 0;
}

.type-category--custom {
  background: linear-gradient(135deg, #f8f4ff 0%, #fff8f0 100%);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px dashed #9c88ff;
  margin: 1rem 0;
}

.type-category--custom .category-title {
  color: #6c5ce7;
}

/* Type Grid */
.acfps-type-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.acfps-type-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem 0.75rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  text-align: center;
  min-height: 100px;
}

.acfps-type-card:hover {
  border-color: #007bff;
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
  transform: translateY(-2px);
}

.acfps-type-card--recent {
  border-color: #28a745;
}

.acfps-type-card--recent:hover {
  border-color: #28a745;
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
}

.acfps-type-card--selected {
  border-color: #007bff;
  background: #e7f3ff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.card-icon {
  font-size: 24px;
  color: #6c757d;
  margin-bottom: 0.5rem;
  transition: all 0.2s ease;
}

.acfps-type-card:hover .card-icon {
  color: #007bff;
  transform: scale(1.1);
}

.acfps-type-card--recent .card-icon {
  color: #28a745;
}

.acfps-type-card--recent:hover .card-icon {
  color: #28a745;
}

.type-label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #495057;
  line-height: 1.2;
}

/* Tooltips */
.card-tooltip {
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: #343a40;
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s ease;
  z-index: 1000;
  margin-bottom: 8px;
  pointer-events: none;
}

.card-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #343a40;
}

.acfps-type-card:hover .card-tooltip {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(-2px);
}

/* No Results */
.no-results {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  text-align: center;
}

.no-results-content {
  max-width: 300px;
}

.no-results-content .material-icons {
  font-size: 3rem;
  color: #dee2e6;
  margin-bottom: 1rem;
}

.no-results-content h6 {
  color: #6c757d;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.no-results-content p {
  color: #adb5bd;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}

/* Keyboard Navigation Hint */
.keyboard-hint {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
  text-align: center;
}

.keyboard-hint .material-icons {
  font-size: 16px;
  vertical-align: middle;
  margin-right: 0.25rem;
  opacity: 0.6;
}

.keyboard-hint small {
  font-size: 0.75rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .acfps-modal {
    max-width: 95vw;
    margin: 1rem;
  }

  .acfps-type-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 0.5rem;
  }

  .acfps-type-card {
    padding: 0.75rem 0.5rem;
    min-height: 80px;
  }

  .modal-body {
    padding: 1rem;
  }

  .card-tooltip {
    display: none; /* Hide tooltips on mobile */
  }
}

@media (max-width: 480px) {
  .acfps-type-grid {
    grid-template-columns: 1fr 1fr;
  }

  .acfps-type-card {
    min-height: 70px;
    padding: 0.5rem;
  }

  .card-icon {
    font-size: 20px;
  }

  .type-label {
    font-size: 0.8rem;
  }
}
</style>

