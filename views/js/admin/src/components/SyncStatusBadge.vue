<template>
  <span
    v-if="syncEnabled"
    class="sync-status-badge"
    :class="['sync-status-badge--' + statusInfo.color]"
    :title="statusInfo.label"
  >
    <span class="material-icons sync-status-badge__icon">{{ statusInfo.icon }}</span>
    <span v-if="showLabel" class="sync-status-badge__label">{{ statusInfo.label }}</span>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  status: string
  showLabel?: boolean
  syncEnabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showLabel: false,
  syncEnabled: true
})

interface StatusInfo {
  label: string
  icon: string
  color: string
}

const statusInfo = computed<StatusInfo>(() => {
  switch (props.status) {
    case 'synced':
      return {
        label: 'Synced',
        icon: 'check_circle',
        color: 'success'
      }
    case 'modified':
      return {
        label: 'Modified',
        icon: 'edit',
        color: 'warning'
      }
    case 'need_push':
      return {
        label: 'Not in theme',
        icon: 'cloud_upload',
        color: 'info'
      }
    case 'theme_only':
      return {
        label: 'Theme only',
        icon: 'cloud_download',
        color: 'primary'
      }
    case 'conflict':
      return {
        label: 'Conflict',
        icon: 'warning',
        color: 'danger'
      }
    case 'disabled':
      return {
        label: 'Sync disabled',
        icon: 'sync_disabled',
        color: 'secondary'
      }
    default:
      return {
        label: 'Unknown',
        icon: 'help',
        color: 'secondary'
      }
  }
})
</script>

<style scoped>
.sync-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.sync-status-badge__icon {
  font-size: 14px;
}

.sync-status-badge--success {
  background-color: #d4edda;
  color: #155724;
}

.sync-status-badge--warning {
  background-color: #fff3cd;
  color: #856404;
}

.sync-status-badge--info {
  background-color: #d1ecf1;
  color: #0c5460;
}

.sync-status-badge--primary {
  background-color: #cce5ff;
  color: #004085;
}

.sync-status-badge--danger {
  background-color: #f8d7da;
  color: #721c24;
}

.sync-status-badge--secondary {
  background-color: #e2e3e5;
  color: #383d41;
}
</style>

