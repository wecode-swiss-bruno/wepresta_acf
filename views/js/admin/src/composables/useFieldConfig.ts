import { ref, watch, type Ref } from 'vue'
import type { FieldConfig } from '@/types'

/**
 * Composable pour gérer la synchronisation bidirectionnelle des propriétés de configuration
 * Évite la duplication de code dans les composants FieldConfig
 */
export function useFieldConfig(
  props: { config: FieldConfig },
  emit: (event: 'update:config', config: FieldConfig) => void
) {
  /**
   * Met à jour une propriété de configuration
   */
  function updateConfig(key: keyof FieldConfig, value: unknown): void {
    emit('update:config', { ...props.config, [key]: value })
  }

  /**
   * Crée un ref local avec synchronisation bidirectionnelle
   * - Prop -> Ref : quand la prop change, le ref est mis à jour
   * - Ref -> Prop : quand le ref change, on émet une mise à jour
   *
   * @param key Clé de la config à synchroniser
   * @param defaultValue Valeur par défaut si undefined
   * @param transform Fonction de transformation avant émission (ex: String -> Number)
   * @returns Ref synchronisé
   */
  function createLocalRef<K extends keyof FieldConfig>(
    key: K,
    defaultValue: FieldConfig[K] = undefined,
    transform?: (value: any) => any
  ): Ref<any> {
    const localRef = ref(props.config[key] ?? defaultValue)

    // Sync prop -> ref
    watch(
      () => props.config[key],
      (newVal) => {
        localRef.value = newVal ?? defaultValue
      }
    )

    // Sync ref -> prop (avec transformation optionnelle)
    watch(localRef, (newVal) => {
      const finalValue = transform ? transform(newVal) : (newVal || undefined)
      updateConfig(key, finalValue)
    })

    return localRef
  }

  /**
   * Crée un ref local pour un boolean (gère conversion PsSwitch "0"/"1" -> boolean)
   */
  function createBooleanRef<K extends keyof FieldConfig>(
    key: K,
    defaultValue: boolean = false
  ): Ref<boolean> {
    const localRef = ref<boolean>((props.config[key] as any) === true || (props.config[key] as any) === '1' || (props.config[key] as any) === 1 || defaultValue)

    // Sync prop -> ref (avec conversion en boolean)
    watch(
      () => props.config[key],
      (newVal) => {
        localRef.value = newVal === true || newVal === '1' || newVal === 1 || defaultValue
      }
    )

    // Sync ref -> prop (force conversion en boolean)
    watch(localRef, (newVal) => {
      updateConfig(key, !!newVal)
    })

    return localRef
  }

  /**
   * Crée un ref local pour un nombre (gère conversion string <-> number)
   */
  function createNumberRef<K extends keyof FieldConfig>(
    key: K,
    defaultValue: number | '' = ''
  ): Ref<string | number> {
    const localRef = ref<string | number>(
      props.config[key] !== null && props.config[key] !== undefined
        ? String(props.config[key])
        : defaultValue
    )

    // Sync prop -> ref
    watch(
      () => props.config[key],
      (newVal) => {
        localRef.value = newVal !== null && newVal !== undefined ? String(newVal) : defaultValue
      }
    )

    // Sync ref -> prop (convertit en number ou undefined)
    watch(localRef, (newVal) => {
      const numVal = newVal === '' || newVal === null ? undefined : Number(newVal)
      updateConfig(key, numVal)
    })

    return localRef
  }

  return {
    updateConfig,
    createLocalRef,
    createBooleanRef,
    createNumberRef,
  }
}

