<template>
    <div class="rounded-md border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-2 px-4 py-3 text-left"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ title }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ open ? collapseLabel : expandLabel }}
            </span>
        </button>
        <div v-if="open" class="space-y-3 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = withDefaults(defineProps<{
    title: string
    defaultOpen?: boolean
    expandLabel?: string
    collapseLabel?: string
}>(), {
    defaultOpen: false,
    expandLabel: 'Show',
    collapseLabel: 'Hide',
})

const open = ref(props.defaultOpen)

watch(
    () => props.defaultOpen,
    (value) => {
        open.value = value
    },
)
</script>
