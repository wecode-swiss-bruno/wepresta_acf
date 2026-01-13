/**
 * Pinia Store for CPT Management
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type {
  CptType,
  CptPost,
  CptTaxonomy,
  CptTerm
} from '../types/cpt'
import { useApi } from '../composables/useApi'

export const useCptStore = defineStore('cpt', () => {
  const { fetchJson } = useApi()

  // State
  const types = ref<CptType[]>([])
  const currentType = ref<CptType | null>(null)
  const taxonomies = ref<CptTaxonomy[]>([])
  const terms = ref<CptTerm[]>([])
  const posts = ref<CptPost[]>([])
  const currentTaxonomy = ref<CptTaxonomy | null>(null)
  const viewMode = ref<'list' | 'edit'>('list')
  const taxonomyViewMode = ref<'list' | 'terms'>('list')
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const activeTypes = computed(() => types.value.filter(t => t.active))
  const sortedTypes = computed(() => [...types.value].sort((a, b) => (a.position || 0) - (b.position || 0)))

  // Actions - Types
  async function fetchTypes() {
    loading.value = true
    error.value = null

    try {
      const data = await fetchJson<{ data: CptType[] }>('/cpt/types')
      types.value = data.data || []
    } catch (e: any) {
      error.value = e.message
      console.error('Error fetching CPT types:', e)
    } finally {
      loading.value = false
    }
  }

  async function fetchType(id: number) {
    loading.value = true
    error.value = null

    try {
      const data = await fetchJson<{ data: CptType }>(`/cpt/types/${id}`)
      currentType.value = data.data
      return data.data
    } catch (e: any) {
      error.value = e.message
      console.error('Error fetching CPT type:', e)
      return null
    } finally {
      loading.value = false
    }
  }

  async function createType(typeData: Partial<CptType>) {
    loading.value = true
    error.value = null

    try {
      const data = await fetchJson<{ data: CptType }>('/cpt/types', {
        method: 'POST',
        body: JSON.stringify(typeData)
      })

      goToList()
      fetchTypes() // Refresh list in background
      return data.data
    } catch (e: any) {
      error.value = e.message
      console.error('Error creating CPT type:', e)
      return null
    } finally {
      loading.value = false
    }
  }

  async function updateType(id: number, typeData: Partial<CptType>) {
    loading.value = true
    error.value = null

    try {
      await fetchJson(`/cpt/types/${id}`, {
        method: 'PUT',
        body: JSON.stringify(typeData)
      })

      goToList()
      fetchTypes() // Refresh list in background
      return true
    } catch (e: any) {
      error.value = e.message
      console.error('Error updating CPT type:', e)
      return false
    } finally {
      loading.value = false
    }
  }

  async function deleteType(id: number) {
    loading.value = true
    error.value = null

    try {
      await fetchJson(`/cpt/types/${id}`, {
        method: 'DELETE'
      })

      await fetchTypes() // Refresh list
      return true
    } catch (e: any) {
      error.value = e.message
      console.error('Error deleting CPT type:', e)
      return false
    } finally {
      loading.value = false
    }
  }

  // Actions - Taxonomies
  async function fetchTaxonomies() {
    try {
      const data = await fetchJson<{ data: CptTaxonomy[] }>('/cpt/taxonomies')
      taxonomies.value = data.data || []
    } catch (e: any) {
      console.error('Error fetching taxonomies:', e)
    }
  }

  async function createTaxonomy(taxonomyData: Partial<CptTaxonomy>) {
    try {
      await fetchJson('/cpt/taxonomies', {
        method: 'POST',
        body: JSON.stringify(taxonomyData)
      })

      await fetchTaxonomies()
      return true
    } catch (e: any) {
      console.error('Error creating taxonomy:', e)
      return false
    }
  }

  async function deleteTaxonomy(id: number) {
    try {
      await fetchJson(`/cpt/taxonomies/${id}`, {
        method: 'DELETE'
      })

      await fetchTaxonomies()
      return true
    } catch (e: any) {
      console.error('Error deleting taxonomy:', e)
      return false
    }
  }

  // Actions - Terms
  async function fetchTermsByTaxonomy(taxonomyId: number) {
    loading.value = true
    error.value = null
    try {
      const data = await fetchJson<CptTerm[]>(`/cpt/taxonomies/${taxonomyId}/terms`)
      terms.value = data || []
    } catch (e: any) {
      error.value = e.message
      console.error('Error fetching terms:', e)
    } finally {
      loading.value = false
    }
  }

  async function createTerm(taxonomyId: number, termData: Partial<CptTerm>) {
    loading.value = true
    try {
      await fetchJson(`/cpt/taxonomies/${taxonomyId}/terms`, {
        method: 'POST',
        body: JSON.stringify(termData)
      })
      await fetchTermsByTaxonomy(taxonomyId)
      return true
    } catch (e: any) {
      error.value = e.message
      console.error('Error creating term:', e)
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateTerm(id: number, termData: Partial<CptTerm>) {
    loading.value = true
    try {
      await fetchJson(`/cpt/terms/${id}`, {
        method: 'PUT',
        body: JSON.stringify(termData)
      })
      if (currentTaxonomy.value) {
        await fetchTermsByTaxonomy(currentTaxonomy.value.id)
      }
      return true
    } catch (e: any) {
      error.value = e.message
      console.error('Error updating term:', e)
      return false
    } finally {
      loading.value = false
    }
  }

  async function deleteTerm(id: number) {
    loading.value = true
    try {
      await fetchJson(`/cpt/terms/${id}`, {
        method: 'DELETE'
      })
      if (currentTaxonomy.value) {
        await fetchTermsByTaxonomy(currentTaxonomy.value.id)
      }
      return true
    } catch (e: any) {
      error.value = e.message
      console.error('Error deleting term:', e)
      return false
    } finally {
      loading.value = false
    }
  }

  // Actions - Posts
  async function fetchPostsByType(typeSlug: string, limit = 50, offset = 0) {
    loading.value = true

    try {
      const data = await fetchJson<{ data: { posts: CptPost[] } }>(`/cpt/${typeSlug}/posts?limit=${limit}&offset=${offset}`)
      posts.value = data.data.posts || []
      return data.data
    } catch (e: any) {
      error.value = e.message
      console.error('Error fetching posts:', e)
      return null
    } finally {
      loading.value = false
    }
  }

  // Navigation
  function createNewType() {
    currentType.value = null
    viewMode.value = 'edit'
  }

  function editType(id: number) {
    fetchType(id)
    viewMode.value = 'edit'
  }

  function goToList() {
    currentType.value = null
    viewMode.value = 'list'
  }

  function manageTaxonomyTerms(taxonomy: CptTaxonomy) {
    currentTaxonomy.value = taxonomy
    taxonomyViewMode.value = 'terms'
    fetchTermsByTaxonomy(taxonomy.id)
  }

  function backToTaxonomies() {
    currentTaxonomy.value = null
    taxonomyViewMode.value = 'list'
    terms.value = []
  }

  // Reset
  function $reset() {
    types.value = []
    currentType.value = null
    taxonomies.value = []
    terms.value = []
    currentTaxonomy.value = null
    posts.value = []
    viewMode.value = 'list'
    taxonomyViewMode.value = 'list'
    loading.value = false
    error.value = null
  }

  return {
    // State
    types,
    currentType,
    taxonomies,
    terms,
    currentTaxonomy,
    posts,
    viewMode,
    taxonomyViewMode,
    loading,
    error,

    // Getters
    activeTypes,
    sortedTypes,

    // Actions
    fetchTypes,
    fetchType,
    createType,
    updateType,
    deleteType,
    fetchTaxonomies,
    createTaxonomy,
    deleteTaxonomy,
    fetchTermsByTaxonomy,
    createTerm,
    updateTerm,
    deleteTerm,
    fetchPostsByType,
    createNewType,
    editType,
    goToList,
    manageTaxonomyTerms,
    backToTaxonomies,
    $reset
  }
})
