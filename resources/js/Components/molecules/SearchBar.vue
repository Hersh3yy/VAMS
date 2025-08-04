<template>
  <div class="search-bar">
    <div class="search-bar__input-wrapper">
      <BaseInput
        v-model="searchValue"
        type="search"
        :placeholder="placeholder"
        :disabled="disabled"
        :size="size"
        class="search-bar__input"
        @keyup.enter="$emit('search', searchValue)"
        @focus="$emit('focus', $event)"
      />
      <div class="search-bar__icon">
        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>
    
    <BaseButton
      v-if="showButton"
      variant="primary"
      :size="size"
      :disabled="disabled || !searchValue"
      @click="$emit('search', searchValue)"
    >
      {{ buttonText }}
    </BaseButton>
  </div>
</template>

<script setup lang="ts">
import { BaseInput, BaseButton } from '@/Components/atoms'

interface Props {
  modelValue: string
  placeholder?: string
  disabled?: boolean
  size?: 'sm' | 'md' | 'lg'
  showButton?: boolean
  buttonText?: string
}

interface Emits {
  (e: 'update:modelValue', value: string): void
  (e: 'search', value: string): void
  (e: 'focus', event: FocusEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Search...',
  disabled: false,
  size: 'md',
  showButton: false,
  buttonText: 'Search'
})

const emit = defineEmits<Emits>()

const searchValue = computed({
  get: () => props.modelValue,
  set: (value: string) => emit('update:modelValue', value)
})
</script>

<style scoped>
.search-bar {
  @apply flex items-center gap-3;
}

.search-bar__input-wrapper {
  @apply relative flex-1;
}

.search-bar__input {
  @apply pr-10;
}

.search-bar__icon {
  @apply absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none;
}
</style> 