<!-- Size exception: domain component (admin entry type CRUD). Consider splitting into EntryTypeForm + EntryTypeModal. -->
<template>
    <BaseModal :show="show" size="2xl" closeable @close="closeModal">
        <template #header>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ entryType ? 'Edit Entry Type' : 'Create Entry Type' }}
            </h3>
        </template>

        <template #body>

            <form id="entry-type-form" @submit.prevent="saveEntryType" class="mt-6 space-y-6">
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
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Field Configuration</label>
                            <div class="flex space-x-2">
                                <button
                                    type="button"
                                    @click="toggleJsonMode"
                                    class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded"
                                >
                                    {{ jsonMode ? 'Form Mode' : 'JSON Mode' }}
                                </button>
                            </div>
                        </div>

                        <!-- JSON Editor Mode -->
                        <div v-if="jsonMode" class="space-y-2">
                            <textarea
                                v-model="jsonFieldConfig"
                                rows="20"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                placeholder="Paste your field_config JSON here..."
                            ></textarea>
                            <div v-if="jsonError" class="text-sm text-red-600">
                                {{ jsonError }}
                            </div>
                            <button
                                type="button"
                                @click="parseJsonConfig"
                                class="text-sm bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700"
                            >
                                Parse JSON
                            </button>
                        </div>

                        <!-- Form Mode -->
                        <div v-else>
                        <div class="space-y-3">
                            <div v-for="(field, index) in formData.field_config" :key="index" class="border rounded-lg p-4 bg-white">
                                <!-- Main Field Configuration -->
                                <div class="flex items-start space-x-2">
                                    <div class="flex-1 grid grid-cols-2 gap-2">
                                        <input
                                            v-model="field.name"
                                            type="text"
                                            placeholder="Field name (e.g., title)"
                                            :class="[
                                                'rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm',
                                                errors[`field_config.${index}.name`] ? 'border-red-500' : ''
                                            ]"
                                        />
                                        <div v-if="errors[`field_config.${index}.name`]" class="text-xs text-red-600 mt-1">
                                            {{ errors[`field_config.${index}.name`] }}
                                        </div>
                                        <input
                                            v-model="field.label"
                                            type="text"
                                            placeholder="Label (e.g., Title)"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        />
                                        <select
                                            v-model="field.type"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                            @change="onFieldTypeChange(field)"
                                        >
                                            <option value="text">Text</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="number">Number</option>
                                            <option value="select">Select</option>
                                            <option value="checkbox">Checkbox</option>
                                            <option value="repeatable">Repeatable Section</option>
                                            <option value="image_collection">Image Collection</option>
                                            <option value="entry_relation">Entry Relation</option>
                                            <option value="object">Object/Group</option>
                                        </select>
                                        <input
                                            v-model="field.placeholder"
                                            type="text"
                                            placeholder="Placeholder (optional)"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        />
                                    </div>
                                    <label class="flex items-center space-x-1 text-sm whitespace-nowrap ml-2">
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

                                <!-- Nested Fields for Repeatable/Image Collection/Object Types -->
                                <div v-if="needsNestedFields(field)" class="mt-4 ml-4 border-l-2 border-indigo-200 pl-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-sm font-medium text-gray-700">
                                            Nested Fields {{ field.type === 'repeatable' ? '(for each item)' : '(for each entry)' }}
                                        </label>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <div v-for="(nestedField, nestedIndex) in (field.fields || [])" :key="nestedIndex" 
                                             class="flex items-start space-x-2 bg-gray-50 p-2 rounded">
                                            <div class="flex-1 grid grid-cols-2 gap-2">
                                                <input
                                                    v-model="nestedField.name"
                                                    type="text"
                                                    placeholder="Field name"
                                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                                />
                                                <input
                                                    v-model="nestedField.label"
                                                    type="text"
                                                    placeholder="Label"
                                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                                />
                                                <select
                                                    v-model="nestedField.type"
                                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                                >
                                                    <option value="text">Text</option>
                                                    <option value="textarea">Textarea</option>
                                                    <option value="number">Number</option>
                                                    <option value="checkbox">Checkbox</option>
                                                    <option value="image">Image</option>
                                                </select>
                                                <input
                                                    v-model="nestedField.placeholder"
                                                    type="text"
                                                    placeholder="Placeholder"
                                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                                />
                                            </div>
                                            <label class="flex items-center space-x-1 text-xs whitespace-nowrap ml-2">
                                                <input v-model="nestedField.required" type="checkbox" class="rounded" />
                                                <span>Required</span>
                                            </label>
                                            <button
                                                type="button"
                                                @click="removeNestedField(field, nestedIndex)"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button
                                        type="button"
                                        @click="addNestedField(field)"
                                        class="mt-2 text-xs text-indigo-600 hover:text-indigo-800"
                                    >
                                        + Add Nested Field
                                    </button>
                                </div>

                                <!-- Additional Options for Special Field Types -->
                                <div v-if="field.type === 'repeatable' || field.type === 'image_collection' || field.type === 'entry_relation'" class="mt-3 ml-4 space-y-2">
                                    <div class="flex space-x-4">
                                        <div class="flex-1">
                                            <label class="text-xs text-gray-600">Min:</label>
                                            <input
                                                v-model.number="field.min"
                                                type="number"
                                                min="0"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                            />
                                        </div>
                                        <div class="flex-1">
                                            <label class="text-xs text-gray-600">Max:</label>
                                            <input
                                                v-model.number="field.max"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div v-if="field.type === 'entry_relation'" class="mt-3 ml-4">
                                    <label class="text-xs text-gray-600">Entry Type Slug:</label>
                                    <input
                                        v-model="field.entry_type_slug"
                                        type="text"
                                        placeholder="e.g., case, blog-post"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                    />
                                    <label class="mt-1 flex items-center text-xs">
                                        <input v-model="field.exclude_current" type="checkbox" class="rounded mr-1" />
                                        <span>Exclude current entry</span>
                                    </label>
                                </div>

                                <div v-if="field.type === 'object'" class="mt-3 ml-4">
                                    <label class="flex items-center text-xs">
                                        <input v-model="field.collapsible" type="checkbox" class="rounded mr-1" />
                                        <span>Collapsible in form</span>
                                    </label>
                                </div>
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
            </form>

        </template>

        <template #footer>
            <BaseButton
                type="submit"
                :disabled="saving"
                :loading="saving"
                variant="primary"
                form="entry-type-form"
            >
                {{ entryType ? 'Update' : 'Create' }}
            </BaseButton>
            <BaseButton
                type="button"
                variant="secondary"
                @click="closeModal"
            >
                Cancel
            </BaseButton>
            <BaseButton
                v-if="entryType"
                type="button"
                variant="danger"
                @click="deleteEntryType"
            >
                Delete
            </BaseButton>
        </template>
    </BaseModal>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import BaseModal from '@/Components/Base/Modal.vue'
