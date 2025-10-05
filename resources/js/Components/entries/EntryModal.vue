<template>
    <div v-if="show" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 dark:bg-gray-800">
                    <div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100" id="modal-title">
                                {{ isEditing ? 'Edit I AM' : 'View I AM' }}
                            </h3>
                            <div class="mt-4">
                                <form @submit.prevent="saveChanges" class="space-y-4">
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Title
                                        </label>
                                        <input
                                            id="title"
                                            v-model="formData.title"
                                            type="text"
                                            :readonly="!isEditing"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            :class="{ 'bg-gray-100 dark:bg-gray-600': !isEditing }"
                                            placeholder="Give your I AM a title..."
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            I AM...
                                        </label>
                                        <textarea
                                            id="content"
                                            v-model="formData.content"
                                            rows="6"
                                            :readonly="!isEditing"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            :class="{ 'bg-gray-100 dark:bg-gray-600': !isEditing }"
                                            placeholder="I AM..."
                                            required
                                        ></textarea>
                                    </div>

                                    <div v-if="isEditing">
                                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Status
                                        </label>
                                        <select
                                            id="status"
                                            v-model="formData.status"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="draft">Draft</option>
                                            <option value="published">Published</option>
                                        </select>
                                    </div>

                                    <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="inline-block bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs mr-2">
                                            {{ entry?.status }}
                                        </span>
                                        Created {{ formatDate(entry?.created_at || '') }}
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button
                            v-if="isEditing"
                            type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="saveChanges"
                            :disabled="saving"
                        >
                            <span v-if="saving">Saving...</span>
                            <span v-else>Save Changes</span>
                        </button>
                        <button
                            v-if="!isEditing"
                            type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="startEditing"
                        >
                            Edit
                        </button>
                        <button
                            v-if="isEditing"
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            @click="cancelEditing"
                        >
                            Cancel
                        </button>
                        <button
                            v-if="!isEditing"
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            @click="closeModal"
                        >
                            Close
                        </button>
                        <button
                            v-if="isEditing"
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-red-300 bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm"
                            @click="deleteEntry"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface Entry {
    id: string
    title: string
    content: any
    status: string
    created_at: string
    updated_at: string
    order: number
}

const props = defineProps<{
    show: boolean
    entry: Entry | null
}>()

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'update', entry: Entry): void
}>()

const isEditing = ref(false)
const saving = ref(false)

const formData = ref({
    title: '',
    content: '',
    status: 'draft'
})

const getContent = (entry: Entry | null): string => {
    if (!entry?.content) return ''
    
    if (typeof entry.content === 'object') {
        return entry.content.statement || entry.content.content || ''
    }
    
    return entry.content
}

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString()
}

watch(() => props.entry, (newEntry) => {
    if (newEntry) {
        formData.value = {
            title: newEntry.title,
            content: getContent(newEntry),
            status: newEntry.status
        }
        isEditing.value = false
    }
}, { immediate: true })

const startEditing = () => {
    isEditing.value = true
}

const cancelEditing = () => {
    if (props.entry) {
        formData.value = {
            title: props.entry.title,
            content: getContent(props.entry),
            status: props.entry.status
        }
    }
    isEditing.value = false
}

const saveChanges = async () => {
    if (!props.entry) return
    
    saving.value = true
    
    try {
        await router.patch(route('entries.update', props.entry.id), {
            title: formData.value.title,
            content: formData.value.content,
            status: formData.value.status
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                emit('update', {
                    ...props.entry!,
                    title: formData.value.title,
                    content: { statement: formData.value.content },
                    status: formData.value.status
                })
                isEditing.value = false
            },
            onError: (errors) => {
                console.error('Entry update error:', errors)
                alert('Unable to save changes. Please try again.')
            }
        })
    } catch (error) {
        console.error('Save error:', error)
        alert('Unable to save changes. Please try again.')
    } finally {
        saving.value = false
    }
}

const deleteEntry = () => {
    if (!props.entry) return
    
    if (confirm('Are you sure you want to delete this I AM? This action cannot be undone.')) {
        router.delete(route('entries.destroy', props.entry.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal()
            }
        })
    }
}

const closeModal = () => {
    emit('close')
}
</script>
