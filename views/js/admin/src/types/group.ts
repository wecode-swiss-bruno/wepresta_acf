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

