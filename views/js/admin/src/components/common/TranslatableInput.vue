<template>
  <div class="translatable-input" v-click-outside="closeDropdown">
    <div class="input-group">
      <!-- Input slot -->
      <slot :lang-id="currentLangId" :value="getModelValue(currentLangId)" :update="updateValue">
        <input
          v-if="type === 'text'"
          type="text"
          class="form-control"
          :value="getModelValue(currentLangId)"
          @input="updateValue(($event.target as HTMLInputElement).value)"
          :placeholder="placeholder"
        />
        <textarea
          v-else-if="type === 'textarea'"
          class="form-control"
          :rows="rows"
          :value="getModelValue(currentLangId)"
          @input="updateValue(($event.target as HTMLTextAreaElement).value)"
          :placeholder="placeholder"
        ></textarea>
      </slot>

      <!-- Language Dropdown -->
      <div class="input-group-append position-relative">
        <button
          class="btn btn-outline-secondary dropdown-toggle"
          type="button"
          @click="toggleDropdown"
          aria-haspopup="true"
          :aria-expanded="isOpen"
        >
          {{ currentLangCode }}
        </button>
        <div 
          v-if="isOpen" 
          class="dropdown-menu dropdown-menu-right show"
          style="display: block; position: absolute; right: 0; top: 100%; z-index: 1000;"
        >
          <a
            v-for="lang in availableLanguages"
            :key="lang.id_lang"
            class="dropdown-item"
            href="#"
            @click.prevent="selectLang(parseInt(lang.id_lang))"
            :class="{ active: currentLangId === parseInt(lang.id_lang) }"
          >
            <span class="mr-2">{{ lang.iso_code.toUpperCase() }}</span>
            <small class="text-muted">{{ lang.name }}</small>
          </a>
          <div v-if="availableLanguages.length === 0" class="dropdown-item disabled">
            No languages found
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useBuilderStore } from '@/stores/builderStore'

const props = withDefaults(
  defineProps<{
    modelValue: any
    type?: 'text' | 'textarea'
    placeholder?: string
    rows?: number
  }>(),
  {
    type: 'text',
    placeholder: '',
    rows: 3
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
}>()

const store = useBuilderStore()

// State
const isOpen = ref(false)

// Directives (simple local click-outside)
const vClickOutside = {
  mounted(el: any, binding: any) {
    el._clickOutside = (event: Event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event)
      }
    }
    document.addEventListener('click', el._clickOutside)
  },
  unmounted(el: any) {
    document.removeEventListener('click', el._clickOutside)
  }
}

// Global UI Lang
const currentLangId = computed(() => store.currentUiLangId)

// Computed
const availableLanguages = computed(() => {
  const configs = (window as any).acfConfig || (window as any).weprestaAcfConfig
  return configs?.availableLanguages || configs?.languages || []
})

const currentLangCode = computed(() => {
  const lang = availableLanguages.value.find(
    (l: any) => parseInt(l.id_lang || l.id) === currentLangId.value
  )
  const code = lang?.iso_code || lang?.code
  return code ? code.toUpperCase() : '??'
})

const defaultLangId = computed(() => {
    const configs = (window as any).acfConfig || (window as any).weprestaAcfConfig
    return parseInt(configs?.currentLangId || configs?.defaultLangId) || 1
})

// Methods
function toggleDropdown() {
  isOpen.value = !isOpen.value
}

function closeDropdown() {
  isOpen.value = false
}

function selectLang(id: number) {
  store.setCurrentUiLangId(id)
  isOpen.value = false
}

function getModelValue(langId: number) {
  if (typeof props.modelValue === 'string') {
    // If it's a string, we treat it as the value for the default language
    return langId === defaultLangId.value ? props.modelValue : ''
  }
  return props.modelValue?.[langId] || ''
}

function updateValue(value: string) {
  let newValue = props.modelValue

  if (!newValue || typeof newValue === 'string') {
    const prevValue = typeof props.modelValue === 'string' ? props.modelValue : ''
    newValue = {}
    if (prevValue && defaultLangId.value !== currentLangId.value) {
        newValue[defaultLangId.value] = prevValue
    }
  } else {
    newValue = { ...newValue }
  }

  newValue[currentLangId.value] = value
  emit('update:modelValue', newValue)
}

onMounted(() => {
  const configs = (window as any).acfConfig || (window as any).weprestaAcfConfig
  if (configs?.currentLangId && !store.currentUiLangId) {
    store.setCurrentUiLangId(parseInt(configs.currentLangId))
  }
})
</script>

<style scoped>
.translatable-input .dropdown-toggle {
  min-width: 65px;
}
.dropdown-menu {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border: 1px solid #ddd;
}
</style>

