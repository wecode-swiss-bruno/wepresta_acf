/**
 * TypeScript types for CPT module
 */

export interface CptType {
  id?: number
  uuid?: string
  slug: string
  name: string | Record<number, string> // string (legacy) or localized map
  description?: string | Record<number, string> // string (legacy) or localized map
  config?: Record<string, any>
  url_prefix: string
  has_archive: boolean
  archive_slug?: string
  seo_config?: {
    title_pattern?: string
    description_pattern?: string
  }
  icon?: string
  position?: number
  active?: boolean
  acf_groups?: number[]
  taxonomies?: number[]
  translations?: Record<number, { name: string; description: string }>
  date_add?: string
  date_upd?: string
}

export interface CptPost {
  id?: number
  uuid?: string
  type_id: number
  slug: string
  title: string
  status: 'draft' | 'published'
  employee_id?: number
  seo_title?: string
  seo_description?: string
  seo_meta?: Record<string, any>
  terms?: number[]
  date_add?: string
  date_upd?: string
}

export interface CptTaxonomy {
  id?: number
  uuid?: string
  slug: string
  name: string
  description?: string
  hierarchical?: boolean
  config?: Record<string, any>
  active?: boolean
  terms?: CptTerm[]
  translations?: Record<number, { name: string; description: string }>
  date_add?: string
  date_upd?: string
}

export interface CptTerm {
  id?: number
  taxonomy_id: number
  parent_id?: number | null
  slug: string
  name: string
  description?: string
  position?: number
  active?: boolean
  children?: CptTerm[]
  post_count?: number
  translations?: Record<number, { name: string; description: string }>
}

export interface CptRelation {
  id?: number
  uuid?: string
  source_type_id: number
  target_type_id: number
  slug: string
  name: string
  config?: Record<string, any>
  active?: boolean
}

export interface CptConfig {
  supports?: string[] // 'title', 'slug', 'featured_image', etc.
  labels?: {
    singular: string
    plural: string
    add_new: string
    edit_item: string
  }
  capabilities?: string[]
}
