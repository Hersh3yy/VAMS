<template>
  <BaseCard :variant="cardVariant" :padding="cardPadding">
    <form @submit.prevent="handleSubmit" class="form-panel">
      <div v-if="title" class="form-panel__header">
        <h3 class="form-panel__title">{{ title }}</h3>
        <p v-if="description" class="form-panel__description">{{ description }}</p>
      </div>
      
      <div class="form-panel__fields">
        <slot />
      </div>
      
      <div v-if="showActions" class="form-panel__actions">
        <div class="form-panel__actions-left">
          <slot name="actions-left" />
        </div>
        
        <div class="form-panel__actions-right">
          <BaseButton
            v-if="showCancelButton"
            variant="secondary"
            type="button"
            :disabled="loading"
            @click="$emit('cancel')"
          >
            {{ cancelButtonText }}
          </BaseButton>
          
          <BaseButton
            variant="primary"
            type="submit"
            :loading="loading"
            :disabled="loading || disabled"
          >
            {{ submitButtonText }}
          </BaseButton>
        </div>
      </div>
    </form>
  </BaseCard>
</template>

<script setup lang="ts">
import { BaseCard, BaseButton } from '@/Components/atoms'

interface Props {
  title?: string
  description?: string
  loading?: boolean
  disabled?: boolean
  showActions?: boolean
  showCancelButton?: boolean
  submitButtonText?: string
  cancelButtonText?: string
  cardVariant?: 'default' | 'elevated' | 'outlined' | 'flat'
  cardPadding?: 'none' | 'sm' | 'md' | 'lg'
}

interface Emits {
  (e: 'submit'): void
  (e: 'cancel'): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  disabled: false,
  showActions: true,
  showCancelButton: true,
  submitButtonText: 'Submit',
  cancelButtonText: 'Cancel',
  cardVariant: 'default',
  cardPadding: 'lg'
})

const emit = defineEmits<Emits>()

const handleSubmit = () => {
  if (!props.loading && !props.disabled) {
    emit('submit')
  }
}
</script>

<style scoped>
.form-panel {
  @apply space-y-6;
}

.form-panel__header {
  @apply space-y-2;
}

.form-panel__title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.form-panel__description {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.form-panel__fields {
  @apply space-y-4;
}

.form-panel__actions {
  @apply flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700;
}

.form-panel__actions-right {
  @apply flex items-center gap-3;
}
</style> 