<template>
  <div :class="cardClasses">
    <slot />
  </div>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'default' | 'elevated' | 'outlined' | 'flat'
  padding?: 'none' | 'sm' | 'md' | 'lg'
  hover?: boolean
  clickable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  padding: 'md',
  hover: false,
  clickable: false
})

const cardClasses = computed(() => {
  const baseClasses = 'rounded-lg'
  
  const variantClasses = {
    default: 'bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700',
    elevated: 'bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700',
    outlined: 'bg-transparent border-2 border-gray-200 dark:border-gray-700',
    flat: 'bg-gray-50 dark:bg-gray-900'
  }
  
  const paddingClasses = {
    none: '',
    sm: 'p-3',
    md: 'p-6',
    lg: 'p-8'
  }
  
  const interactionClasses = []
  if (props.hover) {
    interactionClasses.push('transition-all duration-200 hover:shadow-md hover:-translate-y-1')
  }
  if (props.clickable) {
    interactionClasses.push('cursor-pointer')
  }
  
  return [
    baseClasses,
    variantClasses[props.variant],
    paddingClasses[props.padding],
    ...interactionClasses
  ]
})
</script> 