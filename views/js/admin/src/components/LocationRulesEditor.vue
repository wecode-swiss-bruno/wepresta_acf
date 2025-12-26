<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations'
import type { JsonLogicRule } from '@/types'

defineProps<{
  rules: JsonLogicRule[]
}>()

defineEmits<{
  'update:rules': [rules: JsonLogicRule[]]
}>()

const { t } = useTranslations()

// Location rule operators
const operators = [
  { value: '==', label: 'equals' },
  { value: '!=', label: 'notEquals' },
  { value: 'in', label: 'contains' },
]

// Location rule fields
const ruleFields = [
  { value: 'product.category', label: 'productCategory' },
  { value: 'product.type', label: 'productType' },
  { value: 'product.id', label: 'productId' },
]
</script>

<template>
  <div class="location-rules-editor">
    <p class="text-muted">{{ t('locationRulesHelp') }}</p>
    
    <div class="alert alert-info">
      <strong>Coming in Phase 4</strong><br>
      The visual location rules editor will allow you to define which products 
      this field group appears on based on:
      <ul class="mb-0 mt-2">
        <li v-for="field in ruleFields" :key="field.value">
          {{ t(field.label) }}
        </li>
      </ul>
    </div>

    <div class="available-operators text-muted small mt-3">
      <strong>Available operators:</strong>
      <span v-for="op in operators" :key="op.value" class="ml-2">
        {{ t(op.label) }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.location-rules-editor {
  padding: 1.5rem;
}

.available-operators span {
  margin-left: 0.5rem;
}

.available-operators span::after {
  content: ',';
}

.available-operators span:last-child::after {
  content: '';
}
</style>

