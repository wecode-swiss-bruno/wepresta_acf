<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { AcfField } from '@/types'
import AcfFieldWrapper from './inputs/AcfFieldWrapper.vue'
import AcfTextField from './inputs/AcfTextField.vue'
import AcfTextareaField from './inputs/AcfTextareaField.vue'
import AcfRichTextField from './inputs/AcfRichTextField.vue'
import AcfNumberField from './inputs/AcfNumberField.vue'
import AcfSelectField from './inputs/AcfSelectField.vue'
import AcfBooleanField from './inputs/AcfBooleanField.vue'
import AcfCheckboxField from './inputs/AcfCheckboxField.vue'
import AcfColorField from '@/components/renderer/inputs/AcfColorField.vue'
import AcfDateField from '@/components/renderer/inputs/AcfDateField.vue'
import AcfDateTimeField from '@/components/renderer/inputs/AcfDateTimeField.vue'
import AcfTimeField from '@/components/renderer/inputs/AcfTimeField.vue'
import AcfRadioField from './inputs/AcfRadioField.vue'
import AcfFileUploadField from '@/components/renderer/inputs/AcfFileUploadField.vue'
import AcfListField from '@/components/renderer/inputs/AcfListField.vue'
import AcfRelationField from '@/components/renderer/inputs/AcfRelationField.vue'
import AcfRepeaterField from '@/components/renderer/inputs/AcfRepeaterField.vue'

const props = defineProps<{
  field: AcfField
  value: any
  locale?: string
  entityType?: string
  entityId?: number
}>()

console.log('AcfFieldRenderer mounted - VERSION CHECK 2542', { field: props.field?.slug, locale: props.locale })

const emit = defineEmits<{
  (e: 'update:value', value: any): void
}>()

// Internal state for multiple languages
const currentLang = ref<number>(props.defaultLanguage?.id_lang || 1)

// Initialize current lang if not set
watch(() => props.defaultLanguage, (newVal) => {
  if (newVal && !currentLang.value) {
    currentLang.value = newVal.id_lang
  }
}, { immediate: true })

// Check if translatable
const isTranslatable = computed(() => {
  return !!props.field.value_translatable || !!props.field.translatable
})

// Helper to get value
const getValue = (langId?: number) => {
  if (isTranslatable.value) {
    const id = langId || currentLang.value
    if (typeof props.modelValue === 'object' && props.modelValue !== null) {
      return (props.modelValue as Record<number, any>)[id] ?? ''
    }
    
    // Fallback: if it's a string, it might be legacy or default lang value
    const defaultLangId = props.defaultLanguage?.id_lang || 1
    if (id === defaultLangId) {
        return props.modelValue || ''
    }
    return ''
  }
  return props.modelValue
}

// Helper to set value
const setValue = (val: any) => {
  if (isTranslatable.value) {
    const id = currentLang.value
    let newVal: Record<number, any> = {}
    
    if (typeof props.modelValue === 'object' && props.modelValue !== null) {
      newVal = { ...props.modelValue }
    } else if (typeof props.modelValue === 'string' && props.modelValue !== '') {
      // Migration: Put existing string into default language slot
      const defaultId = props.defaultLanguage?.id_lang || 1
      newVal[defaultId] = props.modelValue
    }
    
    newVal[id] = val
    emit('update:modelValue', newVal)
  } else {
    emit('update:modelValue', val)
  }
}
// Normalizing field config
const processedField = computed(() => {
  const f = { ...props.field }
  if (typeof f.config === 'string') {
    try {
      f.config = JSON.parse(f.config)
    } catch (e) {
      f.config = {}
    }
  } else if (!f.config) {
      f.config = {}
  }
  return f
})
</script>

<template>
  <AcfFieldWrapper 
    :field="processedField" 
    :languages="languages" 
    v-model:currentLang="currentLang"
  >
    <!-- Text / URL / Email -->
    <AcfTextField
      v-if="['text', 'url', 'email', 'password'].includes(processedField.type)"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Date -->
    <AcfDateField
      v-else-if="processedField.type === 'date'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Time -->
    <AcfTimeField
      v-else-if="processedField.type === 'time'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- DateTime -->
    <AcfDateTimeField
      v-else-if="processedField.type === 'datetime'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Textarea -->
    <AcfTextareaField
      v-else-if="processedField.type === 'textarea'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Rich Text (WYSIWYG) -->
    <AcfRichTextField
      v-else-if="processedField.type === 'wysiwyg' || processedField.type === 'richtext'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Number -->
    <AcfNumberField
      v-else-if="processedField.type === 'number' || processedField.type === 'range'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Boolean (Checkbox/Switch) -->
    <AcfBooleanField
      v-else-if="processedField.type === 'boolean' || processedField.type === 'true_false'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Checkbox (Multiple Group) -->
    <AcfCheckboxField
      v-else-if="processedField.type === 'checkbox'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Radio -->
    <AcfRadioField
      v-else-if="processedField.type === 'radio'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Select -->
    <AcfSelectField
      v-else-if="processedField.type === 'select'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Color -->
    <AcfColorField
      v-else-if="processedField.type === 'color'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- File / Image / Video -->
    <AcfFileUploadField
      v-else-if="['file', 'image', 'video', 'gallery'].includes(processedField.type)"
      :model-value="getValue()"
      :field-slug="processedField.slug"
      :field-type="processedField.type as 'file' | 'image' | 'video' | 'gallery'"
      :field="processedField"
      @update:model-value="setValue($event)"
    />

    <!-- Repeater -->
    <AcfRepeaterField
      v-else-if="processedField.type === 'repeater'"
      :field="processedField"
      :model-value="getValue()"
      :languages="languages || []"
      :default-language="defaultLanguage"
      @update:model-value="setValue($event)"
    />

    <!-- List -->
    <AcfListField
      v-else-if="processedField.type === 'list'"
      :field="processedField"
      :model-value="getValue() || []"
      @update:model-value="setValue"
    />

    <!-- Relation -->
    <AcfRelationField
      v-else-if="processedField.type === 'relation' || processedField.type === 'post_object' || processedField.type === 'page_link' || processedField.type === 'taxonomy'"
      :field="processedField"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Unknown -->
    <div v-else class="alert alert-warning">
      Unsupported field type: {{ processedField.type }}
    </div>

  </AcfFieldWrapper>
</template>
