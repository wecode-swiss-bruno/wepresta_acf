<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const config = computed(() => props.field.config || {})
const uniqueId = `acf-rte-${props.field.id}-${Math.floor(Math.random() * 10000)}`
const editorRef = ref<any>(null)
const localValue = ref(props.modelValue || '')

// Sync content from editor
const syncContent = () => {
  if (editorRef.value) {
    const content = editorRef.value.getContent()
    if (content !== props.modelValue) {
      localValue.value = content
      emit('update:modelValue', content)
    }
  }
}

// Initialize TinyMCE
const initEditor = () => {
  // Check if global tinySetup exists (PrestaShop legacy)
  if (typeof (window as any).tinySetup === 'function') {
    (window as any).tinySetup({
      selector: `#${uniqueId}`,
      setup: (editor: any) => {
        editorRef.value = editor
        
        // Multiple events to capture all changes
        editor.on('Change', syncContent)
        editor.on('KeyUp', syncContent)
        editor.on('Blur', syncContent)
        editor.on('NodeChange', syncContent)
        editor.on('input', syncContent)
        
        // Set initial content when editor is ready
        editor.on('init', () => {
           if (props.modelValue) {
             editor.setContent(props.modelValue)
           }
        })
      }
    })
  } else {
    console.warn('[AcfRichTextField] tinySetup not available, using plain textarea')
  }
}

onMounted(() => {
  // Wait for next tick to ensure DOM is ready
  nextTick(() => {
    // Small delay to ensure TinyMCE scripts are loaded
    setTimeout(initEditor, 200)
  })
})

onUnmounted(() => {
  // Sync content before unmounting
  syncContent()
  
  if ((window as any).tinymce) {
    (window as any).tinymce.remove(`#${uniqueId}`)
  }
})

// Watch for external model changes 
watch(() => props.modelValue, (newValue) => {
  if (editorRef.value && (window as any).tinymce) {
    const currentContent = editorRef.value.getContent()
    if (newValue !== currentContent) {
       editorRef.value.setContent(newValue || '')
    }
  }
  localValue.value = newValue || ''
})

// Handle direct textarea input (fallback when TinyMCE not loaded)
const onTextareaInput = (event: Event) => {
  const value = (event.target as HTMLTextAreaElement).value
  localValue.value = value
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="acf-rich-text">
    <textarea
      :id="uniqueId"
      class="form-control rte autoload_rte"
      :value="localValue"
      :rows="config.rows || 10"
      placeholder="HTML Content..."
      @input="onTextareaInput"
    ></textarea>
  </div>
</template>

