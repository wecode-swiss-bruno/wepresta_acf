<template>
  <div class="cpt-term-manager">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button class="btn btn-sm btn-outline-secondary mr-3" @click="cptStore.backToTaxonomies()">
            <i class="material-icons">arrow_back</i>
          </button>
          <h3 class="card-header-title">
            Terms for: <strong>{{ cptStore.currentTaxonomy?.name }}</strong>
          </h3>
        </div>
        <button class="btn btn-primary btn-sm" @click="showCreateTerm = true">
          <i class="material-icons">add</i>
          New Term
        </button>
      </div>
      <div class="card-body">
        <div v-if="cptStore.loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
          </div>
        </div>

        <div v-else-if="cptStore.terms.length === 0" class="alert alert-info">
          No terms found for this taxonomy.
        </div>

        <table v-else class="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Slug</th>
              <th>Description</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="term in cptStore.terms" :key="term.id">
              <td><strong>{{ term.name }}</strong></td>
              <td><code>{{ term.slug }}</code></td>
              <td>{{ term.description || '-' }}</td>
              <td class="text-right">
                <button
                  class="btn btn-sm btn-outline-danger"
                  @click="handleDelete(term)"
                  title="Delete"
                >
                  <i class="material-icons">delete</i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Term Modal -->
    <div v-if="showCreateTerm" class="modal" style="display: block">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">New Term</h5>
            <button type="button" class="close" @click="showCreateTerm = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="term_name">Name *</label>
              <input
                id="term_name"
                v-model="termForm.name"
                type="text"
                class="form-control"
                required
                @input="generateSlug"
              />
            </div>

            <div class="form-group">
              <label for="term_slug">Slug *</label>
              <input
                id="term_slug"
                v-model="termForm.slug"
                type="text"
                class="form-control"
                required
              />
            </div>

            <div class="form-group">
              <label for="term_description">Description</label>
              <textarea
                id="term_description"
                v-model="termForm.description"
                class="form-control"
                rows="2"
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCreateTerm = false">
              Cancel
            </button>
            <button type="button" class="btn btn-primary" :disabled="saving" @click="handleCreate">
              {{ saving ? 'Creating...' : 'Create' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useCptStore } from '../../stores/cptStore'
import type { CptTerm } from '../../types/cpt'

const cptStore = useCptStore()
const showCreateTerm = ref(false)
const saving = ref(false)

const termForm = reactive({
  name: '',
  slug: '',
  description: ''
})

function generateSlug() {
  termForm.slug = termForm.name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
}

async function handleCreate() {
  if (!cptStore.currentTaxonomy) return
  
  saving.value = true
  const success = await cptStore.createTerm(cptStore.currentTaxonomy.id, termForm)
  saving.value = false
  
  if (success) {
    showCreateTerm.value = false
    termForm.name = ''
    termForm.slug = ''
    termForm.description = ''
  }
}

async function handleDelete(term: CptTerm) {
  if (confirm(`Delete term "${term.name}"?`)) {
    await cptStore.deleteTerm(term.id)
  }
}
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1050;
}
.mr-3 {
  margin-right: 1rem;
}
</style>
