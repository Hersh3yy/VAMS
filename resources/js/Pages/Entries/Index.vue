<template>
    <Head :title="entryType.name" />

    <AuthenticatedLayout>
        <div class="content-wrapper">
            <div class="content-container">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-semibold">{{ entryType.name }}</h1>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ entryType.description || `Manage your ${entryType.name.toLowerCase()}` }}
                                </p>
                            </div>
                            <Link 
                                :href="route('entries.create', { type: entryType.slug })" 
                                class="rounded bg-primary px-4 py-2 font-bold text-white hover:bg-primary/90"
                            >
                                Create {{ entryType.name }}
                            </Link>
                        </div>

                        <div v-if="localEntries.length === 0" class="py-8 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No {{ entryType.name.toLowerCase() }} created yet.</p>
                            <Link 
                                :href="route('entries.create', { type: entryType.slug })" 
                                class="mt-2 inline-block text-blue-500 hover:text-blue-700"
                            >
                                Create your first {{ entryType.name }}
                            </Link>
                        </div>

                        <div v-else ref="gridContainer" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="(entry, index) in localEntries"
                                :key="entry.id"
                                :data-id="entry.id"
                                :data-index="index"
                                class="group relative"
                            >
                                <div class="rounded-lg border p-4 transition-shadow hover:shadow-md dark:border-gray-700">
                                    <div class="mb-2 flex items-start justify-between">
                                        <h3 class="flex-1 text-lg font-medium">{{ entry.title }}</h3>
                                        <!-- Status badge hidden - uncomment to re-enable
                                        <span 
                                            class="ml-2 inline-block rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-700"
                                            :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': entry.status === 'published',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': entry.status === 'draft'
                                            }"
                                        >
                                            {{ entry.status }}
                                        </span>
                                        -->
                                    </div>
                                    <!-- One-line summary of fields flagged `summary: true` -->
                                    <p
                                        v-if="hasSummary && summaryFor(entry)"
                                        class="mb-2 truncate text-sm text-gray-500 dark:text-gray-400"
                                        :title="summaryFor(entry)"
                                    >
                                        {{ summaryFor(entry) }}
                                    </p>
                                    <p
                                        v-if="!hasSummary || getEntryContent(entry)"
                                        class="mb-3 whitespace-pre-wrap text-gray-600 dark:text-gray-400"
                                    >
                                        {{ getEntryContent(entry) }}
                                    </p>
                                    <div class="text-xs text-gray-500 dark:text-gray-500">
                                        Created {{ formatDate(entry.created_at) }}
                                    </div>
                                    <div class="mt-3 flex space-x-2">
                                        <button 
                                            @click="openModal(entry)"
                                            class="text-sm text-blue-500 hover:text-blue-700"
                                        >
                                            View
                                        </button>
                                        <button
                                            @click="deleteEntry(entry.id)"
                                            class="text-sm text-red-500 hover:text-red-700"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entry Modal -->
        <EntryModal
            :show="showModal"
            :entry="selectedEntry"
            :entryType="entryType"
            @close="closeModal"
            @update="handleEntryUpdate"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EntryModal from '@/Components/entries/EntryModal.vue'
import { useEntryLookup } from '@/composables/entries/useEntryLookup'
import type { EntryField } from '@/types/entryField'
import { formatSummaryLine, summaryFields } from '@/utils/entryFieldFormat'
import { normalizeEntryContent } from '@/utils/entryTypes'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import Sortable from 'sortablejs'

interface Entry {
    id: string
    title: string
    content: any
    status: string
    created_at: string
    updated_at: string
    order: number
}

interface Props {
    entries: Entry[]
    entryType: {
        id: string
        name: string
        slug: string
        description?: string
        field_config?: EntryField[]
    }
}

const props = defineProps<Props>()

const localEntries = ref([...props.entries])
const gridContainer = ref<HTMLElement | null>(null)
const showModal = ref(false)
const selectedEntry = ref<Entry | null>(null)
let sortableInstance: any = null

