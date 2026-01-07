import type { AcfField, JsonLogicRule } from './field'

/**
 * ACF Field Group
 */
export interface AcfGroup {
  id?: number
  id_wepresta_acf_group?: number
  uuid: string
  title: string
  slug: string
  description?: string | null
  locationRules: JsonLogicRule[]
  placementTab: string
  placementPosition?: string | null
  priority: number
  boOptions: GroupBackendOptions
  foOptions: GroupFrontendOptions
  active: boolean
  fields?: AcfField[]
  fieldCount?: number
  dateAdd?: string
  dateUpd?: string
}

/**
 * Group back-office display options
 */
export interface GroupBackendOptions {
  collapsible?: boolean
  collapsed?: boolean
}

/**
 * Group front-office display options
 */
export interface GroupFrontendOptions {
  visible?: boolean
  template?: string
  wrapperClass?: string
  displayHooks?: Record<string, string>  // Map of entity_type => hook_name (e.g., {product: 'displayProductAdditionalInfo', category: 'displayCategoryHeader'})
  valueScope?: 'global' | 'entity'  // global: shared values (entity_id=0), entity: per-entity values (default)
}

/**
 * Product tab option for placement
 */
export interface ProductTab {
  value: string
  label: string
}

/**
 * Layout width options
 */
export interface LayoutOption {
  value: string
  label: string
}

/**
 * Layout configuration from backend
 */
export interface LayoutOptions {
  widths: LayoutOption[]
  positions: LayoutOption[]
}

/**
 * Front-office hook option (for presentation settings)
 */
export interface FrontHookOption {
  value: string
  label: string
  description?: string
  ps_version?: number
}
