<script setup lang="ts">
import { computed } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { useApi } from '@/composables/useApi'
import PsSwitch from '@/components/ui/PsSwitch.vue'

const store = useBuilderStore()
const { t } = useTranslations()
const api = useApi()

const group = computed(() => store.currentGroup)
const productTabs = computed(() => window.acfConfig?.productTabs || [])

// Auto-generate slug from title
let slugTimeout: number | null = null
async function onTitleChange(): Promise<void> {
  if (!group.value) return

  // Only auto-generate if slug is empty or group is new
  if (!group.value.slug || !group.value.id) {
    if (slugTimeout) {
      clearTimeout(slugTimeout)
    }
    slugTimeout = window.setTimeout(async () => {
      if (group.value?.title) {
        group.value.slug = await api.slugify(group.value.title)
      }
    }, 500)
  }
}
</script>

<template>
  <div v-if="group" class="acfps-group-settings">
    <div class="acfps-form-section">
      <h4>{{ t('general') }}</h4>

      <div class="form-group">
        <label class="form-control-label">{{ t('groupTitle') }} *</label>
        <input
          v-model="group.title"
          type="text"
          class="form-control"
          @input="onTitleChange"
        >
      </div>

      <div class="form-group">
        <label class="form-control-label">{{ t('groupSlug') }} *</label>
        <input
          v-model="group.slug"
          type="text"
          class="form-control"
          pattern="[a-z0-9_\-]+"
        >
        <small class="form-text text-muted">
          Unique identifier used in templates: <code>acf('{{ group.slug || 'slug' }}', 'field_name')</code>
        </small>
      </div>

      <div class="form-group">
        <label class="form-control-label">{{ t('groupDescription') }}</label>
        <textarea
          v-model="group.description"
          class="form-control"
          rows="3"
        />
      </div>
    </div>

    <div class="acfps-form-section">
      <h4>{{ t('presentation') }}</h4>

      <div class="form-group">
        <label class="form-control-label">{{ t('placementTab') }}</label>
        <select v-model="group.placementTab" class="form-control">
          <option
            v-for="tab in productTabs"
            :key="tab.value"
            :value="tab.value"
          >
            {{ tab.label }}
          </option>
        </select>
        <small class="form-text text-muted">
          Product page tab where this field group will appear.
        </small>
      </div>

      <div class="form-group">
        <label class="form-control-label">{{ t('priority') }}</label>
        <input
          v-model.number="group.priority"
          type="number"
          class="form-control"
          min="0"
          max="100"
        >
        <small class="form-text text-muted">
          Lower numbers appear first. Default is 10.
        </small>
      </div>
    </div>

    <div class="acfps-form-section">
      <h4>{{ t('options') }}</h4>

      <div class="form-group">
        <label class="form-control-label">{{ t('active') }}</label>
        <PsSwitch
          v-model="group.active"
          id="group-active"
        />
        <small class="form-text text-muted">
          Inactive groups are hidden in the product form.
        </small>
      </div>

      <div class="form-group">
        <label class="form-control-label">{{ t('showOnFrontend') }}</label>
        <PsSwitch
          v-model="group.foOptions.visible"
          id="group-fo-visible"
        />
        <small class="form-text text-muted">
          Display this group's fields on the product page.
        </small>
      </div>
    </div>
  </div>
</template>

<style scoped>
.acfps-group-settings {
  padding: 0;
}
</style>

