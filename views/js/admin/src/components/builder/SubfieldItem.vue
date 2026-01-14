<script setup lang="ts">
import { ref, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import type { AcfField } from '@/types'
import FieldTypeSelector from '@/components/builder/FieldTypeSelector.vue'

interface Props {
  field: AcfField
  parentField?: AcfField
  depth?: number
}

const props = withDefaults(defineProps<Props>(), {
  depth: 0,
})

const emit = defineEmits<{
  delete: [field: AcfField, parent?: AcfField]
  addSubfield: [parent: AcfField, type: string]
}>()

const store = useBuilderStore()
const { t } = useTranslations()

const showTypeSelector = ref(false)
const expandedRepeaters = ref<Set<string>>(new Set())

const fieldIcons: Record<string, string> = {
  text: 'text_fields',
  textarea: 'notes',
  number: 'pin',
  select: 'list',
  richtext: 'article',
  checkbox: 'check_box',
  radio: 'radio_button_checked',
  boolean: 'toggle_on',
  file: 'attach_file',
  files: 'folder',
  image: 'image',
  gallery: 'collections',
  video: 'videocam',
  date: 'event',
  time: 'schedule',
  datetime: 'calendar_today',
  color: 'palette',
  email: 'email',
  url: 'link',
  relation: 'account_tree',
  list: 'format_list_bulleted',
  repeater: 'repeat',
}

function getFieldIcon(type: string): string {
  return fieldIcons[type] || 'input'
}

function toggleRepeaterExpand(uuid: string): void {
  if (expandedRepeaters.value.has(uuid)) {
    expandedRepeaters.value.delete(uuid)
  } else {
    expandedRepeaters.value.add(uuid)
  }
}

function isRepeaterExpanded(uuid: string): boolean {
  return expandedRepeaters.value.has(uuid)
}

function getSubfields(field: AcfField): AcfField[] {
  return field.children || []
}

function confirmDelete(): void {
  if (confirm(t('confirmDeleteField'))) {
    emit('delete', props.field, props.parentField)
  }
}

function openAddSubfield(): void {
  showTypeSelector.value = true
}

function addFieldType(type: string): void {
  emit('addSubfield', props.field, type)
  showTypeSelector.value = false
}

function reorderSubfields(newOrder: AcfField[]): void {
  store.reorderSubfields(props.field, newOrder)
}

// Indent calculation based on depth
const indentStyle = computed(() => ({
  paddingLeft: `${0.5 + props.depth * 1.75}rem`,
}))

const subfieldsIndentStyle = computed(() => ({
  paddingLeft: `${0.5 + props.depth * 1.75 + 2}rem`,
}))
</script>

<template>
  <div class="acfps-subfield-wrapper" :style="indentStyle">
    <!-- Field item -->
    <div
      class="acfps-field-item acfps-subfield-item"
      :class="{ 
        active: store.selectedField?.uuid === field.uuid,
        'field-incomplete': !field.title?.trim()
      }"
      @click.stop="store.selectField(field)"
    >
      <span class="drag-handle material-icons">drag_indicator</span>
      
      <!-- Warning icon for incomplete fields -->
      <span v-if="!field.title?.trim()" class="material-icons text-warning incomplete-icon" title="Title required">
        warning
      </span>
      
      <!-- Expand toggle for nested repeaters -->
      <button 
        v-if="field.type === 'repeater'"
        class="btn btn-link btn-sm expand-toggle"
        @click.stop="toggleRepeaterExpand(field.uuid)"
      >
        <span class="material-icons">
          {{ isRepeaterExpanded(field.uuid) ? 'expand_more' : 'chevron_right' }}
        </span>
      </button>
      
      <span class="field-icon material-icons">{{ getFieldIcon(field.type) }}</span>
      <div class="field-info">
        <span class="field-title" :class="{ 'text-muted': !field.title?.trim() }">
          {{ field.title || t('untitled') }}
        </span>
        <span class="field-type">{{ field.type }}</span>
        <span v-if="field.type === 'repeater' && getSubfields(field).length > 0" class="subfield-count">
          ({{ getSubfields(field).length }} subfields)
        </span>
      </div>
      <span class="field-slug">{{ field.slug }}</span>
      <button 
        class="btn btn-link btn-sm text-danger"
        @click.stop="confirmDelete"
      >
        <span class="material-icons">delete</span>
      </button>
    </div>
    
    <!-- Nested subfields for repeaters (recursive) -->
    <div 
      v-if="field.type === 'repeater' && isRepeaterExpanded(field.uuid)" 
      class="acfps-nested-subfields"
      :style="subfieldsIndentStyle"
    >
      <VueDraggable
        :model-value="getSubfields(field)"
        :animation="200"
        handle=".drag-handle"
        ghost-class="sortable-ghost"
        chosen-class="sortable-chosen"
        class="nested-subfield-list"
        @update:model-value="reorderSubfields($event)"
      >
        <!-- Recursive component for nested subfields -->
        <SubfieldItem
          v-for="subfield in getSubfields(field)"
          :key="subfield.uuid"
          :field="subfield"
          :parent-field="field"
          :depth="depth + 1"
          @delete="(f, p) => emit('delete', f, p)"
          @addSubfield="(parent, type) => emit('addSubfield', parent, type)"
        />
      </VueDraggable>
      
      <!-- Add subfield button -->
      <button 
        class="btn btn-outline-secondary btn-sm add-nested-subfield-btn"
        @click.stop="openAddSubfield"
      >
        <span class="material-icons">add</span>
        Add Subfield
      </button>
    </div>

    <!-- Field type selector modal -->
    <FieldTypeSelector
      :show="showTypeSelector"
      :disabled-field-types="field.type === 'repeater' ? ['repeater'] : undefined"
      @close="showTypeSelector = false"
      @select="addFieldType"
    />
  </div>
