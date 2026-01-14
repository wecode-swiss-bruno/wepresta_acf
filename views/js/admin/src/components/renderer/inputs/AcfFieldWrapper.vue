<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  languages?: any[]
  currentLang?: number
}>()

const emit = defineEmits<{
  'update:currentLang': [id: number]
}>()

const isTranslatable = computed(() => {
  // Check variations of translatable flag
  return !!props.field.value_translatable || !!props.field.translatable
})

const showLangTabs = computed(() => {
  return !!(isTranslatable.value && props.languages && props.languages.length >= 1)
})
</script>

<template>
  <div class="acf-field-wrapper mb-4">
    <!-- Label -->
    <div class="d-flex align-items-center mb-2">
      <label class="form-control-label mb-0 mr-2">
        {{ field.title || (field as any).label }}
        <span v-if="field.validation?.required" class="text-danger">*</span>
      </label>
      <span v-if="isTranslatable" class="acf-globe-icon" title="Translatable">
        <i class="material-icons">language</i>
      </span>
    </div>

    <!-- Instructions -->
    <small v-if="field.instructions" class="form-text text-muted mb-3" v-html="field.instructions"></small>

    <!-- Language Tabs (Styled like PS Modules tab) -->
    <div v-if="showLangTabs" class="acf-lang-tabs mb-0">
      <button
        v-for="lang in languages"
        :key="lang.id_lang"
        type="button"
        class="acf-lang-tab"
        :class="{ active: currentLang === lang.id_lang }"
        @click="emit('update:currentLang', lang.id_lang)"
      >
        {{ lang.iso_code.toUpperCase() }}
      </button>
    </div>

    <!-- Field Input Slot -->
    <div class="field-input-container" :class="{ 'has-tabs': showLangTabs }">
      <slot></slot>
    </div>
  </div>
</template>

<style scoped>
.acf-globe-icon {
  color: #25b9d7; /* Standard PS Blue/Cyan for translations */
  display: inline-flex;
  align-items: center;
}

.acf-globe-icon i {
  font-size: 16px;
}

.acf-lang-tabs {
  display: flex;
  background: #f1f1f1;
  border: 1px solid #bbcdd2;
  border-bottom: none;
  border-radius: 3px 3px 0 0;
  width: fit-content;
}

.acf-lang-tab {
  border: none;
  background: transparent;
  padding: 8px 15px;
  font-size: 12px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  outline: none;
  border-right: 1px solid #bbcdd2;
  min-width: 50px;
}

.acf-lang-tab:last-child {
  border-right: none;
}

.acf-lang-tab:hover {
  background: #e5e5e5;
}

.acf-lang-tab.active {
  background: #fff;
  color: #333;
}

.field-input-container.has-tabs {
  border: 1px solid #bbcdd2;
  padding: 15px;
  background: #fff;
  border-radius: 0 3px 3px 3px;
}
</style>
