<script setup lang="ts">
import { computed } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { useApi } from '@/composables/useApi'
import PsSwitch from '@/components/ui/PsSwitch.vue'

const emit = defineEmits<{
  'next-step': []
}>()

const store = useBuilderStore()
const { t } = useTranslations()
const api = useApi()

// Auto-save group when proceeding to next step if not saved yet
async function handleNextStep(): Promise<void> {
  // If group is not saved yet, save it first
  if (!isGroupSaved.value && canProceedToNextStep.value) {
    try {
      await store.saveGroup()
    } catch (error) {
      console.error('Failed to save group:', error)
      return
    }
  }

  // Proceed to next step
  emit('next-step')
}

const group = computed(() => store.currentGroup)

// Check if group has valid content to proceed
const canProceedToNextStep = computed(() => {
  return group.value &&
         group.value.title?.trim() &&
         group.value.slug?.trim()
})

// Check if group is saved (has ID)
const isGroupSaved = computed(() => {
  return group.value && group.value.id !== undefined && group.value.id !== null
})

// Ensure foOptions.visible is always a boolean
const showOnFrontend = computed({
  get: () => Boolean(group.value?.foOptions?.visible),
  set: (value: boolean) => {
    if (group.value?.foOptions) {
      group.value.foOptions.visible = value
      // Mark as having unsaved changes
      store.markAsUnsaved()
    }
  }
})

// Mark group as having unsaved changes when title/slug change
function onGroupChange(): void {
  store.markAsUnsaved()
}

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
          @input="onTitleChange; onGroupChange()"
        >
        <small class="form-text text-muted">
          A descriptive name for this field group that will be displayed in the admin interface.
        </small>
      </div>

      <div class="form-group">
        <label class="form-control-label">{{ t('groupSlug') }} *</label>
        <input
          v-model="group.slug"
          type="text"
          class="form-control"
          pattern="[a-z0-9_\-]+"
          @input="onGroupChange"
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
        <small class="form-text text-muted">
          Optional description to help administrators understand the purpose of this field group.
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
          v-model="showOnFrontend"
          id="group-fo-visible"
        />
        <small class="form-text text-muted">
          Display this group's fields on the product page.
        </small>
      </div>
    </div>

    <!-- Step Navigation -->
    <div class="acfps-step-navigation">
      <div></div> <!-- Spacer -->
      <button
        class="btn btn-primary"
        :disabled="!canProceedToNextStep || store.saving"
        @click="handleNextStep"
      >
        <span v-if="store.saving">{{ t('saving') }}</span>
        <span v-else>Next: {{ t('location') }}</span>
        <span v-if="!store.saving" class="material-icons">arrow_forward</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.acfps-group-settings {
  padding: 0;
}


.acfps-step-navigation {
  display: flex;
  justify-content: space-between;
  padding: 1.5rem;
  border-top: 1px solid #dee2e6;
  margin-top: 2rem;
  background: #f8f9fa;
}

.acfps-step-navigation .btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.acfps-step-navigation .btn .material-icons {
  font-size: 18px;
}
</style>

