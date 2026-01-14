<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  saving: boolean
  lastSaved: Date | null
  error: string | string[] | null
}>()

const emit = defineEmits<{
  (e: 'save'): void
}>()

// ...

const statusText = computed(() => {
  if (props.saving) return 'Saving changes...'
  if (props.error) {
    if (Array.isArray(props.error)) {
        return props.error.length + ' errors found'
    }
    return props.error
  }
  if (props.lastSaved) {
    return `Last saved: ${props.lastSaved.toLocaleTimeString()}`
  }
  return 'Unsaved changes'
})
// ...

// Update template to show error details on hover or in a list
/* 
In template: 
<div class="status-info" :title="errorMessageList">
...
<div v-if="hasMultipleErrors" class="error-list-tooltip">
  <ul><li v-for="err in error">{{ err }}</li></ul>
</div>
*/

const errorMessageList = computed(() => {
    if (Array.isArray(props.error)) {
        return props.error.join('\n')
    }
    return props.error || ''
})

const hasMultipleErrors = computed(() => Array.isArray(props.error) && props.error.length > 0)


const statusIcon = computed(() => {
  if (props.saving) return 'sync'
  if (props.error) return 'error'
  if (props.lastSaved) return 'check_circle'
  return 'cloud_upload'
})
</script>

<template>
  <div class="acfps-save-toolbar">
    <div class="acfps-status-indicator" :class="{ 'has-error': error, 'is-saving': saving, 'is-success': lastSaved && !saving && !error }">
      <div class="status-icon-wrapper">
        <i class="material-icons" :class="{ spinning: saving }">{{ statusIcon }}</i>
      </div>
      <div class="status-info" :title="errorMessageList">
        <span class="status-label">{{ statusText }}</span>
        
        <!-- Show first error if array -->
        <span v-if="hasMultipleErrors" class="status-detail status-error-detail" v-html="Array.isArray(error) ? error[0] : ''">
        </span>
        
        <span v-if="lastSaved && !saving && !error" class="status-time">Success</span>
      </div>
    </div>
    
    <button 
      type="button" 
      class="acfps-premium-save-btn" 
      :disabled="saving"
      @click="emit('save')"
    >
      <div class="btn-background"></div>
      <div class="btn-content">
        <i class="material-icons">{{ saving ? 'hourglass_empty' : 'save' }}</i>
        <span>{{ saving ? 'Saving...' : 'Save Changes' }}</span>
      </div>
    </button>
  </div>
</template>

<style scoped>
.acfps-save-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 12px 20px;
  background: #ffffff;
  border-bottom: 1px solid var(--acf-border);
  border-radius: var(--acf-radius) var(--acf-radius) 0 0;
  margin-bottom: 24px;
  box-shadow: var(--acf-shadow);
}

.acfps-status-indicator {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 16px;
  background: var(--acf-bg-light);
  border-radius: 20px;
  border: 1px solid var(--acf-border);
  transition: all 0.3s ease;
}

.acfps-status-indicator.is-saving {
  background: var(--acf-primary-light);
  border-color: #b8ebf4;
  color: var(--acf-primary);
}

.acfps-status-indicator.is-success {
  background: var(--acf-success-bg);
  border-color: #c6f6d5;
  color: var(--acf-success);
}

.acfps-status-indicator.has-error {
  background: var(--acf-danger-bg);
  border-color: #fed7d7;
  color: var(--acf-danger);
}

.status-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-icon-wrapper .material-icons {
  font-size: 18px;
}

.status-info {
  display: flex;
  flex-direction: column;
}

.status-label {
  font-size: 12px;
  font-weight: 600;
  line-height: 1.2;
}

.status-time {
  font-size: 10px;
  opacity: 0.8;
}

.status-error-detail {
  font-size: 10px;
  opacity: 0.9;
  color: var(--acf-danger);
  line-height: 1.4;
  margin-top: 2px;
}

.status-error-detail strong {
    font-weight: 700;
    color: inherit;
}

.acfps-premium-save-btn {
  position: relative;
  border: none;
  background: transparent;
  padding: 0;
  cursor: pointer;
  outline: none;
  border-radius: 6px;
  overflow: hidden;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, var(--acf-primary) 0%, var(--acf-primary-hover) 100%);
  transition: opacity 0.3s ease;
}

.acfps-premium-save-btn:hover:not(:disabled) .btn-background {
  opacity: 0.9;
}

.acfps-premium-save-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  color: white;
  font-weight: 600;
  font-size: 14px;
}

.btn-content .material-icons {
  font-size: 20px;
}

.acfps-premium-save-btn:disabled {
  cursor: not-allowed;
  filter: grayscale(0.5) opacity(0.7);
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Material Icons local helper - Moved to acf-admin.css */
.material-icons {
  font-family: 'Material Icons';
  font-weight: normal;
  font-style: normal;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
  -webkit-font-smoothing: antialiased;
}
</style>
