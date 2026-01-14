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

  // Taxonomy state
  const taxonomies = ref<CptTaxonomy[]>([])
  const currentTaxonomy = ref<CptTaxonomy | null>(null)
  const taxonomyViewMode = ref<'list' | 'edit' | 'terms'>('list')
  const terms = ref<CptTerm[]>([])
  const loadingTerms = ref(false)

  // Post state
  const posts = ref<any[]>([])
  const loadingPosts = ref(false)
  const postsTotal = ref(0)
  const postsLimit = ref(50)
  const postsOffset = ref(0)

  const currentPost = ref<any | null>(null)

  // View state
  const viewMode = ref<'list' | 'edit' | 'posts' | 'post-build'>('list')
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Relations state
  const relations = ref<any[]>([])

  // ... (existing code)

  // Actions - Relations
  async function fetchRelations(sourceTypeId: number) {
    loading.value = true
    try {
      const data = await fetchJson<{ data: any[] }>(`/cpt/relations/${sourceTypeId}`)
      relations.value = data.data || []
      return relations.value
    } catch (e: any) {
      console.error('Error fetching relations:', e)
      return []
    } finally {
      loading.value = false
    }
  }

  async function searchPosts(typeSlug: string, query: string) {
    if (!query) return []
    try {
      // Use efficient search endpoint (assuming backend supports it, otherwise generic list)
      // Note: reusing list endpoint for now with filter if query param supported,
      // but ideally should be /cpt/{slug}/posts?q={query}
      // For now, listing all and filtering client side for safety if API limited
      // or implement generic search
      const data = await fetchJson<{ data: { posts: any[] } }>(`/cpt/${typeSlug}/posts?limit=20&q=${encodeURIComponent(query)}`)
      return data.data.posts
    } catch (e: any) {
      console.error('Error searching posts:', e)
      return []
    }
  }

  // Getters
  const activeTypes = computed(() => types.value.filter(t => t.active))
  const sortedTypes = computed(() => [...types.value].sort((a, b) => (a.position || 0) - (b.position || 0)))
  const sortedTaxonomies = computed(() => [...taxonomies.value].sort((a, b) => (a.name || '').localeCompare(b.name || '')))

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

  async function fetchTaxonomy(id: number) {
    try {
      const data = await fetchJson<{ data: CptTaxonomy }>(`/cpt/taxonomies/${id}`)
      return data.data
    } catch (e: any) {
      console.error('Error fetching taxonomy:', e)
      return null
    }
  }

  async function createTaxonomy(taxonomyData: Partial<CptTaxonomy>) {
    try {
      await fetchJson('/cpt/taxonomies', {
        method: 'POST',
        body: JSON.stringify(taxonomyData)
      })

      await fetchTaxonomies()
      goToTaxonomiesList()
      return true
    } catch (e: any) {
      console.error('Error creating taxonomy:', e)
      return false
    }
  }

  async function updateTaxonomy(id: number, taxonomyData: Partial<CptTaxonomy>) {
    try {
      await fetchJson(`/cpt/taxonomies/${id}`, {
        method: 'PUT',
        body: JSON.stringify(taxonomyData)
      })

      await fetchTaxonomies()
      goToTaxonomiesList()
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
      const data = await fetchJson<{ data: CptTerm[] }>(`/cpt/taxonomies/${taxonomyId}/terms`)
      terms.value = data.data || []
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
      // Refresh terms for current taxonomy if available
      const taxId = termData.taxonomy_id || currentTaxonomy.value?.id
      if (taxId) {
        await fetchTermsByTaxonomy(taxId)
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
      if (currentTaxonomy.value?.id) {
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
  async function fetchPostsByType(typeSlug: string, offset = 0) {
    loadingPosts.value = true
    error.value = null
    try {
      const data = await fetchJson<{ data: { posts: any[], total: number } }>(`/cpt/${typeSlug}/posts?offset=${offset}&limit=${postsLimit.value}`)
      posts.value = data.data.posts
      postsTotal.value = data.data.total
      postsOffset.value = offset
    } catch (e: any) {
      error.value = e.message
      console.error('Error fetching posts:', e)
    } finally {
      loadingPosts.value = false
    }
  }

  async function deletePost(id: number, typeSlug: string) {
    loadingPosts.value = true
    error.value = null
    try {
      await fetchJson(`/cpt/posts/${id}`, { method: 'DELETE' })
      await fetchPostsByType(typeSlug, postsOffset.value)
    } catch (e: any) {
      error.value = e.message
      console.error('Error deleting post:', e)
    } finally {
      loadingPosts.value = false
    }
  }

  async function fetchPost(id: number) {
    loadingPosts.value = true
    error.value = null
    try {
      const data = await fetchJson<{ data: any }>(`/cpt/posts/${id}`)
      currentPost.value = data.data
      return data.data
    } catch (e: any) {
      error.value = e.message
      console.error('Error fetching post:', e)
    } finally {
      loadingPosts.value = false
    }
  }

  async function createPost(typeSlug: string, postData: any) {
    loadingPosts.value = true
    error.value = null
    try {
      const data = await fetchJson<{ data: { id: number } }>(`/cpt/${typeSlug}/posts`, {
        method: 'POST',
        body: JSON.stringify(postData)
      })
      await fetchPostsByType(typeSlug)
      return data.data.id
    } catch (e: any) {
      error.value = e.message
      console.error('Error creating post:', e)
      throw e
    } finally {
      loadingPosts.value = false
    }
  }

  async function updatePost(id: number, typeSlug: string, postData: any) {
    loadingPosts.value = true
    error.value = null
    try {
      await fetchJson(`/cpt/posts/${id}`, {
        method: 'PUT',
        body: JSON.stringify(postData)
      })
      await fetchPostsByType(typeSlug)
    } catch (e: any) {
      error.value = e.message
      console.error('Error updating post:', e)
      throw e
    } finally {
      loadingPosts.value = false
    }
  }

  // Navigation
  function createNewType() {
    currentType.value = null
    error.value = null
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

  // Taxonomy Navigation
  function goToTaxonomiesList() {
    currentTaxonomy.value = null
    taxonomyViewMode.value = 'list'
  }

  function editTaxonomy(id: number) {
    fetchTaxonomy(id).then(tax => {
      if (tax) {
        currentTaxonomy.value = tax
        taxonomyViewMode.value = 'edit'
      }
    })
  }

  function createNewTaxonomy() {
    currentTaxonomy.value = null
    taxonomyViewMode.value = 'edit'
  }

  function manageTerms(taxonomy: CptTaxonomy) {
    currentTaxonomy.value = taxonomy
    taxonomyViewMode.value = 'terms'
    fetchTermsByTaxonomy(taxonomy.id!)
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
    viewMode,
    types,
    currentType,
    sortedTypes,
    taxonomies,
    currentTaxonomy,
    taxonomyViewMode,
    sortedTaxonomies,
    terms,
    loadingTerms,
    posts,
    loadingPosts,
    postsTotal,
    postsLimit,
    postsOffset,
    currentPost,
    // Relations state
    relations,
    loading,
    error,

    // Actions
    fetchTypes,
    createType,
    updateType,
    deleteType,
    fetchType,

    // Taxonomy actions
    fetchTaxonomies,
    createTaxonomy,
    updateTaxonomy,
    deleteTaxonomy,
    fetchTaxonomy,
    fetchTermsByTaxonomy,
    createTerm,
    updateTerm,
    deleteTerm,

    // Post actions
    fetchPostsByType,
    deletePost,
    fetchPost,
    createPost,
    updatePost,

    // Relation actions
    fetchRelations,
    searchPosts,

    // Navigation
    createNewType,
    editType,
    goToList,
    goToTaxonomiesList,
    editTaxonomy,
    createNewTaxonomy,
    manageTerms,
    $reset
  }
})
