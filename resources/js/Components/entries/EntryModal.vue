<template>
    <BaseModal :show="show" size="4xl" @close="closeModal">
        <template #header>
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                {{ isEditing ? `Edit ${props.entryType?.name || 'Entry'}` : `View ${props.entryType?.name || 'Entry'}` }}
            </h3>
        </template>

        <template #body>
            <!-- Use Dynamic Form for complex entry types -->
            <DynamicEntryForm
                v-if="isComplexEntryType && isEditing && entry && entryType"
                :entryType="entryType"
                :entry="entry"
                submitText="Save Changes"
                :showStatus="true"
                @cancel="cancelEditing"
                @submit="handleDynamicSubmit"
            />

            <!-- Read-only view for complex entries -->
            <EntryReadView
                v-else-if="isComplexEntryType && !isEditing && entry && entryType"
                :entry="entry"
                :entry-type="entryType"
            />

            <!-- Simple form for I AM entries (backward compatibility) -->
            <form v-else @submit.prevent="saveChanges" class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Title
                    </label>
                    <input
                        id="title"
                        v-model="formData.title"
                        type="text"
                        :readonly="!isEditing"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-700 dark:text-white"
                        :class="{ 'bg-gray-100 dark:bg-gray-600': !isEditing }"
                        :placeholder="`Give your ${props.entryType?.name || 'entry'} a title...`"
                        required
                    />
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Content
                    </label>
                    <textarea
                        id="content"
                        v-model="formData.content"
                        rows="6"
                        :readonly="!isEditing"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-700 dark:text-white"
                        :class="{ 'bg-gray-100 dark:bg-gray-600': !isEditing }"
                        :placeholder="`Enter ${props.entryType?.name?.toLowerCase() || 'entry'} content...`"
                        required
                    ></textarea>
                </div>

                <div v-if="!isEditing" class="text-sm text-gray-500 dark:text-gray-400">
                    Created {{ formatDate(entry?.created_at || '') }}
                </div>
            </form>
        </template>

        <template #footer>
            <BaseButton
                v-if="isEditing && !isComplexEntryType"
                variant="primary"
                :disabled="saving"
                @click="saveChanges"
            >
                {{ saving ? 'Saving...' : 'Save Changes' }}
            </BaseButton>
            
            <BaseButton
                v-if="!isEditing"
                variant="primary"
                @click="startEditing"
            >
                Edit
            </BaseButton>
            
            <BaseButton
                v-if="isEditing"
                variant="secondary"
                @click="cancelEditing"
            >
                Cancel
            </BaseButton>
            
            <BaseButton
                v-if="!isEditing"
                variant="secondary"
                @click="closeModal"
            >
                Close
            </BaseButton>
            
            <BaseButton
                v-if="isEditing"
                variant="danger"
                @click="deleteEntry"
            >
                Delete
            </BaseButton>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseModal from '@/Components/Base/Modal.vue'
import BaseButton from '@/Components/Base/Button.vue'
import DynamicEntryForm from './DynamicEntryForm.vue'
import EntryReadView from './EntryReadView.vue'

interface Entry {
    id: string
    title: string
    content: any
    status: string
    created_at: string
    updated_at: string
    order: number
}

interface EntryType {
    id: string
    name: string
    slug: string
    field_config?: any[]
}

const props = defineProps<{
    show: boolean
    entry: Entry | null
    entryType?: EntryType | null
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

// Check if this is a complex entry type
const isComplexEntryType = computed(() => {
    return props.entryType?.field_config?.some((field: any) => 
        ['repeatable', 'image_collection', 'object', 'entry_relation'].includes(field.type)
    )
})

const handleDynamicSubmit = (dynamicForm: any) => {
    saving.value = true
    dynamicForm.patch(route('entries.update', props.entry!.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page: any) => {
            // Update the entry with the saved data
            const updatedEntry = {
                ...props.entry!,
                title: dynamicForm.title,
                content: dynamicForm.content,
                status: dynamicForm.status,
                updated_at: new Date().toISOString()
            }
            
            emit('update', updatedEntry)
            isEditing.value = false
            saving.value = false
        },
        onError: (errors: any) => {
            console.error('Entry update error:', errors)
            saving.value = false
        }
    })
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
    
    const entryName = props.entryType?.name || 'entry'
    if (confirm(`Are you sure you want to delete this ${entryName}? This action cannot be undone.`)) {
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
