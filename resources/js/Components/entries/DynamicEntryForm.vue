<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Title is always present -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Title <span class="text-red-500">*</span>
            </label>
            <input
                id="title"
                v-model="form.title"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                required
            />
            <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
        </div>
        
        <!-- Dynamic fields from field_config -->
        <div v-for="field in entryType.field_config" :key="field.name" class="space-y-2">
            <!-- Simple Text Fields -->
            <div v-if="field.type === 'text'">
                <label :for="field.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>
                <input
                    :id="field.name"
                    v-model="form.content[field.name]"
                    type="text"
                    :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}...`"
                    :required="field.required"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                />
            </div>

            <!-- Textarea Fields -->
            <div v-else-if="field.type === 'textarea'">
                <label :for="field.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>
                <textarea
                    :id="field.name"
                    v-model="form.content[field.name]"
                    :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}...`"
                    :required="field.required"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                ></textarea>
            </div>

            <!-- Repeatable Sections -->
            <div v-else-if="field.type === 'repeatable'" class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>
                
                <div v-for="(item, itemIndex) in form.content[field.name]" :key="itemIndex" 
                     class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ field.label }} {{ itemIndex + 1 }}
                        </span>
                        <button
                            type="button"
                            @click="removeRepeatableItem(field.name, itemIndex)"
                            class="text-red-500 hover:text-red-700 text-sm"
                        >
                            Remove
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <div v-for="nestedField in field.fields" :key="nestedField.name" class="space-y-1">
                            <label :for="`${field.name}_${itemIndex}_${nestedField.name}`" 
                                   class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                {{ nestedField.label }}
                                <span v-if="nestedField.required" class="text-red-500">*</span>
                            </label>
                            
                            <!-- Nested Text Field -->
                            <input
                                v-if="nestedField.type === 'text'"
                                :id="`${field.name}_${itemIndex}_${nestedField.name}`"
                                v-model="item[nestedField.name]"
                                type="text"
                                :placeholder="nestedField.placeholder || `Enter ${nestedField.label.toLowerCase()}...`"
                                :required="nestedField.required"
                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                            />
                            
                            <!-- Nested Textarea Field -->
                            <textarea
                                v-else-if="nestedField.type === 'textarea'"
                                :id="`${field.name}_${itemIndex}_${nestedField.name}`"
                                v-model="item[nestedField.name]"
                                :placeholder="nestedField.placeholder || `Enter ${nestedField.label.toLowerCase()}...`"
                                :required="nestedField.required"
                                rows="3"
                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                            ></textarea>
                            
                            <!-- Nested Image Field -->
                            <div v-else-if="nestedField.type === 'image'" class="space-y-2">
                                <input
                                    :id="`${field.name}_${itemIndex}_${nestedField.name}`"
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageUpload($event, field.name, itemIndex, nestedField.name)"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                                />
                                <div v-if="item[nestedField.name]" class="text-xs text-green-600">
                                    Image uploaded: {{ item[nestedField.name] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button
                    type="button"
                    @click="addRepeatableItem(field)"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    + Add {{ field.label }}
                </button>
            </div>

            <!-- Image Collection -->
            <div v-else-if="field.type === 'image_collection'" class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>
                
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
                    <input
                        type="file"
                        multiple
                        accept="image/*"
                        @change="handleImageCollectionUpload($event, field.name)"
                        class="hidden"
                        :id="`${field.name}_upload`"
                    />
                    <label :for="`${field.name}_upload`" class="cursor-pointer">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Click to upload images or drag and drop
                        </div>
                    </label>
                </div>
                
                <div v-if="form.content[field.name] && form.content[field.name].length > 0" class="grid grid-cols-3 gap-4">
                    <div v-for="(image, imageIndex) in form.content[field.name]" :key="imageIndex" 
                         class="relative group border rounded-lg overflow-hidden">
                        <img :src="getImageUrl(image)" :alt="image.alt || 'Image'" class="w-full h-32 object-cover" />
                        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <button
                                type="button"
                                @click="removeImage(field.name, imageIndex)"
                                class="text-white hover:text-red-300"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-2 bg-gray-50 dark:bg-gray-700">
                            <input
                                v-model="image.alt"
                                type="text"
                                placeholder="Alt text..."
                                class="w-full text-xs px-2 py-1 border rounded"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Object/Group Fields -->
            <div v-else-if="field.type === 'object'" class="space-y-3 border rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ field.label }}
                    </label>
                    <button
                        type="button"
                        @click="toggleObjectCollapse(field.name)"
                        class="text-sm text-gray-500 hover:text-gray-700"
                    >
                        {{ collapsedObjects[field.name] ? 'Expand' : 'Collapse' }}
                    </button>
                </div>
                
                <div v-if="!collapsedObjects[field.name]" class="space-y-3">
                    <div v-for="nestedField in field.fields" :key="nestedField.name" class="space-y-1">
                        <label :for="`${field.name}_${nestedField.name}`" 
                               class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ nestedField.label || nestedField.name }}
                        </label>
                        
                        <input
                            v-if="nestedField.type === 'text'"
                            :id="`${field.name}_${nestedField.name}`"
                            v-model="form.content[field.name][nestedField.name]"
                            type="text"
                            :placeholder="nestedField.placeholder || `Enter ${nestedField.label || nestedField.name}...`"
                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                        />
                        
                        <textarea
                            v-else-if="nestedField.type === 'textarea'"
                            :id="`${field.name}_${nestedField.name}`"
                            v-model="form.content[field.name][nestedField.name]"
                            :placeholder="nestedField.placeholder || `Enter ${nestedField.label || nestedField.name}...`"
                            rows="2"
                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status (optional) -->
        <div v-if="showStatus">
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Status
            </label>
            <select
                id="status"
                v-model="form.status"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button
                type="button"
                @click="$emit('cancel')"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                Cancel
            </button>
            <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
            >
                <span v-if="form.processing">{{ submitText || 'Saving...' }}</span>
                <span v-else>{{ submitText || 'Create' }}</span>
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'

