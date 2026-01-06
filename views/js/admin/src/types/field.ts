/**
 * ACF Field Definition
 */
export interface AcfField {
  id?: number
  id_wepresta_acf_field?: number
  uuid: string
  groupId?: number
  id_wepresta_acf_group?: number
  parentId?: number | null  // For subfields in repeaters
  type: string
  title: string  // Default title (fallback)
  slug: string
  instructions?: string | null  // Default instructions (fallback)
  config: FieldConfig
  validation: FieldValidation
  conditions: FieldConditions
  wrapper: FieldWrapper
  foOptions: FieldFrontendOptions
  position: number
  value_translatable?: boolean  // Whether field VALUES are translatable (not metadata)
  valueTranslatable?: boolean   // Alias for camelCase
  translatable?: boolean        // Legacy support - maps to value_translatable
  translations?: FieldTranslations  // Multilingual metadata (title, instructions, placeholder)
  active: boolean
  dateAdd?: string
  dateUpd?: string
  children?: AcfField[]  // Subfields for repeaters
}

/**
 * Field translations for metadata (title, instructions, placeholder)
 * Key: language ISO code (e.g., 'en', 'fr', 'es')
 */
export interface FieldTranslations {
  [langCode: string]: {
    title: string
    instructions?: string
    placeholder?: string
  }
}

/**
 * Field type-specific configuration
 */
export interface FieldConfig {
  placeholder?: string
  defaultValue?: string | number | boolean | null
  choices?: string // Newline-separated "value : Label" pairs
  allowMultiple?: boolean
  min?: number
  max?: number
  step?: number
  prefix?: string
  suffix?: string
  rows?: number
  [key: string]: unknown
}

/**
 * Field validation rules
 */
export interface FieldValidation {
  required?: boolean
  minLength?: number
  maxLength?: number
  min?: number
  max?: number
  pattern?: string
  message?: string
}

/**
 * Field conditional display/behavior rules
 */
export interface FieldConditions {
  show?: JsonLogicRule
  required?: JsonLogicRule
  disabled?: JsonLogicRule
}

/**
 * Field HTML wrapper options
 */
export interface FieldWrapper {
  class?: string
  id?: string
  width?: '100' | '75' | '50' | '33' | '25'
}

/**
 * Field front-end rendering options
 */
export interface FieldFrontendOptions {
  visible?: boolean
  template?: string
  wrapperClass?: string
  beforeContent?: string
  afterContent?: string
}

/**
 * JSONLogic rule structure
 */
export type JsonLogicRule = Record<string, unknown>

/**
 * Field type definition from registry
 */
export interface FieldTypeDefinition {
  type: string
  label: string
  icon: string
  category: 'basic' | 'choice' | 'content' | 'media' | 'relational' | 'layout' | 'custom'
  source?: 'core' | 'theme' | 'uploaded'
}

/**
 * Field type categories for the type selector
 */
export const fieldTypeCategories = {
  basic: 'Basic',
  choice: 'Choice',
  content: 'Content',
  media: 'Media',
  relational: 'Relational',
  layout: 'Layout',
  custom: 'Custom',
} as const
