<script setup lang="ts">
import type { FieldConfig } from '@/types'
import { useTranslations } from '@/composables/useTranslations'
import { useFieldConfig } from '@/composables/useFieldConfig'

const props = defineProps<{
  config: FieldConfig
}>()

const emit = defineEmits<{
  'update:config': [config: FieldConfig]
}>()

const { t } = useTranslations()
const { createBooleanRef } = useFieldConfig(props, emit)

// Local reactive values using the composable
const showHex = createBooleanRef('showHex', true)
</script>

<template>
  <div class="color-field-config">
    <div class="form-group">
      <div class="form-check">
        <input
          v-model="showHex"
          type="checkbox"
          class="form-check-input"
          id="showHex"
        >
        <label class="form-check-label" for="showHex">
          Show Hex Value
        </label>
      </div>
      <small class="form-text text-muted">
        Display the hex code alongside the color swatch on the frontend.
      </small>
    </div>
  </div>
</template>

<style scoped>
.acf-color-input-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.acf-color-picker {
  width: 60px;
  height: 38px;
  padding: 2px;
  cursor: pointer;
}
</style>