// Watch for prop changes
watch(
    () => props.entries,
    newEntries => {
        localEntries.value = [...newEntries]
    },
    { deep: true }
)

// Card summary line ("BRET · Wed 21 Oct, 22:00 · Sold out") from fields flagged `summary: true`
const { titleFor, resolve: resolveEntryTitles } = useEntryLookup()
const summaryFieldList = computed(() => summaryFields(props.entryType.field_config))
const hasSummary = computed(() => summaryFieldList.value.length > 0)

const summaryFor = (entry: Entry): string =>
    formatSummaryLine(summaryFieldList.value, normalizeEntryContent(entry.content), titleFor)

// Related-entry titles shown in summaries (the first two per card) need a lookup.
watch(
    localEntries,
    entries => {
        const relationFields = summaryFieldList.value.filter(field => field.type === 'entry_relation')
        if (relationFields.length === 0) {
            return
        }
        const ids = entries.flatMap(entry => {
            const content = normalizeEntryContent(entry.content)
            return relationFields.flatMap(field => (Array.isArray(content[field.name]) ? content[field.name].slice(0, 2) : []))
        })
        resolveEntryTitles(ids)
    },
    { immediate: true }
)

const getEntryContent = (entry: Entry): string => {
    if (!entry.content) return ''
    
    // Handle JSON content
    if (typeof entry.content === 'object') {
        return entry.content.statement || entry.content.content || ''
    }
    
    return entry.content
}

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString()
}

const handleEnd = (evt: any) => {
    if (evt.oldIndex !== evt.newIndex) {
        // Reorder local array
        const movedEntry = localEntries.value.splice(evt.oldIndex, 1)[0]
        localEntries.value.splice(evt.newIndex, 0, movedEntry)
        
        // Send reorder request to backend
        const orderedIds = localEntries.value.map(entry => entry.id)
        
        router.patch(route('entries.reorder'), {
            orderedIds,
            entry_type_id: props.entryType.id
        }, {
            preserveScroll: true,
            onError: () => {
                // Revert on error
                localEntries.value = [...props.entries]
            }
        })
    }
}

onMounted(() => {
    if (gridContainer.value && localEntries.value.length > 0) {
        sortableInstance = Sortable.create(gridContainer.value, {
            animation: 150,
            ghostClass: 'ghost-item',
            chosenClass: 'chosen-item',
            dragClass: 'drag-item',
            onEnd: handleEnd
        })
    }
})

const openModal = (entry: Entry) => {
    selectedEntry.value = entry
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    selectedEntry.value = null
}

const handleEntryUpdate = (updatedEntry: Entry) => {
    const index = localEntries.value.findIndex(entry => entry.id === updatedEntry.id)
    if (index !== -1) {
        localEntries.value[index] = { ...updatedEntry, order: localEntries.value[index].order }
    }
    
    // Update selectedEntry if it's the same entry (so modal shows updated data)
    if (selectedEntry.value?.id === updatedEntry.id) {
        selectedEntry.value = { ...updatedEntry, order: selectedEntry.value.order }
    }
}

const deleteEntry = (id: string) => {
    const entryName = props.entryType?.name || 'entry'
    if (!confirm(`Are you sure you want to delete this ${entryName}? This action cannot be undone.`)) {
        return
    }

    router.delete(route('entries.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            localEntries.value = localEntries.value.filter(e => e.id !== id)
        }
    })
}

onUnmounted(() => {
    if (sortableInstance) {
        sortableInstance.destroy()
    }
})
</script>

<style scoped>
.ghost-item {
    @apply border-2 border-dashed border-blue-300 bg-blue-100 opacity-50 dark:border-blue-600 dark:bg-blue-900;
}

.chosen-item {
    @apply scale-105 transform ring-2 ring-blue-500;
}

.drag-item {
    @apply rotate-3 transform shadow-lg;
}

.group {
    @apply transition-all duration-200 ease-in-out;
}

.group:hover {
    @apply -translate-y-1 transform shadow-lg;
}
</style>
