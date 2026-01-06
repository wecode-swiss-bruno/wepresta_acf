import { ref, watch, type Ref } from 'vue'
import type { FieldConfig } from '@/types'

/**
 * Composable for managing field configuration with reactive local state.
 * Handles bidirectional sync between props and local refs.
 */
export function useFieldConfig(
  props: { config: FieldConfig },
  emit: (event: 'update:config', config: FieldConfig) => void
) {
  /**
   * Update a specific config key and emit the full config.
   */
  function updateConfig(key: keyof FieldConfig, value: unknown): void {
    emit('update:config', { ...props.config, [key]: value })
  }

  /**
   * Create a local ref for a string config value with automatic sync.
   * @param key Config key to sync
   * @param defaultValue Default value if config key is empty
   */
  function createStringRef(key: keyof FieldConfig, defaultValue = ''): Ref<string> {
    const local = ref((props.config[key] as string) || defaultValue)

    // Sync from props to local
    watch(() => props.config[key], (newVal) => {
      local.value = (newVal as string) || defaultValue
    })

    // Sync from local to props
    watch(local, (newVal) => {
      updateConfig(key, newVal || undefined)
    })

    return local
  }

  /**
   * Create a local ref for a number config value with automatic sync.
   * @param key Config key to sync
   */
  function createNumberRef(key: keyof FieldConfig): Ref<string> {
    const local = ref(
      (props.config[key] !== null && props.config[key] !== undefined)
        ? String(props.config[key])
        : ''
    )

    // Sync from props to local
    watch(() => props.config[key], (newVal) => {
      local.value = (newVal !== null && newVal !== undefined) ? String(newVal) : ''
    })

    // Sync from local to props
    watch(local, (newVal) => {
      const numVal = newVal === '' ? undefined : Number(newVal)
      updateConfig(key, numVal)
    })

    return local
  }

  /**
   * Create a local ref for a boolean config value with automatic sync.
   * Handles PsSwitch values (strings "0"/"1" or booleans).
   * @param key Config key to sync
   * @param defaultValue Default boolean value
   */
  function createBooleanRef(key: keyof FieldConfig, defaultValue = false): Ref<boolean> {
    const local = ref(props.config[key] === true || props.config[key] === '1')

    // Sync from props to local
    watch(() => props.config[key], (newVal) => {
      local.value = newVal === true || newVal === '1'
    })

    // Sync from local to props (ensure boolean conversion)
    watch(local, (newVal) => {
      const boolVal = !!newVal
      updateConfig(key, boolVal)
    })

    return local
  }

  return {
    updateConfig,
    createStringRef,
    createNumberRef,
    createBooleanRef,
  }
}
