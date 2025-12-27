import type { AcfGroup, AcfField, ApiResponse, GroupsListResponse, GroupResponse, FieldResponse, SlugifyResponse } from '@/types'

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
    const url = `${config.apiUrl}${endpoint}`
    
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': config.token,
        ...options.headers,
      },
    })

    const data = await response.json()

    if (!response.ok || data.success === false) {
      throw new Error(data.error || data.message || 'API request failed')
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

  return {
    // Groups
    getGroups,
    getGroup,
    createGroup,
    updateGroup,
    deleteGroup,
    duplicateGroup,
    // Fields
    createField,
    updateField,
    deleteField,
    reorderFields,
    // Utilities
    slugify,
  }
}
