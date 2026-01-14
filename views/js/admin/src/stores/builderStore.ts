import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { AcfGroup, AcfField, FieldTypeDefinition } from '@/types'
import { useApi } from '@/composables/useApi'

export type ViewMode = 'list' | 'edit'

/**
 * Normalize group data to ensure boOptions are objects, not arrays.
 * This handles legacy data that might have been stored as arrays.
 */
function normalizeGroup(group: AcfGroup): AcfGroup {
  return {
    ...group,
    boOptions: Array.isArray(group.boOptions) ? {} : (group.boOptions || {}),
  }
}

export const useBuilderStore = defineStore('builder', () => {
  const api = useApi()

  // State
  const groups = ref<AcfGroup[]>([])
  const currentGroup = ref<AcfGroup | null>(null)
  const selectedField = ref<AcfField | null>(null)
  const viewMode = ref<ViewMode>('list')
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)
  const successMessage = ref<string | null>(null)
  const currentUiLangId = ref<number>(parseInt((window as any).acfConfig?.currentLangId || (window as any).weprestaAcfConfig?.currentLangId) || 1)

  /**
   * Set global UI language for translatable fields
   */
  function setCurrentUiLangId(id: number): void {
    currentUiLangId.value = id
  }
  // Getters
  const activeGroups = computed(() =>
    groups.value.filter(g => g.active)
  )

  const currentFields = computed(() =>
    currentGroup.value?.fields || []
  )

  const fieldTypes = computed<FieldTypeDefinition[]>(() =>
    window.acfConfig?.fieldTypes || []
  )

  const hasUnsavedChanges = computed(() => {
    if (!currentGroup.value) return false

    // Nouveau groupe pas encore sauvegardé
    if (!currentGroup.value.id && currentGroup.value.title) return true

    // Champs nouveaux non sauvegardés
    const hasNewFields = (currentGroup.value.fields || []).some(f => !f.id && f.title.trim())

    return hasNewFields
  })

  // Actions
  async function loadGroups(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const loadedGroups = await api.getGroups()
      groups.value = loadedGroups.map(normalizeGroup)
    } catch (e) {
      error.value = (e as Error).message || 'Failed to load groups'
    } finally {
      loading.value = false
    }
  }

  async function loadGroup(id: number): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const loadedGroup = await api.getGroup(id)
      currentGroup.value = normalizeGroup(loadedGroup)
      viewMode.value = 'edit'
    } catch (e) {
      error.value = (e as Error).message
    } finally {
      loading.value = false
    }
  }

  function createNewGroup(): void {
    currentGroup.value = {
      uuid: crypto.randomUUID(),
      title: '',
      slug: '',
      description: null,
      locationRules: [],
      placementTab: 'extra',
      placementPosition: null,
      priority: 10,
      boOptions: {},
      active: true,
      fields: [],
    }
    selectedField.value = null
    viewMode.value = 'edit'
  }

  async function saveGroup(): Promise<void> {
    if (!currentGroup.value) return

    // ✅ VALIDATION: Vérifier le titre du groupe
    if (!currentGroup.value.title?.trim()) {
      error.value = '❌ Group title is required. Please fill the group title in the Settings tab.'
      return
    }

    // ✅ VALIDATION: Vérifier les champs incomplets
    const invalidFields = (currentGroup.value.fields || [])
      .filter(f => !f.title?.trim())

    if (invalidFields.length > 0) {
      error.value = `❌ ${invalidFields.length} field(s) missing title. Please fill all field titles before saving.`
      // Sélectionner automatiquement le premier champ invalide
      if (invalidFields[0]) {
        selectedField.value = invalidFields[0]
      }
      return
    }

    saving.value = true
    error.value = null
    try {
      let groupId = currentGroup.value.id

      if (groupId) {
        // Update existing group
        const updated = await api.updateGroup(groupId, currentGroup.value)
        const normalizedUpdated = normalizeGroup(updated)

        // Merge updated group data but keep local fields for now
        const localFields = currentGroup.value.fields || []
        currentGroup.value = { ...normalizedUpdated, fields: localFields }

        // Update in list
        const index = groups.value.findIndex(g => g.id === normalizedUpdated.id)
        if (index !== -1) {
          groups.value[index] = normalizedUpdated
        }
      } else {
        // Create new group first
        const created = await api.createGroup(currentGroup.value)
        const normalizedCreated = normalizeGroup(created)
        groupId = normalizedCreated.id
        const localFields = currentGroup.value.fields || []
        currentGroup.value = { ...normalizedCreated, fields: localFields }
        groups.value.push(normalizedCreated)
      }

      // Now save all fields that need saving (including subfields)
      const fieldsToSave = currentGroup.value.fields || []
      for (const field of fieldsToSave) {
        // Les champs ont déjà été validés ci-dessus
        if (!field.title.trim()) continue

        let savedField: AcfField

        if (field.id) {
          // Update existing field
          savedField = await api.updateField(field.id, field)
        } else if (groupId) {
          // Create new field
          savedField = await api.createField(groupId, field)
        } else {
          continue
        }

        // Update field in local state
        const idx = currentGroup.value.fields!.findIndex(f => f.uuid === field.uuid)
        if (idx !== -1) {
          // Preserve local children before overwriting
          const localChildren = field.children || []
          currentGroup.value.fields![idx] = { ...savedField, children: localChildren }
        }

        // Save subfields for repeater fields
        if (field.type === 'repeater' && field.children && field.children.length > 0 && savedField.id) {
          const savedChildren: AcfField[] = []

          for (const subfield of field.children) {
            if (!subfield.title.trim()) continue

            // Set the parent ID to the saved repeater's ID
            const subfieldData = { ...subfield, parentId: savedField.id }

            let savedSubfield: AcfField
            if (subfield.id) {
              savedSubfield = await api.updateField(subfield.id, subfieldData)
            } else if (groupId) {
              savedSubfield = await api.createField(groupId, subfieldData)
            } else {
              continue
            }

            savedChildren.push(savedSubfield)
          }

          // Update children in local state
          if (idx !== -1) {
            currentGroup.value.fields![idx].children = savedChildren
          }
        }
      }

      // Update selected field if it was saved
      if (selectedField.value) {
        const updatedSelected = currentGroup.value.fields?.find(f => f.uuid === selectedField.value?.uuid)
        if (updatedSelected) {
          selectedField.value = updatedSelected
        }
      }

      successMessage.value = 'Group saved successfully'
      setTimeout(() => { successMessage.value = null }, 3000)
    } catch (e) {
      error.value = (e as Error).message
    } finally {
      saving.value = false
    }
  }

  async function deleteGroup(id: number): Promise<void> {
    loading.value = true
    error.value = null
    try {
      await api.deleteGroup(id)
      groups.value = groups.value.filter(g => g.id !== id)
      if (currentGroup.value?.id === id) {
        currentGroup.value = null
        viewMode.value = 'list'
      }
      successMessage.value = 'Group deleted successfully'
      setTimeout(() => { successMessage.value = null }, 3000)
    } catch (e) {
      error.value = (e as Error).message
    } finally {
      loading.value = false
    }
  }

  async function duplicateGroup(id: number): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const duplicated = await api.duplicateGroup(id)
      groups.value.push(duplicated)
      successMessage.value = 'Group duplicated successfully'
      setTimeout(() => { successMessage.value = null }, 3000)
    } catch (e) {
      error.value = (e as Error).message
    } finally {
      loading.value = false
    }
  }

  function goToList(): void {
    // ✅ Confirmation si changements non sauvegardés
    if (hasUnsavedChanges.value) {
      if (!confirm('⚠️ You have unsaved changes. Are you sure you want to leave without saving?')) {
        return
      }
    }

    currentGroup.value = null
    selectedField.value = null
    viewMode.value = 'list'
  }

  // Field management
  function selectField(field: AcfField | null): void {
    selectedField.value = field
  }

  // Génère un slug unique en évitant les conflits avec les slugs existants
  function generateUniqueSlug(baseSlug: string, excludeUuid?: string): string {
    const existingSlugs = currentGroup.value?.fields
      ?.filter(f => f.uuid !== excludeUuid && f.slug)
      ?.map(f => f.slug) || []

    // Si le slug de base n'existe pas, le retourner tel quel
    if (!existingSlugs.includes(baseSlug)) {
      return baseSlug
    }

    // Sinon, trouver le prochain numéro disponible
    const similarSlugs = existingSlugs
      .filter(s => s.startsWith(`${baseSlug}_`))
      .map(s => parseInt(s.split('_').pop() || '0', 10))
      .filter(n => !isNaN(n))

    const nextNumber = similarSlugs.length > 0 ? Math.max(...similarSlugs) + 1 : 2
    return `${baseSlug}_${nextNumber}`
  }

  function addField(type: string, parentField?: AcfField): void {
    if (!currentGroup.value) return

    // ✅ Titres par défaut selon le type de champ
    const defaultTitles: Record<string, string> = {
      text: 'Text Field',
      textarea: 'Textarea',
      number: 'Number Field',
      email: 'Email Field',
      url: 'URL Field',
      select: 'Select Field',
      checkbox: 'Checkbox Field',
      radio: 'Radio Field',
      boolean: 'Toggle Field',
      date: 'Date Field',
      time: 'Time Field',
      datetime: 'Datetime Field',
      color: 'Color Picker',
      richtext: 'Rich Text Editor',
      file: 'File Upload',
      files: 'Multiple Files',
      image: 'Image Upload',
      gallery: 'Image Gallery',
      video: 'Video Field',
      relation: 'Relation Field',
      list: 'List Field',
      repeater: 'Repeater Field',
    }

    // Générer le slug de base à partir du titre par défaut
    const defaultTitle = defaultTitles[type] || 'New Field'
    const baseSlug = defaultTitle.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')

    const newField: AcfField = {
      uuid: crypto.randomUUID(),
      type,
      title: defaultTitle,
      slug: generateUniqueSlug(baseSlug), // Générer le slug unique dès la création
      parentId: parentField?.id || null,
      config: {},
      validation: {},
      conditions: {},
      wrapper: { width: '100' },
      position: 0,
      translations: {},  // Initialize empty translations object
      active: true,
    }

    if (parentField) {
      // Add as subfield to parent
      if (!parentField.children) {
        parentField.children = []
      }
      newField.position = parentField.children.length
      parentField.children.push(newField)

      // Update parent in currentGroup - need to find the actual field in the store
      const fieldInStore = currentGroup.value?.fields?.find(f => f.uuid === parentField.uuid)
      if (fieldInStore) {
        fieldInStore.children = parentField.children
      }
    } else {
      // Add as top-level field
      if (!currentGroup.value.fields) {
        currentGroup.value.fields = []
      }
      newField.position = currentGroup.value.fields.length
      currentGroup.value.fields.push(newField)
    }

    selectedField.value = newField
  }

  // Helper to update a field in the current group (including nested)
  function updateFieldInGroup(field: AcfField): void {
    if (!currentGroup.value?.fields) return

    const index = currentGroup.value.fields.findIndex(f => f.uuid === field.uuid)
    if (index !== -1) {
      currentGroup.value.fields[index] = { ...field }
    }
  }

  // Add subfield to a repeater
  function addSubfield(parentField: AcfField, type: string): void {
    addField(type, parentField)
  }


  // Remove subfield from a repeater
  async function removeSubfield(parentField: AcfField, subfield: AcfField): Promise<void> {
    if (!parentField.children) return

    const index = parentField.children.findIndex(f => f.uuid === subfield.uuid)
    if (index === -1) return

    // If subfield has an ID (saved), delete via API
    if (subfield.id) {
      saving.value = true
      try {
        await api.deleteField(subfield.id)
        parentField.children.splice(index, 1)
        updateFieldInGroup(parentField)
        if (selectedField.value?.uuid === subfield.uuid) {
          selectedField.value = parentField
        }
      } catch (e) {
        error.value = (e as Error).message
      } finally {
        saving.value = false
      }
    } else {
      // Just remove locally
      parentField.children.splice(index, 1)
      updateFieldInGroup(parentField)
      if (selectedField.value?.uuid === subfield.uuid) {
        selectedField.value = parentField
      }
    }
  }

  // Reorder subfields within a repeater
  async function reorderSubfields(parentField: AcfField, newOrder: AcfField[]): Promise<void> {
    parentField.children = newOrder.map((field, index) => ({
      ...field,
      position: index,
    }))

    updateFieldInGroup(parentField)

    // If parent is saved, update via API
    if (parentField.id) {
      const fieldIds = newOrder
        .filter(f => f.id !== undefined)
        .map(f => f.id as number)

      if (fieldIds.length > 0) {
        try {
          await api.reorderFields(parentField.id, fieldIds)
        } catch (e) {
          error.value = (e as Error).message
        }
      }
    }
  }

  async function saveField(field: AcfField): Promise<void> {
    if (!currentGroup.value) return

    // Validate required fields before saving
    if (!field.title.trim()) {
      error.value = 'Field title is required'
      return
    }

    const fields = currentGroup.value.fields || []
    const index = fields.findIndex(f => f.uuid === field.uuid)
    if (index === -1) return

    saving.value = true
    error.value = null

    try {
      if (field.id) {
        // Update existing field
        const updated = await api.updateField(field.id, field)
        fields[index] = updated
        if (selectedField.value?.uuid === field.uuid) {
          selectedField.value = updated
        }
      } else if (currentGroup.value.id) {
        // Create new field (group must be saved first)
        const created = await api.createField(currentGroup.value.id, field)
        fields[index] = created
        if (selectedField.value?.uuid === field.uuid) {
          selectedField.value = created
        }
      }
      successMessage.value = 'Field saved successfully'
      setTimeout(() => { successMessage.value = null }, 3000)
    } catch (e) {
      error.value = (e as Error).message
    } finally {
      saving.value = false
    }
  }

  function updateFieldLocal(field: AcfField): void {
    if (!currentGroup.value) return

    const fields = currentGroup.value.fields || []

    // First, try to find in top-level fields
    const index = fields.findIndex(f => f.uuid === field.uuid)

    if (index !== -1) {
      // Update top-level field locally (preserve children)
      const existingChildren = fields[index].children
      fields[index] = { ...field, children: field.children || existingChildren }
      if (selectedField.value?.uuid === field.uuid) {
        selectedField.value = { ...field, children: field.children || existingChildren }
      }
      return
    }

    // If not found in top-level, search in children of repeater fields
    for (const parentField of fields) {
      if (parentField.type === 'repeater' && parentField.children) {
        const childIndex = parentField.children.findIndex(f => f.uuid === field.uuid)
        if (childIndex !== -1) {
          // Update subfield in parent's children array
          parentField.children[childIndex] = { ...field }
          if (selectedField.value?.uuid === field.uuid) {
            selectedField.value = { ...field }
          }
          return
        }
      }
    }
  }

  async function removeField(field: AcfField): Promise<void> {
    if (!currentGroup.value) return

    const fields = currentGroup.value.fields || []
    const index = fields.findIndex(f => f.uuid === field.uuid)

    if (index === -1) return

    // If field has an ID (saved), delete via API
    if (field.id) {
      saving.value = true
      try {
        await api.deleteField(field.id)
        fields.splice(index, 1)
        if (selectedField.value?.uuid === field.uuid) {
          selectedField.value = null
        }
      } catch (e) {
        error.value = (e as Error).message
      } finally {
        saving.value = false
      }
    } else {
      // Just remove locally
      fields.splice(index, 1)
      if (selectedField.value?.uuid === field.uuid) {
        selectedField.value = null
      }
    }
  }

  async function reorderFields(newOrder: AcfField[]): Promise<void> {
    if (!currentGroup.value) return

    // Update local positions
    currentGroup.value.fields = newOrder.map((field, index) => ({
      ...field,
      position: index,
    }))

    // If group is saved, update via API
    if (currentGroup.value.id) {
      const fieldIds = newOrder
        .filter(f => f.id !== undefined)
        .map(f => f.id as number)

      if (fieldIds.length > 0) {
        try {
          await api.reorderFields(currentGroup.value.id, fieldIds)
        } catch (e) {
          error.value = (e as Error).message
        }
      }
    }
  }

  function clearError(): void {
    error.value = null
  }

  function clearSuccess(): void {
    successMessage.value = null
  }

  return {
    // State
    groups,
    currentGroup,
    selectedField,
    viewMode,
    loading,
    saving,
    error,
    successMessage,
    // Getters
    activeGroups,
    currentFields,
    fieldTypes,
    hasUnsavedChanges,
    // Actions
    loadGroups,
    loadGroup,
    createNewGroup,
    saveGroup,
    deleteGroup,
    duplicateGroup,
    goToList,
    selectField,
    addField,
    addSubfield,
    removeSubfield,
    reorderSubfields,
    saveField,
    updateFieldLocal,
    removeField,
    reorderFields,
    clearError,
    clearSuccess,
    currentUiLangId,
    setCurrentUiLangId,
  }
})

