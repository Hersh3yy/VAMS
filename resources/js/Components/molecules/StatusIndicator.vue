<template>
  <div class="status-indicator">
    <BaseBadge
      :variant="variant"
      :size="size"
      :rounded="rounded"
      class="status-indicator__badge"
    >
      <component
        v-if="icon"
        :is="icon"
        class="status-indicator__icon"
        :class="iconClasses"
      />
      <slot />
    </BaseBadge>
  </div>
</template>

<script setup lang="ts">
import { BaseBadge } from '@/Components/atoms'

interface Props {
  variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
  size?: 'sm' | 'md' | 'lg'
  rounded?: boolean
  icon?: any
  animate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md',
  rounded: false,
  animate: false
})

const iconClasses = computed(() => {
  const baseClasses = 'mr-1'
  const animateClasses = props.animate ? 'animate-pulse' : ''
  return [baseClasses, animateClasses]
})
</script>

<style scoped>
.status-indicator__icon {
  @apply h-3 w-3;
}

.status-indicator__badge {
  @apply inline-flex items-center;
}
</style> 