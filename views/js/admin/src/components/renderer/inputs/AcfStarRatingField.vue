<script setup lang="ts">
import { computed } from 'vue'
import type { AcfField } from '@/types'

const props = defineProps<{
  field: AcfField
  modelValue: number | string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number]
}>()

const config = computed(() => props.field.config || {})

// Get max stars (default 5)
const maxStars = computed(() => {
  return Number(config.value.max) || 5
})

// Current rating value
const currentRating = computed(() => {
  return Number(props.modelValue) || 0
})

// Update rating
const setRating = (value: number) => {
  emit('update:modelValue', value)
}

// Clear rating
const clearRating = () => {
  emit('update:modelValue', 0)
}
</script>

<template>
  <div class="acf-star-rating">
    <div class="acf-star-rating__stars">
      <button
        v-for="star in maxStars"
        :key="star"
        type="button"
        class="acf-star-rating__star"
        :class="{ 'acf-star-rating__star--filled': star <= currentRating }"
        @click="setRating(star)"
        :title="`${star} / ${maxStars}`"
      >
        ★
      </button>
    </div>
    <button 
      v-if="currentRating > 0 && !field.required" 
      type="button" 
      class="acf-star-rating__clear btn btn-sm btn-link"
      @click="clearRating"
    >
      ✕
    </button>
    <span class="acf-star-rating__value">{{ currentRating }} / {{ maxStars }}</span>
  </div>
</template>

<style scoped>
.acf-star-rating {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.acf-star-rating__stars {
  display: flex;
  gap: 2px;
}

.acf-star-rating__star {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #ddd;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  transition: color 0.15s, transform 0.1s;
}

.acf-star-rating__star:hover {
  transform: scale(1.1);
}

.acf-star-rating__star--filled {
  color: #ffc107;
}

.acf-star-rating__star:hover,
.acf-star-rating__stars:hover .acf-star-rating__star {
  color: #ffc107;
}

.acf-star-rating__stars:hover .acf-star-rating__star:hover ~ .acf-star-rating__star {
  color: #ddd;
}

.acf-star-rating__clear {
  padding: 0;
  color: #dc3545;
}

.acf-star-rating__value {
  font-size: 0.85rem;
  color: #6c757d;
  margin-left: 0.5rem;
}
</style>