</template>

<style scoped>
.acfps-subfield-wrapper {
  transition: padding-left 0.2s ease;
}

.acfps-field-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 0.25rem;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
}

.acfps-field-item:hover {
  border-color: #25b9d7;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.acfps-field-item.active {
  background: #e3f2fd;
  border-color: #2196F3;
}

.acfps-subfield-item {
  background: #fff;
  border: 1px solid #ddd;
}

.drag-handle {
  cursor: grab;
  color: #999;
  margin-right: 0.25rem;
  flex-shrink: 0;
}

.drag-handle:active {
  cursor: grabbing;
}

.incomplete-icon {
  font-size: 18px;
  margin-right: 0.25rem;
  flex-shrink: 0;
  animation: pulse 2s infinite;
}

.field-icon {
  margin: 0 0.5rem;
  flex-shrink: 0;
  color: #666;
}

.field-info {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.field-title {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.field-type {
  font-size: 0.75rem;
  color: #999;
  background: #f5f5f5;
  padding: 0.2rem 0.5rem;
  border-radius: 2px;
  white-space: nowrap;
  flex-shrink: 0;
}

.subfield-count {
  font-size: 11px;
  color: #888;
  white-space: nowrap;
  flex-shrink: 0;
}

.field-slug {
  font-size: 0.75rem;
  color: #aaa;
  margin: 0 0.5rem;
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.expand-toggle {
  padding: 0;
  margin-right: 0.25rem;
  flex-shrink: 0;
}

.expand-toggle .material-icons {
  font-size: 20px;
  color: #666;
}

.btn-link {
  padding: 0.25rem;
  margin-left: 0.25rem;
  flex-shrink: 0;
}

.text-danger {
  color: #dc3545;
}

.acfps-nested-subfields {
  background: #f8f9fa;
  border-left: 2px solid #ddd;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  margin-top: 0.25rem;
  border-radius: 0 4px 4px 0;
}

.nested-subfield-list {
  margin-bottom: 0.5rem;
}

.add-nested-subfield-btn {
  width: calc(100% - 1rem);
  margin: 0.5rem 0.5rem 0 0.5rem;
  font-size: 0.875rem;
}

.add-nested-subfield-btn .material-icons {
  font-size: 16px;
  vertical-align: middle;
  margin-right: 0.25rem;
}

/* Field incomplete styles */
.field-incomplete {
  border-left: 3px solid #ffc107 !important;
  background: #fff8e1;
}

.field-incomplete:hover {
  background: #fff3cd;
}

/* Animation for pulse */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Depth-based visual hierarchy */
.acfps-subfield-wrapper {
  position: relative;
}

.acfps-subfield-wrapper:nth-child(n + 2) {
  border-top: 1px dashed #e9e9e9;
  padding-top: 0.5rem;
}
</style>