import BaseButton from '@/Components/Base/Button.vue'

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
const jsonMode = ref(false)
const jsonFieldConfig = ref('')
const jsonError = ref('')

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
        placeholder: '',
        fields: []
    })
}

const removeField = (index) => {
    formData.value.field_config.splice(index, 1)
}

const needsNestedFields = (field) => {
    // Only repeatable and object need nested field configuration
    // image_collection uses a fixed metadata structure handled separately
    return ['repeatable', 'object'].includes(field.type)
}

const onFieldTypeChange = (field) => {
    // Initialize fields array if switching to a complex type
    if (needsNestedFields(field)) {
        if (!field.fields) {
            field.fields = []
        }
    }
}

const addNestedField = (field) => {
    if (!field.fields) {
        field.fields = []
    }
    field.fields.push({
        name: '',
        label: '',
        type: 'text',
        required: false,
        placeholder: ''
    })
}

const removeNestedField = (field, nestedIndex) => {
    field.fields.splice(nestedIndex, 1)
}

const toggleJsonMode = () => {
    jsonMode.value = !jsonMode.value
    jsonError.value = ''
    
    if (jsonMode.value) {
        // Convert form data to JSON
        jsonFieldConfig.value = JSON.stringify(formData.value.field_config, null, 2)
    } else {
        // Parse JSON back to form data
        parseJsonConfig()
    }
}

const parseJsonConfig = () => {
    try {
        const parsed = JSON.parse(jsonFieldConfig.value)
        if (Array.isArray(parsed)) {
            formData.value.field_config = parsed
            jsonError.value = ''
        } else {
            jsonError.value = 'Field config must be an array'
        }
    } catch (e) {
        jsonError.value = 'Invalid JSON: ' + e.message
    }
}

const saveEntryType = () => {
    saving.value = true
    errors.value = {}
    jsonError.value = ''

    // Validate required fields before sending
    if (!formData.value.name.trim()) {
        errors.value.name = 'Name is required'
        saving.value = false
        return
    }

    if (!formData.value.field_config || formData.value.field_config.length === 0) {
        errors.value.field_config = 'At least one field is required'
        saving.value = false
        return
    }

    // Validate each field
    const fieldErrors = {}
    formData.value.field_config.forEach((field, index) => {
        if (!field.name.trim()) {
            fieldErrors[`field_config.${index}.name`] = 'Field name is required'
        }
        if (!field.label.trim()) {
            fieldErrors[`field_config.${index}.label`] = 'Field label is required'
        }
        if (!field.type) {
            fieldErrors[`field_config.${index}.type`] = 'Field type is required'
        }
    })

    if (Object.keys(fieldErrors).length > 0) {
        errors.value = { ...errors.value, ...fieldErrors }
        saving.value = false
        return
    }

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
            console.error('Save error:', err)
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

