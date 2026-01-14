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

// Initialize TinyMCE
const initEditor = () => {
  // Check if global tinySetup exists (PrestaShop legacy)
  if (typeof (window as any).tinySetup === 'function') {
    (window as any).tinySetup({
      selector: `#${uniqueId}`,
      setup: (editor: any) => {
        editorRef.value = editor
        
        editor.on('Change KeyUp', () => {
          const content = editor.getContent()
          if (content !== props.modelValue) {
            emit('update:modelValue', content)
          }
        })

        // Set initial content if available and editor is empty
        editor.on('init', () => {
           if (props.modelValue) {
             editor.setContent(props.modelValue)
           }
        })
      }
    })
  }
}

onMounted(() => {
  // Wait for next tick to ensure DOM is ready
  nextTick(() => {
    initEditor()
  })
})

onUnmounted(() => {
  if ((window as any).tinymce) {
    (window as any).tinymce.remove(`#${uniqueId}`)
  }
})

// Determine if we should update the editor content
watch(() => props.modelValue, (newValue) => {
  if (editorRef.value && (window as any).tinymce) {
    const currentContent = editorRef.value.getContent()
    if (newValue !== currentContent) {
       editorRef.value.setContent(newValue || '')
    }
  }
})
</script>

<template>
  <div class="acf-rich-text">
    <textarea
      :id="uniqueId"
      class="form-control rte"
      :value="modelValue"
      :rows="config.rows || 10"
      placeholder="HTML Content..."
    ></textarea>
  </div>
</template>
