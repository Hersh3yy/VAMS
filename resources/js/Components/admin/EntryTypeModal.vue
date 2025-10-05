<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75" />
            </div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            <div class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6 sm:align-middle">
                <div class="flex items-center justify-between border-b pb-4">
                    <h3 class="text-lg font-medium">
                        {{ entryType ? 'Edit Entry Type' : 'Create Entry Type' }}
                    </h3>
                    <button
                        type="button"
                        class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="closeModal"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveEntryType" class="mt-6 space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input
                            id="name"
                            v-model="formData.name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="e.g., Recipes, Blog Posts"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                        <textarea
                            id="description"
                            v-model="formData.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="A brief description of this entry type..."
                        />
                        <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                    </div>

                    <!-- Field Configuration -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Field Configuration</label>
                        <div class="space-y-3 border rounded-lg p-4 bg-gray-50">
                            <div v-for="(field, index) in formData.field_config" :key="index" class="flex items-start space-x-2 border-b pb-3 last:border-b-0">
                                <div class="flex-1 grid grid-cols-2 gap-2">
                                    <input
                                        v-model="field.name"
                                        type="text"
                                        placeholder="Field name (e.g., title)"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    />
                                    <input
                                        v-model="field.label"
                                        type="text"
                                        placeholder="Label (e.g., Title)"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    />
                                    <select
                                        v-model="field.type"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                        <option value="text">Text</option>
                                        <option value="textarea">Textarea</option>
                                        <option value="number">Number</option>
                                        <option value="select">Select</option>
                                        <option value="checkbox">Checkbox</option>
                                    </select>
                                    <input
                                        v-model="field.placeholder"
                                        type="text"
                                        placeholder="Placeholder (optional)"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    />
                                </div>
                                <label class="flex items-center space-x-1 text-sm">
                                    <input v-model="field.required" type="checkbox" class="rounded" />
                                    <span>Required</span>
                                </label>
                                <button
                                    type="button"
                                    @click="removeField(index)"
                                    class="text-red-500 hover:text-red-700"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="addField"
                            class="mt-2 text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            + Add Field
                        </button>
                        <p v-if="errors.field_config" class="mt-1 text-sm text-red-600">{{ errors.field_config }}</p>
                    </div>

                    <!-- Active Status (only show when editing) -->
                    <div v-if="entryType" class="flex items-center">
                        <input
                            id="is_active"
                            v-model="formData.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Active
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : (entryType ? 'Update' : 'Create') }}
                        </button>
                        <button
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            v-if="entryType"
                            type="button"
                            @click="deleteEntryType"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-red-300 bg-white px-4 py-2 text-base font-medium text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm"
                        >
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    entryType: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close'])

const formData = ref({
    name: '',
    description: '',
    field_config: [
        { name: 'title', label: 'Title', type: 'text', required: true, placeholder: 'Enter title...' },
        { name: 'content', label: 'Content', type: 'textarea', required: true, placeholder: 'Enter content...' }
    ],
    is_active: true
})

const errors = ref({})
const saving = ref(false)

watch(() => props.entryType, (newVal) => {
    if (newVal) {
        formData.value = {
            name: newVal.name || '',
            description: newVal.description || '',
            field_config: newVal.field_config || [],
            is_active: newVal.is_active ?? true
        }
    } else {
        // Reset for create
        formData.value = {
            name: '',
            description: '',
            field_config: [
                { name: 'title', label: 'Title', type: 'text', required: true, placeholder: 'Enter title...' },
                { name: 'content', label: 'Content', type: 'textarea', required: true, placeholder: 'Enter content...' }
            ],
            is_active: true
        }
    }
    errors.value = {}
}, { immediate: true })

const addField = () => {
    formData.value.field_config.push({
        name: '',
        label: '',
        type: 'text',
        required: false,
        placeholder: ''
    })
}

const removeField = (index) => {
    formData.value.field_config.splice(index, 1)
}

const saveEntryType = () => {
    saving.value = true
    errors.value = {}

    const url = props.entryType
        ? route('admin.entry-types.update', props.entryType.id)
        : route('admin.entry-types.store')

    const method = props.entryType ? 'patch' : 'post'

    router[method](url, formData.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
        },
        onError: (err) => {
            errors.value = err
            saving.value = false
        },
        onFinish: () => {
            saving.value = false
        }
    })
}

const deleteEntryType = () => {
    if (confirm(`Are you sure you want to delete "${props.entryType.name}"? This action cannot be undone.`)) {
        router.delete(route('admin.entry-types.destroy', props.entryType.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal()
            }
        })
    }
}

const closeModal = () => {
    formData.value = {
        name: '',
        description: '',
        field_config: [
            { name: 'title', label: 'Title', type: 'text', required: true, placeholder: 'Enter title...' },
            { name: 'content', label: 'Content', type: 'textarea', required: true, placeholder: 'Enter content...' }
        ],
        is_active: true
    }
    errors.value = {}
    saving.value = false
    emit('close')
}
</script>

