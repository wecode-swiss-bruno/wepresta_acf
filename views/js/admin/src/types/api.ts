import type { AcfGroup, AcfField } from './index'

/**
 * Standard API response wrapper
 */
export interface ApiResponse<T> {
  success: boolean
  data?: T
  error?: string
  message?: string
}

/**
 * Groups list response
 */
export type GroupsListResponse = ApiResponse<AcfGroup[]>

/**
 * Single group response
 */
export type GroupResponse = ApiResponse<AcfGroup>

/**
 * Single field response
 */
export type FieldResponse = ApiResponse<AcfField>

/**
 * Slug generation response
 */
export interface SlugifyResponse {
  success: boolean
  data?: {
    slug: string
  }
  error?: string
}

/**
 * Field types list response
 */
export interface FieldTypesResponse {
  success: boolean
  data?: Array<{
    type: string
    label: string
    icon: string
    category: string
  }>
  error?: string
}

/**
 * Window configuration injected by PHP
 */
export interface LocationOption {
  type: string
  value: string
  label: string
  group: string
  icon?: string
  description?: string
  provider: string
  integration_type?: 'symfony' | 'legacy'
  enabled?: boolean
}

export interface Language {
  id: number
  code: string
  name: string
  is_default: boolean
}

export interface AcfConfig {
  apiUrl: string
  token: string
  entityId?: number
  translations: Record<string, string>
  languages: Language[]
  defaultLangId: string
  currentLangId: string
  shopId: number
  fieldTypes: Array<{
    type: string
    label: string
    icon: string
    category: string
  }>
  locations: Record<string, LocationOption[]>
  productTabs: Array<{
    value: string
    label: string
  }>
  layoutOptions: {
    widths: Array<{ value: string; label: string }>
    positions: Array<{ value: string; label: string }>
  }
}

// Extend Window interface
declare global {
  interface Window {
    acfConfig: AcfConfig
  }
}
