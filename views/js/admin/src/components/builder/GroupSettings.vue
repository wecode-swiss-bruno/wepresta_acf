<script setup lang="ts">
import { computed } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'
import { useTranslations } from '@/composables/useTranslations'
import { useApi } from '@/composables/useApi'
import PsSwitch from '@/components/common/PsSwitch.vue'

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
      // await store.saveGroup()
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




// Auto-generate slug from title
async function onTitleChange(): Promise<void> {
  if (!group.value || !group.value.title) return

      try {
        const newSlug = await api.slugify(group.value.title)
    if (group.value) {
          group.value.slug = newSlug
        }
      } catch (error) {
        console.error('Error generating slug:', error)
      }
    }

// Handle slug changes
function onSlugChange(): void {
  if (!group.value) return
  // Slug changes are always allowed for unsaved groups
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
        <small class="form-text text-muted">
          {{ t('groupTitleHelp') }}
        </small>
      </div>

      <div class="form-group">
        <label class="form-control-label">
          {{ t('groupSlug') }} *
          <!-- Icône de cadenas pour les groupes sauvegardés -->
          <i v-if="isGroupSaved"
             class="material-icons text-warning ml-1"
             style="font-size: 16px; vertical-align: middle;"
             :title="t('slugLockedTitle')">
            lock
          </i>
        </label>

        <!-- Input avec style spécial pour groupes sauvegardés -->
        <div class="input-group">
        <input
          v-model="group.slug"
          type="text"
          class="form-control"
          pattern="[a-z0-9_\-]+"
            :disabled="!!isGroupSaved"
            :class="{
              'text-muted': isGroupSaved,
              'border-secondary': isGroupSaved
            }"
          @input="onSlugChange"
          >

          <!-- Badge visuel pour groupes sauvegardés -->
          <div v-if="isGroupSaved" class="input-group-append">
            <span class="input-group-text bg-secondary text-white">
              <i class="material-icons" style="font-size: 14px;">lock</i>
              {{ t('locked') }}
            </span>
          </div>
        </div>

        <!-- Message explicatif selon l'état du groupe -->
        <small class="form-text" :class="{ 'text-muted': !isGroupSaved, 'text-warning': isGroupSaved }">
          <span v-if="isGroupSaved">
            <i class="material-icons" style="font-size: 12px; vertical-align: middle;">info</i>
            {{ t('slugLockedExplanation') }}
          </span>
          <span v-else>
            {{ t('slugUsageHelp') }} acf('{{ group.slug || 'slug' }}', 'field_name')
          </span>
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
          {{ t('groupDescriptionHelp') }}
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
          {{ t('groupInactiveHelp') }}
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
        <span v-else>{{ t('next') }} {{ t('location') }}</span>
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