interface Props {
    entryType: any
    entry?: any
    showStatus?: boolean
    submitText?: string
}

const props = defineProps<Props>()
const emit = defineEmits(['cancel', 'submit'])

// Initialize form content structure from field_config
const initializeContent = () => {
    const content: any = {}
    
    if (props.entry?.content) {
        return props.entry.content
    }
    
    props.entryType.field_config.forEach((field: any) => {
        content[field.name] = match (field.type) {
            'repeatable', 'image_collection' => [],
            'object' => {},
            'checkbox' => false,
            default => ''
        }
    })
    
    return content
}

const form = useForm({
    title: props.entry?.title || '',
    content: initializeContent(),
    status: props.entry?.status || 'published',
    entry_type_id: props.entryType.id
})

const collapsedObjects = reactive<Record<string, boolean>>({})

const addRepeatableItem = (field: any) => {
    const newItem: any = {}
    field.fields?.forEach((f: any) => {
        newItem[f.name] = ''
    })
    form.content[field.name].push(newItem)
}

const removeRepeatableItem = (fieldName: string, itemIndex: number) => {
    form.content[fieldName].splice(itemIndex, 1)
}

const handleImageUpload = (event: Event, fieldName: string, itemIndex: number, nestedFieldName: string) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        // For now, just store the file name - in production, upload to server
        form.content[fieldName][itemIndex][nestedFieldName] = file.name
    }
}

const handleImageCollectionUpload = (event: Event, fieldName: string) => {
    const target = event.target as HTMLInputElement
    const files = target.files
    if (files) {
        Array.from(files).forEach(file => {
            form.content[fieldName].push({
                path: file.name, // In production, upload and get actual path
                alt: '',
                caption: ''
            })
        })
    }
}

const removeImage = (fieldName: string, imageIndex: number) => {
    form.content[fieldName].splice(imageIndex, 1)
}

const getImageUrl = (image: any) => {
    // In production, return actual image URL
    return '/images/placeholder.svg'
}

const toggleObjectCollapse = (fieldName: string) => {
    collapsedObjects[fieldName] = !collapsedObjects[fieldName]
}

const handleSubmit = () => {
    emit('submit', form)
}
</script>
