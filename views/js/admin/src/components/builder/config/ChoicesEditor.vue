<script setup lang="ts">
import { ref, watch, nextTick, computed } from 'vue'
import type { FieldChoice } from '@/types'
import { useTranslations } from '@/composables/useTranslations'

interface Props {
  modelValue: FieldChoice[]
  emptyMessage?: string
  addButtonLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  emptyMessage: 'No options defined yet.',
  addButtonLabel: 'Add Option'
})

const emit = defineEmits<{
  'update:modelValue': [choices: FieldChoice[]]
}>()

const { t } = useTranslations()

// Language management (similar to FieldConfigurator)
const currentLangCode = ref<string>('en')
const languages = computed(() => window.acfConfig?.languages || [])
const defaultLanguage = computed(() => languages.value.find((l: any) => l.is_default) || languages.value[0])

// Get current language object
const currentLanguage = computed(() => {
  return languages.value.find((l: any) => l.code === currentLangCode.value) || defaultLanguage.value
})

// Local choices state
const choices = ref<FieldChoice[]>([...props.modelValue])
const isUpdating = ref(false)

// Initialize default language
if (defaultLanguage.value) {
  currentLangCode.value = defaultLanguage.value.code
}

// Slug generation utility
function generateSlug(text: string): string {
  return text
    .toLowerCase()
    .trim()
    // Remove accents and special chars
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    // Keep only alphanumeric and spaces
    .replace(/[^a-z0-9\s]/g, '')
    // Replace spaces with underscores
    .replace(/\s+/g, '_')
    // Remove multiple underscores
    .replace(/_+/g, '_')
    // Trim underscores
    .replace(/^_|_$/g, '')
    // Ensure not empty
    || 'choice'
}

// Sync from props
watch(() => props.modelValue, (newVal) => {
  if (!isUpdating.value) {
    choices.value = [...newVal]
  }
})

function addChoice(): void {
  const choiceNumber = choices.value.length + 1

  const newChoice: FieldChoice = {
    value: `choice_${choiceNumber}`,  // Auto-generated value
    label: '',                         // Empty - will be filled by translations
    translations: {}
  }

  // Initialize translations for all languages (all empty by default)
  languages.value.forEach((lang: any) => {
    newChoice.translations![lang.id] = ''
  })

  // Push AFTER setting all values
  choices.value.push(newChoice)
  emitChoices()
}

function removeChoice(index: number): void {
  choices.value.splice(index, 1)
  emitChoices()
}

function updateChoice(index: number, field: 'value' | 'label', value: string): void {
  const choice = choices.value[index]

  if (field === 'label') {
    choice.label = value
    // Auto-generate value if it hasn't been manually modified or is empty
    if (!choice.value || choice.value === generateSlug(choice.label)) {
      choice.value = generateSlug(value)
    }
  } else {
    // Validate value before setting
    if (validateChoiceValue(index, value)) {
      choice.value = value
    } else {
      // Reset to auto-generated if invalid
      choice.value = generateSlug(choice.label)
    }
  }

  emitChoices()
}

function updateChoiceTranslation(index: number, langId: any, value: string): void {
  if (!choices.value[index].translations) {
    choices.value[index].translations = {}
  }
  choices.value[index].translations![langId] = value

  // If updating default language translation, sync it to the main label
  const defaultLangId = defaultLanguage.value?.id.toString()
  if (defaultLangId === langId) {
    choices.value[index].label = value  // Keep label in sync with default language translation
  }

  emitChoices()
}

function onValueFocus(index: number): void {
  const choice = choices.value[index]
  // If the current value is auto-generated, clear it to allow manual editing
  if (choice.value === generateSlug(choice.label)) {
    choice.value = ''
  }
}


// Validate choice value
function validateChoiceValue(index: number, value: string): boolean {
  if (!value.trim()) {
    return false // Empty value not allowed
  }

  // Check uniqueness (case-insensitive)
  const normalizedValue = value.toLowerCase()
  return !choices.value.some((choice, i) => {
    if (i === index) return false
    const otherValue = (choice.value || generateSlug(choice.label)).toLowerCase()
    return otherValue === normalizedValue
  })
}

