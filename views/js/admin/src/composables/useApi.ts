import type { AcfGroup, AcfField, ApiResponse, GroupsListResponse, GroupResponse, FieldResponse, SlugifyResponse } from '@/types'

/**
 * API composable for WePresta ACF REST endpoints
 */
export class ApiError extends Error {
  errors?: Record<string, string[]> // Field ID or key -> error messages

  constructor(message: string, errors?: Record<string, string[]>) {
    super(message)
    this.name = 'ApiError'
    this.errors = errors
  }
}

/**
 * API composable for WePresta ACF REST endpoints
 */
export function useApi() {
  const config = window.acfConfig

  /**
   * Make an API request with proper headers
   */
  async function request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    // Ensure endpoint starts with / and apiUrl doesn't end with /
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`
    const cleanApiUrl = config.apiUrl.endsWith('/') ? config.apiUrl.slice(0, -1) : config.apiUrl
    let url = `${cleanApiUrl}${cleanEndpoint}`

    // IMPORTANT: Add CSRF token to ALL requests (GET, POST, PUT, DELETE)
    // PrestaShop 8 with "Protection des jetons" validates the Symfony CSRF token
    // The parameter MUST be '_token' (with underscore) for PS8 Symfony routes
    const separator = url.includes('?') ? '&' : '?'
    url = `${url}${separator}_token=${config.token}`

    // Add timeout to prevent hanging requests
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), 10000) // 10 second timeout

    let response: Response
    try {
      response = await fetch(url, {
        ...options,
        headers: {
          'Content-Type': 'application/json',
          ...options.headers,
        },
        signal: controller.signal,
      })
      clearTimeout(timeoutId)
    } catch (error: any) {
      clearTimeout(timeoutId)
      if (error.name === 'AbortError') {
        throw new Error('Request timeout - API server may be unavailable')
      }
      throw error
    }

    const data = await response.json()

    if (!response.ok || data.success === false) {
      // Pass the detailed errors object if available
      throw new ApiError(
        data.error || data.message || 'API request failed',
        data.errors
      )
    }

    return data
  }

  // Groups API
  async function getGroups(): Promise<AcfGroup[]> {
    const response = await request<GroupsListResponse>('/groups')
    return response.data || []
  }

  async function getGroup(id: number): Promise<AcfGroup> {
    const response = await request<GroupResponse>(`/groups/${id}`)
    if (!response.data) {
      throw new Error('Group not found')
    }
    return response.data
  }

  async function createGroup(data: Partial<AcfGroup>): Promise<AcfGroup> {
    const response = await request<GroupResponse>('/groups', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!response.data) {
      throw new Error('Failed to create group')
    }
    return response.data
  }

  async function updateGroup(id: number, data: Partial<AcfGroup>): Promise<AcfGroup> {
    const response = await request<GroupResponse>(`/groups/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!response.data) {
      throw new Error('Failed to update group')
    }
    return response.data
  }

  async function deleteGroup(id: number): Promise<void> {
    await request<ApiResponse<void>>(`/groups/${id}`, {
      method: 'DELETE',
    })
  }

  async function duplicateGroup(id: number): Promise<AcfGroup> {
    const response = await request<GroupResponse>(`/groups/${id}/duplicate`, {
      method: 'POST',
    })
    if (!response.data) {
      throw new Error('Failed to duplicate group')
    }
    return response.data
  }

  // Fields API
  async function createField(groupId: number, data: Partial<AcfField>): Promise<AcfField> {
    const response = await request<FieldResponse>(`/groups/${groupId}/fields`, {
      method: 'POST',
      body: JSON.stringify(data),
    })
    if (!response.data) {
      throw new Error('Failed to create field')
    }
    return response.data
  }

  async function updateField(id: number, data: Partial<AcfField>): Promise<AcfField> {
    const response = await request<FieldResponse>(`/fields/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    if (!response.data) {
      throw new Error('Failed to update field')
    }
    return response.data
  }

  async function deleteField(id: number): Promise<void> {
    await request<ApiResponse<void>>(`/fields/${id}`, {
      method: 'DELETE',
    })
  }

  async function reorderFields(groupId: number, order: number[]): Promise<void> {
    await request<ApiResponse<void>>(`/groups/${groupId}/fields/reorder`, {
      method: 'POST',
      body: JSON.stringify({ order }),
    })
  }

  // Utilities API
  async function slugify(text: string): Promise<string> {
    const response = await request<SlugifyResponse>('/slugify', {
      method: 'POST',
      body: JSON.stringify({ text }),
    })
    return response.data?.slug || ''
  }

  /**
   * Get available front-office hooks for a specific entity type
   */

  /**
   * Get global values for a group (entity_id = 0)
   */
  async function getGlobalValues(groupId: number): Promise<{ entityType: string, values: Record<string, any> }> {
    const response = await request<{ success: boolean, data: { entityType: string, values: Record<string, any> } }>(`/groups/${groupId}/global-values`)
    return response.data
  }

  /**
   * Save global values for a group (entity_id = 0)
   */
  async function saveGlobalValues(groupId: number, values: Record<string, any>): Promise<void> {
    await request(`/groups/${groupId}/global-values`, {
      method: 'POST',
      body: JSON.stringify({ values }),
    })
  }

  /**
   * Save field values for an entity
   */
  async function saveEntityValues(data: {
    entityType: string
    entityId: number
    values: Record<number | string, any>
    shopId?: number
    langId?: number
  }): Promise<any> {
    return await request('/values', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  /**
   * Generic fetchJson method for custom endpoints
   */
  async function fetchJson<T = any>(endpoint: string, options: RequestInit = {}): Promise<T> {
    return request<T>(endpoint, options)
  }

  return {
    // Groups
    getGroups,
    getGroup,
    createGroup,
    updateGroup,
    deleteGroup,
    duplicateGroup,
    getGlobalValues,
    saveGlobalValues,
    // Fields
    createField,
    updateField,
    deleteField,
    reorderFields,
    // Utilities
    slugify,
    fetchJson,
    // CPT
    getCpts: async (slug: string, params: any = {}) => {
      const qs = new URLSearchParams(params).toString()
      const url = `/cpt/${slug}/posts${qs ? '?' + qs : ''}`
      const response = await request<any>(url)
      return response.data
    },
    getCpt: async (id: number) => {
      const response = await request<any>(`/cpt/posts/${id}`)
      return response.data
    },
    createCpt: async (slug: string, data: any) => {
      const response = await request<any>(`/cpt/${slug}/posts`, {
        method: 'POST',
        body: JSON.stringify(data),
      })
      return response.data
    },
    updateCpt: async (id: number, data: any) => {
      const response = await request<any>(`/cpt/posts/${id}`, {
        method: 'PUT',
        body: JSON.stringify(data),
      })
      return response.data
    },
    deleteCpt: async (id: number) => {
      await request<any>(`/cpt/posts/${id}`, { method: 'DELETE' })
    },
    saveEntityValues,
  }
}
