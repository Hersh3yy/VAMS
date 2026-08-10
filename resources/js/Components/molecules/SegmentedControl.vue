<template>
    <div
        class="grid gap-1 rounded-lg border border-gray-300 bg-gray-100 p-1 dark:border-gray-600 dark:bg-gray-900/50"
        :style="{ gridTemplateColumns: `repeat(${options.length}, minmax(0, 1fr))` }"
        role="tablist"
        :aria-label="ariaLabel"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            role="tab"
            :aria-selected="modelValue === option.value"
            class="rounded-md px-3 py-2 text-sm font-medium transition"
            :class="modelValue === option.value
                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'"
            @click="$emit('update:modelValue', option.value)"
        >
            {{ option.label }}
        </button>
    </div>
</template>

<script setup lang="ts">
export interface SegmentedControlOption {
    label: string
    value: string
}

withDefaults(defineProps<{
    modelValue: string
    options: SegmentedControlOption[]
    ariaLabel?: string
}>(), {
    ariaLabel: 'Options',
})

defineEmits<{
    'update:modelValue': [value: string]
}>()
</script>
