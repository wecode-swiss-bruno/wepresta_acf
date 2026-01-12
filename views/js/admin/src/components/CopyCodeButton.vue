<script setup lang="ts">
/**
 * Copy Code Button Component
 * 
 * Displays a copy button that shows code snippets for ACF fields.
 * Supports Smarty, Twig, and Shortcode syntax.
 */

import { ref, computed } from 'vue'
import { useCodeGenerator } from '@/composables/useCodeGenerator'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  compact?: boolean
}>()

const { 
  selectedLanguage, 
  copiedSlug, 
  generateFieldCode, 
  getQuickCode, 
  copyToClipboard 
} = useCodeGenerator()

const showDropdown = ref(false)
const showAllSnippets = ref(false)

const snippets = computed(() => generateFieldCode(props.field))
const quickCode = computed(() => getQuickCode(props.field))
const isCopied = computed(() => copiedSlug.value === props.field.slug)

async function handleQuickCopy() {
  await copyToClipboard(quickCode.value, props.field.slug)
}

async function handleCopySnippet(code: string) {
  await copyToClipboard(code, props.field.slug)
}

function toggleDropdown() {
  showDropdown.value = !showDropdown.value
  if (!showDropdown.value) {
    showAllSnippets.value = false
  }
}

function closeDropdown() {
  showDropdown.value = false
  showAllSnippets.value = false
}
</script>

<template>
  <div class="copy-code-wrapper" v-click-outside="closeDropdown">
    <!-- Compact mode: single button -->
    <button
      v-if="compact"
      class="btn btn-link btn-sm copy-code-btn"
      :class="{ copied: isCopied }"
      @click.stop="handleQuickCopy"
      :title="isCopied ? 'Copied!' : `Copy ${selectedLanguage} code`"
    >
      <span class="material-icons">
        {{ isCopied ? 'check' : 'content_copy' }}
      </span>
    </button>

    <!-- Full mode: button with dropdown -->
    <div v-else class="copy-code-dropdown">
      <button
        class="btn btn-outline-secondary btn-sm copy-code-trigger"
        :class="{ active: showDropdown }"
        @click.stop="toggleDropdown"
      >
        <span class="material-icons">code</span>
        <span class="btn-text">Copy Code</span>
        <span class="material-icons dropdown-arrow">
          {{ showDropdown ? 'expand_less' : 'expand_more' }}
        </span>
      </button>

      <div v-if="showDropdown" class="dropdown-menu show">
        <!-- Language selector -->
        <div class="dropdown-header">
          <span>Language</span>
          <div class="btn-group btn-group-sm">
            <button 
              class="btn btn-xs"
              :class="selectedLanguage === 'smarty' ? 'btn-primary' : 'btn-outline-secondary'"
              @click="selectedLanguage = 'smarty'"
            >
              Smarty
            </button>
            <button 
              class="btn btn-xs"
              :class="selectedLanguage === 'twig' ? 'btn-primary' : 'btn-outline-secondary'"
              @click="selectedLanguage = 'twig'"
            >
              Twig
            </button>
            <button 
              class="btn btn-xs"
              :class="selectedLanguage === 'shortcode' ? 'btn-primary' : 'btn-outline-secondary'"
              @click="selectedLanguage = 'shortcode'"
            >
              Shortcode
            </button>
          </div>
        </div>

        <div class="dropdown-divider"></div>

        <!-- Quick copy -->
        <div class="quick-copy-section">
          <code class="quick-code" @click="handleQuickCopy">{{ quickCode }}</code>
          <button 
            class="btn btn-sm btn-primary copy-btn"
            :class="{ copied: isCopied }"
            @click="handleQuickCopy"
          >
            <span class="material-icons">
              {{ isCopied ? 'check' : 'content_copy' }}
            </span>
            {{ isCopied ? 'Copied!' : 'Copy' }}
          </button>
        </div>

        <!-- More snippets toggle -->
        <button 
          v-if="snippets.length > 1"
          class="btn btn-link btn-sm show-more-btn"
          @click="showAllSnippets = !showAllSnippets"
        >
          {{ showAllSnippets ? 'Hide' : 'Show all' }} snippets
          <span class="material-icons">
            {{ showAllSnippets ? 'expand_less' : 'expand_more' }}
          </span>
        </button>

        <!-- All snippets -->
        <div v-if="showAllSnippets" class="all-snippets">
          <div 
            v-for="snippet in snippets" 
            :key="snippet.label"
            class="snippet-item"
          >
            <div class="snippet-header">
              <span class="snippet-label">{{ snippet.label }}</span>
              <button 
                class="btn btn-link btn-xs"
                @click="handleCopySnippet(snippet.code)"
              >
                <span class="material-icons">content_copy</span>
              </button>
            </div>
            <pre class="snippet-code"><code>{{ snippet.code }}</code></pre>
            <small v-if="snippet.description" class="snippet-desc">
              {{ snippet.description }}
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.copy-code-wrapper {
  position: relative;
  display: inline-block;
}

.copy-code-btn {
  padding: 0.25rem;
  transition: all 0.2s;
}

.copy-code-btn .material-icons {
  font-size: 16px;
  color: #6c757d;
}

.copy-code-btn:hover .material-icons {
  color: #007bff;
}

.copy-code-btn.copied .material-icons {
  color: #28a745;
}

.copy-code-dropdown {
  position: relative;
}

.copy-code-trigger {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.copy-code-trigger .material-icons {
  font-size: 16px;
}

.dropdown-arrow {
  margin-left: 0.25rem;
}

.dropdown-menu {
  position: absolute;
  right: 0;
  top: 100%;
  min-width: 320px;
  max-width: 400px;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  padding: 0.75rem;
  z-index: 1000;
  margin-top: 0.25rem;
}

.dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.dropdown-header span {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
}

.btn-xs {
  padding: 0.125rem 0.375rem;
  font-size: 0.7rem;
}

.dropdown-divider {
  border-top: 1px solid #e9ecef;
  margin: 0.5rem 0;
}

.quick-copy-section {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 0.5rem;
}

.quick-code {
  flex: 1;
  padding: 0.5rem;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 4px;
  font-size: 0.75rem;
  word-break: break-all;
  cursor: pointer;
  transition: background 0.2s;
}

.quick-code:hover {
  background: #e9ecef;
}

.copy-btn {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  white-space: nowrap;
}

.copy-btn .material-icons {
  font-size: 14px;
}

.copy-btn.copied {
  background: #28a745;
  border-color: #28a745;
}

.show-more-btn {
  width: 100%;
  text-align: center;
  color: #6c757d;
  font-size: 0.75rem;
}

.show-more-btn .material-icons {
  font-size: 16px;
  vertical-align: middle;
}

.all-snippets {
  max-height: 300px;
  overflow-y: auto;
  margin-top: 0.5rem;
}

.snippet-item {
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f0f0f0;
}

.snippet-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.snippet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.snippet-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #6c757d;
}

.snippet-code {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 4px;
  padding: 0.5rem;
  margin: 0;
  font-size: 0.7rem;
  overflow-x: auto;
}

.snippet-desc {
  color: #6c757d;
  display: block;
  margin-top: 0.25rem;
}
</style>
