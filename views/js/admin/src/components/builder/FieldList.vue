<script setup lang="ts">
import { ref, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import type { AcfField } from '@/types'
import FieldTypeSelector from '@/components/builder/FieldTypeSelector.vue'
import SubfieldItem from '@/components/builder/SubfieldItem.vue'
import CopyCodeButton from '@/components/common/CopyCodeButton.vue'

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
      <div class="empty-state-content">
        <span class="material-icons empty-icon">view_list</span>
        <h5 class="empty-title">{{ t('noFields') }}</h5>
        <p class="empty-description">{{ t('getStartedAddingFields') }}</p>
        <button class="btn btn-primary btn-lg empty-action-btn" @click="showTypeSelector = true">
          <span class="material-icons">add</span>
          {{ t('addField') }}
        </button>
      </div>
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
          :class="{ 
            active: store.selectedField?.uuid === field.uuid,
            'field-incomplete': !field.title?.trim()
          }"
          @click="store.selectField(field)"
        >
          <span class="drag-handle material-icons">drag_indicator</span>
          
          <!-- ✅ Warning icon pour champs incomplets -->
          <span v-if="!field.title?.trim()" class="material-icons text-warning incomplete-icon" title="Title required">
            warning
          </span>
          
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
            <span class="field-title" :class="{ 'text-muted': !field.title?.trim() }">
              {{ field.title || t('untitled') }}
            </span>
            <span class="field-type">{{ field.type }}</span>
            <span v-if="field.type === 'repeater' && getSubfields(field).length > 0" class="subfield-count">
              ({{ getSubfields(field).length }} subfields)
            </span>
          </div>
          <span class="field-slug">{{ field.slug }}</span>
          
          <!-- Copy Code Button -->
          <CopyCodeButton 
            v-if="field.slug" 
            :field="field" 
            :compact="true" 
          />
          
          <button 
            class="btn btn-link btn-sm text-danger"
            @click.stop="confirmDelete(field)"
          >
            <span class="material-icons">delete</span>
          </button>
        </div>
        
        <!-- Subfields for repeaters - Using recursive SubfieldItem component -->
        <div 
          v-if="field.type === 'repeater' && isRepeaterExpanded(field.uuid)" 
          class="acfps-subfields"
        >
          <!-- Subfields draggable list (only show if has children) -->
          <VueDraggable
            v-if="getSubfields(field).length > 0"
            :model-value="getSubfields(field)"
            :animation="200"
            handle=".drag-handle"
            ghost-class="sortable-ghost"
            chosen-class="sortable-chosen"
            class="subfield-list"
            @update:model-value="reorderSubfields(field, $event)"
          >
            <SubfieldItem
              v-for="subfield in getSubfields(field)"
              :key="subfield.uuid"
              :field="subfield"
              :parent-field="field"
              :depth="0"
              @delete="(f, p) => confirmDelete(f, p)"
              @addSubfield="(parent) => { addingSubfieldTo = parent; showTypeSelector = true }"
            />
          </VueDraggable>

          <!-- Empty state for repeater -->
          <div v-else class="alert alert-info mb-3">
            <small>{{ t('noSubfields') || 'No subfields yet. Click the button below to add one.' }}</small>
          </div>
          
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

    <!-- Add field button (only when fields exist) -->
    <div v-if="fields.length > 0" class="field-list-footer">
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
      :disabled-field-types="addingSubfieldTo?.type === 'repeater' ? ['repeater'] : undefined"
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


/* Empty state styles */
.acfps-empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  min-height: 300px;
}

.empty-state-content {
  text-align: center;
  max-width: 300px;
}

.empty-icon {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1.5rem;
  display: block;
}

.empty-title {
  color: #6c757d;
  font-size: 1.25rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.empty-description {
  color: #adb5bd;
  font-size: 0.9rem;
  margin-bottom: 2rem;
  line-height: 1.5;
}

.empty-action-btn {
  border-radius: 8px;
  padding: 0.75rem 2rem;
  font-weight: 500;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.empty-action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.empty-action-btn .material-icons {
  margin-right: 0.5rem;
}

/* Field list footer (only shown when fields exist) */
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

/* ✅ Styles pour champs incomplets */
.field-incomplete {
  border-left: 3px solid #ffc107 !important;
  background: #fff8e1;
}

.field-incomplete:hover {
  background: #fff3cd;
}

.incomplete-icon {
  font-size: 18px;
  margin-right: 0.25rem;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>