function emitChoices(): void {
  isUpdating.value = true
  emit('update:modelValue', [...choices.value])
  // Reset flag on next tick to allow prop updates
  nextTick(() => {
    isUpdating.value = false
  })
}
</script>

<template>
  <div class="choices-editor">
    <!-- Language Tabs (only show if multiple languages) -->
    <div v-if="languages.length > 1" class="choice-lang-tabs mb-3">
      <button
        v-for="lang in languages"
        :key="lang.code"
        type="button"
        class="choice-lang-tab"
        :class="{ active: currentLangCode === lang.code }"
        @click="currentLangCode = lang.code"
      >
        <span class="flag-icon flag-icon-{{ lang.code }}"></span>
        {{ lang.code.toUpperCase() }}
        <span v-if="lang.is_default" class="badge badge-primary badge-sm ml-1">{{ t('default') }}</span>
      </button>
    </div>

    <div
      v-for="(choice, index) in choices"
      :key="index"
      class="choice-row"
    >
      <div class="value-input-container">
        <input
          type="text"
          class="form-control"
          :class="{ 'text-muted': !choice.value || choice.value === generateSlug(choice.label) }"
          :placeholder="t('autoPrefix') + ' ' + generateSlug(choice.label)"
          :value="choice.value || generateSlug(choice.label)"
          @input="updateChoice(index, 'value', ($event.target as HTMLInputElement).value)"
          @focus="onValueFocus(index)"
        >
        <small v-if="!choice.value || choice.value === generateSlug(choice.label)"
               class="form-text text-muted value-hint">
          {{ t('autoGeneratedHint') }}
        </small>
        <small v-else class="form-text text-info value-hint">
          {{ t('customValueHint') }}
        </small>
      </div>
      <div class="label-input-container" v-if="currentLanguage">
        <input
          type="text"
          class="form-control"
          :class="{ 'font-weight-bold': currentLanguage.is_default }"
          :placeholder="t('label') + (languages.length > 1 ? ' (' + currentLanguage.code.toUpperCase() + ')' : '')"
          :value="choice.translations ? choice.translations[String(currentLanguage.id)] || '' : choice.label"
          @input="updateChoiceTranslation(index, currentLanguage.id, ($event.target as HTMLInputElement).value)"
        >
      </div>
      <button
        type="button"
        class="btn btn-link text-danger"
        @click="removeChoice(index)"
      >
        <span class="material-icons">delete</span>
      </button>
    </div>
    
    <div v-if="choices.length === 0" class="text-muted mb-2">
      {{ props.emptyMessage }}
    </div>
    
    <button 
      type="button" 
      class="btn btn-sm btn-outline-secondary mt-2"
      @click="addChoice"
    >
      <span class="material-icons">add</span>
      {{ props.addButtonLabel }}
    </button>
  </div>
</template>

<style scoped>
.choice-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  align-items: flex-start;
}

.choice-row .form-control {
  flex: 1;
}

.choice-row .btn-link {
  padding: 0.25rem;
}

.value-input-container {
  flex: 1;
  position: relative;
}

.label-input-container {
  flex: 1;
}

.value-hint {
  font-size: 0.75rem;
  margin-top: 0.25rem;
  margin-bottom: 0.5rem;
}

/* Language tabs */
.choice-lang-tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  border-bottom: 2px solid #e9ecef;
  padding-bottom: 0.5rem;
}

.choice-lang-tab {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.5rem 1rem;
  border: 1px solid #dee2e6;
  border-radius: 4px 4px 0 0;
  background: #f8f9fa;
  color: #495057;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.85rem;
  font-weight: 500;
}

.choice-lang-tab:hover {
  background: #e9ecef;
  border-color: #adb5bd;
}

.choice-lang-tab.active {
  background: white;
  border-bottom-color: white;
  color: #007bff;
  font-weight: 600;
  border-color: #007bff;
  position: relative;
  margin-bottom: -2px;
  padding-bottom: calc(0.5rem + 2px);
}

.choice-lang-tab .badge {
  font-size: 0.65rem;
  padding: 0.1rem 0.3rem;
}
</style>
