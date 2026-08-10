<template>
    <article class="space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Entry {{ index + 1 }}
                </p>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ entry.title }}
                </h2>
            </div>
            <BaseBadge :variant="entry.status === 'published' ? 'success' : 'warning'" size="sm" :rounded="false">
                {{ entry.status }}
            </BaseBadge>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <FieldDisplay
                v-for="field in fields"
                :key="field.name"
                :label="field.label || field.name"
                :value="entry.content?.[field.name] ?? null"
                :type="field.type"
            />
        </div>
    </article>
</template>

<script setup lang="ts">
import BaseBadge from '@/Components/Base/Badge.vue'
import FieldDisplay from '@/Components/molecules/FieldDisplay.vue'
import type { EntryField } from '@/types/entryField'

export interface PreviewEntry {
    title: string
    status: string
    content: Record<string, unknown>
}

defineProps<{
    entry: PreviewEntry
    index: number
    fields: EntryField[]
}>()
</script>
