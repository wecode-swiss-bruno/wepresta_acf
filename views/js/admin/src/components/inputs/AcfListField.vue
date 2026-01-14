<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { AcfField } from '@/types'
import { useTranslations } from '@/composables/useTranslations'

const props = defineProps<{
  field: AcfField
  modelValue: string[] | null | undefined
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const { t } = useTranslations()

// Internal state
const items = ref<string[]>([])

// Initialize
watch(() => props.modelValue, (newVal) => {
  if (Array.isArray(newVal)) {
    // Clone to avoid reference issues
    items.value = [...newVal]
  } else if (newVal === null || newVal === undefined) {
    items.value = []
  } else {
      // Fallback if string provided (e.g. from legacy JSON)
      try {
          // If it's a string, it might be JSON encoded array
          if (typeof newVal === 'string') {
               const parsed = JSON.parse(newVal)
               if(Array.isArray(parsed)) {
                   items.value = parsed
               } else {
                   items.value = []
               }
          }
      } catch (e) {
          items.value = []
      }
  }
}, { immediate: true, deep: true })

function addItem() {
  items.value.push('')
  emitValue()
}

function removeItem(index: number) {
  items.value.splice(index, 1)
  emitValue()
}

function updateItem(index: number, value: string) {
  items.value[index] = value
  emitValue()
}

function emitValue() {
  emit('update:modelValue', [...items.value])
}

const config = computed(() => props.field.config || {})
const max = computed(() => parseInt(String(config.value.max || 0)))
const isMaxReached = computed(() => max.value > 0 && items.value.length >= max.value)

</script>

<template>
  <div class="acf-list-field">
    <div v-if="items.length === 0" class="text-muted font-italic mb-2 small">
      {{ t('noItems') || 'No items' }}
    </div>

    <div v-for="(item, index) in items" :key="index" class="d-flex mb-2 align-items-center">
       <input 
         type="text" 
         class="form-control" 
         :value="item"
         @input="updateItem(index, ($event.target as HTMLInputElement).value)"
         :placeholder="t('item') + ' ' + (index + 1)"
       />
       <button 
         type="button" 
         class="btn btn-outline-danger btn-sm ml-2"
         @click="removeItem(index)"
         title="Remove"
       >
         <i class="material-icons">close</i>
       </button>
    </div>

    <button 
      type="button" 
      class="btn btn-primary btn-sm mt-1"
      @click="addItem"
      :disabled="isMaxReached"
    >
      <i class="material-icons text-sm align-middle">add</i>
      {{ t('add') || 'Add' }}
    </button>
    
    <div v-if="isMaxReached" class="text-danger small mt-1">
        {{ t('maxItemsReached') || 'Maximum items reached' }}
    </div>
  </div>
</template>
