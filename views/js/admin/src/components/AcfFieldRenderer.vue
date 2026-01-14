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
import AcfRadioField from './inputs/AcfRadioField.vue'
import FileUploadField from '@/components/ui/FileUploadField.vue'
import RepeaterGlobalField from '@/components/ui/RepeaterGlobalField.vue'

const props = defineProps<{
  field: AcfField
  modelValue: any
  languages?: any[]
  defaultLanguage?: any
}>()

const emit = defineEmits<{
  'update:modelValue': [value: any]
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
  return !!props.field.value_translatable || !!props.field.valueTranslatable || !!props.field.translatable
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
</script>

<template>
  <AcfFieldWrapper 
    :field="field" 
    :languages="languages" 
    v-model:currentLang="currentLang"
  >
    <!-- Text / URL / Email -->
    <AcfTextField
      v-if="['text', 'url', 'email', 'password', 'date', 'time', 'datetime'].includes(field.type)"
      :field="field"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Textarea -->
    <AcfTextareaField
      v-else-if="field.type === 'textarea'"
      :field="field"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Rich Text (WYSIWYG) -->
    <AcfRichTextField
      v-else-if="field.type === 'wysiwyg' || field.type === 'richtext'"
      :field="field"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Number -->
    <AcfNumberField
      v-else-if="field.type === 'number' || field.type === 'range'"
      :field="field"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Boolean (Checkbox/Switch) -->
    <AcfBooleanField
      v-else-if="field.type === 'boolean' || field.type === 'true_false'"
      :field="field"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Checkbox (Multiple Group) -->
    <AcfCheckboxField
      v-else-if="field.type === 'checkbox'"
      :field="field"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Radio -->
    <AcfRadioField
      v-else-if="field.type === 'radio'"
      :field="field"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Select -->
    <AcfSelectField
      v-else-if="field.type === 'select'"
      :field="field"
      :model-value="getValue()"
      @update:model-value="setValue"
    />

    <!-- Color -->
    <input 
      v-else-if="field.type === 'color'"
      type="color" 
      class="form-control"
      style="height: 40px; padding: 5px;"
      :value="getValue() || '#000000'"
      @input="setValue(($event.target as HTMLInputElement).value)"
    />

    <!-- File / Image / Video -->
    <FileUploadField
      v-else-if="['file', 'image', 'video', 'gallery'].includes(field.type)"
      :model-value="getValue()"
      :field-slug="field.slug"
      :field-type="field.type"
      @update:model-value="setValue($event)"
    />

    <!-- Repeater -->
    <RepeaterGlobalField
      v-else-if="field.type === 'repeater'"
      :field="field"
      :model-value="getValue()"
      :languages="languages || []"
      :default-language="defaultLanguage"
      @update:model-value="setValue($event)"
    />

    <!-- Relation (Fallback to Select for now) -->
    <div v-else-if="field.type === 'relation' || field.type === 'post_object' || field.type === 'page_link' || field.type === 'taxonomy'" class="alert alert-info">
       Relation field type '{{ field.type }}' implementation pending. using Text input for value storage.
       <input type="text" class="form-control mt-2" :value="getValue()" @input="setValue(($event.target as HTMLInputElement).value)" />
    </div>

    <!-- Unknown -->
    <div v-else class="alert alert-warning">
      Unsupported field type: {{ field.type }}
    </div>

  </AcfFieldWrapper>
</template>
