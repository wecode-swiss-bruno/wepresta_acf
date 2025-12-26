<script setup lang="ts">
import { ref, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import type { AcfField } from '@/types'
import FieldTypeSelector from '@/components/FieldTypeSelector.vue'

const store = useBuilderStore()
const { t } = useTranslations()

const showTypeSelector = ref(false)
const addingSubfieldTo = ref<AcfField | null>(null)
const expandedRepeaters = ref<Set<string>>(new Set())

const fields = computed({
  get: () => store.currentFields,
  set: (value: AcfField[]) => {
    store.reorderFields(value)
  }
})

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

function confirmDelete(field: AcfField, parent?: AcfField): void {
  if (confirm(t('confirmDeleteField'))) {
    if (parent) {
      store.removeSubfield(parent, field)
    } else {
      store.removeField(field)
    }
  }
}

function addFieldType(type: string): void {
  if (addingSubfieldTo.value) {
    store.addSubfield(addingSubfieldTo.value, type)
    addingSubfieldTo.value = null
  } else {
    store.addField(type)
  }
  showTypeSelector.value = false
}

function openAddSubfield(parent: AcfField): void {
  addingSubfieldTo.value = parent
  showTypeSelector.value = true
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

function reorderSubfields(parent: AcfField, newOrder: AcfField[]): void {
  store.reorderSubfields(parent, newOrder)
}
</script>

<template>
  <div class="acfps-field-list">
    <!-- Empty state -->
    <div v-if="fields.length === 0" class="acfps-empty-state">
      <span class="material-icons">view_list</span>
      <p>{{ t('noFields') }}</p>
      <button class="btn btn-outline-primary btn-sm" @click="showTypeSelector = true">
        <span class="material-icons">add</span>
        {{ t('addField') }}
      </button>
    </div>

    <!-- Draggable field list -->
    <VueDraggable
      v-else
      v-model="fields"
      :animation="200"
      handle=".drag-handle"
      ghost-class="sortable-ghost"
      chosen-class="sortable-chosen"
      class="field-list-container"
    >
      <div
        v-for="field in fields"
        :key="field.uuid"
        class="acfps-field-wrapper"
      >
        <div
          class="acfps-field-item"
          :class="{ active: store.selectedField?.uuid === field.uuid }"
          @click="store.selectField(field)"
        >
          <span class="drag-handle material-icons">drag_indicator</span>
          
          <!-- Expand toggle for repeaters -->
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
            <span class="field-title">{{ field.title || t('untitled') }}</span>
            <span class="field-type">{{ field.type }}</span>
            <span v-if="field.type === 'repeater' && getSubfields(field).length > 0" class="subfield-count">
              ({{ getSubfields(field).length }} subfields)
            </span>
          </div>
          <span class="field-slug">{{ field.slug }}</span>
          <button 
            class="btn btn-link btn-sm text-danger"
            @click.stop="confirmDelete(field)"
          >
            <span class="material-icons">delete</span>
          </button>
        </div>
        
        <!-- Subfields for repeaters -->
        <div 
          v-if="field.type === 'repeater' && isRepeaterExpanded(field.uuid)" 
          class="acfps-subfields"
        >
          <VueDraggable
            :model-value="getSubfields(field)"
            :animation="200"
            handle=".drag-handle"
            ghost-class="sortable-ghost"
            chosen-class="sortable-chosen"
            class="subfield-list"
            @update:model-value="reorderSubfields(field, $event)"
          >
            <div
              v-for="subfield in getSubfields(field)"
              :key="subfield.uuid"
              class="acfps-field-item acfps-subfield-item"
              :class="{ active: store.selectedField?.uuid === subfield.uuid }"
              @click.stop="store.selectField(subfield)"
            >
              <span class="drag-handle material-icons">drag_indicator</span>
              <span class="field-icon material-icons">{{ getFieldIcon(subfield.type) }}</span>
              <div class="field-info">
                <span class="field-title">{{ subfield.title || t('untitled') }}</span>
                <span class="field-type">{{ subfield.type }}</span>
              </div>
              <span class="field-slug">{{ subfield.slug }}</span>
              <button 
                class="btn btn-link btn-sm text-danger"
                @click.stop="confirmDelete(subfield, field)"
              >
                <span class="material-icons">delete</span>
              </button>
            </div>
          </VueDraggable>
          
          <!-- Add subfield button -->
          <button 
            class="btn btn-outline-secondary btn-sm add-subfield-btn"
            @click.stop="openAddSubfield(field)"
          >
            <span class="material-icons">add</span>
            Add Subfield
          </button>
        </div>
      </div>
    </VueDraggable>

    <!-- Add field button -->
    <div class="field-list-footer">
      <button 
        class="btn btn-outline-secondary btn-block"
        @click="showTypeSelector = true; addingSubfieldTo = null"
      >
        <span class="material-icons">add</span>
        {{ t('addField') }}
      </button>
    </div>

    <!-- Field type selector modal -->
    <FieldTypeSelector
      :show="showTypeSelector"
      @close="showTypeSelector = false; addingSubfieldTo = null"
      @select="addFieldType"
    />
  </div>
</template>

<style scoped>
.acfps-field-list {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.field-list-container {
  flex: 1;
  overflow-y: auto;
}

.field-list-footer {
  padding: 1rem;
  border-top: 1px solid var(--border-color, #e9e9e9);
}

.field-list-footer .btn .material-icons {
  font-size: 18px;
  vertical-align: middle;
  margin-right: 0.25rem;
}

.acfps-field-item .btn-link .material-icons {
  font-size: 18px;
}

.acfps-field-wrapper {
  border-bottom: 1px solid var(--border-color, #e9e9e9);
}

.expand-toggle {
  padding: 0;
  margin-right: 0.25rem;
}

.expand-toggle .material-icons {
  font-size: 20px;
  color: #666;
}

.subfield-count {
  font-size: 11px;
  color: #888;
  margin-left: 0.5rem;
}

.acfps-subfields {
  background: #f8f9fa;
  padding: 0.5rem 0.5rem 0.5rem 2.5rem;
  border-top: 1px dashed #ddd;
}

.subfield-list {
  margin-bottom: 0.5rem;
}

.acfps-subfield-item {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 0.25rem;
  padding: 0.5rem;
}

.acfps-subfield-item:hover {
  border-color: #25b9d7;
}

.add-subfield-btn {
  width: 100%;
  margin-top: 0.25rem;
}

.add-subfield-btn .material-icons {
  font-size: 16px;
  vertical-align: middle;
  margin-right: 0.25rem;
}
</style>

