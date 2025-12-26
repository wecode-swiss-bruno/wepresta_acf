<script setup lang="ts">
import { computed } from 'vue'
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import PsSwitch from '@/components/ui/PsSwitch.vue'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()

const defaultValue = computed({
  get: () => props.config.defaultValue === true,
  set: (value: boolean) => emit('update:config', { ...props.config, defaultValue: value })
})

function updateConfig(key: keyof FieldConfig, value: unknown): void {
  emit('update:config', { ...props.config, [key]: value })
}
</script>

<template>
  <div class="boolean-field-config">
    <div class="form-group">
      <label class="form-control-label">{{ t('defaultValue') }}</label>
      <PsSwitch
        v-model="defaultValue"
        id="boolean-default"
      />
      <small class="form-text text-muted">Default to checked</small>
    </div>

    <div class="form-group">
      <label class="form-control-label">True Label</label>
      <input
        type="text"
        class="form-control"
        :value="config.trueLabel || 'Yes'"
        @input="updateConfig('trueLabel', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Text to display when value is true/checked.
      </small>
    </div>

    <div class="form-group">
      <label class="form-control-label">False Label</label>
      <input
        type="text"
        class="form-control"
        :value="config.falseLabel || 'No'"
        @input="updateConfig('falseLabel', ($event.target as HTMLInputElement).value)"
      >
      <small class="form-text text-muted">
        Text to display when value is false/unchecked.
      </small>
    </div>
  </div>
</template>

<style scoped>
/* Global .ps-switch styles from admin.scss */
</style>

