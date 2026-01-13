<template>
  <div class="cpt-taxonomy-manager">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-header-title">Taxonomies</h3>
        <button class="btn btn-primary btn-sm" @click="showCreateTaxonomy = true">
          <i class="material-icons">add</i>
          New Taxonomy
        </button>
      </div>
      <div class="card-body">
        <div v-if="cptStore.taxonomies.length === 0" class="alert alert-info">
          No taxonomies yet. Create your first one!
        </div>

        <div v-else class="list-group">
          <div
            v-for="taxonomy in cptStore.taxonomies"
            :key="taxonomy.id"
            class="list-group-item d-flex justify-content-between align-items-center"
          >
            <div>
              <strong>{{ taxonomy.name }}</strong>
              <br>
              <code>{{ taxonomy.slug }}</code>
              <small v-if="taxonomy.description" class="text-muted d-block">
                {{ taxonomy.description }}
              </small>
            </div>
            <div class="btn-group">
              <button
                class="btn btn-sm btn-outline-secondary"
                @click="manageTerms(taxonomy)"
                title="Manage Terms"
              >
                <i class="material-icons">category</i>
                Terms
              </button>
              <button
                class="btn btn-sm btn-outline-danger"
                @click="deleteTaxonomy(taxonomy)"
                title="Delete"
              >
                <i class="material-icons">delete</i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Taxonomy Modal -->
    <div v-if="showCreateTaxonomy" class="modal" style="display: block">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">New Taxonomy</h5>
            <button type="button" class="close" @click="showCreateTaxonomy = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="tax_name">Name *</label>
              <input
                id="tax_name"
                v-model="taxonomyForm.name"
                type="text"
                class="form-control"
                required
                @input="generateTaxSlug"
              />
            </div>

            <div class="form-group">
              <label for="tax_slug">Slug *</label>
              <input
                id="tax_slug"
                v-model="taxonomyForm.slug"
                type="text"
                class="form-control"
                required
              />
            </div>

            <div class="form-group">
              <label for="tax_description">Description</label>
              <textarea
                id="tax_description"
                v-model="taxonomyForm.description"
                class="form-control"
                rows="2"
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCreateTaxonomy = false">
              Cancel
            </button>
            <button type="button" class="btn btn-primary" @click="createTaxonomy">
              Create
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

const cptStore = useCptStore()
const showCreateTaxonomy = ref(false)

const taxonomyForm = reactive({
  name: '',
  slug: '',
  description: '',
  hierarchical: true
})

function generateTaxSlug() {
  taxonomyForm.slug = taxonomyForm.name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, '')
}

async function createTaxonomy() {
  const success = await cptStore.createTaxonomy(taxonomyForm)
  
  if (success) {
    showCreateTaxonomy.value = false
    taxonomyForm.name = ''
    taxonomyForm.slug = ''
    taxonomyForm.description = ''
  }
}

function manageTerms(taxonomy: any) {
  cptStore.manageTaxonomyTerms(taxonomy)
}

function deleteTaxonomy(taxonomy: any) {
  if (confirm(`Delete taxonomy "${taxonomy.name}"?`)) {
    cptStore.deleteTaxonomy(taxonomy.id)
  }
}
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.form-section {
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 1.5rem;
}

.form-section:last-child {
  border-bottom: none;
}
</style>
