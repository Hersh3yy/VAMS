<template>
    <Head :title="`Review ${entryType.name} import`" />

    <AuthenticatedLayout>
        <div class="content-wrapper">
            <div class="content-container">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="space-y-6 p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h1 class="text-2xl font-semibold">Review import</h1>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ entries.length }}
                                    {{ entries.length === 1 ? 'entry' : 'entries' }}
                                    ready to add to
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ entryType.name }}</span>.
                                    Nothing has been created yet.
                                </p>
                            </div>
                            <Link
                                :href="route('entries.index', { type: entryType.slug })"
                                class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                ← Back to {{ entryType.name }}
                            </Link>
                        </div>

                        <div class="space-y-4">
                            <EntryJsonPreviewCard
                                v-for="(entry, index) in entries"
                                :key="`${entry.title}-${index}`"
                                :entry="entry"
                                :index="index"
                                :fields="contentFields"
                            />
                        </div>

                        <div class="flex flex-wrap justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <BaseButton
                                type="button"
                                variant="ghost"
                                :disabled="confirmForm.processing || editForm.processing"
                                @click="editJson"
                            >
                                Edit JSON
                            </BaseButton>
                            <BaseButton
                                type="button"
                                variant="primary"
                                :loading="confirmForm.processing"
                                :disabled="confirmForm.processing || editForm.processing"
                                @click="confirmImport"
                            >
                                Confirm &amp; create {{ entries.length }}
                                {{ entries.length === 1 ? 'entry' : 'entries' }}
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BaseButton from '@/Components/Base/Button.vue'
import EntryJsonPreviewCard from '@/Components/entries/EntryJsonPreviewCard.vue'
import type { EntryType } from '@/types/entryField'

interface PreviewEntry {
    title: string
    status: string
    content: Record<string, unknown>
}

const props = defineProps<{
    entryType: EntryType
    entries: PreviewEntry[]
    payload: Record<string, unknown>[] | Record<string, unknown>
}>()

const contentFields = computed(() => props.entryType.field_config ?? [])
const payloadJson = JSON.stringify(props.payload)

const confirmForm = useForm({
    entry_type_id: props.entryType.id,
    payload_json: payloadJson,
})

const editForm = useForm({
    entry_type_id: props.entryType.id,
    payload_json: payloadJson,
})

const confirmImport = (): void => {
    confirmForm.post(route('entries.store-json'))
}

const editJson = (): void => {
    editForm.post(route('entries.edit-json'))
}
</script>
